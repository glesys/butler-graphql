<?php

namespace Butler\Graphql\Tests\Queries;

class TestResolvers
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['name' => 'Thing 1'],
            (object)['name' => 'Thing 2'],
            ['name' => 'Thing 3', 'missing_type' => ['name' => 'Sub Thing']]
        ];
    }
}
