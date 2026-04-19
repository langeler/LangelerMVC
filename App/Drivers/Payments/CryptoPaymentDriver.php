<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class CryptoPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'crypto';
    }

    protected function defaultMethods(): array
    {
        return ['crypto'];
    }

    protected function defaultFlows(): array
    {
        return [
            PaymentFlow::Async->value,
            PaymentFlow::Redirect->value,
            PaymentFlow::ManualReview->value,
        ];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Crypto',
            'regions' => ['GLOBAL'],
            'webhook' => true,
            'idempotency' => true,
            'redirect' => true,
            'customer_action' => true,
            'external_gateway' => false,
            'partial_capture' => false,
            'partial_refund' => false,
            'onchain' => true,
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        $asset = strtoupper((string) $this->paymentMetadata($intent, 'asset', $this->setting('DEFAULT_ASSET', 'BTC')));
        $network = strtolower((string) $this->paymentMetadata($intent, 'network', $this->setting('NETWORK', 'mainnet')));
        $address = $this->resolveDepositAddress($asset);
        $amountAsset = (string) $this->paymentMetadata($intent, 'amount_asset', number_format($intent->amount / 100, 8, '.', ''));
        $expiryMinutes = (int) $this->setting('EXPIRY_MINUTES', 20);

        $nextAction = [
            'type' => 'crypto_invoice',
            'asset' => $asset,
            'network' => $network,
            'address' => $address,
            'amount_asset' => $amountAsset,
            'expires_at' => gmdate('c', time() + ($expiryMinutes * 60)),
            'qr_uri' => sprintf('%s:%s?amount=%s', strtolower($asset), $address, $amountAsset),
        ];

        $resolved = $this->referenceIntent(
            $intent,
            $intent->flow === PaymentFlow::ManualReview->value ? 'pending_review' : 'processing',
            $nextAction,
            true,
            0,
            0,
            0
        );

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Crypto payment invoice created.', $resolved->status);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if ($intent->status === 'captured') {
            return new PaymentResult(true, 'capture', $intent->withDriver($this->driverName()), $this->driverName(), 'Crypto payment is already settled.', 'captured');
        }

        return $this->unsupportedResult($intent, 'capture', 'Crypto payments are captured through reconciliation after on-chain confirmation.');
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if (in_array($intent->status, ['captured', 'partially_refunded', 'refunded'], true)) {
            return new PaymentResult(false, 'cancel', $intent->withDriver($this->driverName()), $this->driverName(), 'A settled crypto payment cannot be cancelled.', $intent->status);
        }

        $resolved = $intent->withDriver($this->driverName())->withTotals(0, 0, 0, 'cancelled');

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'Crypto payment cancelled.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if ($intent->capturedAmount <= 0) {
            return new PaymentResult(false, 'refund', $intent->withDriver($this->driverName()), $this->driverName(), 'Only settled crypto payments can be refunded.', $intent->status);
        }

        $refundAmount = $amount ?? $intent->capturedAmount;
        $address = (string) $this->paymentMetadata($intent, 'refund_address', '');
        $resolved = $intent
            ->withDriver($this->driverName())
            ->withNextAction([
                'type' => 'manual_crypto_refund',
                'refund_address' => $address,
                'refund_amount_minor' => $refundAmount,
                'reason' => $reason,
            ], false)
            ->withTotals($intent->authorizedAmount, $intent->capturedAmount, min($intent->capturedAmount, $intent->refundedAmount + $refundAmount), 'refunded');

        return new PaymentResult(true, 'refund', $resolved, $this->driverName(), $reason ?? 'Crypto refund queued for merchant execution.', 'refunded');
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        $confirmations = (int) ($payload['confirmations'] ?? 0);
        $required = (int) ($this->setting('REQUIRED_CONFIRMATIONS', 1));
        $status = strtolower(trim((string) ($payload['status'] ?? '')));

        if ($status === 'cancelled') {
            return $this->cancel($intent, 'Crypto payment cancelled during reconciliation.');
        }

        if ($status === 'refunded') {
            return $this->refund($intent, (int) ($payload['refund_amount'] ?? $intent->capturedAmount), 'Crypto payment reconciled as refunded.');
        }

        if ($confirmations >= $required || $status === 'confirmed' || $status === 'captured') {
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withNextAction([], false)
                ->withTotals($intent->amount, $intent->amount, $intent->refundedAmount, 'captured')
                ->withReferences(
                    $intent->reference,
                    $intent->providerReference,
                    isset($payload['tx_hash']) ? (string) $payload['tx_hash'] : $intent->externalReference,
                    $intent->webhookReference
                );

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'Crypto payment confirmed on-chain.', 'captured', [
                'confirmations' => $confirmations,
            ]);
        }

        return new PaymentResult(
            true,
            'reconcile',
            $intent->withDriver($this->driverName())->withTotals(0, 0, 0, 'processing'),
            $this->driverName(),
            'Crypto payment is still waiting for confirmations.',
            'processing',
            ['confirmations' => $confirmations]
        );
    }

    private function resolveDepositAddress(string $asset): string
    {
        $address = (string) $this->setting('ADDRESSES.' . $asset, '');

        if ($address !== '') {
            return $address;
        }

        return 'demo-' . strtolower($asset) . '-address';
    }
}
