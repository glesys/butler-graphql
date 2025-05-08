<?php

namespace Butler\Graphql\Tests\Queries;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;

class ThrowCustomErrorProvidingExtensions
{
    public function __invoke($root, $args, $context)
    {
        throw new class('This is a custom exception.') extends Exception implements ClientAware, ProvidesExtensions
        {
            public function isClientSafe(): bool
            {
                return true;
            }

            public function getExtensions(): array
            {
                return [
                    'category' => 'custom-category',
                    'foo' => 'bar',
                ];
            }
        };
    }
}
