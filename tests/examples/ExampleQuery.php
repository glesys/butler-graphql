<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleQuery
{
    public $type = 'required|ExampleType[]';
    public $args = [
        'author' => [
            'type' => 'string',
            'description' => 'Filter posts by author',
            'defaultValue' => 'John Smith',
        ],
    ];

    public function resolve($root, $args, $context)
    {

    }
}
