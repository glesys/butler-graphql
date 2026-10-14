<?php

namespace Butler\Graphql\Tests\Scalars;

use Butler\Graphql\Contracts\Scalar;
use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ValueNode;

class Even implements Scalar
{
    public function description(): string
    {
        return 'An even integer.';
    }

    public function specifiedByURL(): string
    {
        return 'https://example.com/even';
    }

    public function serialize(mixed $value): mixed
    {
        if (! is_int($value) || $value % 2 !== 0) {
            throw new Error("Even cannot represent value: {$value}");
        }

        return $value;
    }

    public function parseValue(mixed $value): mixed
    {
        if (! is_int($value) || $value % 2 !== 0) {
            throw new Error("Even cannot represent value: {$value}");
        }

        return $value;
    }

    public function parseLiteral(ValueNode $valueNode, ?array $variables = null): mixed
    {
        if (! $valueNode instanceof IntValueNode || (int) $valueNode->value % 2 !== 0) {
            throw new Error('Even cannot represent literal', $valueNode);
        }

        return (int) $valueNode->value;
    }
}
