<?php

namespace Tests\Fixtures;

class WithBoundDependency
{
    public function __construct(
        public Alpha $alpha = new Alpha()
    ) {
        //
    }
}