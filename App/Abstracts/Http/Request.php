<?php

namespace App\Abstracts;

use App\Utilities\Filters\SanitationFilterTrait;
use App\Exceptions\RequestException;

abstract class Request
{
	use SanitationFilterTrait;

	protected array $queryParams = [];
	protected array $postParams = [];
	protected array $serverParams = [];
	protected array $headers = [];
	protected array $files = [];

	public function __construct()
	{
		$this->queryParams = $_GET ?? [];
		$this->postParams = $_POST ?? [];
		$this->serverParams = $_SERVER ?? [];
		$this->headers = getallheaders() ?? [];
		$this->files = $_FILES ?? [];
	}

	abstract protected function validate(): bool;

	protected function getQueryParam(string $key, $default = null)
	{
		return $this->queryParams[$key] ?? $default;
	}

	protected function getPostParam(string $key, $default = null)
	{
		return $this->postParams[$key] ?? $default;
	}

	protected function getServerParam(string $key, $default = null)
	{
		return $this->serverParams[$key] ?? $default;
	}

	protected function getHeader(string $key, $default = null)
	{
		return $this->headers[$key] ?? $default;
	}

	protected function getFile(string $key): array
	{
		if (!isset($this->files[$key])) {
			throw new RequestException("File $key not found.");
		}
		return $this->files[$key];
	}

	protected function sanitizeInput(array $input): array
	{
		return $this->sanitize($input);
	}

	protected function getMethod(): string
	{
		return strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
	}
	protected function only(array $keys): array
	{
		return array_intersect_key($this->data, array_flip($keys));
	}

	protected function except(array $keys): array
	{
		return array_diff_key($this->data, array_flip($keys));
	}

}
