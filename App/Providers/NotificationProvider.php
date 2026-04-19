<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Support\NotificationChannelInterface;
use App\Core\Container;
use App\Drivers\Notifications\DatabaseNotificationChannel;
use App\Drivers\Notifications\MailNotificationChannel;
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

class NotificationProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, string>
     */
    private array $channelMap;

    private bool $servicesRegistered = false;

    public function __construct()
    {
        parent::__construct();

        $this->channelMap = [
            'mail' => MailNotificationChannel::class,
            'database' => DatabaseNotificationChannel::class,
        ];
    }

    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        foreach ($this->channelMap as $alias => $class) {
            $this->registerAlias($alias, $class);
            $this->registerLazy($class, fn() => $this->registerInstance($class));
        }

        $this->servicesRegistered = true;
    }

    public function getChannel(string $name): NotificationChannelInterface
    {
        $normalized = $this->normalizeAlias($name);
        $class = $this->channelMap[$normalized] ?? throw new ContainerException(sprintf('Unsupported notification channel [%s].', $normalized));
        $instance = $this->getInstance($class);

        if (!$instance instanceof NotificationChannelInterface) {
            throw new ContainerException(sprintf('Resolved notification channel [%s] does not implement the channel contract.', $normalized));
        }

        return $instance;
    }

    /**
     * @return list<string>
     */
    public function getSupportedChannels(): array
    {
        return array_keys($this->channelMap);
    }

    public function extendChannel(string $alias, string $class): void
    {
        $this->channelMap[$this->normalizeAlias($alias)] = $class;
    }

    private function normalizeAlias(string $name): string
    {
        return $this->toLower(
            $this->trimString((string) ($this->replaceByPattern('/\s+#.*$/', '', $name) ?? $name))
        );
    }
}
