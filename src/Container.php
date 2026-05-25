<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use ReflectionClass;

class Container
{
    private array $bindings = [];
    private array $resolving = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function resolve(string $abstract): object
    {
        // Prevent infinite recursion:
        // A -> B -> C -> A
        if (isset($this->resolving[$abstract])) {
            throw new CircularDependencyException(
                "Circular dependency detected while resolving [{$abstract}]"
            );
        }

        $this->resolving[$abstract] = true;

        try {
            if (isset($this->bindings[$abstract])) {
                $abstract = $this->bindings[$abstract];
            }

            $reflection = new ReflectionClass($abstract);

            if (! $reflection->isInstantiable()) {
                throw new BindingResolutionException(
                    "Target [{$reflection->getShortName()}] is not instantiable."
                );
            }

            $dependencies = $this->dependencies($reflection);

            return $reflection->newInstanceArgs($dependencies);
        } finally {
            // Always cleanup resolving state,
            // even if dependency resolution fails.
            unset($this->resolving[$abstract]);
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