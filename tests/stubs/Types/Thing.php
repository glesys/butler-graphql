<?php

namespace Butler\Graphql\Tests\Types;

use GraphQL\Type\Definition\ResolveInfo;

class Thing
{
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

    // public function missingType($source, $args, $context, ResolveInfo $info)
    // {
    //     return $source;
    // }
}
