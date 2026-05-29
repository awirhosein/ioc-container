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
    private array $resolving = [];
    private array $aliases = [];

    public function bind(string $abstract, $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => $shared,
        ];
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $concrete): void
    {
        $this->instances[$abstract] = $concrete;
    }

    public function alias(string $abstract, string $name): void
    {
        $this->aliases[$name] = $abstract;
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolving = [];
        $this->aliases = [];
    }

    public function resolve(string $abstract): object
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->aliases[$abstract];
        }

        if ($this->hasInstance($abstract)) {
            return $this->instances[$abstract];
        }

        $this->preventInfiniteRecursion($abstract);

        $this->resolving[$abstract] = true;

        try {
            $instance = $this->build(
                $this->concrete($abstract)
            );

            if ($this->isShared($abstract)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } finally {
            // Always cleanup resolving state,
            // even if dependency resolution fails.
            unset($this->resolving[$abstract]);
        }
    }

    public function call($callable)
    {
        $object = $this->resolve($callable[0]);
        $method = new ReflectionMethod($callable[0], $callable[1]);
        $arguments = $this->resolveArguments($method, $method->class);

        return $method->invokeArgs($object, $arguments);
    }

    private function isAlias(string $abstract): bool
    {
        return isset($this->aliases[$abstract]);
    }

    private function hasInstance(string $abstract): bool
    {
        return isset($this->instances[$abstract]);
    }

    /**
     * Prevent infinite recursion:
     * A -> B -> C -> A
     */
    private function preventInfiniteRecursion(string $abstract): void
    {
        if (isset($this->resolving[$abstract])) {
            throw new CircularDependencyException(
                "Circular dependency detected while resolving [{$abstract}]"
            );
        }
    }

    private function concrete(string $abstract): Closure|string
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

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

        $arguments = $this->constructorArguments($reflection);

        return $reflection->newInstanceArgs($arguments);
    }

    private function constructorArguments(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return [];
        }

        return $this->resolveArguments($constructor, $reflection->getName());
    }

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