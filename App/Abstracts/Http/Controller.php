<?php

namespace App\Abstracts\Http;

use App\Core\Router;
use App\Exceptions\Http\ControllerException;
use App\Helpers\ArrayHelper;

abstract class Controller
{
	protected Router $router;
	protected array $middlewares = [];
	protected string $controllerName;
	protected string $action;
	protected array $params = [];

	public function __construct(string $controllerName, string $action, array $params = [])
	{
		$this->controllerName = $controllerName;
		$this->action = $action;
		$this->params = $params;
	}

	/**
	 * Execute the specified action.
	 */
	abstract protected function executeAction(): void;

	/**
	 * Handle the request and dispatch the appropriate action method.
	 *
	 * @param string $method The HTTP method.
	 * @param string $action The action method to call.
	 * @return mixed
	 */
	abstract protected function handleRequest(string $method, string $action): mixed;

	/**
	 * Abstract method for handling the retrieval of resources.
	 *
	 * @return mixed
	 */
	abstract protected function index(): mixed;

	/**
	 * Abstract method for handling the display of a creation form.
	 *
	 * @return mixed
	 */
	abstract protected function create(): mixed;

	/**
	 * Abstract method for handling the creation of a new resource.
	 *
	 * @return mixed
	 */
	abstract protected function store(): mixed;

	/**
	 * Abstract method for handling the updating of a resource.
	 *
	 * @param int $id
	 * @return mixed
	 */
	abstract protected function update(int $id): mixed;

	/**
	 * Abstract method for handling the deletion of a resource.
	 *
	 * @param int $id
	 * @return mixed
	 */
	abstract protected function delete(int $id): mixed;

	/**
	 * Adds middleware to the controller.
	 *
	 * @param string $middleware
	 * @return void
	 */
	protected function addMiddleware(string $middleware): void
	{
		$this->middlewares[] = $middleware;
	}

	/**
	 * Execute all registered middlewares.
	 */
	protected function executeMiddlewares(): void
	{
		foreach ($this->middlewares as $middleware) {
			(new $middleware())->handle();
		}
	}

	/**
	 * Send a JSON response.
	 *
	 * @param array $data
	 * @param int $statusCode
	 */
	protected function jsonResponse(array $data, int $statusCode = 200): void
	{
		header('Content-Type: application/json');
		http_response_code($statusCode);
		echo json_encode($data);
	}

	/**
	 * Redirect to a specific URL.
	 *
	 * @param string $url
	 */
	protected function redirect(string $url): void
	{
		header("Location: $url");
		exit();
	}

	/**
	 * Get request data from superglobals.
	 *
	 * @return array
	 */
	protected function getRequestData(): array
	{
		return $_REQUEST ?? [];
	}

	/**
	 * Sanitize input data.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function sanitizeInput(array $data): array
	{
		return ArrayHelper::sanitize($data);
	}

	/**
	 * Get a parameter from the request, or return a default value if not set.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getParam(string $key, $default = null)
	{
		return $this->params[$key] ?? $default;
	}

	/**
	 * Dispatches the request to the appropriate action method.
	 *
	 * @return void
	 * @throws ControllerException
	 */
	protected function handleRequest(): void
	{
		if (method_exists($this, $this->action)) {
			call_user_func_array([$this, $this->action], $this->params);
		} else {
			throw new ControllerException("Action $this->action not found in controller $this->controllerName.");
		}
	}

	/**
	 * Sanitize input using default filter settings.
	 *
	 * @param array $input
	 * @return array
	 */
	protected function sanitizeInput(array $input): array
	{
		return filter_var_array($input, FILTER_SANITIZE_STRING);
	}

	/**
	 * Validate request data based on defined rules.
	 *
	 * @param array $rules
	 * @return bool
	 * @throws ControllerException
	 */
	protected function validateRequest(array $rules): bool
	{
		foreach ($rules as $field => $rule) {
			if (!isset($this->params[$field]) || !preg_match($rule, $this->params[$field])) {
				throw new ControllerException("Validation failed for field $field.");
			}
		}
		return true;
	}
}
