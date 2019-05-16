<?php

namespace Butler\Graphql\Tests;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;

class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    public function test_data_loader_is_injectable()
    {
        $this->assertIsInjectable(\Butler\Graphql\DataLoader::class);
    }

    public function test_promise_adapter_is_injectable()
    {
        $this->assertIsInjectable(\GraphQL\Executor\Promise\PromiseAdapter::class);
    }

    public function test_include_debug_message_config()
    {
        $includeDebugMessage = $this->app->config->get('butler.graphql.include_debug_message');
        $this->assertFalse($includeDebugMessage);
    }

    public function test_include_trace_config()
    {
        $includeTrace = $this->app->config->get('butler.graphql.include_trace');
        $this->assertFalse($includeTrace);
    }

    public function test_namespace_config()
    {
        $namespace = $this->app->config->get('butler.graphql.namespace');
        $this->assertSame($namespace, '\\App\\Http\\Graphql\\');
    }

    public function test_schema_config()
    {
        $schema = $this->app->config->get('butler.graphql.schema');
        $this->assertStringContainsString('/app/Http/Graphql/schema.graphql', $schema);
    }
}
