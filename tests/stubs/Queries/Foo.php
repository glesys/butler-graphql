<?php

namespace Butler\Graphql\Tests\Queries;

class Foo
{
    public function __invoke($root, $args, $context)
    {
        return 'bar';
    }
}
