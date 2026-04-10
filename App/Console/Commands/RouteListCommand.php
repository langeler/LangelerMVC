<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\Router;

class RouteListCommand extends Command
{
    public function __construct(private readonly Router $router)
    {
    }

    public function name(): string
    {
        return 'route:list';
    }

    public function description(): string
    {
        return 'List registered framework routes.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        foreach ($this->router->listRoutes() as $route) {
            $this->line(sprintf(
                '%-8s %-30s %-36s %s',
                $route['method'],
                $route['path'],
                $route['name'] ?? '-',
                $route['action']
            ));
        }

        return 0;
    }
}
