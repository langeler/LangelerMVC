<?php

namespace App\Core;

use App\Exceptions\ContainerException;
use App\Utilities\Managers\ReflectionManager;
use App\Utilities\Traits\{
    ArrayTrait,
    ErrorTrait,
    ExistenceCheckerTrait,
    TypeCheckerTrait
};

/**
 * Abstract Container Class
 *
 * The `Container` class provides a robust foundation for managing and resolving dependencies
 * in an application. It utilizes PHP reflection and a trait-based architecture to deliver features
 * such as dependency injection, lazy loading, lifecycle management, aliasing, and error handling.
 *
 * Key Features:
 * - Singleton and alias registration for services.
 * - Dependency resolution with reflection-based injection.
 * - Lazy-loading of services via factory callbacks.
 * - Management of lifecycle hooks such as `postConstruct`.
 * - Error handling and validation through traits.
 *
 * Traits Used:
 * - **ArrayTrait**: Provides utility methods for manipulating and validating arrays.
 * - **ExistenceCheckerTrait**: Offers methods to verify the existence of classes, methods, traits, etc.
 * - **TypeCheckerTrait**: Includes type-checking utilities for validating data types.
 * - **ErrorTrait**: Encapsulates error handling with `wrapInTry` for exception transformation.
 *
 * @package App\Core
 * @abstract
 */
abstract class Container
{
    use
        ArrayTrait,               // Provides utility methods for array operations like filtering, mapping.
        ErrorTrait,               // Handles exception wrapping for improved error handlinbg.
        ExistenceCheckerTrait,    // Adds methods for checking the existence of classes, methods, properties.
        TypeCheckerTrait;         // Offers utility methods for type validation and checking.

    /**
     * Constructor for the Container class.
     *
     * Initializes core dependencies, instances, and configuration for the container.
     * Sets up attributes for lifecycle management, dependency injection, and behavior customization.
     *
     * @param ReflectionManager $reflectionManager Reflection utility to manage class, method, property, and attribute operations.
     * @param array $instances Cached instances of resolved services or classes (singleton or lazy instances).
     * @param array $singletons List of classes designated as singletons.
     * @param array $aliases Mapping of short aliases to fully qualified class names.
     * @param array $resolving Tracks classes currently being resolved to detect circular dependencies.
     * @param array $cache Cached ReflectionClass instances for performance optimization.
     * @param array $attributes Categorized attributes for injection, lifecycle management, and other behaviors.
     */
    public function __construct(
        protected ReflectionManager $reflectionManager,
        protected array $instances = [],
        protected array $singletons = [],
        protected array $aliases = [],
        protected array $resolving = [],
        protected array $cache = [],
        protected array $attributes = []
    ) {
        $this->attributes = [
            'property' => [
                'inject' => 'App\Attributes\Inject',         // Marks a property for dependency injection.
                'optional' => 'App\Attributes\Optional',     // Indicates an optional property.
                'lazy' => 'App\Attributes\Lazy',             // Lazily loads a property when accessed.
                'required' => 'App\Attributes\Required',     // Marks a property as required for instantiation.
                'notNull' => 'App\Attributes\NotNull',       // Ensures the property value is not null.
                'defaultValue' => 'App\Attributes\DefaultValue', // Specifies a default value for the property.
            ],
            'method' => [
                'postConstruct' => 'App\Attributes\PostConstruct', // Method invoked after construction.
                'preDestroy' => 'App\Attributes\PreDestroy',       // Method invoked before destruction.
                'eventHandler' => 'App\Attributes\EventHandler',   // Marks a method as an event handler.
            ],
            'class' => [
                'singleton' => 'App\Attributes\Singleton',   // Marks the class as a singleton.
                'prototype' => 'App\Attributes\Prototype',   // Marks the class as a prototype (new instance per request).
                'named' => 'App\Attributes\Named',           // Associates a name with the class for specific resolution.
                'primary' => 'App\Attributes\Primary',       // Marks the class as the primary implementation of a contract.
                'scope' => 'App\Attributes\Scope',           // Defines a specific lifecycle scope for the class.
            ],
            'parameter' => [
                'lazy' => 'App\Attributes\Lazy',             // Lazily resolves a parameter's dependency.
                'required' => 'App\Attributes\Required',     // Marks a parameter as required.
                'defaultValue' => 'App\Attributes\DefaultValue', // Provides a default value for the parameter.
            ],
        ];
    }

