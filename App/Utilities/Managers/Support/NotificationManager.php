<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\NotifiableInterface;
use App\Contracts\Support\NotificationChannelInterface;
use App\Contracts\Support\NotificationInterface;
use App\Contracts\Support\NotificationManagerInterface;
use App\Core\Config;
use App\Core\Database;
use App\Exceptions\AppException;
use App\Providers\CoreProvider;
use App\Providers\NotificationProvider;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class NotificationManager implements NotificationManagerInterface
{
    use ArrayTrait, CheckerTrait, ConversionTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    public function __construct(
        private readonly Config $config,
        private readonly NotificationProvider $provider,
        private readonly Database $database,
        private readonly CoreProvider $coreProvider
    ) {
        $this->provider->registerServices();
    }

    public function send(mixed $notifiable, NotificationInterface $notification): array
    {
        if ((bool) $this->config->get('notifications', 'QUEUE', false)) {
            return [
                'queued' => true,
                'id' => $this->queue($notifiable, $notification),
            ];
        }

        return $this->sendNow($notifiable, $notification);
    }

    /**
     * @return list<string>
     */
    public function availableChannels(): array
    {
        return $this->provider->getSupportedChannels();
    }

    public function sendNow(mixed $notifiable, NotificationInterface $notification): array
    {
        $snapshot = $this->normalizeNotifiable($notifiable);
        $channels = $notification->via($notifiable);
        $results = [];

        foreach ($channels as $channelName) {
            $channel = $this->provider->getChannel((string) $channelName);
            $results[] = $channel->send($snapshot, $notification);
        }

        return [
            'queued' => false,
            'channels' => array_values(array_map('strval', $channels)),
            'results' => $results,
        ];
    }

    public function queue(mixed $notifiable, NotificationInterface $notification, ?string $queue = null): string
    {
        return $this->queueManager()->dispatchNotification(
            $this->normalizeNotifiable($notifiable),
            $notification::class,
            $notification->payload(),
            $notification->via($notifiable),
            $queue ?? (string) $this->config->get('queue', 'DEFAULT_QUEUE', 'default')
        );
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param list<string> $channels
     * @return array<string, mixed>
     */
    public function deliverSnapshot(array $snapshot, string $notificationClass, array $payload, array $channels): array
    {
        $notification = $this->resolveNotification($notificationClass)->withPayload($payload);
        $results = [];

        foreach ($channels as $channelName) {
            $results[] = $this->provider->getChannel($channelName)->send($snapshot, $notification);
        }

        return [
            'queued' => false,
            'channels' => $channels,
            'results' => $results,
        ];
    }

    public function databaseNotifications(?int $notifiableId = null, ?string $notifiableType = null): array
    {
        $criteria = [];

        if ($notifiableId !== null) {
            $criteria['notifiable_id'] = (string) $notifiableId;
        }

        if ($notifiableType !== null && $notifiableType !== '') {
            $criteria['notifiable_type'] = $notifiableType;
        }

        $query = $this->database
            ->dataQuery('framework_notifications')
            ->select(['*']);

        foreach ($criteria as $column => $value) {
            $query->where($column, '=', $value);
        }

        $executable = $query->orderBy('id')->toExecutable();

        return array_map(function (array $row): array {
            $row['data'] = $this->decodeJsonArray((string) ($row['data'] ?? '{}'));

            return $row;
        }, $this->database->fetchAll($executable['sql'], $executable['bindings']));
    }

    public function markAsRead(int $id): bool
    {
        $query = $this->database
            ->dataQuery('framework_notifications')
            ->update('framework_notifications', ['read_at' => time()])
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeNotifiable(mixed $notifiable): array
    {
        if ($notifiable instanceof NotifiableInterface) {
            return [
                'type' => $notifiable->notificationType(),
                'id' => $notifiable->notificationIdentifier(),
                'email' => $notifiable->routeNotificationFor('mail'),
                'routes' => [
                    'mail' => $notifiable->routeNotificationFor('mail'),
                    'database' => $notifiable->notificationIdentifier(),
                ],
            ];
        }

        if (is_array($notifiable)) {
            return [
                'type' => (string) ($notifiable['type'] ?? 'ArrayNotifiable'),
                'id' => $notifiable['id'] ?? null,
                'name' => isset($notifiable['name']) ? (string) $notifiable['name'] : null,
                'email' => isset($notifiable['email']) ? (string) $notifiable['email'] : null,
                'routes' => is_array($notifiable['routes'] ?? null) ? $notifiable['routes'] : [],
            ];
        }

        if (is_string($notifiable)) {
            return [
                'type' => 'StringNotifiable',
                'id' => null,
                'name' => null,
                'email' => $notifiable,
                'routes' => ['mail' => $notifiable],
            ];
        }

        if (is_object($notifiable)) {
            return [
                'type' => $notifiable::class,
                'id' => $notifiable->id ?? null,
                'name' => $notifiable->name ?? null,
                'email' => $notifiable->email ?? null,
                'routes' => [
                    'mail' => $notifiable->email ?? null,
                    'database' => $notifiable->id ?? null,
                ],
            ];
        }

        throw new AppException('The provided notification recipient is not supported.');
    }

    private function resolveNotification(string $class): NotificationInterface
    {
        $instance = str_starts_with($class, 'App\\Modules\\')
            ? new $class()
            : $this->coreProvider->resolveClass($class);

        if (!$instance instanceof NotificationInterface) {
            throw new AppException(sprintf('Notification [%s] does not implement the notification contract.', $class));
        }

        return $instance;
    }

    private function queueManager(): QueueManager
    {
        $instance = $this->coreProvider->resolveClass(QueueManager::class);

        if (!$instance instanceof QueueManager) {
            throw new AppException('The queue manager could not be resolved for queued notifications.');
        }

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonArray(string $payload): array
    {
        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);

            return $this->isArray($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
