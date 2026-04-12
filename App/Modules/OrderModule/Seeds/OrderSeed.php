<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Seeds;

use App\Abstracts\Database\Seed;
use App\Core\Database;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\UserModule\Repositories\UserRepository;

class OrderSeed extends Seed
{
    public static function dependencies(): array
    {
        return [
            \App\Modules\UserModule\Seeds\UserPlatformSeed::class,
            \App\Modules\CartModule\Seeds\CartSeed::class,
        ];
    }

    public function __construct(
        OrderRepository $repository,
        private readonly OrderItemRepository $items,
        private readonly OrderAddressRepository $addresses,
        private readonly CartRepository $carts,
        private readonly CartItemRepository $cartItems,
        private readonly UserRepository $users,
        Database $database
    ) {
        parent::__construct($repository, $database);
    }

    public function run(): void
    {
        if ($this->orders()->count([]) > 0) {
            return;
        }

        $customer = $this->users->findByEmail('customer@langelermvc.test');

        if ($customer === null) {
            return;
        }

        $cart = $this->carts->findActiveByUserId((int) $customer->getKey());

        if ($cart === null) {
            return;
        }

        $cartItems = $this->cartItems->summaryForCart((int) $cart->getKey());

        if ($cartItems === []) {
            return;
        }

        $subtotal = array_reduce($cartItems, static fn(int $carry, array $item): int => $carry + (int) ($item['line_total_minor'] ?? 0), 0);
        $order = $this->orders()->create([
            'user_id' => (int) $customer->getKey(),
            'cart_id' => (int) $cart->getKey(),
            'order_number' => $this->orders()->nextOrderNumber(),
            'contact_name' => (string) ($customer->getAttribute('name') ?? 'Demo Customer'),
            'contact_email' => (string) ($customer->getAttribute('email') ?? 'customer@langelermvc.test'),
            'status' => 'processing',
            'payment_status' => 'captured',
            'payment_driver' => 'testing',
            'payment_reference' => 'demo-seed-order',
            'currency' => 'SEK',
            'subtotal_minor' => $subtotal,
            'total_minor' => $subtotal,
            'payment_intent' => $this->toJson([
                'amount' => $subtotal,
                'currency' => 'SEK',
                'reference' => 'demo-seed-order',
                'status' => 'captured',
                'authorizedAmount' => $subtotal,
                'capturedAmount' => $subtotal,
                'refundedAmount' => 0,
            ], JSON_THROW_ON_ERROR),
        ]);

        foreach ($cartItems as $item) {
            $this->items->create([
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
            'name' => 'Demo Customer',
            'line_one' => 'Framework Street 1',
            'line_two' => '',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'email' => 'customer@langelermvc.test',
            'phone' => '',
        ]);
    }

    public function defaultData(): array
    {
        return [];
    }

    private function orders(): OrderRepository
    {
        return $this->repository;
    }
}