     /**
      * Registers a class as a singleton, ensuring only one instance exists.
      *
      * @param string $className The fully qualified class name to register as a singleton.
      * @throws ContainerException If the class name is invalid or empty.
      */
     protected function registerSingleton(string $className): void
     {
         $this->wrapInTry(
             fn() => (!$this->isString($className) || $this->isEmpty($className))
                 ? throw new ContainerException("Invalid class name provided for singleton registration.")
                 : $this->singletons[$className] = true
         );
     }

     /**
      * Registers an alias for a class name to simplify resolution.
      *
      * @param string $alias The alias for the class.
      * @param string $className The fully qualified class name.
      * @throws ContainerException If the alias or class name is invalid or empty.
      */
     protected function registerAlias(string $alias, string $className): void
     {
         $this->wrapInTry(
             fn() => (!$this->isString($alias) || $this->isEmpty($alias) || !$this->isString($className) || $this->isEmpty($className))
                 ? throw new ContainerException("Invalid alias or class name provided for registration.")
                 : $this->aliases[$alias] = $className
         );
     }

     /**
      * Registers a lazily instantiated class with a factory callback.
      *
      * @param string $className The fully qualified class name.
      * @param callable $factory The factory callback responsible for creating the instance.
      * @throws ContainerException If the class name is invalid, empty, or the factory is not callable.
      */
     protected function registerLazy(string $className, callable $factory): void
     {
         $this->wrapInTry(
             fn() => (!$this->isString($className) || $this->isEmpty($className))
                 ? throw new ContainerException("Invalid class name provided for lazy registration.")
                 : (!$this->isCallable($factory)
                     ? throw new ContainerException("Factory for [$className] is not callable.")
                     : $this->instances[$className] = $factory) &&
                 $this->registerSingleton($className)
         );
     }

     /**
      * Retrieves an instance of a class, resolving dependencies if necessary.
      *
      * @param string $className The fully qualified class name.
      * @return object The resolved instance of the class.
      * @throws ContainerException If the service retrieval fails.
      */
     protected function getInstance(string $className): object
     {
         return $this->wrapInTry(
             fn() => $this->resolveInstance($className),
             new ContainerException("Failed to retrieve service [$className].")
         );
     }

     /**
      * Resolves an instance of the specified class, handling dependencies and lifecycle management.
      *
      * @param string $className The fully qualified class name to resolve.
      * @return object The resolved instance of the class.
      * @throws ContainerException If the class name is invalid, circular dependencies are detected, or resolution fails.
      */
     protected function resolveInstance(string $className): object
     {
         return $this->wrapInTry(
             fn() => (!$this->isString($className) || $this->isEmpty($className))
                 ? throw new ContainerException("Invalid class name provided.")
                 : ($this->keyExists($this->resolving, $className)
                     ? throw new ContainerException("Circular dependency detected for [$className].")
                     : $this->resolving[$className] = true) &&
                 $this->wrapInTry(
                     fn() => $this->processInstance($className),
                     new ContainerException("Failed to resolve class [$className].")
                 ),
             fn() => unset($this->resolving[$className])
         );
     }

     /**
      * Processes the instance of a class, handling aliasing, singleton creation, and instantiation.
      *
      * @param string $className The fully qualified class name to process.
      * @return object The processed instance of the class.
      * @throws ContainerException If alias resolution, instance creation, or processing fails.
      */
     protected function processInstance(string $className): object
     {
         return $this->wrapInTry(
             fn() => $this->arrayKeyExists($className, $this->aliases)
                 ? ($this->arrayKeyExists($alias = $this->aliases[$className], $this->instances)
                     ? ($this->isCallable($this->instances[$alias]) ? $this->instances[$alias] = ($this->instances[$alias])() : $this->instances[$alias])
                     : ($this->arrayKeyExists($alias, $this->singletons) && $this->singletons[$alias]
                         ? $this->instances[$alias] = $this->registerInstance($alias)
                         : $this->registerInstance($alias)))
                 : ($this->arrayKeyExists($className, $this->instances)
                     ? ($this->isCallable($this->instances[$className]) ? $this->instances[$className] = ($this->instances[$className])() : $this->instances[$className])
                     : ($this->arrayKeyExists($className, $this->singletons) && $this->singletons[$className]
                         ? $this->instances[$className] = $this->registerInstance($className)
                         : $this->registerInstance($className))),
             new ContainerException("Failed to process instance for [$className].")
         );
     }

