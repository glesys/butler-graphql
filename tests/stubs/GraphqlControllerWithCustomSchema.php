<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;

class GraphqlControllerWithCustomSchema
{
    use HandlesGraphqlRequests;

    public function schema()
    {
        return 'type Query { foo: String! }';
    }
}
