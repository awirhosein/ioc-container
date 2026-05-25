<?php

namespace Tests\Fixtures\Circular;

class CircleA
{
    public function __construct(
        public CircleB $b
    ) {
        //
    }
}