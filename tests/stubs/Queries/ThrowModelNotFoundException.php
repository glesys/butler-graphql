<?php

namespace Butler\Graphql\Tests\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ThrowModelNotFoundException
{
    public function __invoke($root, $args, $context)
    {
        throw (new ModelNotFoundException())->setModel('Dummy');
    }
}
