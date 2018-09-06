[![Build Status](https://travis-ci.org/glesys/butler-graphql.svg)](https://travis-ci.org/glesys/butler-graphql)
[![Latest Stable Version](https://poser.pugx.org/glesys/butler-graphql/v/stable.svg)](https://packagist.org/packages/glesys/butler-graphql)
[![License](https://poser.pugx.org/glesys/butler-graphql/license.svg)](LICENSE)

# Butler GraphQL

Butler GraphQL is an opinionated package that makes it quick and easy to provide a GraphQL API using Laravel.

## Getting Started

1. Install the `glesys/butler-graphql` package.

```bash
composer require glesys/butler-graphql
```

2. Create a GraphQL schema file. The default location is `app/Http/Graphql/schema.graphql`.

```graphql
type Query {
    pendingSignups: [Signup!]!
}

type Signup {
    email: String!
    verificationToken: String!
}
```

3. Create a resolver for the `pendingSigups` query.

```php
<?php

namespace App\Http\Grapql\Queries;

class PendingSignups
{
    public function __invoke($root, $args, $context)
    {
        return Signups::where('status', 'pending')->get();
    }
}
```

4. Create a controller with the `Butler\Graphql\Concerns\HandlesGraphqlRequests` trait.

```php
<?php

namespace App\Http\Controllers;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;

class GraphqlController extends Controller
{
    use HandlesGraphqlRequests;
}
```

5. Add a route for your GraphQL API endpoint.

```php
$router->match(['get', 'post'], '/graphql', GraphqlController::class);
```

6. Use something like [GraphiQL](https://github.com/graphql/graphiql) or [Insomnia](https://insomnia.rest/) to interact with your GraphQL API.


## Digging Deeper

### Queries

Query resolvers are represented by classes in the `App\Http\Graphql\Queries` namespace. They should be named the same as the query but StudlyCased, i.e. `pendingSignups` => `PendingSignups`.

Queries are invoked as a callable so all you need to do is implement the `__invoke` method.

The following parameters are passed to all resolving methods:

```php
/**
 * @param  mixed  $root
 * @param  array  $args
 * @param  array  $context
 * @param  \GraphQL\Type\Definition\ResolveInfo  $info
 */
```

In addition to return arrayables and objects from the resolving methods you can also return callables that will be invoked with the same set of parameters.

```php
public function __invoke()
{
    return function ($root, $args, $context, $info) {
        //
    };
}
```

### Mutations

Mutation resolvers are represented by simple classes in the `App\Http\Graphql\Mutations` namespace.

Technically mutations and queries are the same thing. They can both accept arguments and return types with fields. Separating them are more of a convention than it is a requirement.

> In REST, any request might end up causing some side-effects on the server, but by convention it's suggested that one doesn't use GET requests to modify data. GraphQL is similar - technically any query could be implemented to cause a data write. However, it's useful to establish a convention that any operations that cause writes should be sent explicitly via a mutation.
>
> – https://graphql.org/learn/queries/#mutations

### Types

Resolving type fields is just as easy as queries and mutations. Define a simple class in the `App\Http\Graphql\Types` namespace and use camelCased method names for fields.

```php
<?php

namespace App\Http\Graphql\Types;

class Signup
{
    public function verificationToken($source, $args, $context, $info)
    {
        return $source->token;
    }
}
```

*NOTE:* If a field name in your GraphQL schema definition match the key (for arrayables) or property (for objects) of your source object, you don't need to define a resolver method for that field.

### N+1 and the Data Loader

Butler GraphQL includes a simple data loader to prevent n+1 issues when loading nested data. It's available in `$context['loader']` and really easy to use:

```php
<?php

namespace App\Http\Graphql\Types;

use App\Models\Article;

class Article
{
    public function comments(Article $source, $args, $context, $info)
    {
        return $context['loader'](function ($articleIds) {
            $comments = Comment::whereIn('article_id', $articleIds)->get();
            return collect($articleIds)->map(function ($articleId) use ($comments)) {
                return $comments->where('article_id', $articleId);
            });
        })->load($source->id);
    }
}
```

## Customize

There's no real need to configure Butler GraphQL. It's designed with *convention over configuration* in mind and should be ready to go without any configuration.

If you want to override any of the available settings you can publish the configuration file using or just use the environment variables listed below.

```bash
php artisan vendor:publish
```

### Change the Schema Location and Namespaces

- `BUTLER_GRAPHQL_SCHEMA` – Defaults to `app_path('Http/Graphql/schema.graphql')`.
- `BUTLER_GRAPHQL_NAMESPACE` – Defaults to `'\\App\\Http\\Graphql\\'`.

### Debugging

- `BUTLER_GRAPHQL_INCLUDE_DEBUG_MESSAGE` – Set to `true` to include the real error message in error responses. Defaults to `false`.
- `BUTLER_GRAPHQL_INCLUDE_TRACE` – Set to `true` to include stack traces in error responses. Defaults to `false`.
