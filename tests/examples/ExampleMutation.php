<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleMutation
{
    public $input = [
        'author' => [
            'type' => 'string',
            'description' => 'The new author name',
        ],
    ];

    public $output = [
        'example' => 'ExampleType',
    ];

    public function resolve($root, $context, $args)
    {
    }
}
