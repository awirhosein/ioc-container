<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use ReflectionClass;

class Container
{
    private array $bindings = [];
    private array $singletons = [];
    private array $instances = [];
    private array $resolving = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
    }

    public function resolve(string $abstract): object
    {
        if ($this->hasInstance($abstract)) {
            return $this->instances[$abstract];
        }

        $concrete = $this->concrete($abstract);

        $this->preventInfiniteRecursion($abstract);

        $this->resolving[$abstract] = true;

        try {
            $reflection = new ReflectionClass($concrete);

            if (! $reflection->isInstantiable()) {
                throw new BindingResolutionException(
                    "Target [{$reflection->getShortName()}] is not instantiable."
                );
            }

            $dependencies = $this->dependencies($reflection);
            $instance = $reflection->newInstanceArgs($dependencies);

            if ($this->isSingleton($abstract)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } finally {
            // Always cleanup resolving state,
            // even if dependency resolution fails.
            unset($this->resolving[$abstract]);
        }
    }

    private function concrete(string $abstract): string
    {
        $concrete = $abstract;

        if ($this->isSingleton($abstract)) {
            $concrete = $this->singletons[$abstract];
        }

        if ($this->isBinding($abstract)) {
            $concrete = $this->bindings[$abstract];
        }

        return $concrete;
    }

    private function isBinding(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    private function isSingleton(string $abstract): bool
    {
        return isset($this->singletons[$abstract]);
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

    private function dependencies(ReflectionClass $reflection): array
    {
        $dependencies = [];
        $constructor = $reflection->getConstructor();

        if (! is_null($constructor)) {
            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $dependency = $parameter->getType();
                $dependencies[] = $this->resolve($dependency->getName());
            }
        }

        return $dependencies;
    }
}