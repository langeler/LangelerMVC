<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Contracts\Http\ServiceInterface;
use App\Exceptions\Http\ServiceException;
use App\Utilities\Traits\ErrorTrait;

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
abstract class Service implements ServiceInterface
{
	use ErrorTrait;

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
	public function execute(): mixed
	{
		return $this->wrapInTry(
			fn(): mixed => $this->handle(),
			ServiceException::class
		);
	}

	/**
	 * Override to implement the actual business workflow.
	 *
	 * @return mixed
	 */
	abstract protected function handle(): mixed;
}
