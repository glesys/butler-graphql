<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleMutationWithoutInput
{
    public $output = [
        'example' => 'ExampleType',
    ];

    public function resolve($root, $context, $args)
    {
    }
}
