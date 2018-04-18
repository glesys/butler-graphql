<?php

namespace Butler\Graphql\Tests\Examples;

class ExampleMutationWithoutResolve
{
    public $input = [
        'author' => [
            'type' => 'string',
        ],
    ];
    public $output = [
        'example' => 'ExampleType',
    ];
}
