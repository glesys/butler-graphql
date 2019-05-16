<?php

namespace Butler\Graphql\Tests\Types;

use GraphQL\Type\Definition\ResolveInfo;

class Thing
{
    public function dataLoaded($source, $args, $context, ResolveInfo $info)
    {
        return $context['loader'](function ($names) {
            return collect($names)->map(function ($name) {
                return strtoupper($name);
            });
        })->load($source['name']);
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
    }
}
