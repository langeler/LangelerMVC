<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use RuntimeException;
use Throwable;

/**
 * Abstract Service Class
 *
 * Responsibilities:
 * - Encapsulate business workflows that don't neatly fit into controllers or repositories alone.
 * - Orchestrate calls to repositories, domain services, caching layers, and other infrastructure components.
 * - Apply business logic and return structured data ready for presentation.
 *
 * Boundaries:
 * - Does NOT handle direct HTTP input (Requests) or output (Responses).
 * - Does NOT render views or deal with presentation logic (that’s for Presenter/View).
 * - Does NOT concern itself with routing or middleware tasks.
 * - Delegates low-level data access to Repositories, not executing queries directly.
 *
 * Usage:
 * Concrete service classes extending this abstract class define their dependencies (via constructor injection)
 * and implement `execute()` to perform their core operations.
 * The controller calls `execute()` and uses the returned data for further steps.
 */
abstract class Service
{
	/**
	 * Constructor for injecting dependencies required by the service.
	 * Concrete services define their own dependencies (e.g., repositories, domain services).
	 *
	 * @param mixed ...$dependencies Optional dependencies for the concrete service.
	 */
	public function __construct(...$dependencies)
	{
		$this->initialize(...$dependencies);
	}

	/**
	 * Initialization hook for extended services.
	 * Concrete classes can override this method to handle any setup after dependencies are injected.
	 *
	 * @param mixed ...$dependencies
	 * @return void
	 */
	protected function initialize(...$dependencies): void
	{
		// Default no-op. Override in subclasses if needed.
	}

	/**
	 * Perform the business operations this service is responsible for.
	 *
	 * Concrete implementations may:
	 * - Interact with repositories to retrieve or manipulate data.
	 * - Apply domain logic and business rules.
	 * - Return data in a form suitable for controllers, presenters, or views.
	 *
	 * @return mixed The structured data or result of the operation.
	 */
	abstract public function execute(): mixed;

	/**
	 * Utility method to handle errors uniformly within the service’s operations.
	 *
	 * Use `wrapInTry()` to consistently catch exceptions and rethrow them as RuntimeExceptions.
	 *
	 * @param callable $operation The operation to attempt.
	 * @return mixed The result of the operation if successful.
	 * @throws RuntimeException If the operation fails.
	 */
	protected function wrapInTry(callable $operation): mixed
	{
		try {
			return $operation();
		} catch (Throwable $e) {
			throw new RuntimeException("An error occurred in the service: {$e->getMessage()}", $e->getCode(), $e);
		}
	}
}
