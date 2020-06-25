<?php

namespace Butler\Graphql\Tests\Types;

use Butler\Graphql\Tests\TypedSubThing;
use Butler\Graphql\Tests\TypedThing;
use Closure;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;

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

        return $context['loader'](Closure::fromCallable([$this, 'sharedDataLoader']))
            ->load($source['name'])
            ->then('strrev');
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
