<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Notifications;

use App\Abstracts\Support\Notification;

class CatalogActivityNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'event' => $this->payload['event'] ?? '',
            'entity' => $this->payload['entity'] ?? '',
            'action' => $this->payload['action'] ?? '',
            'entity_id' => $this->payload['entity_id'] ?? null,
            'name' => $this->payload['name'] ?? '',
            'slug' => $this->payload['slug'] ?? '',
            'state' => $this->payload['state'] ?? '',
            'message' => $this->payload['message'] ?? 'Catalog changes were stored successfully.',
        ];
    }
}
