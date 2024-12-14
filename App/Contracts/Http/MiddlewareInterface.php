<?php

declare(strict_types=1);

namespace App\Contracts\Http;

use App\Contracts\Http\ResponseInterface as ResponseContract;

/**
 * MiddlewareInterface
 *
 * Middleware is invoked to process the request/response pipeline.
 * The abstract Middleware defines `handle()` as the central entry point.
 */
interface MiddlewareInterface
{
	/**
	 * Handle the incoming request and produce a response.
	 *
	 * @return ResponseContract The final response after middleware processing.
	 */
	public function handle(): ResponseContract;
}
