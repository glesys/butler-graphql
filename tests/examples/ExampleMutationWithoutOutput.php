<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleMutationWithoutOutput
{
    public $input = [
        'author' => [
            'type' => 'string',
        ],
    ];

    public function resolve($root, $context, $args)
    {
    }
}
