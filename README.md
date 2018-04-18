# Butler GraphQL

Butler GraphQL is an opinionated Laravel package that makes it quick and easy to provide GraphQL APIs. 

## Installation

```
composer require glesys/butler-graphql
```

## Routes

Butler GraphQL exposes a GraphQL controller which you can direct your routes to.

```
# routes/web.php

$router->get('/graphql', '\Butler\Graphql\GraphqlController');
$router->post('/graphql', '\Butler\Graphql\GraphqlController');
```

## Queries

Queries are responsible for defining what data they return and how the data is loaded. 

Create the file `app/Http/Graphql/Queries/Posts.php`.

```php
<?php

namespace App\Http\Graphql\Queries;

class Posts
{
    public $type = 'required|Post[]';
    public $args = [
        'author' => [
            'type' => 'string',
            'description' => 'Filter posts by author.',
        ],
    ];

    public function resolve($root, $args, $context)
    {
        $query = Post::query();
        
		if (isset($args['author'])) {
			$query = $query->where('author', $args['author']);
		}
        
		return $query->get();
    }
}
```

Then register your Query in config/graphql.php
```
<?php

return [
    'queries' => [
    	/App/Http/Graphql/Queries/Posts::class,
    ],
];

```

## Mutations

Mutations are used to change data. Mutation have an input type which describes the input data and an output type that
wraps the results.

Create the file `app/Http/Graphql/Mutations/UpdateTile.php`
```php
<?php

namespace App\Http\Graphql\Mutations;

class UpdateTitle
{
    public $input = [
        'id' => [
        	'type' => 'required|int',    
		],
        'title' => [
            'type' => 'required|string',
            'description' => 'The new title of the post.',
        ],
    ];
    public $output = [
        'post' => 'Post'
    ];

    public function resolve($root, $args, $context)
    {
        $post = Post::findOrFail($args['input']['id']);
        $post->title = $args['input']['title'];
        $post->save();
        
        return compact('post');
    }
}
```

Then register your Mutation in config/graphql.php
```
<?php

return [
    'mutations' => [
    	/App/Http/Graphql/Mutations/UpdateTitle::class,
    ],
];

```

## Types

Types defines how your data is structured.

```php
<?php

namespace App\Http\Graphql\Types;

class Post
{
    public $fields = [
        'id' => [
        	'type' => 'required|int',
        	'description' => 'Identifies a post.',    
		],
        'title' => [
            'type' => 'required|string',
            'description' => 'The title of the post.',
        ],
        'author' => [
            'type' => 'required|string',
            'description' => 'The name of the author who wrote this post.',
        ],
        'comments' => [
            'type' => 'Comment[]'
        ],
    ];
}
```

The name of the Type is the same as the class name by default. If you want to override the name you can define the `name`
property on the Type.

```php
<?php

namespace App\Http\Graphql\Types;

class Post
{
    public $name = "BlogPost";
}
```

Types are loaded from the `App\Http\Graphql\Types` namespace by default. If you want to load additional namespaces you
can add them to `namespaces` in `config/graphql.php`