<?php

namespace Butler\Graphql\Tests\Queries;

use Exception;

class ResolveUnionFromQuery
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['name' => 'Soundtrack 1', 'size' => 1024, 'encoding' => 'mp3', '__typename' => 'Audio'],
            (object) ['name' => 'Soundtrack 2', 'size' => 2048, 'encoding' => 'mp3', '__typename' => 'Audio'],
            ['name' => 'Soundtrack 3', 'size' => 4096, 'encoding' => 'mp3'],
            ['name' => 'Photo 1', 'size' => 256, 'height' => 100, 'width' => 200, '__typename' => 'Photo'],
            (object) ['name' => 'Video 2', 'size' => 512, 'length' => 3600, '__typename' => 'Video'],
        ];
    }

    public function resolveType($source)
    {
        if ($source['name'] ?? null === 'Soundtrack 3') {
            return 'Audio';
        }
        throw new Exception('Should never be reached');
    }
}
