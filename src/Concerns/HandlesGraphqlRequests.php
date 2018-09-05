<?php

namespace Butler\Graphql\Concerns;

use Butler\Graphql\DataLoader;
use Exception;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionException;

trait HandlesGraphqlRequests
{
    /** @var \Butler\Graphql\DataLoader */
    private $dataLoader;

    /** @var \GraphQL\Executor\Promise\PromiseAdapter */
    private $promiseAdapter;

    /**
     * Create a new instance.
     *
     * @param  \Butler\Graphql\DataLoader  $dataLoader
     * @param  \GraphQL\Executor\Promise\PromiseAdapter  $promiseAdapter
     */
    public function __construct(DataLoader $dataLoader, PromiseAdapter $promiseAdapter)
    {
        $this->dataLoader = $dataLoader;
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * Invoke the Graphql request handler.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function __invoke(Request $request)
    {
        $schema = BuildSchema::build(file_get_contents($this->schemaPath()));
        $result = null;

        GraphQL::promiseToExecute(
            $this->promiseAdapter,
            $schema,
            $request->input('query'),
            null, // root
            ['loader' => $this->dataLoader], // context
            $request->input('variables'),
            null, // operationName
            [$this, 'resolveField'],
            null // validationRules
        )->then(function ($value) use (&$result) {
            $result = $value;
        });

        $this->dataLoader->run();

        $result->setErrorFormatter([$this, 'errorFormatter']);

        return $result->toArray($this->debugFlags());
    }

    public function errorFormatter(Error $error)
    {
        $formattedError = FormattedError::createFromException($error);
        $exception = $error->getPrevious();

        if ($exception instanceof ModelNotFoundException) {
            $formattedError = array_merge($formattedError, [
                'message' => class_basename($exception->getModel()) . ' not found.',
                'category' => 'client',
            ]);
        }

        if ($exception instanceof ValidationException) {
            $formattedError = array_merge($formattedError, [
                'message' => $exception->getMessage(),
                'category' => 'validation',
                'validation' => $exception->errors(),
            ]);
        }

        return $formattedError;
    }

    public function schemaPath()
    {
        return config('butler.graphql.schema');
    }

    public function debugFlags()
    {
        $flags = 0;
        if (config('butler.graphql.include_debug_message')) {
            $flags |= Debug::INCLUDE_DEBUG_MESSAGE;
        }
        if (config('butler.graphql.include_trace')) {
            $flags |= Debug::INCLUDE_TRACE;
        }
        return $flags;
    }

    public function resolveField($source, $args, $context, ResolveInfo $info)
    {

        $field = $this->fieldFromResolver($source, $args, $context, $info)
            ?? $this->fieldFromArray($source, $args, $context, $info)
            ?? $this->fieldFromObject($source, $args, $context, $info);

        return $field instanceof \Closure
            ? $field($source, $args, $context, $info)
            : $field;
    }

    public function fieldFromResolver($source, $args, $context, ResolveInfo $info)
    {
        $className = $this->resolveClassName($info);
        $methodName = $this->resolveMethodName($info);

        try {
            $resolver = app()->make($className);
        } catch (ReflectionException $e) {
            // NOTE: It's OK if the class to reflect does not exist
        }

        if (isset($resolver) && method_exists($resolver, $methodName)) {
            return $resolver->{$methodName}($source, $args, $context, $info);
        }
    }

    public function fieldFromArray($source, $args, $context, ResolveInfo $info)
    {
        $propertyName = $this->propertyName($info);

        if (is_array($source) || $source instanceof \ArrayAccess) {
            if (isset($source[$propertyName])) {
                return $source[$propertyName];
            }
        }
    }

    public function fieldFromObject($source, $args, $context, ResolveInfo $info)
    {
        $propertyName = $this->propertyName($info);

        if (is_object($source)) {
            if (isset($source->{$propertyName})) {
                return $source->{$propertyName};
            }
        }
    }

    public function propertyName(ResolveInfo $info): string
    {
        return Str::snake($info->fieldName);
    }

    protected function resolveClassName(ResolveInfo $info): string
    {
        if ($info->parentType->name === 'Query') {
            return $this->queriesNamespace() . Str::studly($info->fieldName);
        }

        if ($info->parentType->name === 'Mutation') {
            return $this->mutationsNamespace() . Str::studly($info->fieldName);
        }

        return $this->typesNamespace() . Str::studly($info->parentType->name);
    }

    public function resolveMethodName(ResolveInfo $info): string
    {
        if (in_array($info->parentType->name, ['Query', 'Mutation'])) {
            return '__invoke';
        }

        return Str::camel($info->fieldName);
    }

    public function namespace(): string
    {
        return config('butler.graphql.namespace');
    }

    public function queriesNamespace(): string
    {
        return $this->namespace() . 'Queries\\';
    }

    public function mutationsNamespace(): string
    {
        return $this->namespace() . 'Mutations\\';
    }

    public function typesNamespace(): string
    {
        return $this->namespace() . 'Types\\';
    }
}
