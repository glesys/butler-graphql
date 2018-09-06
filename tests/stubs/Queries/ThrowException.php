<?php

namespace Butler\Graphql\Tests\Queries;

use Exception;

class ThrowException
{
    public function __invoke($root, $args, $context)
    {
        throw new Exception('An error occured');
    }
}
