<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;
use Illuminate\Database\Eloquent\Model;

class GraphqlControllerWithEloquentStrictness
{
    use HandlesGraphqlRequests;

    public function __construct()
    {
        Model::preventAccessingMissingAttributes(true);
    }
}
