<?php

declare(strict_types=1);

namespace App\Contracts\Async;

interface ListenerInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(string $event, array $payload = []): mixed;
}
