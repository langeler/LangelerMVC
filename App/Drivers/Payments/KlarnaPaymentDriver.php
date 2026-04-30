<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Exceptions\Support\PaymentException;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class KlarnaPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'klarna';
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
        return ['USERNAME', 'PASSWORD', 'API_BASE'];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'Klarna',
            'docs_url' => 'https://docs.klarna.com/',
            'regions' => ['SE', 'NO', 'FI', 'DK', 'EU', 'US'],
            'webhook' => true,
            'idempotency' => true,
            'partial_capture' => true,
            'partial_refund' => true,
            'redirect' => true,
            'customer_action' => true,
        ];
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $resolved = $this->referenceIntent(
                $intent,
                'requires_action',
                [
                    'type' => 'klarna_sdk',
                    'provider' => 'klarna',
                    'session_id' => $this->referenceSet($intent)[1],
                    'client_token' => 'klarna-client-token-' . substr(hash('sha256', $this->referenceSet($intent)[0]), 0, 24),
                    'authorization_callback_url' => (string) $this->paymentMetadata($intent, 'urls.callback', 'https://merchant.example.test/payments/klarna/callback'),
                ],
                true,
                0,
                0,
                0
            );

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Klarna payment session created.', 'requires_action');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/payments/v1/sessions'),
            $this->basicAuthHeaders(),
            [
                'purchase_country' => (string) $this->paymentMetadata($intent, 'country', $this->setting('PURCHASE_COUNTRY', 'SE')),
                'purchase_currency' => $intent->currency,
                'locale' => (string) $this->paymentMetadata($intent, 'locale', $this->setting('LOCALE', 'sv-SE')),
                'order_amount' => $intent->amount,
                'order_tax_amount' => (int) $this->paymentMetadata($intent, 'tax_amount', 0),
                'merchant_reference1' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
                'merchant_urls' => array_filter([
                    'confirmation' => $this->paymentMetadata($intent, 'urls.return'),
                    'checkout' => $this->paymentMetadata($intent, 'urls.checkout'),
                    'push' => $this->paymentMetadata($intent, 'urls.callback'),
                    'terms' => $this->paymentMetadata($intent, 'urls.terms'),
                ]),
                'order_lines' => $this->klarnaOrderLines($intent),
                'billing_address' => $this->klarnaAddress($intent, 'billing'),
                'shipping_address' => $this->klarnaAddress($intent, 'shipping'),
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'klarna session creation');
        $payload = $response['json'];
        $sessionId = isset($payload['session_id']) ? (string) $payload['session_id'] : null;

        if ($sessionId === null) {
            throw new PaymentException('Klarna session response did not contain a session identifier.');
        }

        $resolved = $this->referenceIntent(
            $intent,
            'requires_action',
            [
                'type' => 'klarna_sdk',
                'provider' => 'klarna',
                'session_id' => $sessionId,
                'client_token' => (string) ($payload['client_token'] ?? ''),
                'authorization_callback_url' => (string) $this->paymentMetadata($intent, 'urls.callback', ''),
            ],
            true,
            0,
            0,
            0
        )->withReferences(
            $sessionId,
            $sessionId,
            null,
            isset($payload['session_id']) ? 'klarna-session-' . (string) $payload['session_id'] : null
        );

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'Klarna payment session created.', 'requires_action', [
            'provider_payload' => $payload,
        ]);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $captureAmount = $amount ?? max($intent->remainingCaptureAmount(), $intent->amount);
            $capturedAmount = min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + $captureAmount);
            $status = $capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured';

            return new PaymentResult(
                true,
                'capture',
                $intent->withDriver($this->driverName())->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
                $this->driverName(),
                'Klarna order captured.',
                $status
            );
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $captureAmount = $amount ?? max($intent->remainingCaptureAmount(), $intent->amount);
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/ordermanagement/v1/orders/' . rawurlencode((string) $intent->providerReference) . '/captures'),
            array_merge($this->basicAuthHeaders(), [
                'Klarna-Idempotency-Key' => (string) ($intent->idempotencyKey ?? uniqid('klarna_capture_', true)),
            ]),
            [
                'captured_amount' => $captureAmount,
                'reference' => $intent->reference,
                'description' => $intent->description !== '' ? $intent->description : 'LangelerMVC capture',
                'order_lines' => $this->klarnaOrderLines($intent),
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'klarna capture');
        $captureId = $response['headers']['Capture-Id'] ?? $response['headers']['capture_id'] ?? null;
        $capturedAmount = min(max($intent->authorizedAmount, $intent->amount), $intent->capturedAmount + $captureAmount);
        $status = $capturedAmount >= max($intent->authorizedAmount, $intent->amount) ? 'captured' : 'partially_captured';

        return new PaymentResult(
            true,
            'capture',
            $intent
                ->withDriver($this->driverName())
                ->withReferences($intent->reference, $intent->providerReference, is_string($captureId) ? $captureId : $intent->externalReference, $intent->webhookReference)
                ->withTotals(max($intent->authorizedAmount, $intent->amount), $capturedAmount, $intent->refundedAmount, $status),
            $this->driverName(),
            'Klarna order captured.',
            $status
        );
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            return new PaymentResult(
                true,
                'cancel',
                $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled'),
                $this->driverName(),
                $reason ?? 'Klarna order cancelled.',
                'cancelled'
            );
        }

        $response = $this->request(
            'POST',
            $this->apiUrl('/ordermanagement/v1/orders/' . rawurlencode((string) $intent->providerReference) . '/cancel'),
            $this->basicAuthHeaders()
        );

        $this->requireSuccessfulResponse($response, [200, 201, 204], 'klarna cancellation');

        return new PaymentResult(
            true,
            'cancel',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled'),
            $this->driverName(),
            $reason ?? 'Klarna order cancelled.',
            'cancelled'
        );
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $refundAmount = $amount ?? $intent->remainingRefundAmount();
            $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
            $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

            return new PaymentResult(
                true,
                'refund',
                $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
                $this->driverName(),
                $reason ?? 'Klarna refund created.',
                $status
            );
        }

        $refundAmount = $amount ?? $intent->remainingRefundAmount();
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/ordermanagement/v1/orders/' . rawurlencode((string) $intent->providerReference) . '/refunds'),
            array_merge($this->basicAuthHeaders(), [
                'Klarna-Idempotency-Key' => uniqid('klarna_refund_', true),
            ]),
            [
                'refunded_amount' => $refundAmount,
                'reference' => $intent->reference,
                'description' => $reason,
                'order_lines' => $this->klarnaOrderLines($intent),
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'klarna refund');
        $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

        return new PaymentResult(
            true,
            'refund',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
            $this->driverName(),
            $reason ?? 'Klarna refund created.',
            $status,
            ['provider_payload' => $response['json']]
        );
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        $authorizationToken = trim((string) ($payload['authorization_token'] ?? ''));

        if (!$this->isLiveMode()) {
            if ($authorizationToken === '') {
                return new PaymentResult(false, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'Klarna reconciliation requires an authorization token or callback payload.', $intent->status);
            }

            $resolved = $intent
                ->withDriver($this->driverName())
                ->withReferences($intent->reference, $this->referenceSet($intent)[1], $authorizationToken, $intent->webhookReference)
                ->withTotals($intent->amount, 0, 0, 'authorized')
                ->withNextAction([], false);

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'Klarna authorization reconciled.', 'authorized');
        }

        if ($authorizationToken === '') {
            return new PaymentResult(false, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'Klarna reconciliation requires the authorization token returned from Klarna.', $intent->status);
        }

        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/payments/v1/authorizations/' . rawurlencode($authorizationToken) . '/order'),
            $this->basicAuthHeaders(),
            [
                'purchase_country' => (string) $this->paymentMetadata($intent, 'country', $this->setting('PURCHASE_COUNTRY', 'SE')),
                'purchase_currency' => $intent->currency,
                'locale' => (string) $this->paymentMetadata($intent, 'locale', $this->setting('LOCALE', 'sv-SE')),
                'billing_address' => $this->klarnaAddress($intent, 'billing'),
                'shipping_address' => $this->klarnaAddress($intent, 'shipping'),
                'order_amount' => $intent->amount,
                'order_tax_amount' => (int) $this->paymentMetadata($intent, 'tax_amount', 0),
                'order_lines' => $this->klarnaOrderLines($intent),
                'merchant_reference1' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'klarna order creation');
        $payloadResponse = $response['json'];
        $orderId = isset($payloadResponse['order_id']) ? (string) $payloadResponse['order_id'] : null;

        if ($orderId === null) {
            throw new PaymentException('Klarna order creation response did not contain an order identifier.');
        }

        $resolved = $intent
            ->withDriver($this->driverName())
            ->withReferences($orderId, $orderId, $authorizationToken, $intent->webhookReference)
            ->withTotals($intent->amount, 0, 0, 'authorized')
            ->withNextAction([], false);

        return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'Klarna authorization converted into an order.', 'authorized', [
            'provider_payload' => $payloadResponse,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function basicAuthHeaders(): array
    {
        $credentials = base64_encode((string) $this->setting('USERNAME') . ':' . (string) $this->setting('PASSWORD'));

        return [
            'Authorization' => 'Basic ' . $credentials,
            'Accept' => 'application/json',
        ];
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) $this->setting('API_BASE', $this->setting('BASE_URL', '')), '/') . $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function klarnaOrderLines(PaymentIntent $intent): array
    {
        $lines = $this->paymentMetadata($intent, 'order_lines', []);

        if (is_array($lines) && $lines !== []) {
            return array_values(array_filter($lines, static fn(mixed $line): bool => is_array($line)));
        }

        return [[
            'type' => 'digital',
            'reference' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
            'name' => $intent->description !== '' ? $intent->description : 'LangelerMVC order',
            'quantity' => 1,
            'unit_price' => $intent->amount,
            'tax_rate' => (int) $this->paymentMetadata($intent, 'tax_rate', 0),
            'total_amount' => $intent->amount,
            'total_tax_amount' => (int) $this->paymentMetadata($intent, 'tax_amount', 0),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function klarnaAddress(PaymentIntent $intent, string $type): array
    {
        $address = $this->paymentMetadata($intent, 'addresses.' . $type, []);

        return is_array($address) ? $address : [];
    }
}
