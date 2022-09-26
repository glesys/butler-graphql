<?php

namespace Butler\Graphql\Tests\Queries;

class ResolveUnionFromType
{
    public function __invoke($root, $args, $context)
    {
        return [
            'name' => 'Thing 1',
            'media' => [
                'name' => 'Video 1',
                'size' => 1024,
                'length' => 3600,
            ],
        ];
    }
}
