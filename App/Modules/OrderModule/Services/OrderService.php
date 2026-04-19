<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Services;

use App\Abstracts\Http\Service;
use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Session;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Security\HttpSecurityManager;
use App\Utilities\Managers\Support\PaymentManager;

class OrderService extends Service
{
    private string $action = 'checkoutForm';

    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly OrderAddressRepository $addresses,
        private readonly CartRepository $carts,
        private readonly CartItemRepository $cartItems,
        private readonly PaymentManager $payments,
        private readonly EventDispatcherInterface $events,
        private readonly AuthManager $auth,
        private readonly Session $session,
        private readonly HttpSecurityManager $httpSecurity,
        private readonly AuditLoggerInterface $audit
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    public function forAction(string $action, array $payload = [], array $context = []): static
    {
        $this->action = $action;
        $this->payload = $payload;
        $this->context = $context;

        return $this;
    }

    protected function handle(): array
    {
        return match ($this->action) {
            'checkout' => $this->checkout(),
            'orders' => $this->ordersPage(),
            'showOrder' => $this->showOrder((int) ($this->context['order'] ?? 0)),
            'capture' => $this->capture((int) ($this->context['order'] ?? 0)),
            'cancel' => $this->cancel((int) ($this->context['order'] ?? 0)),
            'refund' => $this->refund((int) ($this->context['order'] ?? 0)),
            'reconcile' => $this->reconcile((int) ($this->context['order'] ?? 0)),
            default => $this->checkoutForm(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutForm(): array
    {
        $defaultDriver = $this->payments->driverName();
        $defaultIntent = $this->payments->createIntent(0, driver: $defaultDriver);

        return [
            'template' => 'OrderCheckout',
            'status' => 200,
            'title' => 'Checkout',
            'headline' => 'Review and place your order',
            'summary' => 'Orders snapshot the current cart and flow through the framework payment and event layers.',
            'cart' => $this->currentCartPayload(),
            'payment' => [
                'driver' => $defaultDriver,
                'available_drivers' => $this->payments->availableDrivers(),
                'catalog' => $this->payments->driverCatalog(),
                'supported_methods' => $this->payments->supportedMethods($defaultDriver),
                'supported_flows' => $this->payments->supportedFlows($defaultDriver),
                'default_method' => $defaultIntent->method,
                'default_flow' => $defaultIntent->flow,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkout(): array
    {
        $gate = $this->httpSecurity->throttle(
            'checkout:' . ($this->auth->check() ? (string) $this->auth->id() : (string) $this->sessionCartKey()),
            10,
            60
        );

        if (!$gate['allowed']) {
            return $this->response('Checkout throttled', 'Too many checkout attempts. Please wait before trying again.', 429);
        }

        $requestedIdempotencyKey = trim((string) ($this->payload['idempotency_key'] ?? ''));

        if ($requestedIdempotencyKey !== '') {
            $existing = $this->orders->findByPaymentIdempotencyKey($requestedIdempotencyKey);

            if ($existing !== null) {
                return [
                    ...$this->response('Order already created', 'This checkout request has already been processed.', 200),
                    'order' => $this->orderPayload((int) $existing->getKey()),
                    'redirect' => '/orders/' . (int) $existing->getKey(),
                ];
            }
        }

        $cart = $this->currentCart();
        $cartPayload = $this->currentCartPayload();

        if ((int) ($cartPayload['item_count'] ?? 0) === 0) {
            return $this->response('Checkout unavailable', 'The current cart does not contain any items.', 422);
        }

        $requestedDriver = trim((string) ($this->payload['payment_driver'] ?? ''));
        $paymentDriver = $this->resolveRequestedPaymentDriver();

        if ($requestedDriver !== '' && $requestedDriver !== $paymentDriver) {
            return $this->response('Payment unavailable', 'The selected payment driver is not currently enabled.', 422);
        }

        $paymentMethod = $this->resolveRequestedPaymentMethod($paymentDriver);
        $paymentFlow = $this->resolveRequestedPaymentFlow($paymentDriver);

        if (!$this->payments->supportsMethod($paymentMethod, $paymentDriver)) {
            return $this->response('Payment unavailable', 'The selected payment method is not supported by the chosen payment driver.', 422);
        }

        if (!$this->payments->supportsFlow($paymentFlow, $paymentDriver)) {
            return $this->response('Payment unavailable', 'The selected payment flow is not supported by the chosen payment driver.', 422);
        }

        $idempotencyKey = $this->resolveCheckoutIdempotencyKey((int) $cart->getKey());
        $existing = $this->orders->findByPaymentIdempotencyKey($idempotencyKey);

        if ($existing !== null) {
            return [
                ...$this->response('Order already created', 'This checkout request has already been processed.', 200),
                'order' => $this->orderPayload((int) $existing->getKey()),
                'redirect' => '/orders/' . (int) $existing->getKey(),
            ];
        }

        $intent = $this->payments->createIntent(
            (int) ($cartPayload['subtotal_minor'] ?? 0),
            (string) ($cartPayload['currency'] ?? 'SEK'),
            'Order checkout',
            [
                'cart_id' => (int) $cart->getKey(),
                'user_id' => $this->auth->check() ? (int) $this->auth->id() : null,
            ],
            $paymentMethod,
            $paymentFlow,
            $idempotencyKey,
            $paymentDriver
        );
        $payment = $this->payments->authorize($intent);
        $contactName = (string) ($this->payload['name'] ?? '');
        $contactEmail = (string) ($this->payload['email'] ?? '');
        $orderStatus = $this->orderStatusForIntent($payment->intent);

        $order = $this->orders->create([
            'user_id' => $this->auth->check() ? (int) $this->auth->id() : null,
            'cart_id' => (int) $cart->getKey(),
            'order_number' => $this->orders->nextOrderNumber(),
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'status' => $orderStatus,
            'payment_status' => $payment->intent->status,
            'payment_driver' => $payment->driver,
            'payment_method' => $payment->intent->method,
            'payment_flow' => $payment->intent->flow,
            'payment_reference' => $payment->intent->reference,
            'payment_provider_reference' => $payment->intent->providerReference,
            'payment_external_reference' => $payment->intent->externalReference,
            'payment_webhook_reference' => $payment->intent->webhookReference,
            'payment_idempotency_key' => $payment->intent->idempotencyKey,
            'payment_customer_action_required' => $payment->intent->customerActionRequired,
            'currency' => (string) ($cartPayload['currency'] ?? 'SEK'),
            'subtotal_minor' => (int) ($cartPayload['subtotal_minor'] ?? 0),
            'total_minor' => (int) ($cartPayload['subtotal_minor'] ?? 0),
            'payment_next_action' => $this->toJson(
                $payment->intent->nextAction,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            'payment_intent' => $this->toJson(
                $payment->intent->toArray(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ]);

        foreach ((array) ($cartPayload['items'] ?? []) as $item) {
            $this->orderItems->create([
                'order_id' => (int) $order->getKey(),
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => (string) ($item['name'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price_minor' => (int) ($item['unit_price_minor'] ?? 0),
                'line_total_minor' => (int) ($item['line_total_minor'] ?? 0),
                'metadata' => $this->toJson(['slug' => $item['slug'] ?? ''], JSON_THROW_ON_ERROR),
            ]);
        }

        $this->addresses->create([
            'order_id' => (int) $order->getKey(),
            'type' => 'shipping',
            'name' => $contactName,
            'line_one' => (string) ($this->payload['line_one'] ?? ''),
            'line_two' => (string) ($this->payload['line_two'] ?? ''),
            'postal_code' => (string) ($this->payload['postal_code'] ?? ''),
            'city' => (string) ($this->payload['city'] ?? ''),
            'country' => (string) ($this->payload['country'] ?? ''),
            'email' => $contactEmail,
            'phone' => (string) ($this->payload['phone'] ?? ''),
        ]);

        $this->carts->updateStatus((int) $cart->getKey(), 'checked_out');
        $this->events->dispatch('order.created', [
            'order_id' => (int) $order->getKey(),
        ]);

        if ($payment->intent->status === 'captured') {
            $this->events->dispatch('order.paid', [
                'order_id' => (int) $order->getKey(),
            ]);
        }

        $this->audit->record('order.created', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) $order->getKey(),
            'payment_driver' => $payment->driver,
            'payment_status' => $payment->intent->status,
            'payment_method' => $payment->intent->method,
            'payment_flow' => $payment->intent->flow,
            'payment_idempotency_key' => $payment->intent->idempotencyKey,
            'total_minor' => (int) ($cartPayload['subtotal_minor'] ?? 0),
        ], 'order');

        return [
            ...$this->response(
                'Order placed',
                $this->checkoutMessageForIntent($payment->intent),
                $this->responseStatusForIntent($payment->intent)
            ),
            'order' => $this->orderPayload((int) $order->getKey()),
            'redirect' => '/orders/' . (int) $order->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ordersPage(): array
    {
        if (!$this->auth->check()) {
            return $this->response('Authentication required', 'Sign in to review your orders.', 401);
        }

        return [
            'template' => 'OrderList',
            'status' => 200,
            'title' => 'Orders',
            'headline' => 'Your order history',
            'summary' => 'Order data stays available through the repository and presentation layers.',
            'orders' => $this->orders->forUserSummary((int) $this->auth->id()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function showOrder(int $orderId): array
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            return $this->response('Order not found', 'The requested order could not be found.', 404);
        }

        if (!$this->canAccessOrder($orderId, (int) ($order->getAttribute('user_id') ?? 0))) {
            return $this->response('Forbidden', 'You are not allowed to access this order.', 403);
        }

        return [
            'template' => 'OrderDetail',
            'status' => 200,
            'title' => 'Order detail',
            'headline' => 'Order ' . (string) ($order->getAttribute('order_number') ?? ''),
            'summary' => 'Detailed order information rendered from the completed order module.',
            'order' => $this->orderPayload($orderId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function capture(int $orderId): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->response('Forbidden', 'Order capture requires the order.manage permission.', 403);
        }

        return $this->transitionPayment($orderId, 'capture');
    }

    /**
     * @return array<string, mixed>
     */
    private function cancel(int $orderId): array
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            return $this->response('Order not found', 'The requested order could not be found.', 404);
        }

        if (!$this->canAccessOrder($orderId, (int) ($order->getAttribute('user_id') ?? 0))) {
            return $this->response('Forbidden', 'You are not allowed to cancel this order.', 403);
        }

        return $this->transitionPayment($orderId, 'cancel');
    }

    /**
     * @return array<string, mixed>
     */
    private function refund(int $orderId): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->response('Forbidden', 'Order refunds require the order.manage permission.', 403);
        }

        return $this->transitionPayment($orderId, 'refund');
    }

    /**
     * @return array<string, mixed>
     */
    private function reconcile(int $orderId): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->response('Forbidden', 'Payment reconciliation requires the order.manage permission.', 403);
        }

        return $this->transitionPayment($orderId, 'reconcile');
    }

    /**
     * @return array<string, mixed>
     */
    private function transitionPayment(int $orderId, string $action): array
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            return $this->response('Order not found', 'The requested order could not be found.', 404);
        }

        $intent = PaymentIntent::fromArray($this->decodeIntent((string) ($order->getAttribute('payment_intent') ?? '{}')));
        $result = match ($action) {
            'capture' => $this->payments->capture($intent),
            'refund' => $this->payments->refund($intent),
            'reconcile' => $this->payments->reconcile($intent, $this->payload),
            default => $this->payments->cancel($intent),
        };

        if (!$result->successful) {
            return $this->response('Payment transition failed', $result->message, 422);
        }

        $status = $this->orderStatusForIntent($result->intent);
        $updated = $this->orders->updateLifecycle($orderId, [
            'status' => $status,
            'payment_status' => $result->intent->status,
            'payment_driver' => $result->driver,
            'payment_method' => $result->intent->method,
            'payment_flow' => $result->intent->flow,
            'payment_reference' => $result->intent->reference,
            'payment_provider_reference' => $result->intent->providerReference,
            'payment_external_reference' => $result->intent->externalReference,
            'payment_webhook_reference' => $result->intent->webhookReference,
            'payment_idempotency_key' => $result->intent->idempotencyKey,
            'payment_customer_action_required' => $result->intent->customerActionRequired,
            'payment_next_action' => $this->toJson(
                $result->intent->nextAction,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            'payment_intent' => $this->toJson(
                $result->intent->toArray(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ]);

        $event = $this->eventForPaymentTransition($action, $result->intent);

        if ($event !== null) {
            $this->events->dispatch($event, ['order_id' => $orderId]);
        }

        $this->audit->record('order.' . $action, [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) $orderId,
            'payment_driver' => $result->driver,
            'payment_status' => $result->intent->status,
            'payment_method' => $result->intent->method,
            'payment_flow' => $result->intent->flow,
            'status' => $status,
        ], 'order');

        return [
            ...$this->response('Order updated', ucfirst($action) . ' completed successfully.', 200),
            'order' => $this->orderPayload((int) $updated->getKey()),
            'redirect' => '/orders/' . (int) $updated->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(int $orderId): array
    {
        $order = $this->orders->find($orderId);
        $summary = $order !== null ? $this->orders->mapSummary($order) : [];
        $items = $this->orderItems->summaryForOrder($orderId);
        $addresses = $this->addresses->summaryForOrder($orderId);

        return [
            ...$summary,
            'items' => array_map(function (array $item) use ($summary): array {
                return [
                    ...$item,
                    'unit_price' => $this->formatMoneyMinor((int) ($item['unit_price_minor'] ?? 0), (string) ($summary['currency'] ?? 'SEK')),
                    'line_total' => $this->formatMoneyMinor((int) ($item['line_total_minor'] ?? 0), (string) ($summary['currency'] ?? 'SEK')),
                ];
            }, $items),
            'addresses' => $addresses,
        ];
    }

    private function currentCart(): Cart
    {
        if ($this->auth->check()) {
            $cart = $this->carts->findActiveByUserId((int) $this->auth->id());

            if ($cart instanceof Cart) {
                return $cart;
            }
        }

        $guestKey = $this->sessionCartKey();
        $cart = $this->carts->findActiveBySessionKey($guestKey);

        if ($cart instanceof Cart) {
            return $cart;
        }

        return $this->carts->createGuestCart($guestKey, 'SEK');
    }

    /**
     * @return array<string, mixed>
     */
    private function currentCartPayload(): array
    {
        $cart = $this->currentCart();
        $items = $this->cartItems->summaryForCart((int) $cart->getKey());
        $subtotal = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + (int) ($item['line_total_minor'] ?? 0),
            0
        );

        return [
            'id' => (int) $cart->getKey(),
            'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
            'item_count' => count($items),
            'subtotal_minor' => $subtotal,
            'subtotal' => $this->formatMoneyMinor($subtotal, (string) ($cart->getAttribute('currency') ?? 'SEK')),
            'items' => $items,
        ];
    }

    private function resolveRequestedPaymentDriver(): string
    {
        $requested = trim((string) ($this->payload['payment_driver'] ?? ''));

        if ($requested !== '' && in_array($requested, $this->payments->availableDrivers(), true)) {
            return $requested;
        }

        return $this->payments->driverName();
    }

    private function resolveRequestedPaymentMethod(?string $driver = null): PaymentMethod
    {
        $resolvedDriver = $driver ?? $this->resolveRequestedPaymentDriver();
        $requested = $this->payload['payment_method'] ?? $this->payments->createIntent(0, driver: $resolvedDriver)->method;

        return PaymentMethod::fromMixed(is_string($requested) ? $requested : null);
    }

    private function resolveRequestedPaymentFlow(?string $driver = null): PaymentFlow
    {
        $resolvedDriver = $driver ?? $this->resolveRequestedPaymentDriver();
        $requested = $this->payload['payment_flow'] ?? $this->payments->createIntent(0, driver: $resolvedDriver)->flow;

        return PaymentFlow::fromMixed(is_string($requested) ? $requested : null);
    }

    private function resolveCheckoutIdempotencyKey(int $cartId): string
    {
        $requested = trim((string) ($this->payload['idempotency_key'] ?? ''));

        if ($requested !== '') {
            return $requested;
        }

        if ($this->auth->check()) {
            return sprintf('checkout:user:%d:cart:%d', (int) $this->auth->id(), $cartId);
        }

        return sprintf('checkout:guest:%s:cart:%d', $this->sessionCartKey(), $cartId);
    }

    private function sessionCartKey(): string
    {
        $this->session->start();
        $key = $this->session->get('cart.session_key');

        if (is_string($key) && $key !== '') {
            return $key;
        }

        $key = bin2hex(random_bytes(16));
        $this->session->put('cart.session_key', $key);

        return $key;
    }

    private function canAccessOrder(int $orderId, int $ownerId): bool
    {
        return $this->auth->hasPermission('order.manage')
            || ($this->auth->check() && (int) $this->auth->id() === $ownerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function response(string $title, string $message, int $status): array
    {
        $paymentDriver = $this->resolveRequestedPaymentDriver();
        $defaultIntent = $this->payments->createIntent(0, driver: $paymentDriver);

        return [
            'template' => 'OrderCheckout',
            'status' => $status,
            'title' => $title,
            'headline' => $title,
            'summary' => $message,
            'message' => $message,
            'cart' => $this->currentCartPayload(),
            'payment' => [
                'driver' => $paymentDriver,
                'available_drivers' => $this->payments->availableDrivers(),
                'catalog' => $this->payments->driverCatalog(),
                'supported_methods' => $this->payments->supportedMethods($paymentDriver),
                'supported_flows' => $this->payments->supportedFlows($paymentDriver),
                'default_method' => $defaultIntent->method,
                'default_flow' => $defaultIntent->flow,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeIntent(string $payload): array
    {
        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function orderStatusForIntent(PaymentIntent $intent): string
    {
        return match ($intent->status) {
            'captured' => 'processing',
            'partially_refunded', 'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'requires_action' => 'awaiting_payment_action',
            'processing', 'pending_review' => 'pending_payment',
            default => 'placed',
        };
    }

    private function responseStatusForIntent(PaymentIntent $intent): int
    {
        return in_array($intent->status, ['requires_action', 'processing', 'pending_review'], true) ? 202 : 201;
    }

    private function checkoutMessageForIntent(PaymentIntent $intent): string
    {
        return match ($intent->status) {
            'requires_action' => 'The order has been created and is waiting for a customer payment action.',
            'processing' => 'The order has been created and is waiting for asynchronous payment confirmation.',
            'pending_review' => 'The order has been created and is waiting for manual payment review.',
            'captured' => 'The order has been created and payment completed immediately.',
            default => 'The order has been created and payment authorized by the configured driver.',
        };
    }

    private function eventForPaymentTransition(string $action, PaymentIntent $intent): ?string
    {
        return match ($action) {
            'capture' => 'order.paid',
            'refund' => 'order.refunded',
            'cancel' => 'order.cancelled',
            'reconcile' => $intent->status === 'captured' ? 'order.paid' : null,
            default => null,
        };
    }
}
