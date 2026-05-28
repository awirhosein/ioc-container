<?php

namespace Tests\Fixtures\Primitive;

class WithDefaultValue
{
    public function __construct(
        public $alpha = 'default',
        public string $beta = 'default with primitive',
    ) {
        //
    }
}