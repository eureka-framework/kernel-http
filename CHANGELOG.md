# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [5.3.0] - 2023-06-14
- [5.3.0]: https://github.com/eureka-framework/kernel-http/compare/5.2.1...5.3.0
### Changed
- Now officially compatible with PHP 8.2
- Update composer.json
- Update Makefile
- Update GitHub workflow
- Fix some phpstan errors
### Added
- PHPStan config for PHP 8.2 compatibility check

## [5.2.1] - 2022-09-02
### Changed
* Update DataCollection array content (to mixed)

## [5.2.0] - 2022-09-02
### Changed
* CI improvements (php compatibility check, makefile, jenkins)
* Now compatible with PHP 7.4, 8.0 & 8.1
* Fix phpdoc according to phpstan analysis
### Added
* phpstan for static analysis
### Removed
* phpcompatibility (no more maintained)


## [5.1.0] - 2020-11-13
### Added
 * ErrorMiddleware now handle all \Throwable & transform \Error into HttpInternalServerError for error controller.
 * Add a header no cache for redirection.

## [5.0.2] - 2020-11-06
### Changed
 * Re-add  the missing "secrets/" directory in loaded config
 
## [5.0.1] - 2020-11-05
### Changed
 * Better loading for yaml file (now search for sub directory /{env}/ & /secrets/)
 * Require dependency thecodingmachine/safe 1.3

## [5.0.0] - 2020-10-29
### Changed
 * Require php 7.4+
 * Improve code & testability
 * Upgrade phpcodesniffer to v0.7 for composer 2.0
### Added
 * Tests
 * Configs for tests
 * New exceptions
 * New helpers: Session, DataCollection & IpResolver
 * New RateLimiter middleware
### Removed
 * Old exceptions
 * Old unused error handler
 * Old middleware exceptions



## [3.0.0] - 2018
### Added
 * Use official PSR-15
 * New default ErrorController
 * Use original PSR-15 middleware
 * Now use Symfony/DependencyInjection, Symfony/Config & Symfony/Routing

### Changed
 * Require php 7.2+
 * Complete refactoring
 * Lots of changes
 * More SOLID code
 * Re-order config loading
 
### Removed
 * Remove useless Static Middlewares & ApplicationStatic classes
