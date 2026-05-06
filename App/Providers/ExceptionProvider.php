<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Container;
use App\Exceptions\{
    AppException,
    AuthException,
    ConfigException,
    SessionException,
    RouteNotFoundException,
    RouterException,
    Data\CacheException,
    Data\CryptoException,
    Data\FinderException,
    Data\SanitizationException,
    Data\ValidationException,
    Database\DatabaseException,
    Database\MigrationException,
    Database\ModelException,
    Database\RepositoryException,
    Database\SeedException,
    Http\ControllerException,
    Http\MiddlewareException,
    Http\RequestException,
    Http\ResponseException,
    Http\ServiceException,
    Iterator\IteratorException,
    Iterator\IteratorNotFoundException,
    Presentation\PresenterException,
    Presentation\ViewException,
    Support\PaymentException,
    ContainerException
};
use ArgumentCountError;
use ArithmeticError;
use AssertionError;
use BadFunctionCallException;
use BadMethodCallException;
use ClosedGeneratorException;
use CompileError;
use DivisionByZeroError;
use DomainException;
use Error;
use ErrorException;
use FiberError;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use OverflowException;
use ParseError;
use RangeException;
use RequestParseBodyException;
use RuntimeException;
use ReflectionClass;
use TypeError;
use UnderflowException;
use UnexpectedValueException;
use UnhandledMatchError;
use ValueError;

/**
 * ExceptionProvider Class
 *
 * Manages the registration and resolution of core PHP and custom application exceptions.
 * This class dynamically maps exception aliases to their respective class names and ensures that they are registered
 * as singleton instances for consistent usage across the application.
 *
 * Features:
 * - Dynamic mapping of exception aliases to their fully qualified class names.
 * - Alias registration for container-level lookup where useful.
 * - Fresh exception instantiation for each resolution request.
 *
 * @package App\Providers
 */
class ExceptionProvider extends Container
{
    /**
     * A mapping of exception aliases to their fully qualified class names.
     *
     * @var array<string, string>
     */
    protected readonly array $exceptionMap;
    private bool $servicesRegistered = false;

    /**
     * Constructor for ExceptionProvider.
     *
     * Initializes the exception map with core PHP exceptions and custom application exceptions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->exceptionMap = [
            // Core PHP exceptions
            'argumentCount'      => ArgumentCountError::class,
            'arithmetic'         => ArithmeticError::class,
            'assertion'          => AssertionError::class,
            'badFunctionCall'    => BadFunctionCallException::class,
            'badMethodCall'      => BadMethodCallException::class,
            'closedGenerator'    => ClosedGeneratorException::class,
            'compile'            => CompileError::class,
            'divisionByZero'     => DivisionByZeroError::class,
            'domain'             => DomainException::class,
            'error'              => Error::class,
            'errorException'     => ErrorException::class,
            'fiber'              => FiberError::class,
            'invalidArgument'    => InvalidArgumentException::class,
            'length'             => LengthException::class,
            'logic'              => LogicException::class,
            'outOfBounds'        => OutOfBoundsException::class,
            'outOfRange'         => OutOfRangeException::class,
            'overflow'           => OverflowException::class,
            'parse'              => ParseError::class,
            'range'              => RangeException::class,
            'requestParseBody'   => RequestParseBodyException::class,
            'runtime'            => RuntimeException::class,
            'type'               => TypeError::class,
            'underflow'          => UnderflowException::class,
            'unexpectedValue'    => UnexpectedValueException::class,
            'unhandledMatch'     => UnhandledMatchError::class,
            'value'              => ValueError::class,

            // Custom application exceptions
            'app'                => AppException::class,
            'auth'               => AuthException::class,
            'config'             => ConfigException::class,
            'settings'           => ConfigException::class,
            'session'            => SessionException::class,
            'routeNotFound'      => RouteNotFoundException::class,
            'router'             => RouterException::class,

            // Data-related exceptions
            'cache'              => CacheException::class,
            'crypto'             => CryptoException::class,
            'finder'             => FinderException::class,
            'sanitization'       => SanitizationException::class,
            'validation'         => ValidationException::class,
            'payment'            => PaymentException::class,

            // Database-related exceptions
            'database'           => DatabaseException::class,
            'migration'          => MigrationException::class,
            'model'              => ModelException::class,
            'repository'         => RepositoryException::class,
            'seed'               => SeedException::class,

            // HTTP-related exceptions
            'controller'         => ControllerException::class,
            'middleware'         => MiddlewareException::class,
            'request'            => RequestException::class,
            'response'           => ResponseException::class,
            'service'            => ServiceException::class,

            // Iterator-related exceptions
            'iterator'           => IteratorException::class,
            'iteratorNotFound'   => IteratorNotFoundException::class,

            // Presentation-related exceptions
            'presenter'          => PresenterException::class,
            'view'               => ViewException::class,
        ];
    }

    /**
     * Registers the exception services in the application's service container.
     *
     * Maps exception aliases to their respective class names.
     *
     * @return void
     * @throws ContainerException If an error occurs during service registration.
     */
    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        $this->wrapInTry(
            function (): void {
                if (!$this->isArray($this->exceptionMap) || $this->isEmpty($this->exceptionMap)) {
                    throw new ContainerException("The exception map must be a non-empty array.");
                }

                foreach ($this->exceptionMap as $alias => $class) {
                    $this->registerAlias($alias, $class);
                }

                $this->servicesRegistered = true;
            },
            new ContainerException("Error registering exception services.")
        );
    }

    /**
     * Resolves an exception class or alias and returns an instance.
     *
     * @param string $exceptionAlias The alias or class name of the exception.
     * @return object The resolved exception instance.
     * @throws ContainerException If the specified exception is unsupported or on resolution failure.
     */
    public function getException(
        string $exceptionAlias,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ): object
    {
        return $this->wrapInTry(
            function () use ($exceptionAlias, $message, $code, $previous): object {
                $class = $this->exceptionMap[$exceptionAlias]
                    ?? throw new ContainerException("Unsupported exception alias: $exceptionAlias");
                $reflection = new ReflectionClass($class);
                $constructor = $reflection->getConstructor();

                if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                    return $reflection->newInstance();
                }

                $arguments = [];

                foreach ($constructor->getParameters() as $index => $parameter) {
                    $name = $parameter->getName();

                    if ($index === 0 || $name === 'message') {
                        $arguments[] = $message;
                        continue;
                    }

                    if ($name === 'code') {
                        $arguments[] = $code;
                        continue;
                    }

                    if ($name === 'previous') {
                        $arguments[] = $previous;
                        continue;
                    }

                    if ($parameter->isDefaultValueAvailable()) {
                        $arguments[] = $parameter->getDefaultValue();
                        continue;
                    }

                    $arguments[] = $parameter->allowsNull() ? null : '';
                }

                return $reflection->newInstanceArgs($arguments);
            },
            new ContainerException("Error retrieving exception [$exceptionAlias].")
        );
    }
}
