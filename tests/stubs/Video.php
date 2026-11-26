<?php

namespace Butler\Graphql\Tests;

class Video
{
    public function __construct(
        public string $name,
        public int $size,
        public int $length,
    ) {}
}
