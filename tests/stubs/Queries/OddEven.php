<?php

namespace Butler\Graphql\Tests\Queries;

class OddEven
{
    public function __invoke($root, $args, $context)
    {
        return 3;
    }
}
