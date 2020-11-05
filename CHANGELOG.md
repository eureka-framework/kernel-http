# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [5.0.1] - 2020-11-05
### Changed:
 * Better loading for yaml file (now search for sub directory /{env}/ & /secrets/)
 * Require dependency thecodingmachine/safe 1.3

## [5.0.0] - 2020-10-29
### Changed:
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