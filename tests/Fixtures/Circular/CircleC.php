<?php

namespace Tests\Fixtures\Circular;

class CircleC
{
    public function __construct(
        public CircleA $a
    ) {
        //
    }
}