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
use App\Support\Commerce\CommerceTotalsCalculator;

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
        private readonly CommerceTotalsCalculator $totals,
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

        $currency = (string) ($cart->getAttribute('currency') ?? 'SEK');
        $totals = $this->totals->calculate($cartItems, $currency);
        $subtotal = (int) ($totals['subtotal_minor'] ?? 0);
        $total = (int) ($totals['total_minor'] ?? 0);
        $order = $this->orders()->create([
            'user_id' => (int) $customer->getKey(),
            'cart_id' => (int) $cart->getKey(),
            'order_number' => $this->orders()->nextOrderNumber(),
            'contact_name' => (string) ($customer->getAttribute('name') ?? 'Demo Customer'),
            'contact_email' => (string) ($customer->getAttribute('email') ?? 'customer@langelermvc.test'),
            'status' => 'processing',
            'payment_status' => 'captured',
            'payment_driver' => 'testing',
            'payment_method' => 'card',
            'payment_flow' => 'purchase',
            'payment_reference' => 'demo-seed-order',
            'payment_provider_reference' => 'provider-demo-seed-order',
            'payment_external_reference' => 'external-demo-seed-order',
            'payment_webhook_reference' => 'wh-demo-seed-order',
            'payment_idempotency_key' => 'seed-order-customer-2',
            'payment_customer_action_required' => false,
            'currency' => $currency,
            'subtotal_minor' => $subtotal,
            'discount_minor' => (int) ($totals['discount_minor'] ?? 0),
            'shipping_minor' => (int) ($totals['shipping_minor'] ?? 0),
            'tax_minor' => (int) ($totals['tax_minor'] ?? 0),
            'total_minor' => $total,
            'shipping_country' => 'SE',
            'shipping_zone' => 'SE',
            'shipping_option' => 'postnord-service-point',
            'shipping_option_label' => 'PostNord Service Point',
            'shipping_carrier' => 'postnord',
            'shipping_carrier_label' => 'PostNord',
            'shipping_service' => 'service_point',
            'shipping_service_label' => 'Service Point',
            'shipping_service_point_id' => 'PND-STHLM-001',
            'shipping_service_point_name' => 'PostNord Stockholm Service Point',
            'tracking_number' => 'PNDSEEDTRACK001',
            'tracking_url' => 'https://www.postnord.se/en/track-and-trace',
            'shipment_reference' => 'SHP-DEMO-SEED',
            'tracking_events' => $this->toJson([
                [
                    'status' => 'shipped',
                    'label' => 'Shipment handed to PostNord.',
                    'occurred_at' => gmdate(DATE_ATOM),
                    'location' => 'Stockholm',
                    'tracking_number' => 'PNDSEEDTRACK001',
                ],
            ], JSON_THROW_ON_ERROR),
            'shipped_at' => gmdate('Y-m-d H:i:s'),
            'delivered_at' => null,
            'fulfillment_status' => 'ready_to_fulfill',
            'inventory_status' => 'committed',
            'payment_next_action' => $this->toJson([], JSON_THROW_ON_ERROR),
            'payment_intent' => $this->toJson([
                'amount' => $total,
                'currency' => $currency,
                'method' => 'card',
                'flow' => 'purchase',
                'reference' => 'demo-seed-order',
                'providerReference' => 'provider-demo-seed-order',
                'externalReference' => 'external-demo-seed-order',
                'idempotencyKey' => 'seed-order-customer-2',
                'webhookReference' => 'wh-demo-seed-order',
                'nextAction' => [],
                'customerActionRequired' => false,
                'status' => 'captured',
                'authorizedAmount' => $total,
                'capturedAmount' => $total,
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
