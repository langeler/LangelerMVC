<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Contracts\Http\{
	ControllerInterface, // Contract for orchestrating controllers.
	RequestInterface,  // Contract for handling HTTP requests.
	ResponseInterface, // Contract for handling HTTP responses.
	ServiceInterface   // Contract for defining HTTP services.
};

use App\Contracts\Presentation\{
	PresenterInterface, // Contract for defining presentation logic in a presenter.
	ViewInterface       // Contract for handling the rendering of views.
};
use App\Exceptions\Http\ControllerException;
use App\Utilities\Traits\{
	ErrorTrait,
	ExistenceCheckerTrait,
	TypeCheckerTrait
};

/**
 * Base abstract Controller that orchestrates the request handling lifecycle.
 *
 * Responsibilities:
 * - Accept the incoming Request (RequestInterface) and prepare the Response (ResponseInterface)
 * - Delegate business logic to the injected Service (ServiceInterface)
 * - Use a Presenter (PresenterInterface) to format data for the View (ViewInterface)
 * - Render the final output via the View
 * - Remain thin, delegating to other layers for complex operations
 *
 * Boundaries:
 * - No direct database logic, no business logic here (delegated to Service).
 * - No handling of presentation logic beyond calling Presenter and View.
 * - Strict typing and interfaces ensure clarity and consistency with updated classes.
 */
abstract class Controller implements ControllerInterface
{
	use ErrorTrait, ExistenceCheckerTrait, TypeCheckerTrait;

	/**
	 * Constructor for injecting dependencies.
	 *
	 * @param RequestInterface   $request   A request instance providing input data.
	 * @param ResponseInterface  $response  A response instance for sending output.
	 * @param ServiceInterface   $service   A service class handling business logic.
	 * @param PresenterInterface $presenter A presenter class for data formatting.
	 * @param ViewInterface      $view      A view class for rendering the final output.
	 */
	public function __construct(
		protected RequestInterface $request,
		protected ResponseInterface $response,
		protected ServiceInterface $service,
		protected PresenterInterface $presenter,
		protected ViewInterface $view
	) {
		$this->wrapInTry(
			function (): void {
				$this->initializeController();
			},
			ControllerException::class
		);
	}

	/**
	 * Initialize the controller lifecycle.
	 * Perform setup or preparation required before processing the request.
	 *
	 * @return void
	 */
	protected function initialize(): void
	{
	}

	/**
	 * Process the incoming request.
	 * Handle request-specific logic, such as extracting and preparing input data.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		$this->request->handle();
	}

	/**
	 * Execute the main business logic of the controller.
	 * Interact with the service to perform business operations and retrieve raw data.
	 *
	 * @return void
	 */
	protected function execute(): mixed
	{
		return $this->service->execute();
	}

	/**
	 * Finalize the controller lifecycle by preparing the response.
	 * Format and structure the data using the presenter, getting it ready for the view.
	 *
	 * @return ResponseInterface The finalized response object, ready to be rendered.
	 */
	protected function finalize(mixed $result): ResponseInterface
	{
		if ($result instanceof ResponseInterface) {
			return $result;
		}

		if ($this->isArray($result)) {
			$result = $this->preparePresenterData('prepare', $result);
		}

		return $this->respond($result);
	}

	/**
	 * Render the output using the view class.
	 * Defines how extended controllers utilize the view for rendering the final response.
	 *
	 * @param string $method The method to call on the view class
	 * @param array  $data   Data to pass to the view method
	 * @return ResponseInterface The rendered response.
	 */
	protected function render(string $method, string $template, array $data = []): ResponseInterface
	{
		return $this->prepareViewResponse($method, $template, $data);
	}

	/**
	 * Orchestrate the complete controller lifecycle.
	 * Combines the lifecycle methods: initialize, process, execute, finalize, and render.
	 *
	 * @return ResponseInterface The final response after the full lifecycle.
	 */
	public function run(): ResponseInterface
	{
		return $this->wrapInTry(function (): ResponseInterface {
			$this->initialize();
			$this->process();
			$result = $this->execute();

			return $this->finalize($result);
		}, ControllerException::class);
	}

	/**
	 * Perform any default initialization for the controller.
	 * For instance, setting a default locale or loading common assets.
	 *
	 * @return void
	 */
	protected function initializeController(): void
	{
		// Example: Set a default locale if needed.
		// In a real scenario, this might be handled by middleware or configuration.
	}

	/**
	 * Prepare data using the presenter.
	 *
	 * If the finalize logic directly uses the presenter, this helper may not be needed.
	 * Keeping the controller clean and delegating transformations is the goal.
	 *
	 * @param string $method    Presenter method to call.
	 * @param array  $arguments Arguments to pass.
	 * @return mixed Transformed data.
	 */
	protected function preparePresenterData(string $method, array $arguments = []): mixed
	{
		return $this->wrapInTry(function () use ($method, $arguments): mixed {
			if ($arguments !== []) {
				$this->presenter->fill($arguments);
			}

			if (!$this->methodExists($this->presenter, $method)) {
				throw new ControllerException("Presenter method '{$method}' does not exist.");
			}

			return $this->presenter->{$method}();
		}, ControllerException::class);
	}

	/**
	 * Prepare and render the response using the view.
	 *
	 * If needed, the controller can still handle the last step of rendering.
	 * Typically, finalize or render handles this step. The controller itself
	 * should not transform data; it should just pass already prepared data.
	 *
	 * @param string $method   The view method to call.
	 * @param string $template The template name to render.
	 * @param array  $data     Data to pass to the view.
	 * @return ResponseInterface The prepared view response.
	 */
	protected function prepareViewResponse(string $method, string $template, array $data = []): ResponseInterface
	{
		return $this->respondWithView($method, $template, $data);
	}

	/**
	 * Populate the response object with content, status, and headers.
	 *
	 * @param mixed $content
	 * @param int $status
	 * @param array<string, string> $headers
	 * @return ResponseInterface
	 */
	protected function respond(mixed $content = null, int $status = 200, array $headers = []): ResponseInterface
	{
		return $this->wrapInTry(function () use ($content, $status, $headers): ResponseInterface {
			$this->response->setStatus($status);

			foreach ($headers as $key => $value) {
				$this->response->addHeader((string) $key, (string) $value);
			}

			$this->response->setContent($content);

			return $this->response;
		}, ControllerException::class);
	}

	/**
	 * Render a template and mark the response as HTML.
	 *
	 * @param string $method
	 * @param string $template
	 * @param array<string, mixed> $data
	 * @param int $status
	 * @param array<string, string> $headers
	 * @return ResponseInterface
	 */
	protected function respondWithView(
		string $method,
		string $template,
		array $data = [],
		int $status = 200,
		array $headers = []
	): ResponseInterface {
		return $this->wrapInTry(function () use ($method, $template, $data, $status, $headers): ResponseInterface {
			if (!$this->methodExists($this->view, $method)) {
				throw new ControllerException("View method '{$method}' does not exist.");
			}

			$this->response->setStatus($status);
			$this->response->addHeader('Content-Type', 'text/html; charset=UTF-8');

			foreach ($headers as $key => $value) {
				$this->response->addHeader((string) $key, (string) $value);
			}

			$this->response->setContent($this->view->{$method}($template, $data));

			return $this->response;
		}, ControllerException::class);
	}
}
