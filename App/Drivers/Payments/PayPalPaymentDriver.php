<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Abstracts\Support\PaymentDriver;
use App\Exceptions\Support\PaymentException;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;

class PayPalPaymentDriver extends PaymentDriver
{
    public function driverName(): string
    {
        return 'paypal';
    }

    protected function defaultMethods(): array
    {
        return ['wallet', 'card'];
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
        return ['CLIENT_ID', 'CLIENT_SECRET', 'API_BASE'];
    }

    protected function driverCapabilities(): array
    {
        return [
            'label' => 'PayPal',
            'docs_url' => 'https://developer.paypal.com/api/rest/integration/orders-api/',
            'regions' => ['GLOBAL'],
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
                    'type' => 'redirect',
                    'provider' => 'paypal',
                    'url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $this->referenceSet($intent)[1],
                ],
                true,
                0,
                0,
                0
            );

            return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'PayPal order created and awaiting buyer approval.', 'requires_action');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $accessToken = $this->accessToken();
        $intentValue = $intent->flow === PaymentFlow::Purchase->value ? 'CAPTURE' : 'AUTHORIZE';
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/v2/checkout/orders'),
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'PayPal-Request-Id' => (string) ($intent->idempotencyKey ?? uniqid('paypal_', true)),
            ],
            [
                'intent' => $intentValue,
                'purchase_units' => [[
                    'reference_id' => $intent->idempotencyKey ?? $this->referenceSet($intent)[0],
                    'description' => $intent->description !== '' ? $intent->description : 'LangelerMVC order payment',
                    'amount' => [
                        'currency_code' => $intent->currency,
                        'value' => number_format($intent->amount / 100, 2, '.', ''),
                    ],
                ]],
                'application_context' => array_filter([
                    'return_url' => $this->paymentMetadata($intent, 'urls.return'),
                    'cancel_url' => $this->paymentMetadata($intent, 'urls.cancel'),
                    'user_action' => 'PAY_NOW',
                ]),
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'paypal order creation');
        $payload = $response['json'];
        $approveUrl = $this->link($payload, 'approve');
        $resolved = $this->referenceIntent(
            $intent,
            (string) ($payload['status'] ?? 'CREATED') === 'APPROVED' ? 'authorized' : 'requires_action',
            $approveUrl !== null ? [
                'type' => 'redirect',
                'provider' => 'paypal',
                'url' => $approveUrl,
            ] : [],
            $approveUrl !== null,
            (string) ($payload['status'] ?? '') === 'APPROVED' && $intentValue === 'AUTHORIZE' ? $intent->amount : 0,
            0,
            0
        )->withReferences(
            isset($payload['id']) ? (string) $payload['id'] : null,
            isset($payload['id']) ? (string) $payload['id'] : null,
            null,
            isset($payload['id']) ? 'paypal-order-' . (string) $payload['id'] : null
        );

        return new PaymentResult(true, 'authorize', $resolved, $this->driverName(), 'PayPal order created.', $resolved->status, [
            'provider_payload' => $payload,
        ]);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $captureAmount = $amount ?? max($intent->authorizedAmount, $intent->amount);
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withTotals(max($intent->authorizedAmount, $intent->amount), min(max($intent->capturedAmount, 0) + $captureAmount, max($intent->authorizedAmount, $intent->amount)), $intent->refundedAmount, 'captured')
                ->withNextAction([], false);

            return new PaymentResult(true, 'capture', $resolved, $this->driverName(), 'PayPal payment captured.', 'captured');
        }

        $this->ensureLiveRequirements($this->requiredSettings());
        $accessToken = $this->accessToken();
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/v2/checkout/orders/' . rawurlencode((string) $intent->providerReference) . '/capture'),
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'PayPal-Request-Id' => (string) ($intent->idempotencyKey ?? uniqid('paypal_capture_', true)),
            ],
            null
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'paypal capture');
        $payload = $response['json'];
        $captureId = $this->nestedValue($payload, ['purchase_units', 0, 'payments', 'captures', 0, 'id']);
        $status = strtolower((string) ($this->nestedValue($payload, ['purchase_units', 0, 'payments', 'captures', 0, 'status']) ?? 'COMPLETED')) === 'completed'
            ? 'captured'
            : 'processing';
        $resolved = $intent
            ->withDriver($this->driverName())
            ->withTotals(max($intent->authorizedAmount, $intent->amount), $intent->amount, $intent->refundedAmount, $status)
            ->withReferences($intent->reference, $intent->providerReference, is_string($captureId) ? $captureId : $intent->externalReference, $intent->webhookReference)
            ->withNextAction([], false);

        return new PaymentResult(true, 'capture', $resolved, $this->driverName(), 'PayPal payment captured.', $status, [
            'provider_payload' => $payload,
        ]);
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $resolved = $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled')->withNextAction([], false);

            return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'PayPal payment cancelled.', 'cancelled');
        }

        $authorizationId = $intent->externalReference;

        if ($authorizationId === null || $authorizationId === '') {
            return $this->unsupportedResult($intent, 'cancel', 'PayPal cancellation requires an authorization reference.');
        }

        $accessToken = $this->accessToken();
        $response = $this->request(
            'POST',
            $this->apiUrl('/v2/payments/authorizations/' . rawurlencode($authorizationId) . '/void'),
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201, 204], 'paypal void');
        $resolved = $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $intent->refundedAmount, 'cancelled')->withNextAction([], false);

        return new PaymentResult(true, 'cancel', $resolved, $this->driverName(), $reason ?? 'PayPal authorization voided.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $refundAmount = $amount ?? $intent->remainingRefundAmount();
            $refunded = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
            $status = $refunded >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

            return new PaymentResult(
                true,
                'refund',
                $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refunded, $status),
                $this->driverName(),
                $reason ?? 'PayPal payment refunded.',
                $status
            );
        }

        $captureId = $intent->externalReference;

        if ($captureId === null || $captureId === '') {
            return $this->unsupportedResult($intent, 'refund', 'PayPal refunds require a capture reference.');
        }

        $refundAmount = $amount ?? $intent->remainingRefundAmount();
        $accessToken = $this->accessToken();
        $response = $this->requestJson(
            'POST',
            $this->apiUrl('/v2/payments/captures/' . rawurlencode($captureId) . '/refund'),
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'PayPal-Request-Id' => uniqid('paypal_refund_', true),
            ],
            [
                'amount' => [
                    'currency_code' => $intent->currency,
                    'value' => number_format($refundAmount / 100, 2, '.', ''),
                ],
                'note_to_payer' => $reason,
            ]
        );

        $this->requireSuccessfulResponse($response, [200, 201], 'paypal refund');
        $payload = $response['json'];
        $refundedAmount = min($intent->capturedAmount, $intent->refundedAmount + $refundAmount);
        $status = $refundedAmount >= $intent->capturedAmount ? 'refunded' : 'partially_refunded';

        return new PaymentResult(
            true,
            'refund',
            $intent->withDriver($this->driverName())->withTotals($intent->authorizedAmount, $intent->capturedAmount, $refundedAmount, $status),
            $this->driverName(),
            (string) ($payload['status'] ?? ($reason ?? 'PayPal refund created.')),
            $status,
            ['provider_payload' => $payload]
        );
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        if (!$this->isLiveMode()) {
            $requestedStatus = strtolower(trim((string) ($payload['status'] ?? 'approved')));

            if ($requestedStatus === 'cancelled') {
                return $this->cancel($intent, 'PayPal checkout was cancelled by the buyer.');
            }

            if ($intent->flow === PaymentFlow::Purchase->value) {
                return $this->capture($intent->withDriver($this->driverName())->withTotals($intent->amount, $intent->capturedAmount, $intent->refundedAmount, 'authorized'));
            }

            return new PaymentResult(
                true,
                'reconcile',
                $intent->withDriver($this->driverName())->withTotals($intent->amount, $intent->capturedAmount, $intent->refundedAmount, 'authorized')->withNextAction([], false),
                $this->driverName(),
                'PayPal approval reconciled.',
                'authorized'
            );
        }

        $accessToken = $this->accessToken();
        $response = $this->requestJson(
            'GET',
            $this->apiUrl('/v2/checkout/orders/' . rawurlencode((string) $intent->providerReference)),
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ]
        );

        $this->requireSuccessfulResponse($response, [200], 'paypal order lookup');
        $order = $response['json'];
        $status = strtoupper((string) ($order['status'] ?? 'CREATED'));

        if ($status === 'APPROVED') {
            if ($intent->flow === PaymentFlow::Purchase->value) {
                return $this->capture($intent);
            }

            $authorizationResponse = $this->requestJson(
                'POST',
                $this->apiUrl('/v2/checkout/orders/' . rawurlencode((string) $intent->providerReference) . '/authorize'),
                [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'PayPal-Request-Id' => uniqid('paypal_authorize_', true),
                ],
                null
            );

            $this->requireSuccessfulResponse($authorizationResponse, [200, 201], 'paypal authorize');
            $authorizationPayload = $authorizationResponse['json'];
            $authorizationId = $this->nestedValue($authorizationPayload, ['purchase_units', 0, 'payments', 'authorizations', 0, 'id']);
            $resolved = $intent
                ->withDriver($this->driverName())
                ->withReferences($intent->reference, $intent->providerReference, is_string($authorizationId) ? $authorizationId : $intent->externalReference, $intent->webhookReference)
                ->withTotals($intent->amount, $intent->capturedAmount, $intent->refundedAmount, 'authorized')
                ->withNextAction([], false);

            return new PaymentResult(true, 'reconcile', $resolved, $this->driverName(), 'PayPal order authorized.', 'authorized', [
                'provider_payload' => $authorizationPayload,
            ]);
        }

        if (in_array($status, ['VOIDED', 'CANCELLED'], true)) {
            return $this->cancel($intent, 'PayPal order was cancelled upstream.');
        }

        return new PaymentResult(true, 'reconcile', $intent->withDriver($this->driverName()), $this->driverName(), 'PayPal order is still waiting for customer approval.', 'requires_action', [
            'provider_payload' => $order,
        ]);
    }

    private function accessToken(): string
    {
        $response = $this->request(
            'POST',
            $this->apiUrl('/v1/oauth2/token'),
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials',
            [
                'basic_auth' => [
                    'username' => (string) $this->setting('CLIENT_ID'),
                    'password' => (string) $this->setting('CLIENT_SECRET'),
                ],
            ]
        );

        $this->requireSuccessfulResponse($response, [200], 'paypal oauth');
        $payload = $response['body'] !== ''
            ? $this->fromJson($response['body'], true, 512, JSON_THROW_ON_ERROR)
            : [];

        if (!is_array($payload) || !isset($payload['access_token'])) {
            throw new PaymentException('PayPal OAuth response did not contain an access token.');
        }

        return (string) $payload['access_token'];
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) $this->setting('API_BASE'), '/') . $path;
    }

    private function link(array $payload, string $rel): ?string
    {
        $links = $payload['links'] ?? null;

        if (!is_array($links)) {
            return null;
        }

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            if (strcasecmp((string) ($link['rel'] ?? ''), $rel) === 0) {
                return isset($link['href']) ? (string) $link['href'] : null;
            }
        }

        return null;
    }

    private function nestedValue(array $payload, array $segments): mixed
    {
        $current = $payload;

        foreach ($segments as $segment) {
            if (is_int($segment)) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return null;
                }

                $current = $current[$segment];
                continue;
            }

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
