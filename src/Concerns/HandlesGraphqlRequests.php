<?php

namespace Butler\Graphql\Concerns;

use Butler\Graphql\DataLoader;
use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error as GraphqlError;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Amp\call;

trait HandlesGraphqlRequests
{
    private $classCache;
    private $namespaceCache;

    /**
     * Invoke the Graphql request handler.
     *
     * @return array
     */
    public function __invoke(Request $request)
    {
        $this->classCache = [];
        $this->namespaceCache = null;

        $loader = app(DataLoader::class);

        $query = $request->input('query');
        $variables = $request->input('variables');
        $operationName = $request->input('operationName');

        try {
            $document = $this->loadSchemaDocument();

            $schema = BuildSchema::build($document, [$this, 'decorateTypeConfig'], ['assumeValidSDL' => true]);

            $source = Parser::parse($query);

            $this->beforeExecutionHook($schema, $source, $operationName, $variables);

            /** @var \GraphQL\Executor\ExecutionResult */
            $result = null;

            GraphQL::promiseToExecute(
                app(PromiseAdapter::class),
                $schema,
                $source,
                null, // root
                compact('loader'), // context
                $variables,
                $operationName,
                [$this, 'resolveField'],
                null // validationRules
            )->then(function ($value) use (&$result) {
                $result = $value;
            });

            $loader->run();
        } catch (GraphqlError $e) {
            $result = new ExecutionResult(null, [$e]);
        }

        $result->setErrorFormatter([$this, 'errorFormatter']);

        return $this->decorateResponse($result->toArray($this->debugFlags()));
    }

    public function beforeExecutionHook(
        Schema $schema,
        DocumentNode $query,
        ?string $operationName = null,
        $variables = null
    ): void {}

    public function errorFormatter(GraphqlError $graphqlError)
    {
        $formattedError = FormattedError::createFromException($graphqlError);

        $throwable = $graphqlError->getPrevious();

        $this->reportException(
            $throwable instanceof Exception ? $throwable : $graphqlError
        );

        if ($throwable instanceof AuthorizationException) {
            data_set($formattedError, 'message', $throwable->getMessage());
            data_fill($formattedError, 'extensions.category', 'client');
            data_fill($formattedError, 'extensions.code', $throwable->status() ?: 403);

            return $formattedError;
        }

        if (
            $throwable instanceof HttpException &&
            $throwable->getStatusCode() >= 400 &&
            $throwable->getStatusCode() < 500
        ) {
            data_set($formattedError, 'message', $throwable->getMessage());
            data_fill($formattedError, 'extensions.category', 'client');
            data_fill($formattedError, 'extensions.code', $throwable->getStatusCode());

            return $formattedError;
        }

        if ($throwable instanceof ModelNotFoundException) {
            data_set($formattedError, 'message', class_basename($throwable->getModel()) . ' not found.');
            data_fill($formattedError, 'extensions.category', 'client');
            data_fill($formattedError, 'extensions.code', 404);

            return $formattedError;
        }

        if ($throwable instanceof ValidationException) {
            data_set($formattedError, 'message', $throwable->getMessage());
            data_fill($formattedError, 'extensions.category', 'validation');
            data_fill($formattedError, 'extensions.validation', $throwable->errors());

            return $formattedError;
        }

        data_fill($formattedError, 'extensions.category', 'internal');

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

    public function compiledSchemaPath(): ?string
    {
        return null;
    }

    public function schemaExtensions()
    {
        $path = $this->schemaExtensionsPath();
        $glob = $this->schemaExtensionsGlob();

        if ($path && $glob) {
            $path = Str::finish($path, DIRECTORY_SEPARATOR);

            return collect(glob("{$path}{$glob}"))->map(file_get_contents(...))->toArray();
        }

        return [];
    }

    public function schemaExtensionsPath()
    {
        return config('butler.graphql.schema_extensions_path');
    }

    public function schemaExtensionsGlob()
    {
        return config('butler.graphql.schema_extensions_glob');
    }

    protected function loadSchemaDocument(): DocumentNode
    {
        $compiledSchemaPath = $this->compiledSchemaPath();

        if ($compiledSchemaPath && file_exists($compiledSchemaPath)) {
            return AST::fromArray(require $compiledSchemaPath);
        }

        $document = $this->parseDocument();

        if ($compiledSchemaPath) {
            file_put_contents($compiledSchemaPath, "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n");
        }

        return $document;
    }

    protected function parseDocument(): DocumentNode
    {
        $schema = $this->schema();

        foreach ($this->schemaExtensions() as $extension) {
            $schema .= "\n" . $extension;
        }

        DocumentValidator::assertValidSDL($document = Parser::parse($schema));

        return $document;
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
            $flags |= DebugFlag::INCLUDE_DEBUG_MESSAGE;
        }
        if (config('butler.graphql.include_trace')) {
            $flags |= DebugFlag::INCLUDE_TRACE;
        }

        return $flags;
    }

