<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;
use GraphQL\Type\Schema;
use GraphQL\Language\AST\DocumentNode;

class GraphqlControllerWithBeforeExecutionHook
{
    use HandlesGraphqlRequests;

    public $schema = null;
    public $query = null;
    public $operationName = null;
    public $variables = null;

    public function beforeExecutionHook(Schema $schema, DocumentNode $query, string $operationName = null, $variables = null): void
    {
        $this->schema = $schema;
        $this->query = $query;
        $this->operationName = $operationName;
        $this->variables = $variables;
    }
}
