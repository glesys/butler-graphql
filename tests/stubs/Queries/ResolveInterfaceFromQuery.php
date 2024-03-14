<?php

namespace Butler\Graphql\Tests\Queries;

use Butler\Graphql\Tests\Video;
use GraphQL\Type\Definition\ResolveInfo;

class ResolveInterfaceFromQuery
{
    public function __invoke($root, $args, $context)
    {
        return [
            [
                'name' => 'Attachment 1',
                'size' => 256,
                '__typename' => 'Photo',
                'height' => 100,
                'width' => 200,
            ],
            (object) [
                'name' => 'Attachment 2',
                'size' => 1024,
                '__typename' => 'Video',
                'length' => 3600,
            ],
            [
                'name' => 'Attachment 3',
                'size' => 512,
                'height' => 100,
                'width' => 200,
            ],
            new Video('Attachment 4', 2048, 7200),
        ];
    }

    public function resolveType($source, $context, ResolveInfo $info)
    {
        if (is_array($source) && $source['name'] === 'Attachment 3') {
            return 'Photo';
        }
    }
}
