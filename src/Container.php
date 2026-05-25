<?php

namespace Awirhosein\Container;

class Container
{
    public function resolve(string $abstract)
    {
        $reflection = new \ReflectionClass($abstract);

        $dependencies = [];
        $constructor = $reflection->getConstructor();

        if (!is_null($constructor)) {
            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $dependency = $parameter->getType();
                $dependencies[] = $this->resolve($dependency->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}