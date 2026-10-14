<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;

class GraphqlControllerWithInvalidScalar
{
    use HandlesGraphqlRequests;

    public function schema()
    {
        return 'scalar NotAScalar type Query { foo: NotAScalar! }';
    }
}
