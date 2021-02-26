## Upgrade from v4 to v5

### BREAKING: Promises are based on `amphp/amp` instead of `react/promise`

If you have any resolvers waiting for promises using the `->then()` method you need to replace these with the `yield` keyword.

**Example:**

```php
public function topVotedComment(Article $source, $args, $context, $info)
{
    return $context['loader'](Closure::fromCallable([$this, 'loadComments']))
        ->load($source->id)
        ->then(function ($articleComments) {
            return collect($articleComments)->sortByDesc('votes')->first();
        });
}
```

to

```php
public function topVotedComment(Article $source, $args, $context, $info)
{
    $comments = yield $context['loader'](Closure::fromCallable([$this, 'loadComments']))
        ->load($source->id);

    return collect($comments)->sortByDesc('votes')->first();
}
```

### BREAKING: The signature of `Butler\Graphql\DataLoader@__construct` has changed

If you manually instantiate `Butler\Graphql\DataLoader` anywhere you need to update your code. It's no longer necessary to provide a `React\EventLoop\LoopInterface` to the constructor.

**Example:**

```php
$dataLoader = new DataLoader($this->getLoop());
```

to

```php
$dataLoader = new DataLoader();
```
