<?php

namespace Awirhosein\Container;

class Container
{
    public function resolve(string $abstract)
    {
        return new $abstract;
    }
}