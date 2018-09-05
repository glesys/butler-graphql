<?php

namespace Butler\Graphql\Tests;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Illuminate\Http\Request;

class HandlesGraphqlRequestsTest extends AbstractTestCase
{
    public function setUp()
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
        $this->assertSame('internal', array_get($data, 'errors.0.category'));
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
        $this->assertSame('internal', array_get($data, 'errors.0.category'));
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
        $this->assertSame('internal', array_get($data, 'errors.0.category'));
    }

    public function test_model_not_found_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwModelNotFoundException }'
        ]));

        $this->assertSame('Dummy not found.', array_get($data, 'errors.0.message'));
        $this->assertSame('client', array_get($data, 'errors.0.category'));
    }

    public function test_validation_error()
    {
        $controller = $this->app->make(GraphqlController::class);
        $data = $controller(Request::create('/', 'POST', [
            'query' => 'query { throwValidationException }'
        ]));

        $this->assertSame('The given data was invalid.', array_get($data, 'errors.0.message'));
        $this->assertSame('validation', array_get($data, 'errors.0.category'));
        $this->assertSame(['foo' => ['The foo field is required.']], array_get($data, 'errors.0.validation'));
    }
}
