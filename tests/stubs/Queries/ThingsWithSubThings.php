<?php

namespace Butler\Graphql\Tests\Queries;

use Butler\Graphql\Tests\TypedSubThing;
use Butler\Graphql\Tests\TypedThing;

class ThingsWithSubThings
{
    public function __invoke($root, $args, $context)
    {
        return collect([
            new TypedThing('thing', collect([
                new TypedSubThing('sub-thing-foo', new TypedThing('foo', collect())),
                new TypedSubThing('sub-thing-bar', new TypedThing('bar', collect())),
            ]))
        ]);
    }
}
