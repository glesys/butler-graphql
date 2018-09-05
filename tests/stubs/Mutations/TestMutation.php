<?php

namespace Butler\Graphql\Tests\Mutations;

class TestMutation
{
    public function __invoke($root, $args, $context)
    {
        $message = array_get($args, 'input.message');
        return compact('message');
    }
}
