<?php

namespace Butler\Graphql\Tests\Queries;

class DataLoader
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['name' => 'Thing 1'],
            ['name' => 'Thing 2'],
        ];
    }
}
