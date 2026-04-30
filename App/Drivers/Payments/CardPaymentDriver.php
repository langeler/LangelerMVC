<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class CardPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'card';
    }

    protected function defaultMethods(): array
    {
        return ['card'];
    }

    protected function defaultFlows(): array
    {
        return [
            PaymentFlow::AuthorizeCapture->value,
            PaymentFlow::Purchase->value,
            PaymentFlow::Redirect->value,
        ];
    }

    protected function requiredSettings(): array
    {
        return [
            'CREATE_URL',
            'CAPTURE_URL',
            'REFUND_URL',
        ];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Credit / Debit Card',
            'regions' => ['GLOBAL'],
            'webhook' => true,
            'idempotency' => true,
            'partial_capture' => true,
            'partial_refund' => true,
            'redirect' => true,
            'customer_action' => true,
            'docs_url' => (string) $this->setting('DOCS_URL', ''),
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['CREATE_URL']);

            $response = $this->requestJson(
                'POST',
                (string) $this->setting('CREATE_URL'),
                $this->authorizationHeaders(),
                [
                    'amount' => $intent->amount,
                    'currency' => $intent->currency,
                    'description' => $intent->description,
                    'merchant_reference' => $intent->idempotencyKey ?? $intent->reference,
                    'payment_method' => 'card',
                    'payment_flow' => $intent->flow,
                    'metadata' => $intent->metadata,
                    'return_url' => $this->paymentMetadata($intent, 'urls.return'),
                    'cancel_url' => $this->paymentMetadata($intent, 'urls.cancel'),
                    'callback_url' => $this->paymentMetadata($intent, 'urls.callback'),
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'card authorization');
            $payload = $response['json'];
            $status = (string) ($payload['status'] ?? ($intent->flow === PaymentFlow::Purchase->value ? 'captured' : 'authorized'));
            $nextAction = is_array($payload['next_action'] ?? null) ? $payload['next_action'] : [];
            $requiresAction = (bool) ($payload['customer_action_required'] ?? !empty($nextAction));

            $resolved = $this->referenceIntent(
                $intent,
                $status,
                $nextAction,
                $requiresAction,
                (int) ($payload['authorized_amount'] ?? $intent->amount),
                (int) ($payload['captured_amount'] ?? ($status === 'captured' ? $intent->amount : 0)),
                (int) ($payload['refunded_amount'] ?? 0)
            )->withReferences(
                isset($payload['reference']) ? (string) $payload['reference'] : null,
                isset($payload['provider_reference']) ? (string) $payload['provider_reference'] : null,
                isset($payload['external_reference']) ? (string) $payload['external_reference'] : null,
                isset($payload['webhook_reference']) ? (string) $payload['webhook_reference'] : null
            );

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), (string) ($payload['message'] ?? 'Card payment initiated.'), $status, [
                'provider_payload' => $payload,
            ]);
        }

        $resolved = match ($intent->flow) {
            PaymentFlow::Purchase->value => $this->referenceIntent($intent, 'captured', [], false, $intent->amount, $intent->amount, 0),
            PaymentFlow::Redirect->value => $this->referenceIntent($intent, 'requires_action', [
                'type' => 'redirect',
                'provider' => 'card',
                'url' => 'https://payments.example.test/card/3ds/' . $this->referenceSet($intent)[0],
            ], true, $intent->amount, 0, 0),
            default => $this->referenceIntent($intent, 'authorized', [], false, $intent->amount, 0, 0),
        };

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Card payment prepared.', $resolved->status);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if ($intent->remainingCaptureAmount() <= 0 && $intent->capturedAmount > 0) {
            return new PaymentResult(true, 'capture', $intent->withDriver($this->driverName()), $this->driverName(), 'Card payment already captured.', $intent->status);
        }

        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['CAPTURE_URL']);

            $response = $this->requestJson(
                'POST',
                (string) $this->setting('CAPTURE_URL'),
                $this->authorizationHeaders(),
                [
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'amount' => $amount ?? $intent->remainingCaptureAmount(),
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'card capture');
            $payload = $response['json'];
            $capturedAmount = (int) ($payload['captured_amount'] ?? ($intent->capturedAmount + ($amount ?? $intent->remainingCaptureAmount())));
            $status = (string) ($payload['status'] ?? ($capturedAmount >= $intent->authorizedAmount ? 'captured' : 'partially_captured'));
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withTotals($intent->authorizedAmount, $capturedAmount, $intent->refundedAmount, $status);

            return new PaymentResult(true, 'capture', $resolved, $this->driverName(), (string) ($payload['message'] ?? 'Card payment captured.'), $status, [
                'provider_payload' => $payload,
            ]);
        }

        $captureAmount = $amount ?? $intent->remainingCaptureAmount();

        if ($captureAmount <= 0 || $captureAmount > $intent->remainingCaptureAmount()) {
            return new PaymentResult(false, 'capture', $intent->withDriver($this->driverName()), $this->driverName(), 'Invalid card capture amount.', $intent->status);
        }

        $capturedAmount = $intent->capturedAmount + $captureAmount;
        $status = $capturedAmount >= $intent->authorizedAmount ? 'captured' : 'partially_captured';

        return new PaymentResult(
            true,
            'capture',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $capturedAmount, $intent->refundedAmount, $status),
            $this->driverName(),
            'Card payment captured.',
            $status
        );
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if ($this->isLiveMode() && (string) $this->setting('CANCEL_URL', '') !== '') {
            $response = $this->requestJson(
                'POST',
                (string) $this->setting('CANCEL_URL'),
                $this->authorizationHeaders(),
                [
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202, 204], 'card cancellation');
        }

        $resolved = $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled');

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'Card payment cancelled.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if ($intent->remainingRefundAmount() <= 0) {
            return new PaymentResult(false, 'refund', $intent->withDriver($this->driverName()), $this->driverName(), 'Card payment has no refundable balance.', $intent->status);
        }

        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['REFUND_URL']);

            $refundAmount = $amount ?? $intent->remainingRefundAmount();
            $response = $this->requestJson(
                'POST',
                (string) $this->setting('REFUND_URL'),
                $this->authorizationHeaders(),
                [
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'amount' => $refundAmount,
                    'reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'card refund');
            $payload = $response['json'];
            $refundedAmount = (int) ($payload['refunded_amount'] ?? ($intent->refundedAmount + $refundAmount));
            $status = (string) ($payload['status'] ?? ($refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded'));
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status);

            return new PaymentResult(true, 'refund', $resolved, $this->driverName(), (string) ($payload['message'] ?? 'Card payment refunded.'), $status, [
                'provider_payload' => $payload,
            ]);
        }

        $refundAmount = $amount ?? $intent->remainingRefundAmount();

        if ($refundAmount <= 0 || $refundAmount > $intent->remainingRefundAmount()) {
            return new PaymentResult(false, 'refund', $intent->withDriver($this->driverName()), $this->driverName(), 'Invalid card refund amount.', $intent->status);
        }

        $refundedAmount = $intent->refundedAmount + $refundAmount;
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

        return new PaymentResult(
            true,
            'refund',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
            $this->driverName(),
            $reason ?? 'Card payment refunded.',
            $status
        );
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        if ($this->isLiveMode() && (string) $this->setting('RECONCILE_URL', '') !== '') {
            $response = $this->requestJson(
                'POST',
                (string) $this->setting('RECONCILE_URL'),
                $this->authorizationHeaders(),
                [
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'payload' => $payload,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'card reconciliation');
            $data = $response['json'];
            $status = (string) ($data['status'] ?? $intent->status);
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withNextAction(is_array($data['next_action'] ?? null) ? $data['next_action'] : [], (bool) ($data['customer_action_required'] ?? false))
                ->withTotals(
                    (int) ($data['authorized_amount'] ?? $intent->authorizedAmount),
                    (int) ($data['captured_amount'] ?? $intent->capturedAmount),
                    (int) ($data['refunded_amount'] ?? $intent->refundedAmount),
                    $status
                );

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), (string) ($data['message'] ?? 'Card payment reconciled.'), $status, [
                'provider_payload' => $data,
            ]);
        }

        $requestedStatus = strtolower(trim((string) ($payload['status'] ?? '')));

        if ($intent->status === 'requires_action') {
            $status = $requestedStatus === 'captured'
                ? 'captured'
                : ($requestedStatus === 'cancelled' ? 'cancelled' : 'authorized');
            $capturedAmount = $status === 'captured' ? $intent->amount : $intent->capturedAmount;

            return new PaymentResult(
                true,
                'reconcile',
                $intent
                    ->withDriver($this->driverName())
                    ->withNextAction([], false)
                    ->withTotals($intent->amount, $capturedAmount, $intent->refundedAmount, $status),
                $this->driverName(),
                'Card payment reconciled.',
                $status
            );
        }

        return new PaymentResult(false, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'This card payment does not require reconciliation.', $intent->status);
    }

    /**
     * @return array<string, string>
     */
    private function authorizationHeaders(): array
    {
        $apiKey = trim((string) $this->setting('API_KEY', ''));
        $scheme = strtoupper((string) $this->setting('AUTH_SCHEME', 'Bearer')) === 'BASIC' ? 'Basic' : 'Bearer';

        if ($apiKey === '') {
            return [];
        }

        return [
            'Authorization' => $scheme . ' ' . $apiKey,
        ];
    }
}
