<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleMutationWithStatusField
{
    public $input = [];

    public $output = [
        'example' => 'ExampleType',
        'status' => 'int',
    ];

    public function resolve($root, $context, $args)
    {
    }
}
