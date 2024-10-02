<?php

namespace App\Abstracts;

use App\Exceptions\MiddlewareException;

abstract class Middleware
{
	protected array $requestData = [];
	protected array $responseHeaders = [];
	protected array $middlewareStack = [];


	public function __construct(array $requestData = [])
	{
		$this->requestData = $requestData;
	}

	abstract protected function handle(): void;

	abstract protected function process(): bool;

	protected function setHeader(string $name, string $value): void
	{
		$this->responseHeaders[$name] = $value;
	}

	protected function applyHeaders(): void
	{
		foreach ($this->responseHeaders as $name => $value) {
			header("$name: $value");
		}
	}

	protected function modifyRequest(array $data): void
	{
		$this->requestData = array_merge($this->requestData, $data);
	}

	protected function abort(int $statusCode = 403, string $message = 'Forbidden'): void
	{
		http_response_code($statusCode);
		echo $message;
		exit();
	}

	protected function next()
	{
		if (!empty($this->middlewareStack)) {
			$nextMiddleware = array_shift($this->middlewareStack);
			return $nextMiddleware->process();
		}
		throw new MiddlewareException("No more middleware to process.");
	}

	protected function addMiddleware(callable $middleware): void
	{
		$this->middlewareStack[] = $middleware;
	}

	protected function removeMiddleware(): void
	{
		array_shift($this->middlewareStack);
	}
}
