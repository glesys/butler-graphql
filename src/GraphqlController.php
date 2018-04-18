<?php

namespace Butler\Graphql;

use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Log;

class GraphqlController extends Controller
{
    public function __invoke(
        Request $request,
        TypeLoader $typeLoader
    ) {
        $rootMutation = $typeLoader->loadMutations(config('graphql.mutations'));
        $rootQuery = $typeLoader->loadQueries(config('graphql.queries'));

        $schema = new Schema([
            'query' => $rootQuery,
            'mutation' => $rootMutation,
        ]);

        $root = [];
        $context = [];

        $result = GraphQL::executeQuery(
            $schema,
            $request->input('query'),
            $root,
            $context,
            $request->input('variables')
        )
            ->setErrorFormatter(function (Error $error) {
                $formattedError = FormattedError::createFromException($error);

                if (($exception = $error->getPrevious()) instanceof ValidationException) {
                    $formattedError = array_merge($formattedError, [
                        'message' => $exception->getMessage(),
                        'category' => 'validation',
                        'fields' => $exception->errors(),
                    ]);
                }

                return $formattedError;
            });

        collect($result->errors)->each(function ($exception) {
            Log::error('Graphql error', compact('exception'));
        });

        return $result->toArray(
            app()->environment('local')
                ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE
                : false
        );
    }
}