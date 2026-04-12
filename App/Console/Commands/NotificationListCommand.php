<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Support\NotificationManager;

class NotificationListCommand extends Command
{
    public function __construct(private readonly NotificationManager $notifications)
    {
    }

    public function name(): string
    {
        return 'notification:list';
    }

    public function description(): string
    {
        return 'List stored database notifications.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $notifiableId = isset($options['id']) ? (int) $options['id'] : null;
        $notifiableType = isset($options['type']) ? (string) $options['type'] : null;
        $notifications = $this->notifications->databaseNotifications($notifiableId, $notifiableType);

        if ($notifications === []) {
            $this->info('No database notifications stored.');
            return 0;
        }

        foreach ($notifications as $notification) {
            $this->line(sprintf(
                '#%s %-32s %-18s %s',
                (string) ($notification['id'] ?? ''),
                (string) ($notification['notification'] ?? ''),
                (string) ($notification['notifiable_type'] ?? ''),
                $this->encodeJsonPayload($notification['data'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
        }

        return 0;
    }
}
