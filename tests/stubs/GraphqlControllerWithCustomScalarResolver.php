<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;
use Butler\Graphql\Contracts\Scalar;
use GraphQL\Language\AST\ValueNode;

class GraphqlControllerWithCustomScalarResolver
{
    use HandlesGraphqlRequests;

    public function schema()
    {
        return 'scalar Custom type Query { foo: Custom! }';
    }

    protected function resolveScalar(string $name): ?object
    {
        return $name === 'Custom'
            ? new class implements Scalar
            {
                public function serialize(mixed $value): mixed
                {
                    return "custom:{$value}";
                }

                public function parseValue(mixed $value): mixed
                {
                    return $value;
                }

                public function parseLiteral(ValueNode $valueNode, ?array $variables = null): mixed
                {
                    return $valueNode->value;
                }
            }
        : null;
    }
}