     /**
      * Registers an instance of the specified class, resolving its dependencies.
      *
      * @param string $className The fully qualified class name to register.
      * @return object The newly created instance of the class.
      * @throws ContainerException If the class instantiation fails or its dependencies cannot be resolved.
      */
     protected function registerInstance(string $className): object
     {
         return $this->wrapInTry(
             fn() => ($reflectionClass = $this->cachedInstance($className)) &&
                 ($constructor = $this->reflectionManager->getClassConstructor($reflectionClass))
                 ? $this->reflectionManager->newClassInstanceArgs(
                     $reflectionClass,
                     $this->filterNonEmpty(
                         $this->map(
                             fn($parameter) => $this->reflectionManager->getParameterType($parameter) &&
                                               !$this->reflectionManager->isBuiltinType($this->reflectionManager->getParameterType($parameter))
                                 ? $this->resolveInstance($this->reflectionManager->getParameterType($parameter)->getName())
                                 : ($this->reflectionManager->isParameterOptional($parameter)
                                     ? $this->reflectionManager->getParameterDefaultValue($parameter)
                                     : throw new ContainerException(
                                         "Cannot resolve parameter [{$this->reflectionManager->getParameterName($parameter)}]."
                                       )
                                   ),
                             $this->reflectionManager->getFunctionParameters($constructor)
                         )
                     )
                 )
                 : $this->reflectionManager->newClassInstanceWithoutConstructor($reflectionClass),
             new ContainerException("Failed to create instance for [$className].")
         );
     }

     /**
      * Retrieves a cached ReflectionClass instance for the specified class or creates a new one.
      *
      * @param string $className The fully qualified class name.
      * @return \ReflectionClass The ReflectionClass instance for the specified class.
      * @throws ContainerException If the class does not exist or the ReflectionClass creation fails.
      */
     protected function cachedInstance(string $className): \ReflectionClass
     {
         return $this->wrapInTry(
             fn() => !$this->classExists($className)
                 ? throw new ContainerException("Class [$className] does not exist.")
                 : ($this->arrayKeyExists($className, $this->cache)
                     ? $this->cache[$className]
                     : $this->cache[$className] = $this->reflectionManager->createClass($className)),
             new ContainerException("Failed to cache ReflectionClass for [$className].")
         );
     }

     /**
      * Injects dependencies into properties marked with the "inject" attribute.
      *
      * @param object $object The target object for dependency injection.
      * @param object $reflectionClass The reflection class for the target object.
      * @throws ContainerException If a property cannot be injected due to missing or unresolvable dependencies.
      */
     protected function injectProperties(object $object, object $reflectionClass): void
     {
         $this->map(
             fn($property) => $this->map(
                 fn($attr) => $this->keyExists($this->attributes['property'], 'inject') &&
                              $this->reflectionManager->getAttributeName($attr) === $this->attributes['property']['inject']
                     ? $this->wrapInTry(
                         fn() => $this->reflectionManager->hasPropertyType($property) &&
                                 !$this->reflectionManager->isBuiltinType($this->reflectionManager->getPropertyType($property))
                             ? $this->reflectionManager->setPropertyValue(
                                 $property,
                                 $object,
                                 $this->resolve($this->reflectionManager->getPropertyType($property)->getName())
                             )
                             : null,
                         new ContainerException("Failed to inject property [{$property->getName()}].")
                     )
                     : null,
                 $this->filter(
                     $this->reflectionManager->getPropertyAttributes($property),
                     fn($attr) => $this->reflectionManager->getAttributeName($attr) === $this->attributes['property']['inject']
                 )
             ),
             $this->filter(
                 $this->reflectionManager->getClassProperties($reflectionClass, true),
                 fn($property) => $this->propertyExists($reflectionClass, $property->getName())
             )
         );
     }

