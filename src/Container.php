<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use Awirhosein\Container\Exceptions\UnresolvableDependencyException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $aliases = [];
    private array $resolving = [];

    /**
     * Register a binding in the container.
     */
    public function bind(string $abstract, Closure|string $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => $shared,
        ];
    }

    /**
     * Register a shared binding (singleton) in the container.
     */
    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->bind($abstract, $concrete, shared: true);
    }

    /**
     * Register an already-constructed object as a singleton.
     */
    public function instance(string $abstract, object $concrete): void
    {
        $this->instances[$abstract] = $concrete;
    }

    /**
     * Register an alias for an abstract.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve an abstract type out of the container.
     */
    public function resolve(string $abstract): object
    {
        $abstract = $this->resolveAlias($abstract);

        if ($this->hasInstance($abstract)) {
            return $this->instances[$abstract];
        }

        $this->detectCircularDependency($abstract);

        $this->resolving[$abstract] = true;

        try {
            $instance = $this->build($this->concrete($abstract));

            if ($this->isShared($abstract)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } finally {
            // Always cleanup resolving state,
            // even if an exception is thrown during dependency resolution.
            unset($this->resolving[$abstract]);
        }
    }

    /**
     * Call a method on a class, auto-resolving its dependencies.
     */
    public function call(array $callable): mixed
    {
        [$class, $methodName] = $callable;

        $object = $this->resolve($class);
        $method = new ReflectionMethod($class, $methodName);
        $arguments = $this->resolveArguments($method, $method->class);

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Determine if the given abstract has been registered in the container.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Reset the container.
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolving = [];
        $this->aliases = [];
    }

    private function resolveAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    private function hasInstance(string $abstract): bool
    {
        return isset($this->instances[$abstract]);
    }

    /**
     * Detect circular dependencies before resolution begins.
     *
     * Example cycle: A → B → C → A
     */
    private function detectCircularDependency(string $abstract): void
    {
        if (isset($this->resolving[$abstract])) {
            throw new CircularDependencyException(
                "Circular dependency detected while resolving [{$abstract}]"
            );
        }
    }

    /**
     * Get the concrete implementation for the given abstract.
     */
    private function concrete(string $abstract): Closure|string
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    /**
     * Build an instance from a concrete class name or closure.
     */
    private function build(Closure|string $concrete): object
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        $reflection = new ReflectionClass($concrete);

        if (! $reflection->isInstantiable()) {
            throw new BindingResolutionException(
                "Target [{$reflection->getShortName()}] is not instantiable."
            );
        }

        return $reflection->newInstanceArgs(
            $this->constructorArguments($reflection)
        );
    }

    /**
     * Resolve the constructor arguments for the given class.
     */
    private function constructorArguments(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return [];
        }

        return $this->resolveArguments($constructor, $reflection->getName());
    }

    /**
     * Resolve the arguments for a given method or constructor.
     */
    private function resolveArguments(ReflectionMethod $method, string $className): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type && ! $type->isBuiltin()) {
                $arguments[] = $this->resolve($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new UnresolvableDependencyException(
                "Cannot resolve dependency [\${$parameter->getName()}] in [{$className}]"
            );
        }

        return $arguments;
    }

    private function isShared(string $abstract): bool
    {
        return $this->bindings[$abstract]['shared'] ?? false;
    }
}