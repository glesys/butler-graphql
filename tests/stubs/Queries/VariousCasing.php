<?php

namespace Butler\Graphql\Tests\Queries;

class VariousCasing
{
    public function __invoke($root, $args, $context)
    {
        return [
            [
                'name' => 'Test 1',
                'camelCase' => 'using camelCase',
                'snakeCase' => 'using snakeCase',
            ],
            [
                'name' => 'Test 2',
                'camel_case' => 'using camel_case',
                'snake_case' => 'using snake_case',
            ],
            (object) [
                'name' => 'Test 3',
                'camel-case' => 'using camel-case',
                'snake-case' => 'using snake-case',
            ],
        ];
    }
}
