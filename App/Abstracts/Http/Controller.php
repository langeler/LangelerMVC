<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Contracts\Http\RequestInterface;
use App\Contracts\Http\ResponseInterface;
use App\Contracts\Http\ServiceInterface;
use App\Contracts\Presentation\PresenterInterface;
use App\Contracts\Presentation\ViewInterface;
use RuntimeException;
use Throwable;

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
abstract class Controller
{
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
		$this->wrapInTry(fn() => $this->initializeController());
	}

	/**
	 * Initialize the controller lifecycle.
	 * Perform setup or preparation required before processing the request.
	 *
	 * @return void
	 */
	abstract protected function initialize(): void;

	/**
	 * Process the incoming request.
	 * Handle request-specific logic, such as extracting and preparing input data.
	 *
	 * @return void
	 */
	abstract protected function process(): void;

	/**
	 * Execute the main business logic of the controller.
	 * Interact with the service to perform business operations and retrieve raw data.
	 *
	 * @return void
	 */
	abstract protected function execute(): void;

	/**
	 * Finalize the controller lifecycle by preparing the response.
	 * Format and structure the data using the presenter, getting it ready for the view.
	 *
	 * @return ResponseInterface The finalized response object, ready to be rendered.
	 */
	abstract protected function finalize(): ResponseInterface;

	/**
	 * Render the output using the view class.
	 * Defines how extended controllers utilize the view for rendering the final response.
	 *
	 * @param string $method The method to call on the view class
	 * @param array  $data   Data to pass to the view method
	 * @return ResponseInterface The rendered response.
	 */
	abstract protected function render(string $method, array $data = []): ResponseInterface;

	/**
	 * Orchestrate the complete controller lifecycle.
	 * Combines the lifecycle methods: initialize, process, execute, finalize, and render.
	 *
	 * @return ResponseInterface The final response after the full lifecycle.
	 */
	abstract protected function run(): ResponseInterface;

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
	 * Consistent error handling for callable operations.
	 *
	 * @param callable $callback The operation to execute.
	 * @return mixed Result of the operation.
	 * @throws RuntimeException On failure.
	 */
	protected function wrapInTry(callable $callback): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new RuntimeException("An error occurred: {$e->getMessage()}", $e->getCode(), $e);
		}
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
		return $this->wrapInTry(fn() => $this->presenter->{$method}(...$arguments));
	}

	/**
	 * Prepare and render the response using the view.
	 *
	 * If needed, the controller can still handle the last step of rendering.
	 * Typically, finalize or render handles this step. The controller itself
	 * should not transform data; it should just pass already prepared data.
	 *
	 * @param string $method The method to call on the view class
	 * @param array  $data   Data to pass to the view
	 * @return ResponseInterface The prepared view response.
	 */
	protected function prepareViewResponse(string $method, array $data = []): ResponseInterface
	{
		return $this->wrapInTry(fn() => $this->view->{$method}($data));
	}
}
