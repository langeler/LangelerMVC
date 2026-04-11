<?php

namespace App\Providers;

use App\Core\Container;
use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Async\FailedJobStoreInterface;
use App\Contracts\Auth\GuardInterface;
use App\Contracts\Auth\PasswordBrokerInterface;
use App\Contracts\Auth\UserProviderInterface;
use App\Contracts\Support\NotificationManagerInterface;
use App\Contracts\Support\PaymentManagerInterface;
use App\Exceptions\ContainerException;
use App\Utilities\Managers\Async\DatabaseFailedJobStore;
use App\Utilities\Managers\Async\EventDispatcher;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Managers\Security\PasswordBroker;
use App\Utilities\Managers\Security\SessionGuard;
use App\Utilities\Managers\Support\NotificationManager;
use App\Utilities\Managers\Support\PaymentManager;
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * ModuleProvider Class
 *
 * Registers module classes with aliases derived from the resolved class metadata.
 */
class ModuleProvider extends Container
{
	use PatternTrait {
		PatternTrait::match as private matchPattern;
	}

	/**
	 * Map of aliases => fully qualified module class names.
	 *
	 * @var array<string, string>
	 */
	protected array $moduleMap = [];

	/**
	 * Populates the module map dynamically.
	 *
	 * @param array $classes
	 * @return void
	 */
	public function populate(array $classes): void
	{
		$this->wrapInTry(function () use ($classes): void {
			$aliases = [];
			$shortNameCounts = [];

			foreach ($classes as $class) {
				if (!$this->isArray($class) || !isset($class['class'], $class['shortName'])) {
					continue;
				}

				$shortName = $class['shortName'];
				$shortNameCounts[$shortName] = ($shortNameCounts[$shortName] ?? 0) + 1;
			}

			foreach ($classes as $class) {
				if (!$this->isArray($class) || !isset($class['class'], $class['shortName'])) {
					continue;
				}

				$fqcn = $class['class'];
				$shortName = $class['shortName'];
				$moduleAlias = $this->buildModuleAlias($fqcn, $shortName);

				$aliases[$fqcn] = $fqcn;
				$aliases[$moduleAlias] = $fqcn;

				if (($shortNameCounts[$shortName] ?? 0) === 1) {
					$aliases[$shortName] = $fqcn;
				}
			}

			$this->moduleMap = $aliases;
		}, new ContainerException('Failed to populate module map.'));
	}

	/**
	 * Registers module-related services in the container.
	 *
	 * @return void
	 */
	public function registerServices(): void
	{
		$this->wrapInTry(function (): void {
			$this->registerAlias(GuardInterface::class, SessionGuard::class);
			$this->registerAlias(UserProviderInterface::class, DatabaseUserProvider::class);
			$this->registerAlias(PasswordBrokerInterface::class, PasswordBroker::class);
			$this->registerAlias(EventDispatcherInterface::class, EventDispatcher::class);
			$this->registerAlias(NotificationManagerInterface::class, NotificationManager::class);
			$this->registerAlias(PaymentManagerInterface::class, PaymentManager::class);
			$this->registerAlias(FailedJobStoreInterface::class, DatabaseFailedJobStore::class);

			foreach ($this->moduleMap as $alias => $class) {
				$this->registerAlias($alias, $class);
			}
		}, new ContainerException('Error registering module services.'));
	}

	/**
	 * Resolves and returns a module class instance by alias or FQCN.
	 *
	 * @param string $alias
	 * @return object
	 */
	public function getModule(string $alias): object
	{
		return $this->wrapInTry(function () use ($alias): object {
			$class = $this->moduleMap[$alias] ?? $alias;

			return $this->getInstance($class);
		}, new ContainerException("Module [$alias] could not be resolved."));
	}

	/**
	 * Builds a stable module-qualified alias from a module class name.
	 *
	 * @param string $fqcn
	 * @param string $shortName
	 * @return string
	 */
	private function buildModuleAlias(string $fqcn, string $shortName): string
	{
		if ($this->matchPattern('/App\\\\Modules\\\\([^\\\\]+)/', $fqcn, $matches) === 1) {
			return $matches[1] . '.' . $shortName;
		}

		return $shortName;
	}
}
