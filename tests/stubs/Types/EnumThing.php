<?php

namespace Butler\Graphql\Tests\Types;

use GraphQL\Type\Definition\ResolveInfo;

class EnumThing
{
    public function typeFieldEnum($source, $args, $context, ResolveInfo $info)
    {
        return $source['enum'];
    }

    public function typeFieldString($source, $args, $context, ResolveInfo $info)
    {
        return "{$source['enum']->name}:{$source['enum']->value}";
    }

    public function subEnum($source, $args, $context, ResolveInfo $info)
    {
        return \SubEnum::BAZ;
    }
}
