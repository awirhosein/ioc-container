<?php

namespace Tests\Unit;

use Awirhosein\Container\Container;
use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Alpha;
use Tests\Fixtures\Beta;
use Tests\Fixtures\Circular\CircleA;
use Tests\Fixtures\Contracts\Omega;
use Tests\Fixtures\Gamma;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    #[Test]
    public function resolves_plain_class()
    {
        $resolve = $this->container->resolve(Alpha::class);

        $this->assertInstanceOf(Alpha::class, $resolve);
    }

    #[Test]
    public function resolves_single_dependency()
    {
        $resolve = $this->container->resolve(Beta::class);

        $this->assertInstanceOf(Beta::class, $resolve);
    }

    #[Test]
    public function resolves_recursive_dependencies()
    {
        $resolve = $this->container->resolve(Gamma::class);

        $this->assertInstanceOf(Gamma::class, $resolve);
        $this->assertInstanceOf(Beta::class, $resolve->beta);
        $this->assertInstanceOf(Alpha::class, $resolve->beta->alpha);
    }

    #[Test]
    public function resolves_bound_abstraction()
    {
        $this->container->bind(Omega::class, Alpha::class);

        $resolve = $this->container->resolve(Omega::class);

        $this->assertInstanceOf(Alpha::class, $resolve);
    }

    #[Test]
    public function throws_exception_for_unbound_interface()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target [Omega] is not instantiable.');

        $this->container->resolve(Omega::class);
    }

    #[Test]
    public function detects_circular_dependency()
    {
        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            'Circular dependency detected while resolving [Tests\Fixtures\Circular\CircleA]'
        );

        $this->container->resolve(CircleA::class);
    }

    #[Test]
    public function resolves_singleton_as_same_instance()
    {
        $this->container->singleton(Omega::class, Alpha::class);

        $firstResolve = $this->container->resolve(Omega::class);
        $secondResolve = $this->container->resolve(Omega::class);

        $this->assertInstanceOf(Alpha::class, $firstResolve);
        $this->assertSame($firstResolve, $secondResolve);
    }
}