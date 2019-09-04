<?php

namespace Butler\Graphql\Tests\Mutations;

use Illuminate\Support\Arr;

class TestMutation
{
    public function __invoke($root, $args, $context)
    {
        $message = Arr::get($args, 'input.message');
        return compact('message');
    }
}
