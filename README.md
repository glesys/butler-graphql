![.github/workflows/test.yml](https://github.com/glesys/butler-graphql/workflows/.github/workflows/test.yml/badge.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/glesys/butler-graphql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/glesys/butler-graphql/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/glesys/butler-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/glesys/butler-graphql/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/glesys/butler-graphql.svg)](https://packagist.org/packages/glesys/butler-graphql)
[![License](https://img.shields.io/github/license/glesys/butler-graphql.svg)](LICENCE)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/3288/badge)](https://bestpractices.coreinfrastructure.org/projects/3288)


# Butler GraphQL

Butler GraphQL is an opinionated package that makes it quick and easy to provide a GraphQL API using Laravel.

## Getting Started

1. Install the `glesys/butler-graphql` package.

```bash
composer require glesys/butler-graphql
```

_NOTE:_ If you're using Laravel < 5.5 or Lumen you need to register `Butler\Graphql\ServiceProvider::class` manually.

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

3. Create a resolver for the `pendingSignups` query.

```php
<?php

namespace App\Http\Graphql\Queries;

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
$router->post('/graphql', GraphqlController::class);
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

### Interfaces

Butler GraphQL supports the use of interfaces in your schema but needs a little bit of help to be able to know what type to use for resolving fields.

The easiest way to tell Butler GraphQL what type to use is to provide a `__typename` key or property in your data. For example:

```php
<?php

namespace App\Http\Graphql\Types;

class Post
{
    public function attachment($source, $args, $context, $info)
    {
        return [
            '__typename' => 'Photo',
            'height' => 200,
            'width' => 300,
        ];
    }
}
```

You can also use the `resolveTypeFor[Field]` in your parent's resolver to dynamically decide what type to use:

```php
<?php

namespace App\Http\Graphql\Types;

class Post
{
    public function attachment($source, $args, $context, $info)
    {
        return [
            'height' => 200,
            'width' => 300,
        ];
    }

    public function resolveTypeForAttachment($source, $context, $info)
    {
        if (isset($source['height'], $source['width'])) {
            return 'Photo';
        }
        if (isset($source['length'])) {
            return 'Video';
        }
    }
}
```

For queries and mutations you only have to define a `resolveType` method:

```php
<?php

namespace App\Http\Graphql\Queries;

use App\Attachment;

class Attachments
{
    public function __invoke($source, $args, $context, $info)
    {
        return Attachment::all();
    }

    public function resolveType($source, $context, $info)
    {
        return $source->type; // `Photo` or `Video`
    }
}
```

If none of the above are available Butler GraphQL will resort to the base class name of the data if it's an object.


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
            return Comment::whereIn('article_id', $articleIds)
                ->cursor()
                ->groupBy('article_id');
        }, [])->load($source->id);
    }
}
```

_NOTE:_ You can provide a default value as the second argument to the loader factory. This can be useful when you know some Articles might not have any Comments, like in the example above. Defaults to `null`.

#### Shared Data Loaders

If you have multiple resolvers working with the same underlying data you don't need to duplicate your code or deal with extra round trips to the database.

All you have to do is to define a separate loader function and reuse it in your resolvers:

```php
<?php

namespace App\Http\Graphql\Types;

use App\Models\Article;
use Closure;

class Article
{
    public function comments(Article $source, $args, $context, $info)
    {
        return $context['loader'](Closure::fromCallable([$this, 'loadComments']), [])
            ->load($source->id);
    }

    public function topVotedComment(Article $source, $args, $context, $info)
    {
        $comments = yield $context['loader'](Closure::fromCallable([$this, 'loadComments']))
            ->load($source->id);

        return collect($comments)->sortByDesc('votes')->first();
    }

    private function loadComments($articleIds)
    {
        return Comment::whereIn('article_id', $articleIds)
            ->cursor()
            ->groupBy('article_id');
    }
}
```

Butler GraphQL will make sure that `loadComments` is only called once.

If you don't want to use `Closure::fromCallable(...)` you can change the accessibility of `loadComments` to `public`.

## Splitting a Large Schema File Into Multiple Files

Butler GraphQL lets you easily split certain parts of your GraphQL schema file to separate files.

*schema.grapqhl*
```graphql
type Query {
    users: [User!]!
}

type User {
    id: ID!
    username: String!
}
```

*schema-user-attributes.grapqhl*
```graphql
extend type User {
    firstName: String
    lastName: String
    email: String!
}
```

*NOTE:* A base schema is always required and it's recommended to stay away from multiple levels of extensions.

## Customize

There's no real need to configure Butler GraphQL. It's designed with *convention over configuration* in mind and should be ready to go without any configuration.

If you want to override any of the available settings you can publish the configuration file using or just use the environment variables listed below.

```bash
php artisan vendor:publish
```

### Change the Schema Location and Namespaces

- `BUTLER_GRAPHQL_SCHEMA` – Defaults to `app_path('Http/Graphql/schema.graphql')`.
- `BUTLER_GRAPHQL_NAMESPACE` – Defaults to `'App\\Http\\Graphql\\'`.

### Change the Path and Pattern for Partial Schema Files

- `BUTLER_GRAPHQL_SCHEMA_EXTENSIONS_PATH` – Defaults to `app_path('Http/Graphql/')`.
- `BUTLER_GRAPHQL_SCHEMA_EXTENSIONS_GLOB` – Defaults to `'schema-*.graphql'`.

### Debugging

- `BUTLER_GRAPHQL_INCLUDE_DEBUG_MESSAGE` – Set to `true` to include the real error message in error responses. Defaults to `false`.
- `BUTLER_GRAPHQL_INCLUDE_TRACE` – Set to `true` to include stack traces in error responses. Defaults to `false`.

#### Debugbar

Butler GraphQL has support for automatically decorating responses with additional debug information when using [laravel-debugbar](https://github.com/barryvdh/laravel-debugbar). Details such as database queries and memory usage will automatically be available in the response _if barryvdh/laravel-debugbar is installed_.

To install and activate it, simply install `barryvdh/laravel-debugbar` as a `require-dev` dependency.

```
composer require barryvdh/laravel-debugbar --dev
```

When installed, make sure that `APP_DEBUG` is set to `true`, that's it.

Customizing what data to collect and include in the response is easily done by copying the [default config file](https://github.com/barryvdh/laravel-debugbar/blob/master/config/debugbar.php) to `config/debugbar.php` and adjust as needed.

### Authorization

If you need to authorize GraphQL request you can use the `beforeExecutionHook` method to inspect the schema and query and implement your authorization logic there.

```php
<?php

namespace App\Http\Controllers;

use Butler\Graphql\Concerns\HandlesGraphqlRequests;
use GraphQL\Type\Schema;
use GraphQL\Language\AST\DocumentNode;

class GraphqlController extends Controller
{
    use HandlesGraphqlRequests;

    /**
     * @param  $schema         A parsed version of your GraphQL schema
     * @param  $query          A parsed version of the consumer's query
     * @param  $operationName  The passed operation name (for when the query contains multiple operations)
     * @param  $variables      Any variables passed alongside the query
     */
    public function beforeExecutionHook(Schema $schema, DocumentNode $query, string $operationName = null, $variables = null): void
    {
        // TODO: Implement custom authorization logic here
    }
}
```

## How To Contribute

Development happens at GitHub; any typical workflow using Pull Requests are welcome. In the same spirit, we use the GitHub issue tracker for all reports (regardless of the nature of the report, feature request, bugs, etc.).

All changes are supposed to be covered by unit tests, if testing is impossible or very unpractical that warrants a discussion in the comments section of the pull request.

### Code standard

As the library is intended for use in Laravel applications we encourage code standard to follow [upstream Laravel practices](https://laravel.com/docs/master/contributions#coding-style) - in short that would mean [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).
