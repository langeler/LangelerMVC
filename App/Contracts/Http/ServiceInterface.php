<?php

declare(strict_types=1);

namespace App\Contracts\Http;

/**
 * ServiceInterface
 *
 * The abstract Service class provides a `execute()` method for performing complex business workflows.
 * Services return processed data for controllers or other layers to use.
 */
interface ServiceInterface
{
	/**
	 * Execute the main business logic of the service.
	 *
	 * @return mixed The result of the operation, structured for the caller’s needs.
	 */
	public function execute(): mixed;
}
