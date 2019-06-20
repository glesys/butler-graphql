<?php

namespace Butler\Graphql\Tests\Queries;

class ResolvesFalseCorrectly
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['id' => 1, 'requiredFlag' => true],
            ['id' => 2, 'requiredFlag' => false],
            (object)['id' => 3, 'requiredFlag' => true],
            (object)['id' => 4, 'requiredFlag' => false],
        ];
    }
}
