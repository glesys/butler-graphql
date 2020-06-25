<?php

namespace Butler\Graphql\Tests\Types;

use Butler\Graphql\Tests\TypedSubThing;

class SubThing
{
    public function name(TypedSubThing $subThing)
    {
        return $subThing->name;
    }
}
