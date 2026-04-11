<?php

declare(strict_types=1);

namespace App\Contracts\Async;

interface EventDispatcherInterface
{
    public function listen(string $event, callable|string|array $listener, bool $queued = false, string $queue = 'default'): void;

    /**
     * @param array<string, array<int, callable|string|array>|callable|string|array> $listeners
     */
    public function subscribe(array $listeners): void;

    /**
     * @param string|object $event
     * @param array<string, mixed> $payload
     * @return list<mixed>
     */
    public function dispatch(string|object $event, array $payload = []): array;

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function listeners(?string $event = null): array;
}
