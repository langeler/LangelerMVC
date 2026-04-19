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
            'transport' => 'soap',
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
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

        $message = $this->isLiveMode()
            ? 'Walley live mode requires the configured WSDL/method mapping during merchant onboarding.'
            : 'Walley checkout prepared in reference mode.';

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), $message, 'requires_action');
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        $captureAmount = $amount ?? max($intent->remainingCaptureAmount(), $intent->amount);
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
}
