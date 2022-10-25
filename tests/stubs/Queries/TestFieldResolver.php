<?php

namespace Butler\Graphql\Tests\Queries;

class TestFieldResolver
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['message' => null],
            ['message' => 'foo'],
            collect(),
            (object) ['message' => 'foo'],
        ];
    }
}
