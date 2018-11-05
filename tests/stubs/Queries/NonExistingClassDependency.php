<?php

namespace Butler\Graphql\Tests\Queries;

class NonExistingClassDependency
{
    public function __construct(NonExistingClass $nonExistinObject)
    {
    }

    public function __invoke($root, $args, $context)
    {
        throw new \Exception("This query is used for testing. It should fail in the constructor and never reach the invoke.");
    }
}
