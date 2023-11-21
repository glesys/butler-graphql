<?php

namespace Butler\Graphql\Tests\Queries;

class ExtendedQuery
{
    public function __invoke()
    {
        return [
            'field1' => 'This is field 1',
            'field2' => 'This is field 2',
        ];
    }
}
