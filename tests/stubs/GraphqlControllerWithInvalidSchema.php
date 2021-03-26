<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;

class GraphqlControllerWithInvalidSchema
{
    use HandlesGraphqlRequests;

    public function schema()
    {
        return 'type Query { things: [MissingThing]!';
    }
}
