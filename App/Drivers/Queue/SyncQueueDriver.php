<?php

declare(strict_types=1);

namespace App\Drivers\Queue;

use App\Contracts\Async\QueueDriverInterface;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;

class SyncQueueDriver implements QueueDriverInterface
{
    use ArrayTrait, CheckerTrait, ManipulationTrait;

    /**
     * @var list<array<string, mixed>>
     */
    private array $queue = [];

    public function driverName(): string
    {
        return 'sync';
    }

    public function capabilities(): array
    {
        return [
            'immediate' => true,
            'persistent' => false,
            'delay' => true,
            'inspect' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        $value = $this->capabilities();

        foreach (explode('.', trim($feature)) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    public function push(array $envelope, string $queue = 'default', int $delay = 0): string
    {
        $id = (string) ($envelope['id'] ?? bin2hex(random_bytes(16)));
        $this->queue[] = [
            ...$envelope,
            'id' => $id,
            'queue' => $queue,
            'attempts' => (int) ($envelope['attempts'] ?? 0),
            'available_at' => time() + max(0, $delay),
            'created_at' => time(),
            'reserved_at' => null,
        ];

        return $id;
    }

    public function pop(string $queue = 'default'): ?array
    {
        foreach ($this->queue as $index => $envelope) {
            if (($envelope['queue'] ?? 'default') !== $queue) {
                continue;
            }

            if ((int) ($envelope['available_at'] ?? 0) > time()) {
                continue;
            }

            $selected = $envelope;
            $selected['attempts'] = ((int) ($selected['attempts'] ?? 0)) + 1;
            $selected['reserved_at'] = time();
            unset($this->queue[$index]);

            return $selected;
        }

        return null;
    }

    public function delete(string $id): bool
    {
        return true;
    }

    public function release(array $envelope, int $delay = 0): bool
    {
        $this->queue[] = [
            ...$envelope,
            'id' => (string) ($envelope['id'] ?? bin2hex(random_bytes(16))),
            'queue' => (string) ($envelope['queue'] ?? 'default'),
            'attempts' => (int) ($envelope['attempts'] ?? 0),
            'available_at' => time() + max(0, $delay),
            'reserved_at' => null,
            'created_at' => (int) ($envelope['created_at'] ?? time()),
        ];

        return true;
    }

    public function pending(string $queue = 'default'): array
    {
        return array_values(array_filter(
            $this->queue,
            static fn(array $envelope): bool => (string) ($envelope['queue'] ?? 'default') === $queue
        ));
    }
}
