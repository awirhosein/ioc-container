<?php

namespace Tests\Unit;

use Awirhosein\Container\Container;
use Awirhosein\Container\Exceptions\ContainerException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Alpha;
use Tests\Fixtures\Beta;
use Tests\Fixtures\Contracts\Omega;
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

        $resolve = $container->resolve(Gamma::class);

        $this->assertInstanceOf(Gamma::class, $resolve);
    }

    #[Test]
    public function binding_abstraction_to_concrete()
    {
        $container = new Container();
        $container->bind(Omega::class, Alpha::class);

        $resolve = $container->resolve(Omega::class);

        $this->assertInstanceOf(Alpha::class, $resolve);
    }

    #[Test]
    public function throws_exception_when_resolving_unbound_interface()
    {
        $this->expectException(ContainerException::class);

        $container = new Container();
        
        $container->resolve(Omega::class);
    }
}