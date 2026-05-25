<?php

namespace Tests\Fixtures\Circular;

class CircleB
{
    public function __construct(
        public CircleC $c
    ) {
        //
    }
}