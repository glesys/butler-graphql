# Butler GraphQL

Butler GraphQL is an opinionated package that makes it quick and easy to provide GraphQL APIs using Laravel.

## Getting Started

1. Install the `glesys/butler-graphql` package.

```bash
composer require glesys/butler-graphql
```

2. Add the Service Provider to your `config/app.php` config.

```php
'providers' => [

    \Butler\Graphql\ServiceProvider::class,

]
```

3. Create a GraphQL schema file. The default location is `app/Http/Graphql/schema.graphql`.

```graphql
type Query {
    things: [Thing!]!
}

type Thing {
    name: String!
}
```

4. Create a query resolver. The default namespace of queries, mutations and types are `App\Http\Graphql\Queries`, `App\Http\Graphql\Mutations` and `App\Http\Graphql\Types`.

```php
<?php

namespace App\Http\Grapql\Queries;

class Things
{
    public function __invoke($root, $args, $context)
    {
        return Thing::all();
    }
}
```

5. Add the `Butler\Graphql\Concerns\HandlesGraphqlRequests` trait to one of your
    controllers.

```php
<?php

namespace App\Http\Controllers;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;

class GraphqlController extends Controller
{
    use HandlesGraphqlRequests;
}
```

5. Add a route for the GraphQL API endpoint.

```php

$router->match(['get', 'post'], '/graphql', \App\Http\Controllers\GraphqlController::class);

```

6. Use something like [GraphiQL](https://github.com/graphql/graphiql) or [Insomnia](https://insomnia.rest/) to interact with your GraphQL API.


## Digging Deeper

### Queries

Queries are represented by classes in the `App\Http\Graphql\Queries` namespace. They should be named the same as the query but CamelCased, i.e. `pendingSignups` => `PendingSignups`.

Queries are invoked as callables so all you need to do is implement the `__invoke` method.

```php

class PendingSignups
{
    /**
     * @param  mixed  $root
     * @param  array  $args
     * @param  array  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $info
     */
    public function __invoke($root, $args, $context, $info)
    {
        //
    }
}
```

### Mutations

### Types
