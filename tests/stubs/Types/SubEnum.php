<?php

namespace Butler\Graphql\Tests\Types;

class SubEnum
{
    public function name($source)
    {
        return strrev($source->name);
    }
}
