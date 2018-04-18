<?php

namespace Butler\Graphql;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use Illuminate\Support\Str;

class Lexer
{

    private $namespaces = [];
    private $typeMap = [
        'boolean' => BooleanType::class,
        'float' => FloatType::class,
        'id' => IDType::class,
        'int' => IntType::class,
        'string' => StringType::class,
        'required' => NonNull::class,
    ];

    /**
     * Lexer constructor.
     * @param array $namespaces
     */
    public function __construct(array $namespaces = [])
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @param string $source
     * @return array
     */
    public function evaluate(string $source): array
    {
        $expressions = collect(explode('|', $source))
            ->map(function ($expression) {
                return trim($expression);
            })
            ->values()
            ->filter()
            ->all();

        return $this->resolveTypes($expressions);
    }

    /**
     * @param array $expressions
     * @return array
     * @throws \Exception
     */
    private function resolveTypes(array $expressions): array
    {
        if (count($expressions) == 0) {
            return [];
        }

        $expression = array_shift($expressions);

        $base = [];
        if (Str::endsWith($expression, '[]')) {
            $base = [ListOfType::class];
            $expression = rtrim($expression, '[]');
        }

        if (array_key_exists($expression, $this->typeMap)) {
            array_push($base, $this->typeMap[$expression]);
            return array_merge($base, $this->resolveTypes($expressions));
        }

        foreach ($this->namespaces as $namespace) {
            $className = "{$namespace}\\{$expression}";

            if (class_exists($className)) {
                array_push($base, $className);
                return array_merge($base, $this->resolveTypes($expressions));
            }
        }

        throw new \Exception("Could not parse the GraphQL type definition: {$expression}");
    }
}