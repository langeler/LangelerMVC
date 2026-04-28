<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\Support\PaymentDriverInterface;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
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
            'reconcile' => true,
            'webhook' => true,
            'idempotency' => true,
            'partial_capture' => true,
            'partial_refund' => true,
            'external_gateway' => false,
            'redirect' => true,
            'customer_action' => true,
            'methods' => $this->supportedMethods(),
            'flows' => $this->supportedFlows(),
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

    public function supportedMethods(): array
    {
        return PaymentMethod::values();
    }

    public function supportedFlows(): array
    {
        return PaymentFlow::values();
    }

    public function supportsMethod(PaymentMethod|string $method): bool
    {
        $value = $method instanceof PaymentMethod ? $method->value : trim((string) $method);

        return in_array($value, $this->supportedMethods(), true);
    }

    public function supportsFlow(PaymentFlow|string $flow): bool
    {
        $value = $flow instanceof PaymentFlow ? $flow->value : trim((string) $flow);

        return in_array($value, $this->supportedFlows(), true);
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        $resolved = $this->normalizeIntent($intent);

        return match ($resolved->flow) {
            PaymentFlow::Purchase->value => new PaymentResult(
                true,
                'authorize',
                $resolved->withTotals($resolved->amount, $resolved->amount, 0, 'captured'),
                $this->driverName(),
                'Payment completed immediately.',
                'captured'
            ),
            PaymentFlow::Redirect->value => new PaymentResult(
                true,
                'authorize',
                $resolved
                    ->withTotals($resolved->amount, 0, 0, 'requires_action')
                    ->withNextAction($this->redirectAction($resolved), true),
                $this->driverName(),
                'Customer action is required to complete this payment.',
                'requires_action'
            ),
            PaymentFlow::Async->value => new PaymentResult(
                true,
                'authorize',
                $resolved
                    ->withTotals($resolved->amount, 0, 0, 'processing')
                    ->withNextAction($this->asyncAction($resolved), false),
                $this->driverName(),
                'Payment is waiting for asynchronous confirmation.',
                'processing'
            ),
            PaymentFlow::ManualReview->value => new PaymentResult(
                true,
                'authorize',
                $resolved
                    ->withTotals(0, 0, 0, 'pending_review')
                    ->withNextAction([
                        'type' => 'manual_review',
                        'message' => 'Manual review must complete before authorization.',
                    ], false),
                $this->driverName(),
                'Payment is pending manual review.',
                'pending_review'
            ),
            default => new PaymentResult(
                true,
                'authorize',
                $resolved->withTotals($resolved->amount, 0, 0, 'authorized'),
                $this->driverName(),
                'Payment authorized.',
                'authorized'
            ),
        };
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if (!in_array($intent->status, ['authorized', 'partially_captured', 'captured'], true)) {
            return new PaymentResult(false, 'capture', $intent, $this->driverName(), 'Only authorized payments can be captured.', $intent->status);
        }

        $captureAmount = $amount ?? $intent->remainingCaptureAmount();

        if ($captureAmount <= 0 || $captureAmount > $intent->remainingCaptureAmount()) {
            return new PaymentResult(false, 'capture', $intent, $this->driverName(), 'Invalid capture amount.', $intent->status);
        }

        $captured = $intent->capturedAmount + $captureAmount;
        $status = $captured >= $intent->authorizedAmount ? 'captured' : 'partially_captured';
        $resolved = $intent
            ->withTotals($intent->authorizedAmount, $captured, $intent->refundedAmount, $status)
            ->withNextAction([], false);

        return new PaymentResult(true, 'capture', $resolved, $this->driverName(), 'Payment captured.', $status);
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if (
            !in_array($intent->status, ['pending', 'authorized', 'requires_action', 'processing', 'pending_review'], true)
            || $intent->capturedAmount > 0
        ) {
            return new PaymentResult(
                false,
                'cancel',
                $intent,
                $this->driverName(),
                'Only uncaptured payments can be cancelled.',
                $intent->status
            );
        }

        $resolved = $intent
            ->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled')
            ->withNextAction([], false);

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'Payment cancelled.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if (!in_array($intent->status, ['captured', 'partially_refunded', 'refunded'], true) && $intent->capturedAmount === 0) {
            return new PaymentResult(false, 'refund', $intent, $this->driverName(), 'Only captured payments can be refunded.', $intent->status);
        }

        $refundAmount = $amount ?? $intent->remainingRefundAmount();

        if ($refundAmount <= 0 || $refundAmount > $intent->remainingRefundAmount()) {
            return new PaymentResult(false, 'refund', $intent, $this->driverName(), 'Invalid refund amount.', $intent->status);
        }

        $refunded = $intent->refundedAmount + $refundAmount;
        $status = $refunded >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';
        $resolved = $intent->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refunded, $status);

        return new PaymentResult(true, 'refund', $resolved, $this->driverName(), $reason ?? 'Payment refunded.', $status);
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        $resolved = $this->normalizeIntent($intent);
        $requestedStatus = $this->normalizeStatus(trim((string) ($payload['status'] ?? $payload['payment_status'] ?? '')));

        if ($requestedStatus === 'cancelled') {
            return $this->cancel($resolved, 'Payment provider reported a cancellation.');
        }

        if ($requestedStatus === 'refunded') {
            if ($resolved->capturedAmount <= 0 && $resolved->amount > 0) {
                $resolved = $resolved->withTotals($resolved->amount, $resolved->amount, 0, 'captured');
            }

            return $this->refund($resolved, null, 'Payment provider reported a refund.');
        }

        if (in_array($requestedStatus, ['authorized', 'captured'], true)) {
            $captured = $requestedStatus === 'captured' ? $resolved->amount : 0;

            return new PaymentResult(
                true,
                'reconcile',
                $resolved
                    ->withTotals($resolved->amount, $captured, $resolved->refundedAmount, $requestedStatus)
                    ->withNextAction([], false),
                $this->driverName(),
                'Payment webhook reconciliation completed.',
                $requestedStatus,
                ['webhook' => true]
            );
        }

        if ($resolved->status === 'requires_action') {
            $captured = $resolved->flow === PaymentFlow::Purchase->value ? $resolved->amount : 0;
            $status = $captured > 0 ? 'captured' : 'authorized';

            return new PaymentResult(
                true,
                'reconcile',
                $resolved
                    ->withTotals($resolved->amount, $captured, 0, $status)
                    ->withNextAction([], false),
                $this->driverName(),
                'Payment customer action completed.',
                $status,
                ['webhook' => true]
            );
        }

        if ($resolved->status === 'processing' || $requestedStatus === 'processing') {
            $captured = $resolved->flow === PaymentFlow::Purchase->value ? $resolved->amount : 0;
            $status = $captured > 0 ? 'captured' : 'authorized';

            return new PaymentResult(
                true,
                'reconcile',
                $resolved
                    ->withTotals($resolved->amount, $captured, 0, $status)
                    ->withNextAction([], false),
                $this->driverName(),
                'Asynchronous payment reconciliation completed.',
                $status,
                ['webhook' => true]
            );
        }

        return new PaymentResult(
            false,
            'reconcile',
            $resolved,
            $this->driverName(),
            'This payment does not require reconciliation.',
            $resolved->status
        );
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'paid', 'succeeded', 'success', 'settled', 'completed', 'capture.completed', 'payment.captured' => 'captured',
            'approved', 'authorised', 'authorized', 'authorization.created' => 'authorized',
            'cancelled', 'canceled', 'voided', 'abandoned', 'failed', 'denied' => 'cancelled',
            'refund', 'refunded', 'refund.completed', 'payment.refunded' => 'refunded',
            default => $status,
        };
    }

    private function normalizeIntent(PaymentIntent $intent): PaymentIntent
    {
        $seed = implode('|', [
            (string) $intent->amount,
            $intent->currency,
            $intent->description,
            $intent->method,
            $intent->flow,
            $intent->idempotencyKey ?? $intent->reference ?? 'testing',
        ]);
        $suffix = substr(hash('sha256', $seed), 0, 16);
        $reference = $intent->reference ?? ('pay_' . $suffix);
        $providerReference = $intent->providerReference ?? ('provider_' . $suffix);
        $externalReference = $intent->externalReference ?? ('external_' . $suffix);
        $webhookReference = $intent->webhookReference ?? ('wh_' . $suffix);

        return $intent->withReferences(
            $reference,
            $providerReference,
            $externalReference,
            $webhookReference
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function redirectAction(PaymentIntent $intent): array
    {
        return [
            'type' => 'redirect',
            'url' => 'https://payments.langelermvc.test/authorize/' . ($intent->reference ?? 'pending'),
            'return_url' => 'https://langelermvc.test/orders/complete/' . ($intent->reference ?? 'pending'),
            'method' => $intent->method,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function asyncAction(PaymentIntent $intent): array
    {
        return [
            'type' => 'webhook',
            'webhook_reference' => $intent->webhookReference,
            'provider_reference' => $intent->providerReference,
            'method' => $intent->method,
        ];
    }
}
