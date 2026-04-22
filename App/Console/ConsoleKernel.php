<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\AuditListCommand;
use App\Console\Commands\AuditPruneCommand;
use App\Console\Commands\CacheClearCommand;
use App\Console\Commands\ConfigShowCommand;
use App\Console\Commands\EventListCommand;
use App\Console\Commands\FrameworkDoctorCommand;
use App\Console\Commands\HealthCheckCommand;
use App\Console\Commands\MigrateCommand;
use App\Console\Commands\MigrateRollbackCommand;
use App\Console\Commands\MigrateStatusCommand;
use App\Console\Commands\ModuleMakeCommand;
use App\Console\Commands\ModuleListCommand;
use App\Console\Commands\NotificationListCommand;
use App\Console\Commands\QueueFailedCommand;
use App\Console\Commands\QueueDrainCommand;
use App\Console\Commands\QueuePruneFailedCommand;
use App\Console\Commands\QueueRetryCommand;
use App\Console\Commands\QueueStopCommand;
use App\Console\Commands\QueueWorkCommand;
use App\Console\Commands\RouteListCommand;
use App\Console\Commands\SeedCommand;
use App\Contracts\Console\CommandInterface;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;

class ConsoleKernel
{
    use ArrayTrait, CheckerTrait, ManipulationTrait;

    /**
     * @var array<string, CommandInterface>
     */
    private array $commands = [];

    public function __construct(
        AuditListCommand $auditList,
        AuditPruneCommand $auditPrune,
        MigrateCommand $migrate,
        MigrateStatusCommand $migrateStatus,
        MigrateRollbackCommand $migrateRollback,
        SeedCommand $seed,
        RouteListCommand $routeList,
        CacheClearCommand $cacheClear,
        ConfigShowCommand $configShow,
        FrameworkDoctorCommand $frameworkDoctor,
        ModuleListCommand $moduleList,
        ModuleMakeCommand $moduleMake,
        HealthCheckCommand $healthCheck,
        QueueWorkCommand $queueWork,
        QueueFailedCommand $queueFailed,
        QueueStopCommand $queueStop,
        QueueDrainCommand $queueDrain,
        QueuePruneFailedCommand $queuePruneFailed,
        QueueRetryCommand $queueRetry,
        EventListCommand $eventList,
        NotificationListCommand $notificationList
    ) {
        foreach ([
            $auditList,
            $auditPrune,
            $migrate,
            $migrateStatus,
            $migrateRollback,
            $seed,
            $routeList,
            $cacheClear,
            $configShow,
            $frameworkDoctor,
            $moduleList,
            $moduleMake,
            $healthCheck,
            $queueWork,
            $queueFailed,
            $queueStop,
            $queueDrain,
            $queuePruneFailed,
            $queueRetry,
            $eventList,
            $notificationList,
        ] as $command) {
            $this->commands[$command->name()] = $command;
        }
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $input = $argv;
        array_shift($input);
        $command = $input[0] ?? 'help';

        if ($command === 'help' || $command === 'list') {
            $this->renderHelp();
            return 0;
        }

        $arguments = array_slice($input, 1);
        [$positionals, $options] = $this->parseArguments($arguments);

        if (!isset($this->commands[$command])) {
            fwrite(STDERR, sprintf("Unknown command [%s]. Run `console list` to inspect commands.\n", $command));
            return 1;
        }

        return $this->commands[$command]->handle($positionals, $options);
    }

    /**
     * @return array<string, string>
     */
    public function commandDescriptions(): array
    {
        $descriptions = [];

        foreach ($this->commands as $name => $command) {
            $descriptions[$name] = $command->description();
        }

        ksort($descriptions);

        return $descriptions;
    }

    private function renderHelp(): void
    {
        fwrite(STDOUT, "LangelerMVC Console\n\n");

        foreach ($this->commandDescriptions() as $name => $description) {
            fwrite(STDOUT, sprintf("  %-18s %s\n", $name, $description));
        }

        fwrite(STDOUT, PHP_EOL);
    }

    /**
     * @param array<int, string> $arguments
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function parseArguments(array $arguments): array
    {
        $positionals = [];
        $options = [];

        foreach ($arguments as $argument) {
            if ($this->startsWith($argument, '--')) {
                $trimmed = substr($argument, 2);
                $parts = explode('=', $trimmed, 2);
                $options[$parts[0]] = $parts[1] ?? true;
                continue;
            }

            $positionals[] = $argument;
        }

        return [$positionals, $options];
    }
}
