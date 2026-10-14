<?php

namespace Butler\Graphql\Tests\Queries;

class Shout
{
    public function __invoke($root, $args, $context)
    {
        return 'hello';
    }
}
