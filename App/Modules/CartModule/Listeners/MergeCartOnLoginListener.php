<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Listeners;

use App\Contracts\Async\ListenerInterface;
use App\Modules\CartModule\Services\CartService;

class MergeCartOnLoginListener implements ListenerInterface
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function subscriptions(): array
    {
        return [
            'auth.login' => [
                'method' => 'handle',
                'queued' => false,
            ],
        ];
    }

    public function handle(string $event, array $payload = []): mixed
    {
        $userId = (int) ($payload['user_id'] ?? 0);

        if ($userId > 0) {
            $this->cartService->mergeGuestCartToUser($userId);
        }

        return null;
    }
}