     /**
      * Invokes methods marked with the "postConstruct" attribute.
      *
      * @param object $object The target object for invoking post-construction methods.
      * @param object $reflectionClass The reflection class for the target object.
      * @throws ContainerException If a post-construction method fails or its dependencies cannot be resolved.
      */
     protected function invokeMethods(object $object, object $reflectionClass): void
     {
         $this->wrapInTry(
             fn() => $this->map(
                 fn($method) => $this->map(
                     fn($attr) => $this->keyExists($this->attributes['method'], 'postConstruct') &&
                                  $this->reflectionManager->getAttributeName($attr) === $this->attributes['method']['postConstruct']
                         ? $this->wrapInTry(
                             fn() => $this->reflectionManager->invokeMethod(
                                 $method,
                                 $object,
                                 ...$this->map(
                                     fn($p) => $this->reflectionManager->getParameterType($p) &&
                                               !$this->reflectionManager->isBuiltinType($this->reflectionManager->getParameterType($p))
                                         ? $this->resolve($this->reflectionManager->getParameterType($p)->getName())
                                         : ($this->reflectionManager->isParameterOptional($p)
                                             ? $this->reflectionManager->getParameterDefaultValue($p)
                                             : throw new ContainerException(
                                                 "Cannot resolve parameter [{$this->reflectionManager->getParameterName($p)}]."
                                               )
                                           ),
                                     $this->reflectionManager->getFunctionParameters($method)
                                 )
                             ),
                             new ContainerException("Failed to invoke post-construct method [{$method->getName()}] on [$object].")
                         )
                         : null,
                     $this->reflectionManager->getMethodAttributes($method)
                 ),
                 $this->reflectionManager->getClassMethods($reflectionClass)
             ),
             new ContainerException("Failed to invoke methods for the provided reflection class.")
         );
     }

     /**
      * Processes a specific attribute for the given class or object.
      *
      * @param object|string $class The class or object to process.
      * @param string $attribute The attribute to look for.
      * @return array The arguments of the matched attributes.
      * @throws ContainerException If the class type is invalid or attribute processing fails.
      */
     protected function processAttribute(object|string $class, string $attribute): array
     {
         return $this->wrapInTry(
             fn() => ($this->isObject($class) || $this->isString($class))
                 ? $this->map(
                     fn($attr) => $this->reflectionManager->getAttributeArguments($attr),
                     $this->filter(
                         $this->reflectionManager->getClassAttributes($this->cachedInstance($class)),
                         fn($a) => $this->keyExists($this->attributes['class'], $attribute)
                             ? $this->reflectionManager->getAttributeName($a) === $this->attributes['class'][$attribute]
                             : $this->reflectionManager->getAttributeName($a) === $attribute
                     )
                 )
                 : throw new ContainerException("Invalid class type provided. Must be an object or string."),
             new ContainerException("Failed to process attribute [$attribute] for class [$class].")
         );
     }

     /**
      * Processes enum cases for the given class or object.
      *
      * @param object|string $class The class or object to process.
      * @return array An array of enum cases if the class is an enum, otherwise an empty array.
      * @throws ContainerException If the class type is invalid or enum processing fails.
      */
     protected function processEnum(object|string $class): array
     {
         return $this->wrapInTry(
             fn() => ($this->isObject($class) || $this->isString($class))
                 ? ($this->reflectionManager->isClassEnum($this->cachedInstance($class))
                     ? $this->flatten($this->reflectionManager->getEnumCases($this->cachedInstance($class)))
                     : [])
                 : throw new ContainerException("Invalid class provided. Must be an object or string."),
             new ContainerException("Failed to process enum for class [$class].")
         );
     }

     /**
      * Invokes a global function with the specified arguments.
      *
      * @param string $functionName The name of the global function to invoke.
      * @param array $args An array of arguments to pass to the function.
      * @return mixed The result of the invoked function.
      * @throws ContainerException If the function invocation fails.
      */
     protected function invokeFunction(string $functionName, array $args = []): mixed
     {
         return $this->wrapInTry(
             fn() => $this->isString($functionName) && $this->functionExists($functionName)
                 ? $this->reflectionManager->invokeFunctionArgs(
                     $this->reflectionManager->createFunction($functionName),
                     $args
                 )
                 : throw new ContainerException("Function [$functionName] does not exist or is invalid."),
             new ContainerException("Failed to invoke global function [$functionName].")
         );
     }

     /**
      * Verifies if a specific PHP extension is loaded and executes corresponding callbacks.
      *
      * @param string $extension The name of the PHP extension to verify.
      * @param callable $onDetected A callback to execute if the extension is loaded.
      * @param callable $onMissing A callback to execute if the extension is not loaded.
      * @return void
      * @throws ContainerException If the extension verification process fails.
      */
     protected function verifyExtension(string $extension, callable $onDetected, callable $onMissing): void
     {
         $this->wrapInTry(
             fn() => $this->isString($extension) && $this->functionExists('extension_loaded') && extension_loaded($extension)
                 ? $onDetected($this)
                 : $onMissing($this),
             new ContainerException("Verification failed for extension [$extension].")
         );
     }

     /**
      * Abstract method to register services within the container.
      *
      * This method must be implemented by subclasses to define service bindings and registrations
      * specific to the application's requirements.
      *
      * @return void
      */
     abstract public function registerServices(): void;
}
