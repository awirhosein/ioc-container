<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use Awirhosein\Container\Exceptions\UnresolvableDependencyException;
use Closure;
use ReflectionClass;

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

        $arguments = $this->arguments($reflection);

        return $reflection->newInstanceArgs($arguments);
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