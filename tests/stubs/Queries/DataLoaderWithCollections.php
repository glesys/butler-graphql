<?php

namespace Butler\Graphql\Tests\Queries;

use Butler\Graphql\Tests\TypedThing;

class DataLoaderWithCollections
{
    public function __invoke($root, $args, $context)
    {
        return collect([
            new TypedThing('Thing 1', collect([
                ['name' => 'Thing 1'],
                ['name' => 'Thing 2'],
            ]))
        ]);
    }
}
