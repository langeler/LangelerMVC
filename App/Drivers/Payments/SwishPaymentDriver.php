<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class SwishPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'swish';
    }

    protected function defaultMethods(): array
    {
        return ['local_instant'];
    }

    protected function defaultFlows(): array
    {
        return [
            PaymentFlow::Redirect->value,
            PaymentFlow::Async->value,
        ];
    }

    protected function requiredSettings(): array
    {
        return ['API_BASE', 'PAYEE_ALIAS', 'CALLBACK_URL', 'CERT_PATH'];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Swish',
            'docs_url' => 'https://developer.swish.nu/',
            'regions' => ['SE'],
            'webhook' => true,
            'idempotency' => true,
            'redirect' => true,
            'customer_action' => true,
            'partial_capture' => false,
            'partial_refund' => false,
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        if (!$this->isLiveMode()) {
            [$reference, $providerReference] = $this->referenceSet($intent);
            $resolved = $this->referenceIntent(
                $intent,
                'requires_action',
                [
                    'type' => 'swish',
                    'provider' => 'swish',
                    'instruction_uuid' => $providerReference,
                    'app_url' => 'swish://paymentrequest?token=' . $providerReference,
                    'qr_url' => 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled/' . $providerReference,
                ],
                true,
                0,
                0,
                0
            )->withReferences($reference, $providerReference, null, 'swish-callback-' . $providerReference);

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Swish payment request created.', 'requires_action');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $instructionUuid = $intent->idempotencyKey ?? $this->referenceSet($intent)[1];
        $response = $this->requestJson(
            'POST',
            rtrim((string) $this->setting('API_BASE'), '/') . '/paymentrequests',
            [
                'Accept' => 'application/json',
            ],
            [
                'payeeAlias' => (string) $this->setting('PAYEE_ALIAS'),
                'amount' => number_format($intent->amount / 100, 2, '.', ''),
                'currency' => $intent->currency,
                'callbackUrl' => (string) $this->paymentMetadata($intent, 'urls.callback', $this->setting('CALLBACK_URL')),
                'message' => substr($intent->description !== '' ? $intent->description : 'LangelerMVC order', 0, 50),
                'payerAlias' => $this->paymentMetadata($intent, 'payer_alias'),
            ],
            [
                'ssl' => [
                    'cert' => (string) $this->setting('CERT_PATH'),
                    'key' => (string) $this->setting('KEY_PATH', ''),
                    'passphrase' => (string) $this->setting('PASSPHRASE', ''),
                ],
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201, 202], 'swish payment request');
        $location = (string) ($response['headers']['Location'] ?? $response['headers']['location'] ?? '');
        $requestId = trim((string) basename($location)) !== '' ? (string) basename($location) : $instructionUuid;
        $resolved = $this->referenceIntent(
            $intent,
            'requires_action',
            [
                'type' => 'swish',
                'provider' => 'swish',
                'instruction_uuid' => $requestId,
                'app_url' => 'swish://paymentrequest?token=' . $requestId,
                'qr_url' => 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled/' . $requestId,
            ],
            true,
            0,
            0,
            0
        )->withReferences($requestId, $requestId, null, 'swish-callback-' . $requestId);

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Swish payment request created.', 'requires_action', [
            'provider_payload' => $response['json'],
        ]);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if ($intent->status === 'captured') {
            return new PaymentResult(true, 'capture', $intent->withDriver($this->driverName()), $this->driverName(), 'Swish payments settle immediately after approval.', 'captured');
        }

        return $this->unsupportedResult($intent, 'capture', 'Swish payments are captured through reconciliation after payer approval.');
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        $resolved = $intent->withDriver($this->driverName())->withTotals(0, 0, 0, 'cancelled')->withNextAction([], false);

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'Swish payment cancelled.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if ($intent->capturedAmount <= 0) {
            return new PaymentResult(false, 'refund', $intent->withDriver($this->driverName()), $this->driverName(), 'Only settled Swish payments can be refunded.', $intent->status);
        }

        $refundAmount = $amount ?? $intent->capturedAmount;
        $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';
        $resolved = $intent
            ->withDriver($this->driverName())
            ->withNextAction([
                'type' => 'swish_refund',
                'reason' => $reason,
            ], false)
            ->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status);

        return new PaymentResult(true, 'refund', $resolved, $this->driverName(), $reason ?? 'Swish refund recorded.', $status);
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $status = strtolower(trim((string) ($payload['status'] ?? 'paid')));

            if (in_array($status, ['cancelled', 'declined', 'error'], true)) {
                return $this->cancel($intent, 'Swish payment request was not completed.');
            }

            $resolved = $intent
                ->withDriver($this->driverName())
                ->withNextAction([], false)
                ->withTotals($intent->amount, $intent->amount, $intent->refundedAmount, 'captured');

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'Swish payment confirmed.', 'captured');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $requestId = (string) ($intent->providerReference ?? $intent->reference ?? '');
        $response = $this->requestJson(
            'GET',
            rtrim((string) $this->setting('API_BASE'), '/') . '/paymentrequests/' . rawurlencode($requestId),
            [
                'Accept' => 'application/json',
            ],
            null,
            [
                'ssl' => [
                    'cert' => (string) $this->setting('CERT_PATH'),
                    'key' => (string) $this->setting('KEY_PATH', ''),
                    'passphrase' => (string) $this->setting('PASSPHRASE', ''),
                ],
            ]
        );

        $this->requireSuccessfulResponse($response, [200], 'swish payment status');
        $providerStatus = strtoupper((string) ($response['json']['status'] ?? 'CREATED'));

        if (in_array($providerStatus, ['DECLINED', 'ERROR', 'CANCELLED'], true)) {
            return $this->cancel($intent, 'Swish payment was cancelled or declined.');
        }

        if ($providerStatus === 'PAID') {
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withNextAction([], false)
                ->withTotals($intent->amount, $intent->amount, $intent->refundedAmount, 'captured');

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'Swish payment confirmed.', 'captured', [
                'provider_payload' => $response['json'],
            ]);
        }

        return new PaymentResult(true, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'Swish payment is still pending approval.', 'requires_action', [
            'provider_payload' => $response['json'],
        ]);
    }
}
