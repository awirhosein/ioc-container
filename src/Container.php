<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use Awirhosein\Container\Exceptions\UnresolvableDependencyException;
use ReflectionClass;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $resolving = [];

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

    public function resolve(string $abstract): object
    {
        if ($this->hasInstance($abstract)) {
            return $this->instances[$abstract];
        }

        $this->preventInfiniteRecursion($abstract);

        $concrete = $this->concrete($abstract);

        $this->resolving[$abstract] = true;

        try {
            $reflection = new ReflectionClass($concrete);

            if (! $reflection->isInstantiable()) {
                throw new BindingResolutionException(
                    "Target [{$reflection->getShortName()}] is not instantiable."
                );
            }

            $arguments = $this->arguments($reflection);
            $instance = $reflection->newInstanceArgs($arguments);

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

    private function concrete(string $abstract): string
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    private function arguments(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return [];
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
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
                "Cannot resolve dependency [\${$parameter->getName()}] in [{$reflection->getName()}]"
            );
        }

        return $arguments;
    }

    private function isShared(string $abstract): bool
    {
        return $this->bindings[$abstract]['shared'] ?? false;
    }
}