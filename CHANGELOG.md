# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


## [3.1.0] - 2020-03-13

### Changed

- Require PHP 7.2.5.
- Support Laravel 7.


## [3.0.0] - 2020-01-30

### Changed
- **BREAKING**: Removes leading slashes in the default namespace configuration to better support dependency injection with the laravel container. [#24](https://github.com/glesys/butler-graphql/pull/24)


## [2.2.0] - 2019-09-12

### Added
- Support for shared data loaders when resolvers use the same underlying data.


## [2.1.0] - 2019-09-05

### Changed
- Support Laravel 6 🎉.


## [2.0.1] - 2019-06-20

### Fixed
- When resolving `Boolean` values from arrays or objects `false` was incorrectly filtered out, resulting in a `null` value instead.


## [2.0.0] - 2019-05-17

### Added
- Interface support 🎉. Types are resolved using `$source['__typename']`, `$source->__typename`, `Parent@resolveTypeForField()` (`Query@resolveType` for queries and mutations) or class base name.

### Changed
- **BREAKING**: Upgrade to webonyx/graphql-php@0.13.4 for improved performance and specification compliance. The `category` and `validation` keys previously available next to `message` in errors has now been moved to the `extensions` part of errors as per the [June 2018 GraphQL specification](https://graphql.github.io/graphql-spec/June2018/#sec-Errors).
- **BREAKING**: Support for various casing from source data. Previously Butler GraphQL assumed `snake_case` for source data attributes when resolving fields (to mimic the Eloquent standard). This has now been extended to support `snake_case`, `camelCase` and `kebab-case` by default. To change this behaviour you can override the `propertyNames(ResolveInfo $info): array` method in your GraphQL controller. The old `propertyName(ResolveInfo $info): string` method has been removed.


## [1.2.1] - 2018-11-21

### Fixed
- Stop catching Exceptions in `HandlesGraphqlRequests->fieldFromResolver` to avoid intercepting Exceptions from the service container.
- Pass `operationName` from the request to `GraphQL::promiseToExecute`.


## [1.2.0] - 2018-09-14

### Added
- Decorate the response with debug information when using [barryvdh/laravel-debugbar](https://github.com/barryvdh/laravel-debugbar).

### Changed
- Upgrade webonyx/graphql-php@0.12.6 for improved schema language support.

### Fixed
- The code in `HandlesGraphqlRequests->errorFormatter()` always assumed `\Exception` from `\GraphQL\Error\Error->getPrevious()` when it fact it returns `\Throwable`. We now ensure that we always pass `\Exception` to `ExceptionHandler->report()`.


## [1.1.2] - 2018-09-07

### Fixed
- Use `base_path()` instead of `app_path()` to ensure the default config works in Lumen.


## [1.1.1] - 2018-09-07

### Fixed
- Handle reporting of GraphQL errors (invalid queries and schema validation errors).


## [1.1.0] - 2018-09-07

### Added
- Implement error reporting of exceptions during requests.


## [1.0.0] - 2018-09-06

This marks the release of Butler GraphQL v1.0.0, completely rewritten to make it easier than ever to create GraphQL APIs using Laravel.

### Added
- Simplified setup with auto-disovered service provider with easy-to-use conventions.
- Use a schema.graphql file to define your GraphQL API.
- Data Loader included to prevent N+1 issues.


## [0.2.0] - 2018-04-26

### Added
- Add a status field to the output of all mutations.


## [0.1.0] - 2018-04-18

### Added
- Initial release
