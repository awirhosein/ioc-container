<?php

namespace Tests\Unit;

use Awirhosein\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Something;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    #[Test]
    public function resolve_plain_class()
    {
        $container = new Container();

        $resolve = $container->resolve(Something::class);

        $this->assertInstanceOf(Something::class, $resolve);
    }
}