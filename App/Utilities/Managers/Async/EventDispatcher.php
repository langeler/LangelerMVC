<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Async;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Async\ListenerInterface;
use App\Core\Config;
use App\Exceptions\AppException;
use App\Providers\CoreProvider;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class EventDispatcher implements EventDispatcherInterface
{
    use ArrayTrait, CheckerTrait, TypeCheckerTrait;

    /**
     * @var array<string, list<array<string, mixed>>>
     */
    private array $listeners = [];

    public function __construct(
        private readonly QueueManager $queue,
        private readonly ModuleManager $moduleManager,
        private readonly CoreProvider $coreProvider,
        private readonly Config $config
    ) {
        $this->registerDiscoveredListeners();
    }

    public function listen(string $event, callable|string|array $listener, bool $queued = false, string $queue = 'default'): void
    {
        $this->listeners[$event] ??= [];
        $this->listeners[$event][] = [
            'listener' => $listener,
            'queued' => $queued,
            'queue' => $queue,
        ];
    }

    public function subscribe(array $listeners): void
    {
        foreach ($listeners as $event => $definitions) {
            $definitions = is_array($definitions) ? $definitions : [$definitions];

            foreach ($definitions as $definition) {
                $queued = false;
                $queue = 'default';
                $listener = $definition;

                if (is_array($definition) && array_key_exists('listener', $definition)) {
                    $listener = $definition['listener'];
                    $queued = (bool) ($definition['queued'] ?? false);
                    $queue = (string) ($definition['queue'] ?? 'default');
                }

                $this->listen((string) $event, $listener, $queued, $queue);
            }
        }
    }

    public function dispatch(string|object $event, array $payload = []): array
    {
        $eventName = is_object($event) ? $event::class : $event;
        $results = [];

        foreach ($this->listeners[$eventName] ?? [] as $definition) {
            $listener = $definition['listener'] ?? null;
            $queued = (bool) ($definition['queued'] ?? false);
            $queue = (string) ($definition['queue'] ?? $this->defaultQueue());

            if ($queued) {
                $this->queue->dispatchListener($listener, $eventName, $payload, $queue);
                $results[] = ['queued' => true, 'listener' => $this->describeListener($listener)];
                continue;
            }

            $results[] = $this->invokeListener($listener, $eventName, $payload);
        }

        return $results;
    }

    public function listeners(?string $event = null): array
    {
        if ($event !== null) {
            return [$event => $this->listeners[$event] ?? []];
        }

        return $this->listeners;
    }

    private function invokeListener(mixed $listener, string $event, array $payload): mixed
    {
        if (is_string($listener)) {
            $instance = $this->resolveClass($listener);

            if ($instance instanceof ListenerInterface || method_exists($instance, 'handle')) {
                return $instance->handle($event, $payload);
            }

            if (is_callable($instance)) {
                return $instance($event, $payload);
            }
        }

        if (is_array($listener) && count($listener) === 2) {
            [$target, $method] = $listener;
            $instance = is_object($target) ? $target : $this->resolveClass((string) $target);

            if (!method_exists($instance, (string) $method)) {
                throw new AppException(sprintf('Listener method [%s] is not available.', (string) $method));
            }

            return $instance->{(string) $method}($event, $payload);
        }

        if (is_callable($listener)) {
            return $listener($event, $payload);
        }

        throw new AppException('Invalid event listener definition.');
    }

    private function resolveClass(string $class): object
    {
        if ($class !== '' && str_starts_with($class, 'App\\Modules\\')) {
            return $this->moduleManager->resolveModule($class);
        }

        return $this->coreProvider->resolveClass($class);
    }

    private function defaultQueue(): string
    {
        return (string) $this->config->get('queue', 'DEFAULT_QUEUE', 'default');
    }

    private function describeListener(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && count($listener) === 2) {
            return sprintf('%s@%s', is_object($listener[0]) ? $listener[0]::class : (string) $listener[0], (string) $listener[1]);
        }

        return 'callable';
    }

    private function registerDiscoveredListeners(): void
    {
        foreach ($this->moduleManager->collectClasses('Listeners') as $class) {
            $listenerClass = (string) ($class['class'] ?? '');

            if ($listenerClass === '' || !class_exists($listenerClass) || !method_exists($listenerClass, 'subscriptions')) {
                continue;
            }

            $subscriptions = $listenerClass::subscriptions();

            if (!$this->isArray($subscriptions)) {
                continue;
            }

            foreach ($subscriptions as $event => $definitions) {
                $definitions = $this->isArray($definitions) && $this->keyExists($definitions, 'method')
                    ? [$definitions]
                    : (is_array($definitions) ? $definitions : []);

                foreach ($definitions as $definition) {
                    if (!$this->isArray($definition)) {
                        continue;
                    }

                    $method = (string) ($definition['method'] ?? 'handle');
                    $queued = (bool) ($definition['queued'] ?? false);
                    $queue = (string) ($definition['queue'] ?? $this->defaultQueue());
                    $this->listen((string) $event, [$listenerClass, $method], $queued, $queue);
                }
            }
        }
    }
}
