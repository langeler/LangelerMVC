<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Seeds;

use App\Abstracts\Database\Seed;
use App\Core\Database;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Modules\UserModule\Repositories\UserRepository;

class CartSeed extends Seed
{
    public static function dependencies(): array
    {
        return [
            \App\Modules\UserModule\Seeds\UserPlatformSeed::class,
            \App\Modules\ShopModule\Seeds\ShopSeed::class,
        ];
    }

    public function __construct(
        CartRepository $repository,
        private readonly CartItemRepository $items,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
        Database $database
    ) {
        parent::__construct($repository, $database);
    }

    public function run(): void
    {
        $customer = $this->users->findByEmail('customer@langelermvc.test');
        $product = $this->products->findPublishedBySlug('starter-platform-license');

        if ($customer === null || $product === null) {
            return;
        }

        $cart = $this->carts()->findActiveByUserId((int) $customer->getKey())
            ?? $this->carts()->createUserCart((int) $customer->getKey(), 'SEK');

        if ($this->items->forCart((int) $cart->getKey()) !== []) {
            return;
        }

        $this->items->addOrIncrement((int) $cart->getKey(), $this->products->mapProductData($product), 1);
    }

    public function defaultData(): array
    {
        return [];
    }

    private function carts(): CartRepository
    {
        return $this->repository;
    }
}
