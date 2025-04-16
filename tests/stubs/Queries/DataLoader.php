<?php

namespace Butler\Graphql\Tests\Queries;

class DataLoader
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['id' => 1, 'name' => 'Thing 1'],
            ['id' => 2, 'name' => 'Thing 2'],
        ];
    }
}
