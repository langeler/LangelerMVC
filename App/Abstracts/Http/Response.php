<?php

namespace App\Abstracts;

use App\Exceptions\ResponseException;

abstract class Response
{
	protected int $statusCode = 200;
	protected array $headers = [];
	protected array $cookies = [];
	protected $content;

	public function __construct($content = '', int $statusCode = 200)
	{
		$this->content = $content;
		$this->statusCode = $statusCode;
	}

	abstract protected function format(): mixed;

	protected function setStatusCode(int $code): void
	{
		$this->statusCode = $code;
	}

	protected function addHeader(string $name, string $value): void
	{
		$this->headers[$name] = $value;
	}

	protected function addCookie(string $name, string $value, array $options = []): void
	{
		$this->cookies[] = [
			'name' => $name,
			'value' => $value,
			'options' => $options,
		];
	}

	protected function sendHeaders(): void
	{
		http_response_code($this->statusCode);
		foreach ($this->headers as $name => $value) {
			header("$name: $value");
		}
		foreach ($this->cookies as $cookie) {
			setcookie($cookie['name'], $cookie['value'], $cookie['options']);
		}
	}

	protected function sendContent(): void
	{
		echo $this->content;
	}

	protected function jsonResponse(array $data, int $statusCode = 200): void
	{
		$this->setStatusCode($statusCode);
		$this->addHeader('Content-Type', 'application/json');
		$this->content = json_encode($data);
		$this->sendHeaders();
		$this->sendContent();
	}
}
