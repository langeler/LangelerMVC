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
use ReflectionParameter;

class SeedRunner
{
    use ArrayTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var array<class-string, object>
     */
    private array $resolvedDependencies = [];

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
        $seeds = $this->sortDiscoveredSeeds($seeds);

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

            $arguments[] = $this->resolveDependency($typeName, $class, $parameter, [$class]);
        }

        $instance = $reflection->newInstanceArgs($arguments);

        if (!$instance instanceof Seed) {
            throw new SeedException(sprintf('Resolved seed [%s] is invalid.', $class));
        }

        return $instance;
    }

    /**
     * @param list<string> $stack
     */
    private function resolveDependency(string $typeName, string $seedClass, ReflectionParameter $parameter, array $stack = []): object
    {
        if ($typeName === Database::class) {
            return $this->database;
        }

        if ($typeName === ModuleManager::class) {
            return $this->moduleManager;
        }

        if ($typeName === ErrorManager::class) {
            return $this->errorManager;
        }

        if (isset($this->resolvedDependencies[$typeName])) {
            return $this->resolvedDependencies[$typeName];
        }

        if (is_subclass_of($typeName, Repository::class)) {
            return $this->resolvedDependencies[$typeName] = new $typeName($this->database);
        }

        if (!class_exists($typeName)) {
            throw new SeedException(sprintf(
                'Seed [%s] constructor parameter [%s] references an unknown dependency [%s].',
                $seedClass,
                $parameter->getName(),
                $typeName
            ));
        }

        return $this->resolvedDependencies[$typeName] = $this->instantiateDependency(
            $typeName,
            $seedClass,
            $stack === [] ? [$seedClass] : $stack
        );
    }

    /**
     * @param list<string> $stack
     */
    private function instantiateDependency(string $class, string $seedClass, array $stack): object
    {
        if (isset($this->resolvedDependencies[$class])) {
            return $this->resolvedDependencies[$class];
        }

        if (in_array($class, $stack, true)) {
            throw new SeedException(sprintf(
                'Circular seed dependency resolution detected while building [%s] for seed [%s].',
                $class,
                $seedClass
            ));
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new SeedException(sprintf(
                'Seed [%s] dependency [%s] is not instantiable.',
                $seedClass,
                $class
            ));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        $stack[] = $class;

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType) {
                if ($parameter->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }

                throw new SeedException(sprintf(
                    'Seed [%s] dependency [%s] has an unsupported constructor parameter [%s].',
                    $seedClass,
                    $class,
                    $parameter->getName()
                ));
            }

            if ($type->isBuiltin()) {
                if ($parameter->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }

                throw new SeedException(sprintf(
                    'Seed [%s] dependency [%s] has an unresolved builtin constructor parameter [%s].',
                    $seedClass,
                    $class,
                    $parameter->getName()
                ));
            }

            $dependencyType = $type->getName();

            if ($dependencyType === $class) {
                throw new SeedException(sprintf(
                    'Seed [%s] dependency [%s] cannot recursively resolve itself.',
                    $seedClass,
                    $class
                ));
            }

            $arguments[] = $this->resolveDependency($dependencyType, $seedClass, $parameter, $stack);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @param array<int, array{name:string,module:string,class:string,file:string}> $seeds
     * @return array<int, array{name:string,module:string,class:string,file:string}>
     */
    private function sortDiscoveredSeeds(array $seeds): array
    {
        $byClass = [];
        $byName = [];

        foreach ($seeds as $seed) {
            $byClass[$seed['class']] = $seed;
            $byName[$seed['name']] = $seed['class'];
        }

        $ordered = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $class) use (&$visit, &$ordered, &$visiting, &$visited, $byClass, $byName): void {
            if (isset($visited[$class])) {
                return;
            }

            if (isset($visiting[$class])) {
                throw new SeedException(sprintf('Circular seed dependency detected for [%s].', $class));
            }

            $candidate = $byClass[$class] ?? null;

            if ($candidate === null) {
                return;
            }

            $visiting[$class] = true;
            $dependencies = is_callable([$class, 'dependencies']) ? $class::dependencies() : [];

            foreach ($dependencies as $dependency) {
                $dependencyClass = $byClass[(string) $dependency]['class']
                    ?? $byName[(string) $dependency]
                    ?? (is_string($dependency) ? $dependency : '');

                if ($dependencyClass !== '' && isset($byClass[$dependencyClass])) {
                    $visit($dependencyClass);
                }
            }

            unset($visiting[$class]);
            $visited[$class] = true;
            $ordered[] = $candidate;
        };

        foreach ($seeds as $seed) {
            $visit($seed['class']);
        }

        return $ordered;
    }

    private function resolveModuleName(string $class): string
    {
        return $this->match('/App\\\\Modules\\\\([^\\\\]+)/', $class, $matches) === 1
            ? (string) $matches[1]
            : 'Framework';
    }
}
