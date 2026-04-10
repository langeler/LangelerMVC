<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\CacheManager;

class CacheClearCommand extends Command
{
    public function __construct(private readonly CacheManager $cache)
    {
    }

    public function name(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Clear the configured framework cache store.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $cleared = $this->cache->clear();
        $this->info($cleared ? 'Cache cleared.' : 'Cache clear reported no changes.');

        return $cleared ? 0 : 1;
    }
}
