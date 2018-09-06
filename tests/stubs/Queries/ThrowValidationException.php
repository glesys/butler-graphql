<?php

namespace Butler\Graphql\Tests\Queries;

class ThrowValidationException
{
    public function __invoke($root, $args, $context)
    {
        validator([], [
            'foo' => 'required|string',
        ])->validate();
    }
}
