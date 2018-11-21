# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


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
