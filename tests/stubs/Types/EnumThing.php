<?php

namespace Butler\Graphql\Tests\Types;

use GraphQL\Type\Definition\ResolveInfo;

class EnumThing
{
    public function fieldBackedByResolverForEnum(\ThingStatus $source, $args, $context, ResolveInfo $info)
    {
        return "{$source->name}:{$source->value}";
    }
}
