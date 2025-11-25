<?php

return [

    'graphql' => [

        'include_debug_message' => env('BUTLER_GRAPHQL_INCLUDE_DEBUG_MESSAGE', false),
        'include_trace' => env('BUTLER_GRAPHQL_INCLUDE_TRACE', false),

        'namespace' => env('BUTLER_GRAPHQL_NAMESPACE', 'App\\Http\\Graphql\\'),

        'schema' => env('BUTLER_GRAPHQL_SCHEMA', base_path('app/Http/Graphql/schema.graphql')),

        'schema_cache_store' => env('BUTLER_GRAPHQL_SCHEMA_CACHE_STORE', null),
        'schema_cache_key' => env('BUTLER_GRAPHQL_SCHEMA_CACHE_KEY', 'butler-graphql:schema-cache'),
        'schema_cache_ttl' => env('BUTLER_GRAPHQL_SCHEMA_CACHE_TTL', null),

        'schema_extensions_path' => env('BUTLER_GRAPHQL_SCHEMA_EXTENSIONS_PATH', base_path('app/Http/Graphql/')),
        'schema_extensions_glob' => env('BUTLER_GRAPHQL_SCHEMA_EXTENSIONS_GLOB', 'schema-*.graphql'),

    ],

];
