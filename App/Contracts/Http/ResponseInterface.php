<?php

declare(strict_types=1);

namespace App\Contracts\Http;

/**
 * ResponseInterface
 *
 * The abstract Response class focuses on representing and manipulating an HTTP response.
 * The primary public-facing operation is `send()`, which outputs the response to the client.
 */
interface ResponseInterface
{
	/**
	 * Set the HTTP status code.
	 *
	 * @param int $status
	 * @return void
	 */
	public function setStatus(int $status): void;

	/**
	 * Retrieve the current HTTP status code.
	 *
	 * @return int
	 */
	public function getStatus(): int;

	/**
	 * Add or overwrite a response header.
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function addHeader(string $key, string $value): void;

	/**
	 * Retrieve the response headers.
	 *
	 * @return array<string, string>
	 */
	public function getHeaders(): array;

	/**
	 * Set the response content.
	 *
	 * @param mixed $content
	 * @return void
	 */
	public function setContent(mixed $content): void;

	/**
	 * Retrieve the response content.
	 *
	 * @return mixed
	 */
	public function getContent(): mixed;

	/**
	 * Prepare headers and content metadata before sending.
	 *
	 * @return void
	 */
	public function prepareForSend(): void;

	/**
	 * Convert the response into an array representation.
	 *
	 * @return array{status:int,headers:array<string,string>,content:string}
	 */
	public function toArray(): array;

	/**
	 * Send the response.
	 *
	 * @return void
	 */
	public function send(): void;
}
