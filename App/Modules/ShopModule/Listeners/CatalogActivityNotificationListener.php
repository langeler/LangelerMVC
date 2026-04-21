<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Listeners;

use App\Contracts\Async\ListenerInterface;
use App\Modules\ShopModule\Notifications\CatalogActivityNotification;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Support\NotificationManager;

class CatalogActivityNotificationListener implements ListenerInterface
{
    public function __construct(
        private readonly NotificationManager $notifications,
        private readonly UserRepository $users
    ) {
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function subscriptions(): array
    {
        return [
            'shop.category.saved' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
            'shop.product.saved' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
        ];
    }

    public function handle(string $event, array $payload = []): mixed
    {
        $actorId = (int) ($payload['actor_id'] ?? 0);

        if ($actorId <= 0) {
            return null;
        }

        $actor = $this->users->find($actorId);

        if ($actor === null) {
            return null;
        }

        $notification = new CatalogActivityNotification([
            'event' => $event,
            'entity' => $payload['entity'] ?? '',
            'action' => $payload['action'] ?? 'saved',
            'entity_id' => $payload['entity_id'] ?? null,
            'name' => $payload['name'] ?? '',
            'slug' => $payload['slug'] ?? '',
            'state' => $payload['state'] ?? '',
            'message' => $payload['message'] ?? 'Catalog changes were stored successfully.',
        ]);

        $this->notifications->queue($actor, $notification, 'notifications');

        return null;
    }
}
