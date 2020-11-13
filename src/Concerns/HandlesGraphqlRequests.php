<?php

namespace Butler\Graphql\Concerns;

use Butler\Graphql\DataLoader;
use Exception;
use GraphQL\Error\Debug;
use GraphQL\Error\Error as GraphqlError;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HandlesGraphqlRequests
{
    /**
     * Invoke the Graphql request handler.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function __invoke(Request $request)
    {
        $loader = app(DataLoader::class);
        $schema = BuildSchema::build($this->schema(), [$this, 'decorateTypeConfig']);
        $result = null;

        GraphQL::useExperimentalExecutor();

        GraphQL::promiseToExecute(
            app(PromiseAdapter::class),
            $schema,
            $request->input('query'),
            null, // root
            compact('loader'), // context
            $request->input('variables'),
            $request->input('operationName'),
            [$this, 'resolveField'],
            null // validationRules
        )->then(function ($value) use (&$result) {
            $result = $value;
        });

        $loader->run();

        $result->setErrorFormatter([$this, 'errorFormatter']);

        return $this->decorateResponse($result->toArray($this->debugFlags()));
    }

    public function errorFormatter(GraphqlError $graphqlError)
    {
        $formattedError = FormattedError::createFromException($graphqlError);
        $throwable = $graphqlError->getPrevious();

        $this->reportException(
            $throwable instanceof Exception
            ? $throwable
            : $graphqlError
        );

        if ($throwable instanceof ModelNotFoundException) {
            return array_merge($formattedError, [
                'message' => class_basename($throwable->getModel()) . ' not found.',
                'extensions' => [
                    'category' => 'client',
                ],
            ]);
        }

        if ($throwable instanceof ValidationException) {
            return array_merge($formattedError, [
                'message' => $throwable->getMessage(),
                'extensions' => [
                    'category' => 'validation',
                    'validation' => $throwable->errors(),
                ],
            ]);
        }

        return $formattedError;
    }

    public function reportException(Exception $exception)
    {
        app(ExceptionHandler::class)->report($exception);
    }

    public function schema()
    {
        return file_get_contents($this->schemaPath());
    }

    public function schemaPath()
    {
        return config('butler.graphql.schema');
    }

    public function decorateTypeConfig(array $config, TypeDefinitionNode $typeDefinitionNode)
    {
        if ($this->shouldDecorateWithResolveType($typeDefinitionNode)) {
            $config['resolveType'] = [$this, 'resolveType'];
        }
        return $config;
    }

    protected function shouldDecorateWithResolveType(TypeDefinitionNode $typeDefinitionNode)
    {
        return $typeDefinitionNode instanceof InterfaceTypeDefinitionNode
            || $typeDefinitionNode instanceof UnionTypeDefinitionNode;
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

    public function resolveType($source, $context, ResolveInfo $info)
    {
        return $this->typeFromArray($source, $context, $info)
            ?? $this->typeFromObject($source, $context, $info)
            ?? $this->typeFromParentResolver($source, $context, $info)
            ?? $this->typeFromBaseClass($source, $context, $info);
    }

    public function fieldFromResolver($source, $args, $context, ResolveInfo $info)
    {
        $className = $this->resolveClassName($info);
        $methodName = $this->resolveFieldMethodName($info);

        if (app()->has($className) || class_exists($className)) {
            $resolver = app($className);
            if (method_exists($resolver, $methodName)) {
                return $resolver->{$methodName}($source, $args, $context, $info);
            }
        }
    }

    public function fieldFromArray($source, $args, $context, ResolveInfo $info)
    {
        if (is_array($source) || $source instanceof \ArrayAccess) {
            return collect($this->propertyNames($info))
                ->map(function ($propertyName) use ($source) {
                    return $source[$propertyName] ?? null;
                })
                ->reject(function ($value) {
                    return is_null($value);
                })
                ->first();
        }
    }

    public function fieldFromObject($source, $args, $context, ResolveInfo $info)
    {
        if (is_object($source)) {
            return collect($this->propertyNames($info))
                ->map(function ($propertyName) use ($source) {
                    return $source->{$propertyName} ?? null;
                })
                ->reject(function ($value) {
                    return is_null($value);
                })
                ->first();
        }
    }

    public function typeFromArray($source, $context, ResolveInfo $info)
    {
        if (is_array($source) || $source instanceof \ArrayAccess) {
            return $source['__typename'] ?? null;
        }
    }

    public function typeFromObject($source, $context, ResolveInfo $info)
    {
        if (is_object($source)) {
            return $source->__typename ?? null;
        }
    }

    public function typeFromParentResolver($source, $context, ResolveInfo $info)
    {
        $className = $this->resolveClassName($info);
        $methodName = $this->resolveTypeMethodName($info);

        if (app()->has($className) || class_exists($className)) {
            $resolver = app($className);
            if (method_exists($resolver, $methodName)) {
                return $resolver->{$methodName}($source, $context, $info);
            }
        }
    }

    public function typeFromBaseClass($source, $context, ResolveInfo $info)
    {
        if (is_object($source)) {
            return class_basename($source);
        }
    }

    public function propertyNames(ResolveInfo $info): array
    {
        return collect([
            Str::snake($info->fieldName),
            Str::camel($info->fieldName),
            Str::kebab(Str::camel($info->fieldName)),
        ])->unique()->toArray();
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

    public function resolveFieldMethodName(ResolveInfo $info): string
    {
        if (in_array($info->parentType->name, ['Query', 'Mutation'])) {
            return '__invoke';
        }

        return Str::camel($info->fieldName);
    }

    public function resolveTypeMethodName(ResolveInfo $info): string
    {
        if (in_array($info->parentType->name, ['Query', 'Mutation'])) {
            return 'resolveType';
        }

        return 'resolveTypeFor' . ucfirst(Str::camel($info->fieldName));
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

    public function decorateResponse(array $data): array
    {
        if (app()->bound('debugbar') && app('debugbar')->isEnabled()) {
            $data['debug'] = app('debugbar')->getData();
        }
        return $data;
    }
}
