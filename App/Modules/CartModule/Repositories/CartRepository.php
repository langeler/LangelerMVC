<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\CartModule\Models\Cart;

class CartRepository extends Repository
{
    protected string $modelClass = Cart::class;

    public function findActiveByUserId(int $userId): ?Cart
    {
        $cart = $this->findOneBy([
            'user_id' => $userId,
            'status' => 'active',
        ]);

        return $cart instanceof Cart ? $cart : null;
    }

    public function findActiveBySessionKey(string $sessionKey): ?Cart
    {
        $cart = $this->findOneBy([
            'session_key' => $sessionKey,
            'status' => 'active',
        ]);

        return $cart instanceof Cart ? $cart : null;
    }

    public function createGuestCart(string $sessionKey, string $currency = 'SEK'): Cart
    {
        /** @var Cart $cart */
        $cart = $this->create([
            'user_id' => null,
            'session_key' => $sessionKey,
            'status' => 'active',
            'currency' => $currency,
        ]);

        return $cart;
    }

    public function createUserCart(int $userId, string $currency = 'SEK'): Cart
    {
        /** @var Cart $cart */
        $cart = $this->create([
            'user_id' => $userId,
            'session_key' => null,
            'status' => 'active',
            'currency' => $currency,
        ]);

        return $cart;
    }

    public function updateStatus(int $cartId, string $status): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), [
                'status' => $status,
                'updated_at' => $this->freshTimestamp(),
            ])
            ->where('id', '=', $cartId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }
}
