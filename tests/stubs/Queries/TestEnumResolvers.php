<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Butler\Graphql\Tests\Queries;

require_once __DIR__ . '/../enums.php';

class TestEnumResolvers
{
    public function __invoke($root, $args, $context)
    {
        return [
            ['name' => 'Enum Thing 1', 'enum' => \ThingEnum::FOO],
            ['name' => 'Enum Thing 2', 'enum' => \ThingEnum::BAR],
        ];
    }
}
