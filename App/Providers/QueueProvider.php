<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Async\QueueDriverInterface;
use App\Core\Container;
use App\Drivers\Queue\DatabaseQueueDriver;
use App\Drivers\Queue\SyncQueueDriver;
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

class QueueProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, string>
     */
    private array $driverMap;

    private bool $servicesRegistered = false;

    public function __construct()
    {
        parent::__construct();

        $this->driverMap = [
            'sync' => SyncQueueDriver::class,
            'database' => DatabaseQueueDriver::class,
        ];
    }

    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        foreach ($this->driverMap as $alias => $class) {
            $this->registerAlias($alias, $class);
            $this->registerLazy($class, fn() => $this->registerInstance($class));
        }

        $this->servicesRegistered = true;
    }

    public function getQueueDriver(array $settings): QueueDriverInterface
    {
        $driver = $this->normalizeDriverAlias((string) ($settings['DRIVER'] ?? 'sync'));
        $class = $this->driverMap[$driver] ?? throw new ContainerException(sprintf('Unsupported queue driver [%s].', $driver));
        $instance = $this->getInstance($class);

        if (!$instance instanceof QueueDriverInterface) {
            throw new ContainerException(sprintf('Resolved queue driver [%s] does not implement the queue contract.', $driver));
        }

        return $instance;
    }

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return array_keys($this->driverMap);
    }

    public function extendDriver(string $alias, string $class): void
    {
        $this->driverMap[$this->normalizeDriverAlias($alias)] = $class;
    }

    private function normalizeDriverAlias(string $driver): string
    {
        return $this->toLower(
            $this->trimString((string) ($this->replaceByPattern('/\s+#.*$/', '', $driver) ?? $driver))
        );
    }
}
