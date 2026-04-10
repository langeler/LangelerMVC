<?php

declare(strict_types=1);

namespace App\Core;

use App\Abstracts\Database\Seed;
use App\Abstracts\Database\Repository;
use App\Exceptions\Database\SeedException;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use ReflectionClass;
use ReflectionNamedType;

class SeedRunner
{
    use ArrayTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    public function __construct(
        private readonly Database $database,
        private readonly ModuleManager $moduleManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    /**
     * @return array<int, array{name:string,module:string,class:string}>
     */
    public function discoverSeeds(?string $module = null): array
    {
        $classes = $module !== null
            ? $this->moduleManager->getClasses($module, 'Seeds')
            : $this->moduleManager->collectClasses('Seeds');

        $seeds = [];

        foreach ($classes as $class) {
            if (!$this->isArray($class) || !$this->isString($class['class'] ?? null)) {
                continue;
            }

            if (!is_subclass_of($class['class'], Seed::class)) {
                continue;
            }

            $seeds[] = [
                'name' => (string) ($class['shortName'] ?? $class['class']),
                'module' => $this->resolveModuleName((string) $class['class']),
                'class' => (string) $class['class'],
                'file' => (string) ($class['file'] ?? ''),
            ];
        }

        usort(
            $seeds,
            static fn(array $left, array $right): int => strcmp($left['file'], $right['file'])
        );

        return array_map(
            static fn(array $seed): array => [
                'name' => $seed['name'],
                'module' => $seed['module'],
                'class' => $seed['class'],
            ],
            $seeds
        );
    }

    /**
     * @return list<string>
     */
    public function run(?string $module = null, ?string $seed = null): array
    {
        $selected = array_values(array_filter(
            $this->discoverSeeds($module),
            function (array $candidate) use ($seed): bool {
                if ($seed === null || $seed === '') {
                    return true;
                }

                return $this->toLowerString($candidate['name']) === $this->toLowerString($seed)
                    || $this->toLowerString($candidate['class']) === $this->toLowerString($seed);
            }
        ));

        $executed = [];

        foreach ($selected as $candidate) {
            $instance = $this->resolveSeed($candidate['class']);

            if (!$instance instanceof Seed) {
                throw new SeedException(sprintf('Resolved seed [%s] is invalid.', $candidate['class']));
            }

            $instance->run();
            $executed[] = $candidate['name'];
        }

        return $executed;
    }

    private function resolveSeed(string $class): Seed
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw new SeedException(sprintf('Seed [%s] must define a constructor with repository and database dependencies.', $class));
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new SeedException(sprintf('Seed [%s] has an unsupported constructor signature.', $class));
            }

            $typeName = $type->getName();

            if ($typeName === Database::class) {
                $arguments[] = $this->database;
                continue;
            }

            if (is_subclass_of($typeName, Repository::class)) {
                $arguments[] = new $typeName($this->database);
                continue;
            }

            throw new SeedException(sprintf('Seed [%s] constructor parameter [%s] cannot be resolved by the seed runner.', $class, $parameter->getName()));
        }

        $instance = $reflection->newInstanceArgs($arguments);

        if (!$instance instanceof Seed) {
            throw new SeedException(sprintf('Resolved seed [%s] is invalid.', $class));
        }

        return $instance;
    }

    private function resolveModuleName(string $class): string
    {
        return $this->match('/App\\\\Modules\\\\([^\\\\]+)/', $class, $matches) === 1
            ? (string) $matches[1]
            : 'Framework';
    }
}
