<?php

namespace Awirhosein\Container;

use Awirhosein\Container\Exceptions\ContainerException;
use ReflectionClass;

class Container
{
    private array $bindings = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function resolve(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->resolve($this->bindings[$abstract]);
        }

        $reflection = new ReflectionClass($abstract);

        if (! $reflection->isInstantiable()) {
            throw new ContainerException("Traget [{$reflection->getShortName()}] is not instantiable.");
        }

        $dependencies = $this->dependencies($reflection);

        return $reflection->newInstanceArgs($dependencies);
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