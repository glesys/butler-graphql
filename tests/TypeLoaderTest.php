<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Lexer;
use Butler\Graphql\Tests\Examples\ExampleMutation;
use Butler\Graphql\Tests\Examples\ExampleMutationWithoutInput;
use Butler\Graphql\Tests\Examples\ExampleMutationWithoutOutput;
use Butler\Graphql\Tests\Examples\ExampleMutationWithoutResolve;
use Butler\Graphql\Tests\Examples\ExampleQuery;
use Butler\Graphql\Tests\Examples\ExampleQueryWithoutResolve;
use Butler\Graphql\Tests\Examples\ExampleQueryWithoutType;
use Butler\Graphql\Tests\Examples\ExampleType;
use Butler\Graphql\TypeLoader;
use Butler\Graphql\TypeRegistry;
use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;

class TypeLoaderTest extends TestCase
{
    protected $typeRegistry;

    public function setUp()
    {
        $this->typeRegistry = Mockery::mock(TypeRegistry::class);
    }

    protected function typeLoader(): TypeLoader
    {
        return new TypeLoader(
            new TypeRegistry(
                new Lexer(['Butler\Graphql\Tests\Examples'])
            )
        );
    }

    public function test_load_mutations_throws_on_mutation_without_input()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Butler\Graphql\Tests\Examples\ExampleMutationWithoutInput must have a `input` property');
        $this->typeLoader()->loadMutations([ExampleMutationWithoutInput::class]);
    }

    public function test_load_mutations_throws_on_mutation_without_output()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Butler\Graphql\Tests\Examples\ExampleMutationWithoutOutput must have a `output` property');
        $this->typeLoader()->loadMutations([ExampleMutationWithoutOutput::class]);
    }

    public function test_load_mutations_throws_on_mutation_without_resolve()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Butler\Graphql\Tests\Examples\ExampleMutationWithoutResolve must have a `resolve` method');
        $this->typeLoader()->loadMutations([ExampleMutationWithoutResolve::class]);
    }

    public function test_load_mutations()
    {
        $mutations = $this->typeLoader()->loadMutations([ExampleMutation::class]);

        $this->assertEquals([
            'name' => 'Mutations',
            'fields' => [
                [
                    'args' => [
                        'input' => new InputObjectType([
                            'name' => 'ExampleMutationInput',
                            'fields' => [
                                'author' => [
                                    'type' => new StringType(),
                                    'description' => 'The new author name',
                                ],
                            ],
                        ]),
                    ],
                    'type' => new ObjectType([
                        'name' => 'ExampleMutationOutput',
                        'fields' => [
                            'example' => new ObjectType([
                                'name' => 'ExampleType',
                                'description' => null,
                                'fields' => [
                                    'author' => [
                                        'description' => 'A property on Example',
                                        'type' => new StringType(),
                                    ],
                                ],
                            ]),
                        ],
                    ]),
                    'resolve' => function () {
                    },
                    'description' => null,
                ],
            ],
        ], $mutations->config, 'create mutation correctly');
    }

    public function test_load_queries_throws_on_mutation_without_type()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Butler\Graphql\Tests\Examples\ExampleQueryWithoutType must have a `type` property');
        $this->typeLoader()->loadQueries([ExampleQueryWithoutType::class]);
    }

    public function test_load_queries_throws_on_mutation_without_resolve()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Butler\Graphql\Tests\Examples\ExampleQueryWithoutResolve must have a `resolve` method');
        $this->typeLoader()->loadQueries([ExampleQueryWithoutResolve::class]);
    }

    public function test_load_queries()
    {
        $rootQuery = $this->typeLoader()->loadQueries([ExampleQuery::class]);
        $this->assertEquals([
            'name' => 'Queries',
            'fields' => [
                [
                    'args' => [
                        [
                            'name' => 'author',
                            'defaultValue' => 'John Smith',
                            'description' => 'Filter posts by author',
                            'type' => new StringType(),
                        ],
                    ],
                    'resolve' => function () {
                    },
                    'type' => new NonNull(
                        new ListOfType(
                            new ObjectType([
                                'name' => 'ExampleType',
                                'description' => null,
                                'fields' => [
                                    'author' => [
                                        'description' => 'A property on Example',
                                        'type' => new StringType(),
                                    ],
                                ],
                            ])
                        )
                    ),
                ],
            ],
        ], $rootQuery->config);
    }
}
