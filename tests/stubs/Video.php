<?php

namespace Butler\Graphql\Tests;

class Video
{
    public function __construct(string $name, int $size, int $length)
    {
        $this->name = $name;
        $this->size = $size;
        $this->length = $length;
    }
}
