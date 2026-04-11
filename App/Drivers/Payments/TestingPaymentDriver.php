<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\Support\PaymentDriverInterface;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;
use App\Utilities\Traits\ArrayTrait;

class TestingPaymentDriver implements PaymentDriverInterface
{
    use ArrayTrait;

    public function driverName(): string
    {
        return 'testing';
    }

    public function capabilities(): array
    {
        return [
            'authorize' => true,
            'capture' => true,
            'cancel' => true,
            'refund' => true,
            'webhook' => false,
            'external_gateway' => false,
        ];
    }

    public function supports(string $feature): bool
    {
        $value = $this->capabilities();

        foreach (explode('.', trim($feature)) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        $resolved = $intent
            ->withReference($intent->reference ?? ('pay_' . bin2hex(random_bytes(8))))
            ->withTotals($intent->amount, 0, 0, 'authorized');

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Payment authorized.');
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if (!in_array($intent->status, ['authorized', 'partially_captured', 'captured'], true)) {
            return new PaymentResult(false, 'capture', $intent, $this->driverName(), 'Only authorized payments can be captured.');
        }

        $captureAmount = $amount ?? $intent->remainingCaptureAmount();

        if ($captureAmount <= 0 || $captureAmount > $intent->remainingCaptureAmount()) {
            return new PaymentResult(false, 'capture', $intent, $this->driverName(), 'Invalid capture amount.');
        }

        $captured = $intent->capturedAmount + $captureAmount;
        $status = $captured >= $intent->authorizedAmount ? 'captured' : 'partially_captured';
        $resolved = $intent->withTotals($intent->authorizedAmount, $captured, $intent->refundedAmount, $status);

        return new PaymentResult(true, 'capture', $resolved, $this->driverName(), 'Payment captured.');
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if (!in_array($intent->status, ['pending', 'authorized'], true)) {
            return new PaymentResult(false, 'cancel', $intent, $this->driverName(), 'Only pending or authorized payments can be cancelled.');
        }

        $resolved = $intent->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled');

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'Payment cancelled.');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if (!in_array($intent->status, ['captured', 'partially_refunded', 'refunded'], true) && $intent->capturedAmount === 0) {
            return new PaymentResult(false, 'refund', $intent, $this->driverName(), 'Only captured payments can be refunded.');
        }

        $refundAmount = $amount ?? $intent->remainingRefundAmount();

        if ($refundAmount <= 0 || $refundAmount > $intent->remainingRefundAmount()) {
            return new PaymentResult(false, 'refund', $intent, $this->driverName(), 'Invalid refund amount.');
        }

        $refunded = $intent->refundedAmount + $refundAmount;
        $status = $refunded >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';
        $resolved = $intent->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refunded, $status);

        return new PaymentResult(true, 'refund', $resolved, $this->driverName(), $reason ?? 'Payment refunded.');
    }
}
