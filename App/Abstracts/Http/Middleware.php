<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use RuntimeException;            // Exception thrown if an error occurs at runtime.
use Throwable;                   // Base interface for all errors and exceptions in PHP.

use App\Contracts\Http\ResponseInterface; // Contract for handling HTTP responses.

/**
 * Abstract Middleware Class
 *
 * Responsibilities:
 * - Provide a contract for handling the request/response lifecycle within the middleware layer.
 * - Define a main `handle()` method that concrete classes must implement.
 * - Offer optional lifecycle hooks (`before()`, `after()`, `authenticate()`, `authorize()`) that do nothing by default,
 *   allowing subclasses to override only what they need.
 *
 * Alignment with Updated Classes:
 * - Uses strict typing.
 * - Specifies return types where known.
 * - Remains focused on its responsibility: filtering or transforming the request/response pipeline.
 * - Does not incorporate business logic or presentation logic, staying aligned with layered architecture principles.
 */
abstract class Middleware
{
	/**
	 * Abstract method that each middleware must implement.
	 *
	 * This is the main entry point for the middleware. It should handle the request,
	 * optionally call `authenticate()`, `authorize()`, `before()` methods, pass the
	 * request to the next handler (e.g., controller), and then optionally call `after()`.
	 *
	 * @return ResponseInterface The final response after the middleware processing.
	 */
	abstract protected function handle(): ResponseInterface;

	/**
	 * Lifecycle hook to run before handling the request.
	 * Default is a no-op. Subclasses can override this.
	 *
	 * @return void
	 */
	protected function before(): void
	{
		// No-op by default.
	}

	/**
	 * Lifecycle hook to run after handling the request and before sending the response.
	 * Default is a no-op. Subclasses can override this.
	 *
	 * @return void
	 */
	protected function after(): void
	{
		// No-op by default.
	}

	/**
	 * Lifecycle hook for authentication checks.
	 * Default is a no-op. Subclasses can override to handle authentication.
	 *
	 * @return void
	 */
	protected function authenticate(): void
	{
		// No-op by default.
	}

	/**
	 * Lifecycle hook for authorization checks.
	 * Default is a no-op. Subclasses can override to handle authorization.
	 *
	 * @return void
	 */
	protected function authorize(): void
	{
		// No-op by default.
	}

	/**
	 * Utility method to ensure consistent error handling.
	 *
	 * @param callable $operation The operation to execute.
	 * @return mixed The result of the operation.
	 * @throws RuntimeException On failure.
	 */
	protected function wrapInTry(callable $operation): mixed
	{
		try {
			return $operation();
		} catch (Throwable $e) {
			throw new RuntimeException("An error occurred: {$e->getMessage()}", $e->getCode(), $e);
		}
	}
}
