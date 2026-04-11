<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\EventDispatcher;

class EventListCommand extends Command
{
    public function __construct(private readonly EventDispatcher $events)
    {
    }

    public function name(): string
    {
        return 'event:list';
    }

    public function description(): string
    {
        return 'List registered framework events and listeners.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $listeners = $this->events->listeners();

        if ($listeners === []) {
            $this->info('No event listeners are registered.');
            return 0;
        }

        foreach ($listeners as $event => $definitions) {
            $this->line($event);

            foreach ($definitions as $definition) {
                $listener = $definition['listener'] ?? null;
                $description = is_string($listener)
                    ? $listener
                    : (is_array($listener) && count($listener) === 2
                        ? sprintf('%s@%s', is_object($listener[0]) ? $listener[0]::class : (string) $listener[0], (string) $listener[1])
                        : 'callable');

                $suffix = (bool) ($definition['queued'] ?? false)
                    ? ' [queued:' . (string) ($definition['queue'] ?? 'default') . ']'
                    : '';

                $this->line('  - ' . $description . $suffix);
            }
        }

        return 0;
    }
}
