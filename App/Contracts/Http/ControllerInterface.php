<?php

declare(strict_types=1);

namespace App\Contracts\Http;

use App\Contracts\Http\ResponseInterface as ResponseContract;

/**
 * ControllerInterface
 *
 * Defines the public contract for controllers.
 * While the abstract Controller class has several protected lifecycle methods,
 * the main public-facing action that external code relies on is `run()`.
 *
 * Controllers return a ResponseInterface after completing their lifecycle.
 */
interface ControllerInterface
{
	/**
	 * Orchestrate the complete controller lifecycle and produce a response.
	 *
	 * @return ResponseContract The final response after the full lifecycle.
	 */
	public function run(): ResponseContract;
}
