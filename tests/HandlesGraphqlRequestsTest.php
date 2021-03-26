<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Tests\Types\Thing;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Mockery;

class HandlesGraphqlRequestsTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app->config->set('butler.graphql.namespace', '\\Butler\\Graphql\\Tests\\');
        $this->app->config->set('butler.graphql.schema', __DIR__ . '/stubs/schema.graphql');
    }

    public function test_resolvers()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);
        $this->app->config->set('butler.graphql.include_trace', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { testResolvers { name, typeField, typeFieldWithClosure, missingType { name } } }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'testResolvers' => [
                        [
                            'name' => 'Thing 1',
                            'typeField' => 'typeField value',
                            'typeFieldWithClosure' => 'typeFieldWithClosure value',
                            'missingType' => null,
                        ],
                        [
                            'name' => 'Thing 2',
                            'typeField' => 'typeField value',
                            'typeFieldWithClosure' => 'typeFieldWithClosure value',
                            'missingType' => null,
                        ],
                        [
                            'name' => 'Thing 3',
                            'typeField' => 'typeField value',
                            'typeFieldWithClosure' => 'typeFieldWithClosure value',
                            'missingType' => [
                                'name' => 'Sub Thing',
                            ],
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_mutation()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'mutation ($input: TestMutationInput) { testMutation(input: $input) { message } }',
            'variables' => [
                'input' => [
                    'message' => 'Hello World!',
                ],
            ],
        ]));

        $this->assertSame('Hello World!', Arr::get($data, 'data.testMutation.message'));
    }

    public function test_data_loader()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                dataLoader {
                    dataLoaded
                    dataLoadedByKey
                    dataLoadedUsingArray
                    dataLoadedUsingObject
                    dataLoadedWithDefault
                    sharedDataLoaderOne
                    sharedDataLoaderTwo
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'dataLoader' => [
                        [
                            'dataLoaded' => 'THING 1',
                            'dataLoadedByKey' => 'By key: Thing 1',
                            'dataLoadedUsingArray' => 'As array: Thing 1',
                            'dataLoadedUsingObject' => 'As object: Thing 1',
                            'dataLoadedWithDefault' => 'Thing 1',
                            'sharedDataLoaderOne' => 'thing 1',
                            'sharedDataLoaderTwo' => '1 gniht',
                        ],
                        [
                            'dataLoaded' => 'THING 2',
                            'dataLoadedByKey' => 'By key: Thing 2',
                            'dataLoadedUsingArray' => 'As array: Thing 2',
                            'dataLoadedUsingObject' => 'As object: Thing 2',
                            'dataLoadedWithDefault' => 'default value',
                            'sharedDataLoaderOne' => 'thing 2',
                            'sharedDataLoaderTwo' => '2 gniht',
                        ],
                    ],
                ],
            ],
            $data
        );

        $this->assertEquals(1, Thing::$inlineDataLoaderInvokations, 'inline data loader should only be invoked once');
        $this->assertEquals(2, Thing::$inlineDataLoaderResolves, 'inline data loader should be resolved multiple times');

        $this->assertEquals(1, Thing::$sharedDataLoaderInvokations, 'sharedDataLoader should only be invoked once');
        $this->assertEquals(4, Thing::$sharedDataLoaderResolves, 'sharedDataLoader should be resolved multiple times');
    }

    public function test_data_loader_with_collections()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { dataLoaderWithCollections { name subThings { name } } }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'dataLoaderWithCollections' => [
                        [
                            'name' => 'Thing 1',
                            'subThings' => [
                                ['name' => 'Thing 1 – Sub Thing 1'],
                                ['name' => 'Thing 1 – Sub Thing 2'],
                            ],
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));

        $this->assertFalse(Arr::has($data, 'errors.0.debugMessage'), 'debugMessage should not be included');
        $this->assertFalse(Arr::has($data, 'errors.0.trace'), 'trace should not be included');
        $this->assertSame('Internal server error', Arr::get($data, 'errors.0.message'));
        $this->assertSame('internal', Arr::get($data, 'errors.0.extensions.category'));
    }

    public function test_error_reporting_with_exception()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(Mockery::type(Exception::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));
    }

    public function test_error_reporting_with_php_error()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(Mockery::type(Error::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwError }'
        ]));
    }

    public function test_error_with_debug_message()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));

        $this->assertFalse(Arr::has($data, 'errors.0.trace'), 'trace should not be included');
        $this->assertSame('An error occured', Arr::get($data, 'errors.0.debugMessage'));
        $this->assertSame('Internal server error', Arr::get($data, 'errors.0.message'));
        $this->assertSame('internal', Arr::get($data, 'errors.0.extensions.category'));
    }

    public function test_error_with_trace()
    {
        $this->app->config->set('butler.graphql.include_trace', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));

        $this->assertFalse(Arr::has($data, 'errors.0.debugMessage'), 'debugMessage should not be included');
        $this->assertGreaterThan(10, Arr::get($data, 'errors.0.trace'));
        $this->assertSame('Internal server error', Arr::get($data, 'errors.0.message'));
        $this->assertSame('internal', Arr::get($data, 'errors.0.extensions.category'));
    }

    public function test_model_not_found_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwModelNotFoundException }'
        ]));

        $this->assertSame('Dummy not found.', Arr::get($data, 'errors.0.message'));
        $this->assertSame('client', Arr::get($data, 'errors.0.extensions.category'));
    }

    public function test_validation_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwValidationException }'
        ]));

        $this->assertSame('The given data was invalid.', Arr::get($data, 'errors.0.message'));
        $this->assertSame('validation', Arr::get($data, 'errors.0.extensions.category'));
        $this->assertSame(['foo' => ['The foo field is required.']], Arr::get($data, 'errors.0.extensions.validation'));
    }

    public function test_invalid_schema()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(Mockery::type(SyntaxError::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlControllerWithInvalidSchema::class);
        $controller(Request::create('/', 'POST', [
            'query' => 'hello world'
        ]));
    }

    public function test_invalid_query()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(Mockery::type(SyntaxError::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlController::class);
        $controller(Request::create('/', 'POST', [
            'query' => 'hello world'
        ]));
    }

    public function test_without_debugbar()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { ping }',
        ]));
        $this->assertSame(['data' => ['ping' => 'pong']], $data);
    }

    public function test_with_debugbar()
    {
        $debugBar = Mockery::mock(\stdClass::class);
        $debugBar->shouldReceive('isEnabled')->once()->andReturnTrue();
        $debugBar->shouldReceive('getData')->once()->andReturn(['queries' => 10]);

        $this->app->instance('debugbar', $debugBar);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { ping }',
        ]));

        $this->assertSame(
            [
                'data' => [
                    'ping' => 'pong',
                ],
                'debug' => [
                    'queries' => 10,
                ]
            ],
            $data
        );
    }

    public function test_fieldFromResolver_doesnt_swallow_errors()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);
        $this->app->config->set('butler.graphql.include_trace', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { nonExistingClassDependency }'
        ]));

        $this->assertContains(data_get($data, 'errors.0.debugMessage'), [
            "Class Butler\Graphql\Tests\Queries\NonExistingClass does not exist", // Laravel < 6.0
            "Target class [Butler\Graphql\Tests\Queries\NonExistingClass] does not exist.", // Laravel >= 6.0
        ]);
    }

    public function test_operationName()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query query1 { testResolvers { name } } query pingpong { ping }',
            'operationName' => 'pingpong',
        ]));

        $this->assertSame(
            [
                'data' => [
                    'ping' => 'pong',
                ],
            ],
            $data
        );
    }

    public function test_resolve_interface_from_query()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                resolveInterfaceFromQuery {
                    __typename
                    name
                    size
                    ... on Photo {
                        height
                        width
                    }
                    ... on Video {
                        length
                    }
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'resolveInterfaceFromQuery' => [
                        [
                            '__typename' => 'Photo',
                            'name' => 'Attachment 1',
                            'size' => 256,
                            'height' => 100,
                            'width' => 200,
                        ],
                        [
                            '__typename' => 'Video',
                            'name' => 'Attachment 2',
                            'size' => 1024,
                            'length' => 3600,
                        ],
                        [
                            '__typename' => 'Photo',
                            'name' => 'Attachment 3',
                            'size' => 512,
                            'height' => 100,
                            'width' => 200,
                        ],
                        [
                            '__typename' => 'Video',
                            'name' => 'Attachment 4',
                            'size' => 2048,
                            'length' => 7200,
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_resolve_interface_from_type()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                resolveInterfaceFromType {
                    name
                    attachment {
                        __typename
                        name
                    }
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'resolveInterfaceFromType' => [
                        'name' => 'Thing 1',
                        'attachment' => [
                            '__typename' => 'Photo',
                            'name' => 'Attachment 1',
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_resolve_union_from_query()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                resolveUnionFromQuery {
                    __typename
                    ...on Audio {
                        name
                        size
                        encoding
                    }
                    ...on Attachment {
                        name
                        size
                    }
                    ...on Photo {
                        height
                        width
                    }
                    ...on Video {
                        length
                    }
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'resolveUnionFromQuery' => [
                        [
                            '__typename' => 'Audio',
                            'name' => 'Soundtrack 1',
                            'size' => 1024,
                            'encoding' => 'mp3',
                        ],
                        [
                            '__typename' => 'Audio',
                            'name' => 'Soundtrack 2',
                            'size' => 2048,
                            'encoding' => 'mp3',
                        ],
                        [
                            '__typename' => 'Audio',
                            'name' => 'Soundtrack 3',
                            'size' => 4096,
                            'encoding' => 'mp3',
                        ],
                        [
                            '__typename' => 'Photo',
                            'name' => 'Photo 1',
                            'size' => 256,
                            'height' => 100,
                            'width' => 200,
                        ],
                        [
                            '__typename' => 'Video',
                            'name' => 'Video 2',
                            'size' => 512,
                            'length' => 3600,
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_resolve_union_from_type()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                resolveUnionFromType {
                    name
                    media {
                        __typename
                        ...on Attachment {
                            name
                            size
                        }
                        ...on Video {
                            length
                        }
                    }
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'resolveUnionFromType' => [
                        'name' => 'Thing 1',
                        'media' => [
                            '__typename' => 'Video',
                            'name' => 'Video 1',
                            'size' => 1024,
                            'length' => 3600,
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_resolves_false_correctly()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                resolvesFalseCorrectly {
                    id
                    requiredFlag
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'resolvesFalseCorrectly' => [
                        ['id' => '1', 'requiredFlag' => true],
                        ['id' => '2', 'requiredFlag' => false],
                        ['id' => '3', 'requiredFlag' => true],
                        ['id' => '4', 'requiredFlag' => false],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_various_casing()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                variousCasing {
                    name
                    camelCase
                    snake_case
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'variousCasing' => [
                        [
                            'name' => 'Test 1',
                            'camelCase' => 'using camelCase',
                            'snake_case' => 'using snakeCase',
                        ],
                        [
                            'name' => 'Test 2',
                            'camelCase' => 'using camel_case',
                            'snake_case' => 'using snake_case',
                        ],
                        [
                            'name' => 'Test 3',
                            'camelCase' => 'using camel-case',
                            'snake_case' => 'using snake-case',
                        ],
                    ],
                ],
            ],
            $data
        );
    }
    public function test_nested_collections()
    {
        $this->app->config->set('butler.graphql.include_debug_message', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query {
                thingsWithSubThings {
                    name
                    subThings {
                        name
                    }
                }
            }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'thingsWithSubThings' => [
                        [
                            'name' => 'thing',
                            'subThings' => [
                                ['name' => 'thing – Sub Thing 1'],
                                ['name' => 'thing – Sub Thing 2'],
                            ]
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_before_execution_hook()
    {
        $controller = $this->app->make(GraphqlControllerWithBeforeExecutionHook::class);

        $controller(Request::create('/', 'POST', [
            'query' => 'query monkey { foo } query { bar }',
            'operationName' => 'fooBar',
            'variables' => ['foo' => 'bar'],
        ]));

        $this->assertInstanceOf(Schema::class, $controller->schema);
        $this->assertInstanceOf(DocumentNode::class, $controller->query);
        $this->assertSame('fooBar', $controller->operationName);
        $this->assertEquals(['foo' => 'bar'], $controller->variables);
    }

    public function test_custom_schema()
    {
        $controller = $this->app->make(GraphqlControllerWithCustomSchema::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { foo }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'foo' => 'bar',
                ],
            ],
            $data
        );
    }
}
