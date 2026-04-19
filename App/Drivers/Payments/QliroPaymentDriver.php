<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Exceptions\Support\PaymentException;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class QliroPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'qliro';
    }

    protected function defaultMethods(): array
    {
        return ['card', 'bnpl', 'local_instant', 'bank_transfer'];
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
        return ['API_BASE', 'API_KEY', 'MERCHANT_CONFIRMATION_URL', 'MERCHANT_TERMS_URL'];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Qliro',
            'docs_url' => 'https://developers.qliro.com/docs',
            'regions' => ['SE', 'NO', 'FI', 'DK'],
            'webhook' => true,
            'idempotency' => true,
            'redirect' => true,
            'customer_action' => true,
            'partial_capture' => false,
            'partial_refund' => true,
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
                    'type' => 'iframe',
                    'provider' => 'qliro',
                    'order_id' => $providerReference,
                    'payment_link' => 'https://payments.qliro.test/link/' . $providerReference,
                    'html_snippet' => '<iframe src="https://payments.qliro.test/checkout/' . $providerReference . '"></iframe>',
                ],
                true,
                0,
                0,
                0
            )->withReferences($reference, $providerReference, null, 'qliro-checkout-' . $providerReference);

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Qliro checkout created.', 'requires_action');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $createOrderResponse = $this->requestJson(
            'POST',
            $this->apiUrl('/checkout/merchantapi/orders'),
            [
                'Accept' => 'application/json',
            ],
            [
                'OrderId' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
                'MerchantApiKey' => (string) $this->setting('API_KEY'),
                'Country' => (string) $this->paymentMetadata($intent, 'country', $this->setting('COUNTRY', 'SE')),
                'Currency' => $intent->currency,
                'Language' => (string) $this->paymentMetadata($intent, 'locale', $this->setting('LOCALE', 'sv-se')),
                'MerchantConfirmationUrl' => (string) $this->paymentMetadata($intent, 'urls.return', $this->setting('MERCHANT_CONFIRMATION_URL')),
                'MerchantTermsUrl' => (string) $this->paymentMetadata($intent, 'urls.terms', $this->setting('MERCHANT_TERMS_URL')),
                'MerchantCheckoutStatusPushUrl' => (string) $this->paymentMetadata($intent, 'urls.callback', $this->setting('MERCHANT_CHECKOUT_STATUS_PUSH_URL', '')),
                'MerchantOrderManagementStatusPushUrl' => (string) $this->paymentMetadata($intent, 'urls.order_management_callback', $this->setting('MERCHANT_ORDER_MANAGEMENT_STATUS_PUSH_URL', '')),
                'OrderItems' => $this->qliroOrderItems($intent),
            ]
        );

        $this->requireSuccessfulResponse($createOrderResponse, [200, 201], 'qliro create order');
        $createdPayload = $createOrderResponse['json'];
        $orderId = isset($createdPayload['OrderId']) ? (string) $createdPayload['OrderId'] : null;

        if ($orderId === null) {
            throw new PaymentException('Qliro create-order response did not contain an order identifier.');
        }

        $getOrderResponse = $this->requestJson(
            'GET',
            $this->apiUrl('/checkout/merchantapi/orders/' . rawurlencode($orderId)),
            [
                'Accept' => 'application/json',
            ]
        );

        $this->requireSuccessfulResponse($getOrderResponse, [200], 'qliro get order');
        $payload = $getOrderResponse['json'];
        $resolved = $this->referenceIntent(
            $intent,
            'requires_action',
            [
                'type' => 'iframe',
                'provider' => 'qliro',
                'order_id' => $orderId,
                'payment_link' => isset($payload['PaymentLink']) ? (string) $payload['PaymentLink'] : null,
                'html_snippet' => isset($payload['OrderHtmlSnippet']) ? (string) $payload['OrderHtmlSnippet'] : null,
            ],
            true,
            0,
            0,
            0
        )->withReferences($orderId, $orderId, null, 'qliro-checkout-' . $orderId);

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Qliro checkout created.', 'requires_action', [
            'provider_payload' => $payload,
        ]);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if (!$this->isLiveMode() || (string) $this->setting('CAPTURE_URL', '') === '') {
            $captureAmount = $amount ?? max($intent->remainingCaptureAmount(), $intent->amount);
            $capturedAmount = min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + $captureAmount);
            $status = $capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured';

            return new PaymentResult(
                true,
                'capture',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
                $this->driverName(),
                'Qliro order marked as shipped.',
                $status
            );
        }

        $response = $this->requestJson(
            'POST',
            (string) $this->setting('CAPTURE_URL'),
            ['Accept' => 'application/json'],
            [
                'OrderId' => $intent->providerReference,
                'Items' => $this->qliroOrderItems($intent),
                'Amount' => $amount ?? max($intent->remainingCaptureAmount(), $intent->amount),
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201, 202], 'qliro mark shipped');
        $capturedAmount = min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + ($amount ?? max($intent->remainingCaptureAmount(), $intent->amount)));
        $status = $capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured';

        return new PaymentResult(
            true,
            'capture',
            $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
            $this->driverName(),
            'Qliro order marked as shipped.',
            $status,
            ['provider_payload' => $response['json']]
        );
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if ($this->isLiveMode() && (string) $this->setting('CANCEL_URL', '') !== '') {
            $response = $this->requestJson(
                'POST',
                (string) $this->setting('CANCEL_URL'),
                ['Accept' => 'application/json'],
                [
                    'OrderId' => $intent->providerReference,
                    'Reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'qliro cancel order');
        }

        return new PaymentResult(
            true,
            'cancel',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled'),
            $this->driverName(),
            $reason ?? 'Qliro order cancelled.',
            'cancelled'
        );
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        $refundAmount = $amount ?? max($intent->remainingRefundAmount(), $intent->capturedAmount);

        if ($this->isLiveMode() && (string) $this->setting('REFUND_URL', '') !== '') {
            $response = $this->requestJson(
                'POST',
                (string) $this->setting('REFUND_URL'),
                ['Accept' => 'application/json'],
                [
                    'OrderId' => $intent->providerReference,
                    'Items' => $this->qliroOrderItems($intent),
                    'Amount' => $refundAmount,
                    'Reason' => $reason,
                ]
            );

            $this->requireSuccessfulResponse($response, [200, 201, 202], 'qliro return items');
        }

        $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

        return new PaymentResult(
            true,
            'refund',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
            $this->driverName(),
            $reason ?? 'Qliro return registered.',
            $status
        );
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $status = strtolower(trim((string) ($payload['status'] ?? 'completed')));
            $paymentStatus = in_array($status, ['completed', 'accepted'], true) ? 'authorized' : ($status === 'onhold' ? 'authorized' : 'requires_action');

            return new PaymentResult(
                true,
                'reconcile',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount, $intent->refundedAmount, $paymentStatus)->withNextAction([], false),
                $this->driverName(),
                'Qliro order reconciled.',
                $paymentStatus
            );
        }

        $response = $this->requestJson(
            'GET',
            $this->apiUrl('/checkout/merchantapi/orders/' . rawurlencode((string) $intent->providerReference)),
            ['Accept' => 'application/json']
        );

        $this->requireSuccessfulResponse($response, [200], 'qliro get order');
        $payloadResponse = $response['json'];
        $checkoutStatus = strtolower((string) ($payloadResponse['CustomerCheckoutStatus'] ?? 'inprocess'));

        if (in_array($checkoutStatus, ['completed', 'onhold'], true)) {
            return new PaymentResult(
                true,
                'reconcile',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount, $intent->refundedAmount, 'authorized')->withNextAction([], false),
                $this->driverName(),
                'Qliro checkout reconciled.',
                'authorized',
                ['provider_payload' => $payloadResponse]
            );
        }

        return new PaymentResult(true, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'Qliro checkout is still in progress.', 'requires_action', [
            'provider_payload' => $payloadResponse,
        ]);
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) $this->setting('API_BASE'), '/') . $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function qliroOrderItems(PaymentIntent $intent): array
    {
        $items = $this->paymentMetadata($intent, 'order_items', []);

        if (is_array($items) && $items !== []) {
            return array_values(array_filter($items, static fn(mixed $item): bool => is_array($item)));
        }

        return [[
            'MerchantReference' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
            'Description' => $intent->description !== '' ? $intent->description : 'LangelerMVC order',
            'Type' => 'Product',
            'Quantity' => 1,
            'PricePerItemIncVat' => round($intent->amount / 100, 2),
            'PricePerItemExVat' => round($intent->amount / 100, 2),
        ]];
    }
}
