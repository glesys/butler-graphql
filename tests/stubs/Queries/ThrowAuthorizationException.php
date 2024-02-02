<?php

namespace Butler\Graphql\Tests\Queries;

use Illuminate\Auth\Access\AuthorizationException;

class ThrowAuthorizationException
{
    public function __invoke($root, $args, $context)
    {
        throw new AuthorizationException();
    }
}
