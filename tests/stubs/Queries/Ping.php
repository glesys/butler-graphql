<?php

namespace Butler\Graphql\Tests\Queries;

class Ping
{
    public function __invoke($root, $args, $context)
    {
        return 'pong';
    }
}
