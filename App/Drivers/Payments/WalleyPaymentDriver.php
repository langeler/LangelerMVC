<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class WalleyPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'walley';
    }

    protected function defaultMethods(): array
    {
        return ['bnpl'];
    }

    protected function defaultFlows(): array
    {
        return [
            PaymentFlow::Redirect->value,
            PaymentFlow::AuthorizeCapture->value,
        ];
    }

    protected function requiredSettings(): array
    {
        return ['CREATE_URL', 'CAPTURE_URL', 'REFUND_URL', 'CANCEL_URL', 'RECONCILE_URL'];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Walley',
            'docs_url' => 'https://dev.walleypay.com/paymentsApi/',
            'regions' => ['SE', 'NO', 'FI', 'DK'],
            'webhook' => true,
            'idempotency' => true,
            'redirect' => true,
            'customer_action' => true,
            'partial_capture' => true,
            'partial_refund' => true,
            'transport' => 'soap_or_configured_endpoint',
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['CREATE_URL']);
            $response = $this->requestJson(
                'POST',
                $this->endpointUrl('CREATE_URL'),
                $this->authorizationHeaders(),
                [
                    'merchant_id' => (string) $this->setting('MERCHANT_ID', ''),
                    'reference' => $intent->idempotencyKey ?? $intent->reference,
                    'amount' => $intent->amount,
                    'currency' => $intent->currency,
                    'description' => $intent->description,
                    'return_url' => $this->paymentMetadata($intent, 'urls.return', $this->setting('RETURN_URL', '')),
                    'callback_url' => $this->paymentMetadata($intent, 'urls.callback', $this->setting('CALLBACK_URL', '')),
                    'metadata' => $intent->metadata,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'walley checkout creation');
            $payload = $response['json'];
            $reference = (string) ($payload['reference'] ?? $payload['order_reference'] ?? $intent->reference ?? $this->referenceSet($intent)[0]);
            $providerReference = (string) ($payload['provider_reference'] ?? $payload['order_id'] ?? $payload['id'] ?? $reference);
            $status = (string) ($payload['status'] ?? 'requires_action');
            $nextAction = is_array($payload['next_action'] ?? null)
                ? $payload['next_action']
                : [
                    'type' => 'redirect',
                    'provider' => 'walley',
                    'url' => (string) ($payload['redirect_url'] ?? $payload['checkout_url'] ?? ''),
                    'transport' => (string) ($payload['transport'] ?? 'configured_endpoint'),
                ];

            $resolved = $this->referenceIntent(
                $intent,
                $status,
                $nextAction,
                !empty(array_filter($nextAction)),
                0,
                0,
                0
            )->withReferences($reference, $providerReference, null, 'walley-callback-' . $providerReference);

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), (string) ($payload['message'] ?? 'Walley checkout created.'), $status, [
                'provider_payload' => $payload,
            ]);
        }

        [$reference, $providerReference] = $this->referenceSet($intent);

        $resolved = $this->referenceIntent(
            $intent,
            'requires_action',
            [
                'type' => 'redirect',
                'provider' => 'walley',
                'url' => (string) $this->paymentMetadata($intent, 'urls.redirect', 'https://checkout.walley.test/session/' . $providerReference),
                'transport' => 'soap',
                'wsdl' => (string) $this->setting('WSDL_URL', ''),
            ],
            true,
            0,
            0,
            0
        )->withReferences($reference, $providerReference, null, 'walley-callback-' . $providerReference);

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Walley checkout prepared in reference mode.', 'requires_action');
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        $captureAmount = $amount ?? max($intent->remainingCaptureAmount(), $intent->amount);

        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['CAPTURE_URL']);
            $response = $this->requestJson(
                'POST',
                $this->endpointUrl('CAPTURE_URL'),
                $this->authorizationHeaders(),
                [
                    'merchant_id' => (string) $this->setting('MERCHANT_ID', ''),
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'amount' => $captureAmount,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'walley capture');
            $payload = $response['json'];
            $capturedAmount = (int) ($payload['captured_amount'] ?? min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + $captureAmount));
            $status = (string) ($payload['status'] ?? ($capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured'));

            return new PaymentResult(
                true,
                'capture',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
                $this->driverName(),
                (string) ($payload['message'] ?? 'Walley capture recorded.'),
                $status,
                ['provider_payload' => $payload]
            );
        }

        $capturedAmount = min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + $captureAmount);
        $status = $capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured';

        return new PaymentResult(
            true,
            'capture',
            $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
            $this->driverName(),
            'Walley capture recorded.',
            $status
        );
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['CANCEL_URL']);
            $response = $this->requestJson(
                'POST',
                $this->endpointUrl('CANCEL_URL'),
                $this->authorizationHeaders(),
                [
                    'merchant_id' => (string) $this->setting('MERCHANT_ID', ''),
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202, 204], 'walley cancellation');
        }

        return new PaymentResult(
            true,
            'cancel',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled'),
            $this->driverName(),
            $reason ?? 'Walley authorization released.',
            'cancelled'
        );
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        $refundAmount = $amount ?? max($intent->remainingRefundAmount(), $intent->capturedAmount);

        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['REFUND_URL']);
            $response = $this->requestJson(
                'POST',
                $this->endpointUrl('REFUND_URL'),
                $this->authorizationHeaders(),
                [
                    'merchant_id' => (string) $this->setting('MERCHANT_ID', ''),
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'amount' => $refundAmount,
                    'reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'walley refund');
            $payload = $response['json'];
            $refundedAmount = (int) ($payload['refunded_amount'] ?? min($intent->capturedAmount, $intent->refundedAmount + $refundAmount));
            $status = (string) ($payload['status'] ?? ($refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded'));

            return new PaymentResult(
                true,
                'refund',
                $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
                $this->driverName(),
                (string) ($payload['message'] ?? 'Walley refund recorded.'),
                $status,
                ['provider_payload' => $payload]
            );
        }

        $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

        return new PaymentResult(
            true,
            'refund',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
            $this->driverName(),
            $reason ?? 'Walley refund recorded.',
            $status
        );
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        if ($this->isLiveMode()) {
            $this->ensureLiveRequirements(['RECONCILE_URL']);
            $response = $this->requestJson(
                'POST',
                $this->endpointUrl('RECONCILE_URL'),
                $this->authorizationHeaders(),
                [
                    'merchant_id' => (string) $this->setting('MERCHANT_ID', ''),
                    'reference' => $intent->reference,
                    'provider_reference' => $intent->providerReference,
                    'payload' => $payload,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'walley reconciliation');
            $payloadResponse = $response['json'];
            $status = strtolower(trim((string) ($payloadResponse['status'] ?? $payloadResponse['payment_status'] ?? 'authorized')));
            $capturedAmount = $status === 'captured'
                ? (int) ($payloadResponse['captured_amount'] ?? $intent->amount)
                : $intent->capturedAmount;

            return new PaymentResult(
                true,
                'reconcile',
                $intent
                    ->withDriver($this->driverName())
                    ->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status)
                    ->withNextAction([], false),
                $this->driverName(),
                (string) ($payloadResponse['message'] ?? 'Walley payment reconciled.'),
                $status,
                ['provider_payload' => $payloadResponse]
            );
        }

        $status = strtolower(trim((string) ($payload['status'] ?? 'authorized')));

        if ($status === 'cancelled') {
            return $this->cancel($intent, 'Walley payment cancelled during reconciliation.');
        }

        if ($status === 'captured') {
            return new PaymentResult(
                true,
                'reconcile',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $intent->amount, $intent->refundedAmount, 'captured')->withNextAction([], false),
                $this->driverName(),
                'Walley payment captured during reconciliation.',
                'captured'
            );
        }

        return new PaymentResult(
            true,
            'reconcile',
            $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount, $intent->refundedAmount, 'authorized')->withNextAction([], false),
            $this->driverName(),
            'Walley authorization reconciled.',
            'authorized'
        );
    }

    /**
     * @return array<string, string>
     */
    private function authorizationHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];
        $apiKey = trim((string) $this->setting('API_KEY', ''));

        if ($apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        $username = trim((string) $this->setting('USERNAME', ''));
        $password = trim((string) $this->setting('PASSWORD', ''));

        if ($username !== '' || $password !== '') {
            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }

        return $headers;
    }

    private function endpointUrl(string $key): string
    {
        $url = trim((string) $this->setting($key, ''));

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        $base = rtrim(trim((string) $this->setting('API_BASE', '')), '/');

        return $base !== '' ? $base . '/' . ltrim($url, '/') : $url;
    }
}
