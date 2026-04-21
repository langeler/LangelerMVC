<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Listeners;

use App\Contracts\Async\ListenerInterface;
use App\Modules\CartModule\Notifications\CartMergedNotification;
use App\Modules\CartModule\Services\CartService;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Support\NotificationManager;

class MergeCartOnLoginListener implements ListenerInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly NotificationManager $notifications,
        private readonly UserRepository $users
    ) {
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
            $result = $this->cartService->mergeGuestCartToUser($userId);

            if (!($result['merged'] ?? false)) {
                return null;
            }

            $user = $this->users->find($userId);

            if ($user !== null) {
                $this->notifications->sendNow($user, new CartMergedNotification([
                    'guest_cart_id' => $result['guest_cart_id'] ?? null,
                    'user_cart_id' => $result['user_cart_id'] ?? null,
                    'merged_items' => $result['merged_items'] ?? 0,
                    'message' => sprintf(
                        'Your guest cart was merged into your account cart with %d item(s).',
                        (int) ($result['merged_items'] ?? 0)
                    ),
                ]));
            }
        }

        return null;
    }
}
