<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;
use GraphQL\Language\AST\DocumentNode;

class GraphqlControllerWithCompiledSchema
{
    use HandlesGraphqlRequests;

    public function parseDocument(): DocumentNode
    {
        throw new \Exception('Should never be called if a compiled schema is available.');
    }

    public function compiledSchemaPath(): ?string
    {
        return __DIR__ . '/schema-compiled.php';
    }
}
