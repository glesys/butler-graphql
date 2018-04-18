<?php

namespace Butler\Graphql;

use Exception;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;

class TypeRegistry
{
    protected $instances = [];
    protected $inputTypes = [];
    protected $lexer;
    protected $outputTypes = [];

    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function inputType(string $name, array $config): InputObjectType
    {
        if (!array_key_exists($name, $this->inputTypes)) {
            $this->inputTypes[$name] = new InputObjectType($config);
        }

        return $this->inputTypes[$name];
    }

    public function outputType(string $name, array $config): ObjectType
    {
        if (!array_key_exists($name, $this->outputTypes)) {
            $this->outputTypes[$name] = new ObjectType($config);
        }

        return $this->outputTypes[$name];
    }

    public function type($type): Type
    {
        if (is_string($type)) {
            $type = $this->lexer->evaluate($type);
        }

        $class = array_shift($type);
        if (count($type) === 0) {
            return $this->spawn($class);
        }

        return $this->spawn($class, $this->type($type));
    }

    private function spawn(string $className, ...$args): Type
    {
        // WebOnyx instantiates scalar types by default so we have to use their internal type registry for those types.
        $scalarTypes = [
            IDType::class => Type::ID,
            StringType::class => Type::STRING,
            FloatType::class => Type::FLOAT,
            IntType::class => Type::INT,
            BooleanType::class => Type::BOOLEAN,
        ];

        if (array_key_exists($className, $scalarTypes)) {
            return Type::getInternalTypes()[$scalarTypes[$className]];
        }

        if (!array_key_exists($className, $this->instances)) {
            $instance = new $className(...$args);

            // If the object is not a child of Graphql\Type we assume it's a custom type with `name` and `fields`
            // properties. That means we have to instantiate an Object Type and resolve each field's type.
            if (!$instance instanceof Type) {
                if (!property_exists($instance, 'fields')) {
                    throw new Exception(get_class($instance) . ' must have a `fields` property');
                }

                $instance = new ObjectType([
                    'name' => data_get($instance, 'name', class_basename(get_class($instance))),
                    'description' => data_get($instance, 'description'),
                    'fields' => collect($instance->fields)->mapWithKeys(function ($config, $name) {
                        return [
                            $name => [
                                'description' => data_get($config, 'description'),
                                'type' => $this->type(data_get($config, 'type')),
                            ],
                        ];
                    })->all(),
                ]);
            }

            // If we encounter a "Wrapping Type" such as ListOf or NonNull we ignore the cache as we want
            // a fresh instance each time.
            if (is_null(data_get($instance, 'name'))) {
                return $instance;
            }

            $this->instances[$className] = $instance;
        }

        return $this->instances[$className];
    }
}