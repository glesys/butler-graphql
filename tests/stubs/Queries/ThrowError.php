<?php

namespace Butler\Graphql\Tests\Queries;

use Error;

class ThrowError
{
    public function __invoke($root, $args, $context)
    {
        throw new Error('error');
    }
}