    public function resolveField($source, $args, $context, ResolveInfo $info)
    {
        if ($resolver = $this->resolverForField($info)) {
            $field = $resolver($source, $args, $context, $info);
        } else {
            $field = $this->fieldFromArray($source, $args, $context, $info)
                ?? $this->fieldFromObject($source, $args, $context, $info);
        }

        if ($this->fieldIsBackedEnum($field) && $this->returnTypeIsLeaf($info)) {
            $field = $field->value;
        }

        return call(static function () use ($field, $source, $args, $context, $info) {
            return $field instanceof \Closure
                ? $field($source, $args, $context, $info)
                : $field;
        });
    }

    public function resolveType($source, $context, ResolveInfo $info)
    {
        return $this->typeFromArray($source, $context, $info)
            ?? $this->typeFromObject($source, $context, $info)
            ?? $this->typeFromParentResolver($source, $context, $info)
            ?? $this->typeFromBaseClass($source, $context, $info);
    }

    public function resolverForField(ResolveInfo $info)
    {
        $className = $this->resolveClassName($info);
        $methodName = $this->resolveFieldMethodName($info);

        if ($resolver = $this->make($className)) {
            if (method_exists($resolver, $methodName)) {
                return fn (...$args) => $resolver->{$methodName}(...$args);
            }
        }
    }

    public function fieldFromArray($source, $args, $context, ResolveInfo $info)
    {
        if (is_array($source) || $source instanceof \ArrayAccess) {
            return collect($this->propertyNames($info))
                ->map(function ($propertyName) use ($source) {
                    try {
                        return $source[$propertyName] ?? null;
                    } catch (MissingAttributeException) {
                        return null;
                    }
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
                    try {
                        return $source->{$propertyName} ?? null;
                    } catch (MissingAttributeException) {
                        return null;
                    }
                })
                ->reject(function ($value) {
                    return is_null($value);
                })
                ->first();
        }
    }

    public function fieldIsBackedEnum($field): bool
    {
        return function_exists('enum_exists') && $field instanceof \BackedEnum;
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

        if ($resolver = $this->make($className)) {
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
        return $this->namespaceCache ?? $this->namespaceCache = config('butler.graphql.namespace');
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

    public function returnTypeIsLeaf(ResolveInfo $info): bool
    {
        $returnType = $info->returnType instanceof WrappingType
           ? $info->returnType->getWrappedType(true)
           : $info->returnType;

        return $returnType instanceof LeafType;
    }

    public function decorateResponse(array $data): array
    {
        if (app()->bound('debugbar') && app('debugbar')->isEnabled()) {
            $data['debug'] = app('debugbar')->getData();
        }

        return $data;
    }

    protected function make(string $className)
    {
        if (array_key_exists($className, $this->classCache)) {
            return $this->classCache[$className];
        }

        $class = app()->has($className) || class_exists($className)
            ? app($className)
            : null;

        return $this->classCache[$className] = $class;
    }
}
