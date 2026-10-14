<?php

namespace Butler\Graphql\Tests\Queries;

class Untouched
{
    public function __invoke($root, $args, $context)
    {
        return $args['value'];
    }
}
