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
	 * Send the response.
	 *
	 * @return void
	 */
	public function send(): void;
}
