<?php

namespace Butler\Graphql\Tests;

use Illuminate\Support\Collection;

class TypedThing
{
    public $name;
    public $subThings;

    public function __construct(string $name, Collection $subThings)
    {
        $this->name = $name;
        $this->subThings = $subThings;
    }
}
