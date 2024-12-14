<?php

declare(strict_types=1);

namespace App\Contracts\Http;

/**
 * RequestInterface
 *
 * Represents the HTTP request data and provides a contract for accessing input, files, and headers.
 * The abstract Request class includes methods for sanitization, validation, transformation, and handling.
 * These steps may be used by middleware or controllers that need standardized request data.
 *
 * Note: Even if some methods in the abstract class are protected, the interface should expose the methods
 * intended for external usage (like input retrieval and handling). If the architecture dictates all steps
 * (sanitize, validate, transform, handle) as part of the request contract, they can be included.
 * Otherwise, only methods likely to be called externally (input, files, headers) should be included.
 *
 * Here, we assume all abstract methods define the request’s contract.
 */
interface RequestInterface
{
	/**
	 * Sanitize the request data.
	 *
	 * @return void
	 */
	public function sanitize(): void;

	/**
	 * Validate the request data.
	 *
	 * @return void
	 */
	public function validate(): void;

	/**
	 * Transform the request data.
	 *
	 * @return void
	 */
	public function transform(): void;

	/**
	 * Handle final preparation of the request data.
	 *
	 * @return void
	 */
	public function handle(): void;

	/**
	 * Retrieve a specific input value.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function input(string $key, mixed $default = null): mixed;

	/**
	 * Retrieve all input data.
	 *
	 * @return array
	 */
	public function all(): array;

	/**
	 * Retrieve file information.
	 *
	 * @param string|null $key
	 * @return mixed
	 */
	public function file(?string $key = null): mixed;

	/**
	 * Retrieve a specific header.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function header(string $key, mixed $default = null): mixed;

	/**
	 * Retrieve all headers.
	 *
	 * @return array
	 */
	public function headers(): array;
}
