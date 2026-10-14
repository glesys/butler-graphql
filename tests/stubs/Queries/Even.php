<?php

namespace Butler\Graphql\Tests\Queries;

class Even
{
    public function __invoke($root, $args, $context)
    {
        return $args['value'];
    }
}
