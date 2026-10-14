<?php

namespace Butler\Graphql\Tests\Scalars;

class NotAScalar
{
    public function serialize(mixed $value): mixed
    {
        return $value;
    }
}
