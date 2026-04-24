<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Session;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Support\Commerce\CartPricingManager;
use App\Utilities\Managers\Security\AuthManager;

class CartService extends Service
{
    private string $action = 'show';

    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly CartRepository $carts,
        private readonly CartItemRepository $items,
        private readonly ProductRepository $products,
        private readonly Session $session,
        private readonly AuthManager $auth,
        private readonly CartPricingManager $pricing
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
            'addItem' => $this->addItem(),
            'updateItem' => $this->updateItem((int) ($this->context['item'] ?? 0)),
            'removeItem' => $this->removeItem((int) ($this->context['item'] ?? 0)),
            'applyDiscount' => $this->applyDiscount(),
            'removeDiscount' => $this->removeDiscount(),
            default => $this->show(),
        };
    }

    /**
     * Used by the auth login listener to merge a guest cart into the active user cart.
     */
    public function mergeGuestCartToUser(int $userId): array
    {
        $guestKey = $this->guestSessionKey(false);

        if ($guestKey === null || $guestKey === '') {
            return ['merged' => false, 'guest_cart_id' => null, 'user_cart_id' => null, 'merged_items' => 0];
        }

        $guestCart = $this->carts->findActiveBySessionKey($guestKey);

        if (!$guestCart instanceof Cart) {
            return ['merged' => false, 'guest_cart_id' => null, 'user_cart_id' => null, 'merged_items' => 0];
        }

        $userCart = $this->carts->findActiveByUserId($userId)
            ?? $this->carts->createUserCart($userId, (string) ($guestCart->getAttribute('currency') ?? 'SEK'));
        $mergedItems = 0;

        foreach ($this->items->forCart((int) $guestCart->getKey()) as $item) {
            $metadata = $this->decodeMetadata((string) ($item->getAttribute('metadata') ?? '{}'));
            $mergedItems += (int) ($item->getAttribute('quantity') ?? 1);
            $this->items->addOrIncrement((int) $userCart->getKey(), [
                'id' => (int) ($item->getAttribute('product_id') ?? 0),
                'name' => (string) ($item->getAttribute('product_name') ?? ''),
                'price_minor' => (int) ($item->getAttribute('unit_price_minor') ?? 0),
                'slug' => (string) ($metadata['slug'] ?? ''),
                'currency' => (string) ($metadata['currency'] ?? 'SEK'),
                'category_id' => (int) ($metadata['category_id'] ?? 0),
                'fulfillment_type' => (string) ($metadata['fulfillment_type'] ?? 'physical_shipping'),
                'fulfillment_label' => (string) ($metadata['fulfillment_label'] ?? 'Physical shipping'),
                'fulfillment_policy' => is_array($metadata['fulfillment_policy'] ?? null) ? $metadata['fulfillment_policy'] : [],
            ], (int) ($item->getAttribute('quantity') ?? 1));
        }

        if ((string) ($userCart->getAttribute('discount_code') ?? '') === '' && (string) ($guestCart->getAttribute('discount_code') ?? '') !== '') {
            $userCart = $this->carts->syncDiscountState(
                (int) $userCart->getKey(),
                (string) ($guestCart->getAttribute('discount_code') ?? ''),
                (string) ($guestCart->getAttribute('discount_label') ?? ''),
                ($guestCart->getAttribute('discount_snapshot') !== null ? (string) $guestCart->getAttribute('discount_snapshot') : null)
            );
        }

        $this->carts->deleteModel($guestCart);
        $this->session->forget('cart.session_key');

        return [
            'merged' => true,
            'guest_cart_id' => (int) $guestCart->getKey(),
            'user_cart_id' => (int) $userCart->getKey(),
            'merged_items' => $mergedItems,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function show(): array
    {
        return [
            'template' => 'CartPage',
            'status' => 200,
            'title' => 'Your cart',
            'headline' => 'Current cart contents',
            'summary' => 'Guest and authenticated carts resolve through the completed session and persistence layers.',
            'cart' => $this->cartPayload($this->currentCart()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addItem(): array
    {
        $quantity = max(1, (int) ($this->payload['quantity'] ?? 1));
        $product = isset($this->payload['product_id'])
            ? $this->products->find((int) $this->payload['product_id'])
            : $this->products->findPublishedBySlug((string) ($this->payload['slug'] ?? ''));

        if ($product === null) {
            return $this->response('Unable to add item', 'The requested product could not be found.', 404);
        }

        if ($this->requiresStock((string) ($product->getAttribute('fulfillment_type') ?? 'physical_shipping'))
            && (int) ($product->getAttribute('stock') ?? 0) < $quantity) {
            return $this->response('Unable to add item', 'The requested quantity is not currently available.', 409);
        }

        $cart = $this->currentCart();
        $this->items->addOrIncrement((int) $cart->getKey(), $this->products->mapProductData($product), $quantity);

        return [
            ...$this->response('Item added', 'The selected product was added to the cart.', 200),
            'cart' => $this->cartPayload($cart),
            'redirect' => '/cart',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updateItem(int $itemId): array
    {
        if ($itemId <= 0) {
            return $this->response('Unable to update item', 'A valid cart item identifier is required.', 422);
        }

        $item = $this->items->find($itemId);

        if ($item === null) {
            return $this->response('Unable to update item', 'The requested cart item could not be found.', 404);
        }

        $quantity = max(1, (int) ($this->payload['quantity'] ?? 1));
        $product = $this->products->find((int) ($item->getAttribute('product_id') ?? 0));

        if ($product !== null
            && $this->requiresStock((string) ($product->getAttribute('fulfillment_type') ?? 'physical_shipping'))
            && (int) ($product->getAttribute('stock') ?? 0) < $quantity) {
            return $this->response('Unable to update item', 'The requested quantity exceeds the currently available stock.', 409);
        }

        $this->items->updateQuantity($itemId, $quantity);

        return [
            ...$this->response('Item updated', 'The cart item quantity was updated.', 200),
            'cart' => $this->cartPayload($this->currentCart()),
            'redirect' => '/cart',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function removeItem(int $itemId): array
    {
        if ($itemId <= 0) {
            return $this->response('Unable to remove item', 'A valid cart item identifier is required.', 422);
        }

        $this->items->delete($itemId);

        return [
            ...$this->response('Item removed', 'The selected cart item was removed.', 200),
            'cart' => $this->cartPayload($this->currentCart()),
            'redirect' => '/cart',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyDiscount(): array
    {
        $cart = $this->currentCart();
        $code = strtoupper(trim((string) ($this->payload['coupon_code'] ?? $this->payload['discount_code'] ?? '')));

        if ($code === '') {
            return [
                ...$this->response('Promotion unavailable', 'Enter a promotion code before applying it to the cart.', 422),
                'cart' => $this->cartPayload($cart),
                'redirect' => '/cart',
            ];
        }

        $pricing = $this->cartPayload($cart, [
            'discount_code' => $code,
        ]);
        $promotion = is_array($pricing['promotion'] ?? null) ? $pricing['promotion'] : [];

        if (!($promotion['applied'] ?? false)) {
            return [
                ...$this->response('Promotion unavailable', (string) ($promotion['message'] ?? 'The promotion code could not be applied to the current cart.'), 422),
                'cart' => $this->cartPayload($cart),
                'redirect' => '/cart',
            ];
        }

        $this->persistDiscountState($cart, $promotion);

        return [
            ...$this->response('Promotion applied', (string) ($promotion['message'] ?? 'The promotion code was applied to the cart.'), 200),
            'cart' => $this->cartPayload($cart),
            'redirect' => '/cart',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function removeDiscount(): array
    {
        $cart = $this->currentCart();
        $this->clearDiscountState($cart);

        return [
            ...$this->response('Promotion removed', 'The stored promotion code was removed from the cart.', 200),
            'cart' => $this->cartPayload($cart),
            'redirect' => '/cart',
        ];
    }

    private function currentCart(): Cart
    {
        $userId = $this->auth->check() ? (int) $this->auth->id() : null;

        if ($userId !== null) {
            return $this->carts->findActiveByUserId($userId)
                ?? $this->carts->createUserCart($userId, 'SEK');
        }

        $sessionKey = $this->guestSessionKey();

        return $this->carts->findActiveBySessionKey($sessionKey)
            ?? $this->carts->createGuestCart($sessionKey, 'SEK');
    }

    private function guestSessionKey(bool $create = true): ?string
    {
        $this->session->start();
        $key = $this->session->get('cart.session_key');

        if (is_string($key) && $key !== '') {
            return $key;
        }

        if (!$create) {
            return null;
        }

        $key = bin2hex(random_bytes(16));
        $this->session->put('cart.session_key', $key);

        return $key;
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(Cart $cart, array $context = []): array
    {
        $items = $this->items->summaryForCart((int) $cart->getKey());
        $pricing = $this->pricing->price($items, (string) ($cart->getAttribute('currency') ?? 'SEK'), [
            'country' => (string) ($this->payload['country'] ?? ''),
            'shipping_option' => (string) ($this->payload['shipping_option'] ?? ''),
            'discount_code' => (string) ($context['discount_code'] ?? $cart->getAttribute('discount_code') ?? ''),
        ]);
        $promotion = is_array($pricing['promotion'] ?? null) ? $pricing['promotion'] : [];
        $storedCode = strtoupper(trim((string) ($cart->getAttribute('discount_code') ?? '')));

        if ($storedCode !== '') {
            if (($promotion['applied'] ?? false) && strtoupper((string) ($promotion['code'] ?? '')) === $storedCode) {
                $this->persistDiscountState($cart, $promotion);
            } elseif (($promotion['requested_code'] ?? '') === $storedCode) {
                $this->clearDiscountState($cart);
                $promotion = [];
                $pricing = $this->pricing->price($items, (string) ($cart->getAttribute('currency') ?? 'SEK'), [
                    'country' => (string) ($this->payload['country'] ?? ''),
                    'shipping_option' => (string) ($this->payload['shipping_option'] ?? ''),
                ]);
            }
        }

        return [
            'id' => (int) $cart->getKey(),
            'status' => (string) ($cart->getAttribute('status') ?? 'active'),
            'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
            'items' => $items,
            'item_count' => count($items),
            ...$pricing,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function response(string $title, string $message, int $status): array
    {
        return [
            'template' => 'CartPage',
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
    private function decodeMetadata(string $metadata): array
    {
        try {
            $decoded = $this->fromJson($metadata, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function requiresStock(string $fulfillmentType): bool
    {
        return in_array(strtolower(trim($fulfillmentType)), [
            'physical_shipping',
            'store_pickup',
            'scheduled_pickup',
        ], true);
    }

    /**
     * @param array<string, mixed> $promotion
     */
    private function persistDiscountState(Cart $cart, array $promotion): void
    {
        $snapshot = is_array($promotion['snapshot'] ?? null)
            ? $this->toJson($promotion['snapshot'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            : null;

        $fresh = $this->carts->syncDiscountState(
            (int) $cart->getKey(),
            (string) ($promotion['code'] ?? ''),
            (string) ($promotion['label'] ?? ''),
            $snapshot
        );

        $cart->forceFill($fresh->getAttributes());
    }

    private function clearDiscountState(Cart $cart): void
    {
        $fresh = $this->carts->clearDiscountState((int) $cart->getKey());
        $cart->forceFill($fresh->getAttributes());
    }
}
