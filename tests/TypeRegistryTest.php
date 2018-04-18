<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Lexer;
use Butler\Graphql\Tests\Examples\ExampleType;
use Butler\Graphql\TypeRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use Mockery;
use PHPUnit\Framework\TestCase;

class TypeRegistryTest extends TestCase
{
    protected $typeRegistry;
    protected $lexer;

    public function setUp()
    {
        $this->lexer = Mockery::mock(Lexer::class);
    }

    public function test_input_type()
    {
        $typeRegistry = new TypeRegistry($this->lexer);
        $inputType = $typeRegistry->inputType('ExampleInput', [
            'name' => 'ExampleInput',
            'fields' => [
                'author' => [
                    'type' => Type::string(),
                    'description' => 'The author',
                ],
            ],
        ]);

        $this->assertInstanceOf(InputObjectType::class, $inputType);
        $this->assertEquals('ExampleInput', $inputType->name);
    }

    public function test_output_type()
    {
        $typeRegistry = new TypeRegistry($this->lexer);
        $outputType = $typeRegistry->outputType('ExampleOutput', [
            'name' => 'ExampleOutput',
            'fields' => [
                'example' => [
                    'type' => new ExampleType(),
                    'description' => 'The example',
                ],
            ],
        ]);

        $this->assertInstanceOf(ObjectType::class, $outputType);
        $this->assertEquals('ExampleOutput', $outputType->name);
    }

    public function test_type_resolves_recursively_and_instantiates_objects()
    {
        $this->lexer
            ->shouldReceive('evaluate')
            ->once()
            ->with('ExampleType[]')
            ->andReturn([ListOfType::class, ExampleType::class]);

        $this->lexer
            ->shouldReceive('evaluate')
            ->once()
            ->with('string')
            ->andReturn([StringType::class]);

        $typeRegistry = new TypeRegistry($this->lexer);

        $instance = $typeRegistry->type('ExampleType[]');

        $this->assertInstanceOf(ListOfType::class, $instance, 'returns list');
        $this->assertInstanceOf(ObjectType::class, $instance->getWrappedType(), 'list of ObjectType');
        $this->assertEquals('ExampleType', $instance->getWrappedType()->name, 'name is correct');
    }

    public function test_type_returns_same_instance_of_class()
    {
        $this->lexer
            ->shouldReceive('evaluate')
            ->twice()
            ->with('ExampleType')
            ->andReturn([ExampleType::class]);

        $this->lexer
            ->shouldReceive('evaluate')
            ->twice()
            ->with('string')
            ->andReturn([StringType::class]);

        $typeRegistry = new TypeRegistry($this->lexer);

        $instance1 = $typeRegistry->type('ExampleType');
        $instance2 = $typeRegistry->type('ExampleType');

        $this->assertSame($instance1, $instance2, 'only returns one instance');
    }

    public function test_type_returns_unique_instances_of_wrapping_types()
    {
        $typeRegistry = new TypeRegistry($this->lexer);

        $instance1 = $typeRegistry->type([ListOfType::class, StringType::class]);
        $instance2 = $typeRegistry->type([ListOfType::class, StringType::class]);

        $this->assertNotSame($instance1, $instance2, 'returns different instances');
    }
}