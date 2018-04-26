<?php

namespace Butler\Graphql;

use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;

class TypeLoader
{
    /** @var TypeRegistry */
    private $typeRegistry;

    /**
     * TypeLoader constructor.
     * @param TypeRegistry $typeRegistry
     */
    public function __construct(TypeRegistry $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * @param array $mutations
     * @throws Exception
     * @return ObjectType
     */
    public function loadMutations(array $mutations): ObjectType
    {
        return new ObjectType([
            'name' => 'Mutations',
            'fields' => collect($mutations)->mapWithKeys(function ($className, $name) {
                return [$name => $this->fieldsForMutation($className)];
            })->all(),
        ]);
    }

    /**
     * @param array $queries
     * @return ObjectType
     */
    public function loadQueries(array $queries): ObjectType
    {
        return new ObjectType([
            'name' => 'Queries',
            'fields' => collect($queries)->mapWithKeys(function ($className, $name) {
                return [$name => $this->fieldsForQuery($className)];
            })->all(),
        ]);
    }

    /**
     * @param string $className
     * @param array $config
     * @return InputObjectType
     */
    private function fieldsForInputType(string $className, array $config): InputObjectType
    {
        $typeName = class_basename($className) . 'Input';
        return $this->typeRegistry->inputType($typeName, [
            'name' => $typeName,
            'fields' => collect($config)->mapWithKeys(function ($data, $name) {
                return [
                    $name => [
                        'type' => $this->typeRegistry->type(data_get($data, 'type')),
                        'description' => data_get($data, 'description'),
                    ],
                ];
            })->all(),
        ]);
    }

    /**
     * @param string $className
     * @return array
     */
    private function fieldsForMutation(string $className): array
    {
        $mutationInstance = new $className();

        $this->validateMutation($mutationInstance);

        return [
            'args' => [
                'input' => $this->fieldsForInputType($className, $mutationInstance->input),
            ],
            'description' => data_get($mutationInstance, 'description'),
            'resolve' => function ($root, $args, $context) use ($mutationInstance) {
                $output = call_user_func([$mutationInstance, 'resolve'], $root, $args, $context);
                if (!array_key_exists('status', $output)) {
                    $output['status'] = 'ok';
                }
                return $output;
            },
            'type' => $this->fieldsForOutputType($className, $mutationInstance->output),
        ];
    }

    /**
     * @param string $className
     * @param array $config
     * @return ObjectType
     */
    private function fieldsForOutputType(string $className, array $config): ObjectType
    {
        $typeName = class_basename($className) . 'Output';

        if (!array_key_exists('status', $config)) {
            $config['status'] = 'required|string';
        }

        return $this->typeRegistry->outputType($typeName, [
            'name' => $typeName,
            'fields' => collect($config)->mapWithKeys(function ($type, $name) {
                return [$name => $this->typeRegistry->type($type)];
            })->all(),
        ]);
    }

    /**
     * @param string $className
     * @return array
     */
    public function fieldsForQuery(string $className): array
    {
        $queryInstance = new $className();

        $this->validateQuery($queryInstance);

        return [
            'args' => collect(data_get($queryInstance, 'args', []))
                ->map(function (array $config, string $name) use ($queryInstance) {
                    return
                        [
                            'name' => $name,
                            'defaultValue' => data_get($config, 'defaultValue'),
                            'description' => data_get($config, 'description'),
                            'type' => $this->typeRegistry->type(data_get($config, 'type')),
                        ];
                })->values()->all(),
            'resolve' => function ($root, $args, $context) use ($queryInstance) {
                return call_user_func([$queryInstance, 'resolve'], $root, $args, $context);
            },
            'type' => $this->typeRegistry->type($queryInstance->type),
        ];
    }

    /**
     * @param $instance
     * @throws Exception
     */
    private function validateMutation($instance): void
    {
        if (!property_exists($instance, 'input')) {
            throw new Exception(get_class($instance) . ' must have a `input` property');
        }
        if (!method_exists($instance, 'resolve')) {
            throw new Exception(get_class($instance) . ' must have a `resolve` method');
        }
        if (!property_exists($instance, 'output')) {
            throw new Exception(get_class($instance) . ' must have a `output` property');
        }
    }

    /**
     * @param $instance
     * @throws Exception
     */
    private function validateQuery($instance): void
    {
        if (!property_exists($instance, 'type')) {
            throw new Exception(get_class($instance) . ' must have a `type` property');
        }
        if (!method_exists($instance, 'resolve')) {
            throw new Exception(get_class($instance) . ' must have a `resolve` method');
        }
    }
}