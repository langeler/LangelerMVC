<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Session;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
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
        private readonly AuthManager $auth
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
            $mergedItems += (int) ($item->getAttribute('quantity') ?? 1);
            $this->items->addOrIncrement((int) $userCart->getKey(), [
                'id' => (int) ($item->getAttribute('product_id') ?? 0),
                'name' => (string) ($item->getAttribute('product_name') ?? ''),
                'price_minor' => (int) ($item->getAttribute('unit_price_minor') ?? 0),
                'slug' => '',
                'currency' => (string) (($this->decodeMetadata((string) ($item->getAttribute('metadata') ?? '{}'))['currency'] ?? 'SEK')),
            ], (int) ($item->getAttribute('quantity') ?? 1));
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

        $this->items->updateQuantity($itemId, max(1, (int) ($this->payload['quantity'] ?? 1)));

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
    private function cartPayload(Cart $cart): array
    {
        $items = $this->items->summaryForCart((int) $cart->getKey());
        $subtotal = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + (int) ($item['line_total_minor'] ?? 0),
            0
        );

        return [
            'id' => (int) $cart->getKey(),
            'status' => (string) ($cart->getAttribute('status') ?? 'active'),
            'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
            'items' => $items,
            'item_count' => count($items),
            'subtotal_minor' => $subtotal,
            'subtotal' => $this->formatMoneyMinor($subtotal, (string) ($cart->getAttribute('currency') ?? 'SEK')),
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
}
