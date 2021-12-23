<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Butler\Graphql\Tests\Types;

use Butler\Graphql\Tests\TypedSubThing;
use Butler\Graphql\Tests\TypedThing;
use Closure;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;

if (PHP_VERSION_ID >= 80100) {
    require __DIR__ . '/../enums.php';
}

class Thing
{
    public static $inlineDataLoaderInvokations = 0;
    public static $inlineDataLoaderResolves = 0;

    public static $sharedDataLoaderInvokations = 0;
    public static $sharedDataLoaderResolves = 0;

    public function dataLoaded($source, $args, $context, ResolveInfo $info)
    {
        self::$inlineDataLoaderResolves++;

        return $context['loader'](function ($names) {
            self::$inlineDataLoaderInvokations++;

            return collect($names)->map(function ($name) {
                return strtoupper($name);
            });
        })->load($source['name']);
    }

    public function dataLoadedByKey($source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function () {
            return [
                'Thing 1' => 'By key: Thing 1',
                'Thing 2' => 'By key: Thing 2',
            ];
        })->load($source['name']);
    }

    public function dataLoadedWithDefault($source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function () {
            return [
                'Thing 1' => 'Thing 1',
            ];
        }, 'default value')->load($source['name']);
    }

    public function dataLoadedUsingArray($source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function ($sources) {
            return collect($sources)->map(function ($source) {
                return "As array: {$source['name']}";
            });
        })->load((array) $source);
    }

    public function dataLoadedUsingObject($source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function ($sources) {
            return collect($sources)->map(function ($source) {
                return "As object: {$source->name}";
            });
        })->load((object) $source);
    }

    public function sharedDataLoader($names)
    {
        self::$sharedDataLoaderInvokations++;

        return collect($names)->map(function ($name) {
            return strtolower($name);
        });
    }

    public function sharedDataLoaderOne($source, $args, $context, ResolveInfo $info)
    {
        self::$sharedDataLoaderResolves++;

        return $context['loader']([$this, 'sharedDataLoader'])
            ->load($source['name']);
    }

    public function sharedDataLoaderTwo($source, $args, $context, ResolveInfo $info)
    {
        self::$sharedDataLoaderResolves++;

        return strrev(
            yield $context['loader'](Closure::fromCallable([$this, 'sharedDataLoader']))
                ->load($source['name'])
        );
    }

    public function subThings(TypedThing $source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function ($thingNames) {
            return collect($thingNames)->map(function ($thingName) {
                return collect([
                    new TypedSubThing("{$thingName} – Sub Thing 1"),
                    new TypedSubThing("{$thingName} – Sub Thing 2"),
                ]);
            });
        })->load($source->name);
    }

    public function typeField($source, $args, $context, ResolveInfo $info)
    {
        return 'typeField value';
    }

    public function typeFieldWithClosure($source, $args, $context, ResolveInfo $info)
    {
        return function () {
            return 'typeFieldWithClosure value';
        };
    }

    public function typeFieldWithEnum($source, $args, $context, ResolveInfo $info)
    {
        if (function_exists('enum_exists') && enum_exists(\ThingStatus::class)) {
            return \ThingStatus::FOO;
        }

        return 'foo';
    }

    public function resolveTypeForAttachment($source, $context, ResolveInfo $info)
    {
        if (is_array($source) && $source['name'] === 'Attachment 1') {
            return 'Photo';
        }
        throw new Exception('Should never be reached');
    }

    public function resolveTypeForMedia($source, $context, ResolveInfo $info)
    {
        if (is_array($source) && $source['name'] === 'Video 1') {
            return 'Video';
        }
        throw new Exception('Should never be reached');
    }
}
