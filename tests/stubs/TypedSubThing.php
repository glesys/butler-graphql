<?php

namespace Butler\Graphql\Tests;

use Illuminate\Contracts\Support\Arrayable;

class TypedSubThing implements Arrayable
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
        ];
    }
}
