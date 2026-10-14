<?php

namespace Butler\Graphql\Tests\Scalars;

use Butler\Graphql\Contracts\Scalar;
use GraphQL\Error\Error;
use GraphQL\Language\AST\ValueNode;

class Shout implements Scalar
{
    public function description(): string
    {
        return 'Shout from class.';
    }

    public function specifiedByURL(): string
    {
        return 'https://example.com/shout-from-class';
    }

    public function serialize(mixed $value): mixed
    {
        return strtoupper($value);
    }

    public function parseValue(mixed $value): mixed
    {
        throw new Error('Shout is output only.');
    }

    public function parseLiteral(ValueNode $valueNode, ?array $variables = null): mixed
    {
        throw new Error('Shout is output only.', $valueNode);
    }
}
