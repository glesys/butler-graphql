<?php

namespace Butler\Graphql\Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
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

        $this->assertSame('Hello World!', array_get($data, 'data.testMutation.message'));
    }

    public function test_data_loader()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { dataLoader { dataLoaded } }'
        ]));

        $this->assertSame(
            [
                'data' => [
                    'dataLoader' => [
                        ['dataLoaded' => 'THING 1'],
                        ['dataLoaded' => 'THING 2'],
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

        $this->assertFalse(array_has($data, 'errors.0.debugMessage'), 'debugMessage should not be included');
        $this->assertFalse(array_has($data, 'errors.0.trace'), 'trace should not be included');
        $this->assertSame('Internal server error', array_get($data, 'errors.0.message'));
        $this->assertSame('internal', array_get($data, 'errors.0.extensions.category'));
    }

    public function test_error_reporting_with_exception()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once();

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));
    }

    public function test_error_reporting_with_php_error()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once();

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

        $this->assertFalse(array_has($data, 'errors.0.trace'), 'trace should not be included');
        $this->assertSame('An error occured', array_get($data, 'errors.0.debugMessage'));
        $this->assertSame('Internal server error', array_get($data, 'errors.0.message'));
        $this->assertSame('internal', array_get($data, 'errors.0.extensions.category'));
    }

    public function test_error_with_trace()
    {
        $this->app->config->set('butler.graphql.include_trace', true);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwException }'
        ]));

        $this->assertFalse(array_has($data, 'errors.0.debugMessage'), 'debugMessage should not be included');
        $this->assertGreaterThan(10, array_get($data, 'errors.0.trace'));
        $this->assertSame('Internal server error', array_get($data, 'errors.0.message'));
        $this->assertSame('internal', array_get($data, 'errors.0.extensions.category'));
    }

    public function test_model_not_found_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwModelNotFoundException }'
        ]));

        $this->assertSame('Dummy not found.', array_get($data, 'errors.0.message'));
        $this->assertSame('client', array_get($data, 'errors.0.extensions.category'));
    }

    public function test_validation_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwValidationException }'
        ]));

        $this->assertSame('The given data was invalid.', array_get($data, 'errors.0.message'));
        $this->assertSame('validation', array_get($data, 'errors.0.extensions.category'));
        $this->assertSame(['foo' => ['The foo field is required.']], array_get($data, 'errors.0.extensions.validation'));
    }

    public function test_invalid_query()
    {
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once();

        $this->app->instance(ExceptionHandler::class, $handler);

        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
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

        $this->assertSame("Class Butler\Graphql\Tests\Queries\NonExistingClass does not exist", data_get($data, 'errors.0.debugMessage'));
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
}
