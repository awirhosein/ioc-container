<?php

namespace Tests\Fixtures;

class Delta extends Alpha
{
    public function index(Beta $beta): Beta
    {
        return $beta;
    }
}