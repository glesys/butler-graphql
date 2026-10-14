<?php

namespace Butler\Graphql\Contracts;

use GraphQL\Language\AST\ValueNode;

interface Scalar
{
    /**
     * Convert a value returned by a resolver into its JSON representation.
     */
    public function serialize(mixed $value): mixed;

    /**
     * Convert a value given as a JSON variable into its internal representation.
     */
    public function parseValue(mixed $value): mixed;

    /**
     * Convert a value written inline in the query into its internal representation.
     */
    public function parseLiteral(ValueNode $valueNode, ?array $variables = null): mixed;
}
