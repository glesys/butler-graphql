<?php

namespace Butler\Graphql\Tests\Queries;

class ResolveInterfaceFromType
{
    public function __invoke($root, $args, $context)
    {
        return [
            'name' => 'Thing 1',
            'attachment' => [
                'name' => 'Attachment 1',
                'height' => 100,
                'width' => 200,
            ]
        ];
    }
}
