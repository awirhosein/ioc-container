<?php

namespace Tests\Unit;

use Awirhosein\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Alpha;
use Tests\Fixtures\Beta;
use Tests\Fixtures\Gamma;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    #[Test]
    public function resolve_plain_class()
    {
        $container = new Container();
        $resolve = $container->resolve(Alpha::class);

        $this->assertInstanceOf(Alpha::class, $resolve);
    }

    #[Test]
    public function resolve_single_dependency()
    {
        $container = new Container();
        $resolve = $container->resolve(Beta::class);

        $this->assertInstanceOf(Beta::class, $resolve);
    }

    #[Test]
    public function resolve_recursive_dependencies()
    {
        $container = new Container();
        $resolved = $container->resolve(Gamma::class);

        $this->assertInstanceOf(Gamma::class, $resolved);
    }
}