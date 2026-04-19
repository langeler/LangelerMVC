<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Services;

use App\Abstracts\Http\Service;
use App\Contracts\Async\EventDispatcherInterface;
use App\Core\Session;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Support\Payments\PaymentIntent;
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
        private readonly HttpSecurityManager $httpSecurity
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
            default => $this->checkoutForm(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutForm(): array
    {
        return [
            'template' => 'OrderCheckout',
            'status' => 200,
            'title' => 'Checkout',
            'headline' => 'Review and place your order',
            'summary' => 'Orders snapshot the current cart and flow through the framework payment and event layers.',
            'cart' => $this->currentCartPayload(),
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

        $cart = $this->currentCart();
        $cartPayload = $this->currentCartPayload();

        if ((int) ($cartPayload['item_count'] ?? 0) === 0) {
            return $this->response('Checkout unavailable', 'The current cart does not contain any items.', 422);
        }

        $intent = $this->payments->createIntent(
            (int) ($cartPayload['subtotal_minor'] ?? 0),
            (string) ($cartPayload['currency'] ?? 'SEK'),
            'Order checkout'
        );
        $payment = $this->payments->authorize($intent);
        $contactName = (string) ($this->payload['name'] ?? '');
        $contactEmail = (string) ($this->payload['email'] ?? '');

        $order = $this->orders->create([
            'user_id' => $this->auth->check() ? (int) $this->auth->id() : null,
            'cart_id' => (int) $cart->getKey(),
            'order_number' => $this->orders->nextOrderNumber(),
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'status' => 'placed',
            'payment_status' => $payment->intent->status,
            'payment_driver' => $payment->driver,
            'payment_reference' => $payment->intent->reference,
            'currency' => (string) ($cartPayload['currency'] ?? 'SEK'),
            'subtotal_minor' => (int) ($cartPayload['subtotal_minor'] ?? 0),
            'total_minor' => (int) ($cartPayload['subtotal_minor'] ?? 0),
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

        return [
            ...$this->response('Order placed', 'The order has been created and payment authorized by the configured driver.', 201),
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
            default => $this->payments->cancel($intent),
        };

        if (!$result->successful) {
            return $this->response('Payment transition failed', $result->message, 422);
        }

        $status = match ($action) {
            'capture' => 'processing',
            'refund' => 'refunded',
            default => 'cancelled',
        };

        $updated = $this->orders->updateLifecycle($orderId, [
            'status' => $status,
            'payment_status' => $result->intent->status,
            'payment_reference' => $result->intent->reference,
            'payment_intent' => $this->toJson(
                $result->intent->toArray(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ]);

        $event = match ($action) {
            'capture' => 'order.paid',
            'refund' => 'order.refunded',
            default => 'order.cancelled',
        };
        $this->events->dispatch($event, ['order_id' => $orderId]);

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
        return [
            'template' => 'OrderCheckout',
            'status' => $status,
            'title' => $title,
            'headline' => $title,
            'summary' => $message,
            'message' => $message,
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
}
