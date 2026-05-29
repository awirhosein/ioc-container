<?php

namespace Tests\Unit;

use Awirhosein\Container\Container;
use Awirhosein\Container\Exceptions\BindingResolutionException;
use Awirhosein\Container\Exceptions\CircularDependencyException;
use Awirhosein\Container\Exceptions\UnresolvableDependencyException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Alpha;
use Tests\Fixtures\Beta;
use Tests\Fixtures\Circular\CircleA;
use Tests\Fixtures\Contracts\Omega;
use Tests\Fixtures\Delta;
use Tests\Fixtures\Gamma;
use Tests\Fixtures\Primitive\WithDefaultValue;
use Tests\Fixtures\Primitive\WithPrimitiveParameter;
use Tests\Fixtures\WithBoundDependency;
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
        $resolved = $this->container->resolve(Alpha::class);

        $this->assertInstanceOf(Alpha::class, $resolved);
    }

    #[Test]
    public function resolves_single_dependency()
    {
        $resolved = $this->container->resolve(Beta::class);

        $this->assertInstanceOf(Beta::class, $resolved);
    }

    #[Test]
    public function resolves_recursive_dependencies()
    {
        $resolved = $this->container->resolve(Gamma::class);

        $this->assertInstanceOf(Gamma::class, $resolved);
        $this->assertInstanceOf(Beta::class, $resolved->beta);
        $this->assertInstanceOf(Alpha::class, $resolved->beta->alpha);
    }

    #[Test]
    public function resolves_bound_abstraction()
    {
        $this->container->bind(Omega::class, Alpha::class);

        $resolved = $this->container->resolve(Omega::class);

        $this->assertInstanceOf(Alpha::class, $resolved);
    }

    #[Test]
    public function resolves_closure_binding()
    {
        $this->container->bind(Beta::class, function (Container $container) {
            return new Beta($container->resolve(Alpha::class));
        });

        $resolved = $this->container->resolve(Beta::class);

        $this->assertInstanceOf(Beta::class, $resolved);
    }

    #[Test]
    public function resolves_bound_dependency_before_using_default_value()
    {
        $this->container->bind(Alpha::class, Delta::class);

        $resolved = $this->container->resolve(WithBoundDependency::class);

        $this->assertInstanceOf(Delta::class, $resolved->alpha);
    }

    #[Test]
    public function resolves_singleton_as_same_instance()
    {
        $this->container->singleton(Omega::class, Alpha::class);

        $first = $this->container->resolve(Omega::class);
        $second = $this->container->resolve(Omega::class);

        $this->assertSame($first, $second);
        $this->assertInstanceOf(Alpha::class, $first);
    }

    #[Test]
    public function resolves_registered_instance()
    {
        $instance = new WithPrimitiveParameter('John Doe');
        $this->container->instance(WithPrimitiveParameter::class, $instance);

        $resolved = $this->container->resolve(WithPrimitiveParameter::class);

        $this->assertSame($instance, $resolved);
    }

    #[Test]
    public function resolves_alias()
    {
        $this->container->alias(Alpha::class, 'alpha');

        $resolved = $this->container->resolve('alpha');

        $this->assertInstanceOf(Alpha::class, $resolved);
    }

    #[Test]
    public function uses_default_value_for_primitive_parameter()
    {
        $resolved = $this->container->resolve(WithDefaultValue::class);

        $this->assertInstanceOf(WithDefaultValue::class, $resolved);
        $this->assertSame('default', $resolved->alpha);
        $this->assertSame('default with primitive', $resolved->beta);
    }

    #[Test]
    public function calls_method_with_injected_dependencies()
    {
        $result = $this->container->call([Delta::class, 'index']);

        $this->assertInstanceOf(Beta::class, $result);
    }

    #[Test]
    public function checks_if_abstract_is_bound()
    {
        $this->container->bind(Omega::class, Alpha::class);
        $this->assertTrue($this->container->bound(Omega::class));

        $this->container->singleton(Beta::class, Alpha::class);
        $this->assertTrue($this->container->bound(Beta::class));

        $this->container->instance(Alpha::class, new Alpha());
        $this->assertTrue($this->container->bound(Alpha::class));
    }

    #[Test]
    public function flushes_the_container()
    {
        $this->container->bind(Omega::class, Alpha::class);
        $this->container->singleton(Beta::class, Alpha::class);
        $this->container->instance(Alpha::class, new Alpha());

        $this->container->flush();

        $this->assertFalse($this->container->bound(Omega::class));
        $this->assertFalse($this->container->bound(Beta::class));
        $this->assertFalse($this->container->bound(Alpha::class));
    }

    #[Test]
    public function throws_exception_for_unbound_interface()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target [Omega] is not instantiable.');

        $this->container->resolve(Omega::class);
    }

    #[Test]
    public function throws_exception_for_unresolvable_primitive()
    {
        $this->expectException(UnresolvableDependencyException::class);
        $this->expectExceptionMessage(
            'Cannot resolve dependency [$name] in [Tests\Fixtures\Primitive\WithPrimitiveParameter]'
        );

        $this->container->resolve(WithPrimitiveParameter::class);
    }

    #[Test]
    public function throws_exception_for_circular_dependency()
    {
        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            'Circular dependency detected while resolving [Tests\Fixtures\Circular\CircleA]'
        );

        $this->container->resolve(CircleA::class);
    }
}