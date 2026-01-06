# kernel-http

[![Current version](https://img.shields.io/packagist/v/eureka/kernel-http.svg?logo=composer)](https://packagist.org/packages/eureka/kernel-http)
[![Supported PHP version](https://img.shields.io/static/v1?logo=php&label=PHP&message=>%3D8.3&color=777bb4)](https://packagist.org/packages/eureka/kernel-http)
![Build](https://github.com/eureka-framework/kernel-http/workflows/CI/badge.svg)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=eureka-framework_kernel-http&metric=alert_status)](https://sonarcloud.io/dashboard?id=eureka-framework_kernel-http)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=eureka-framework_kernel-http&metric=coverage)](https://sonarcloud.io/dashboard?id=eureka-framework_kernel-http)

Kernel Http for any Eureka Framework application.

Define global Application, Controller &amp; Component kernel versions

## Installation

If you wish to install it in your project, require it via composer:

```bash
composer require eureka/kernel-http
```


## Usage

```php
<?php

declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Eureka\Kernel\Http\Application\Application;
use Eureka\Kernel\Http\Kernel;

//~ Define Loader & add main classes for config
require_once __DIR__ . '/vendor/autoload.php';

$root  = realpath(__DIR__ . '/');
$env   = 'dev';
$debug = true;

// Run application
// Applications exception should be caught. Try catch useful only when you have a bug in kernel component
try {
    $application = new Application(new Kernel($root, $env, $debug));
    $response    = $application->run();
    $application->send($response);
} catch (\Exception $exception) {
    echo 'Exception: ' . $exception->getMessage() . PHP_EOL;
    echo 'Trace: ' . $exception->getTraceAsString() . PHP_EOL;
    exit(1);
}

```

### Config tree example
```
config/
|_ packages/
|  |_ domains/
|      - user.yaml
|   -  app.yaml
|   - database.yaml
|   - services.yaml
|_ dev/
|   - app.yaml
|_ test/
|   - app.yaml
|_ secrets/
|   - database.yaml
|_ services.yaml
```

Config loading order and overrides:
1. `kernel.yaml` is loaded
2. Then `services.yaml`
3. Then `packages/`
4. Then `{current_env}/`
5. Finally `secrets/`




### Kernel config
```yaml
parameters:

  #~ Debug
  kernel.debug: false

  #~ Environment
  kernel.environment: 'prod' # Overridden in Kernel class, but define here as helper

  #~ Errors
  kernel.error.display:   'false'
  kernel.error.reporting: !php/const E_ALL

  #~ Directories
  kernel.directory.root:   '..' # Overridden in Kernel class, but define here as helper
  kernel.directory.cache:  '%kernel.directory.root%/var/cache'
  kernel.directory.log:    '%kernel.directory.root%/var/log'
  kernel.directory.config: '%kernel.directory.root%/config'
  kernel.directory.vendor: '%kernel.directory.root%/vendor'

  #~ Compiler passes
  kernel.compiler_pass:
    - My\Custom\CompilerPass
```


### Global services config
```yaml
parameters:
  app.name: 'kernel'

services:
  # Main container
  Psr\Container\ContainerInterface:
    alias: 'service_container'

  # Main Logger
  logger:
    alias: Psr\Log\NullLogger

  # Main cache item pool
  cache:
    alias: Psr\Cache\CacheItemPoolInterface

  #~ PSR-17 Factories public alias (used by Application via container directly)
  response_factory:
    alias: Nyholm\Psr7\Factory\Psr17Factory
    public: true

  request_factory:
    alias: Nyholm\Psr7\Factory\Psr17Factory
    public: true

  server_request_factory:
    alias: Nyholm\Psr7\Factory\Psr17Factory
    public: true

  stream_factory:
    alias: Nyholm\Psr7\Factory\Psr17Factory
    public: true

  uri_factory:
    alias: Nyholm\Psr7\Factory\Psr17Factory
    public: true

```

### Application (controllers) config
```yaml
services:
  _defaults:
    autowire: true
    public:   true # Important, because Controller middleware use container to get appropriate controller and use it

  #~ Auto discovery kernel controllers (mainly error controller + interfaces)
  Eureka\Kernel\Http\Controller\:
    resource: '../../vendor/eureka/kernel-http/src/Controller/*'
    calls:
      - setLogger:               [ '@logger' ]
      - setRouter:               [ '@router' ]
      - setResponseFactory:      [ '@response_factory' ]
      - setRequestFactory:       [ '@request_factory' ]
      - setServerRequestFactory: [ '@server_request_factory' ]
      - setStreamFactory:        [ '@stream_factory' ]
      - setUriFactory:           [ '@uri_factory' ]

  #~ Auto discovery application controllers
  Application\Controller\:
    resource: '../../src/Controller/*'
    calls:
      - setLogger:               [ '@logger' ]
      - setRouter:               [ '@router' ]
      - setResponseFactory:      [ '@response_factory' ]
      - setRequestFactory:       [ '@request_factory' ]
      - setServerRequestFactory: [ '@server_request_factory' ]
      - setStreamFactory:        [ '@stream_factory' ]
      - setUriFactory:           [ '@uri_factory' ]

  #~ Auto discovery kernel services
  Eureka\Kernel\Http\Service\:
    resource: '../../vendor/eureka/kernel-http/src/Service/*'
```

### Middleware config
```yaml
parameters:

  # app.middleware Name is important, used by app to get list of middlewares
  # order is important, as the first is process, then the following, etc.
  app.middleware:
    error:      'Eureka\Kernel\Http\Middleware\ErrorMiddleware'
    router:     'Eureka\Kernel\Http\Middleware\RouterMiddleware'
    logger:     'Eureka\Kernel\Http\Middleware\ResponseTimeLoggerMiddleware'
    quota:      'Eureka\Kernel\Http\Middleware\RateLimiterMiddleware'
    controller: 'Eureka\Kernel\Http\Middleware\ControllerMiddleware'

services:
  _defaults:
    autowire: true
    public:   true # Must be true, because Application use container to retrieve each middleware and assign them to handler
    bind:
      $applicationName: '%app.name%'
      $logger: '@logger'
      $cache:  '@cache'

  #~ Global Kernel Services Middleware
  Eureka\Kernel\Http\Middleware\:
    resource: '../../vendor/eureka/kernel-http/src/Middleware'

  #~ Global Application Services Middleware
  Application\Middleware\:
    resource: '../../src/Middleware'
```

### Router config
```yaml
services:
  _defaults:
    autowire: true

  router:
    alias: Symfony\Component\Routing\Router

  Symfony\Component\Routing\:
    resource: '../../vendor/symfony/routing/*'
    exclude:  '../../vendor/symfony/routing/{Router,Tests}'

  Symfony\Component\Config\:
    resource: '../../vendor/symfony/config/*'
    exclude:  '../../vendor/symfony/config/{FileLocator,Tests}'

  Symfony\Component\Config\Loader\LoaderInterface:
    alias: Symfony\Component\Routing\Loader\YamlFileLoader

  Symfony\Component\Config\FileLocatorInterface:
    alias: Symfony\Component\Config\FileLocator

  Symfony\Component\Config\FileLocator:
    arguments:
      - '%kernel.directory.config%'

  Symfony\Component\Routing\Router:
    arguments:
      $loader:   '@Symfony\Component\Routing\Loader\YamlFileLoader'
      $resource: 'routes.yaml'
      $options:
        cache_dir: '%kernel.directory.cache%/%kernel.environment%'
        debug:     '%kernel.debug%'
```

### Routes configs
```yaml
services:
  _defaults:
    autowire: true

  router:
    alias: Symfony\Component\Routing\Router

  Symfony\Component\Routing\:
    resource: '../../vendor/symfony/routing/*'
    exclude:  '../../vendor/symfony/routing/{Router,Tests}'

  Symfony\Component\Config\:
    resource: '../../vendor/symfony/config/*'
    exclude:  '../../vendor/symfony/config/{FileLocator,Tests}'

  Symfony\Component\Config\Loader\LoaderInterface:
    alias: Symfony\Component\Routing\Loader\YamlFileLoader

  Symfony\Component\Config\FileLocatorInterface:
    alias: Symfony\Component\Config\FileLocator

  Symfony\Component\Config\FileLocator:
    arguments:
      - '%kernel.directory.config%'

  Symfony\Component\Routing\Router:
    arguments:
      $loader:   '@Symfony\Component\Routing\Loader\YamlFileLoader'
      $resource: 'routes.yaml'
      $options:
        cache_dir: '%kernel.directory.cache%/%kernel.environment%'
        debug:     '%kernel.debug%'

```

### Components configs
```yaml
services:
  _defaults:
    autowire: true

  Nyholm\Psr7\:
    resource: '%kernel.directory.root%/vendor/nyholm/psr7/src/*'

  #~ PSR
  Eureka\Component\Http\:
    resource: '%kernel.directory.root%/vendor/eureka/component-http/src/*'

  Psr\Log\:
    resource: '%kernel.directory.root%/vendor/psr/log/src/*'
    
  Psr\Cache\CacheItemPoolInterface:
    alias: Symfony\Component\Cache\Adapter\ArrayAdapter

  Symfony\Component\Cache\Adapter\ArrayAdapter: ~

```


### Routes configs
```yaml
# ===== HOME =====
home:
  path: /
  controller: Application\Controller\ExampleController::home
  methods: [ GET ]
  defaults:
    rateLimiterQuota: 100 # Used to set rate limit on this endpoint
    rateLimiterTTL:   60  # Used to set rate limit on this endpoint

test_url_with_param:
  path: /blog/{id}/{title}
  controller: Application\Controller\ExampleController::viewWithParams
  methods: [ GET ]
  defaults:
    someString: 'value'
    someBool:   true
    someInt:    42
  requirements:
    id:    '\d+'
    title: '[a-zA-Z-]+'

```

Default value & requirements values are injected in controller actions, with the following order:
- ServerRequest object (always pass)
- defaults (in same order as defined in yaml file)
- requirements (in same order as defined in yaml. Requirement are always injected as string)


**Example of controller:**
```php
<?php

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit\Mock;

use Eureka\Kernel\Http\Controller\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExampleController extends Controller
{
    public function home(): ResponseInterface
    {
        return $this->getResponse('Hello world!');
    }
    
    public function viewWithParams(
        ServerRequestInterface $serverRequest,
        string $someString,
        bool $someBool,
        int $someInt,
        string $id,
        string $title,
    ): ResponseInterface {
        return $this->getResponse("entity id: $id, title: $title, someString: $someString, someBool: " . ($someBool ? 'true' : 'false') . ", someInt: $someInt");
    }

```


## Contributing

See the [CONTRIBUTING](CONTRIBUTING.md) file.


### Install / update project

You can install project with the following command:
```bash
make install
```

And update with the following command:
```bash
make update
```

NB: For the components, the `composer.lock` file is not committed.

### Testing & CI (Continuous Integration)

#### Tests
You can run unit tests (with coverage) on your side with following command:
```bash
make php/tests
```

You can run integration tests (without coverage) on your side with following command:
```bash
make php/integration
```

For prettier output (but without coverage), you can use the following command:
```bash
make php/testdox # run tests without coverage reports but with prettified output
```

#### Code Style
You also can run code style check with following commands:
```bash
make php/check
```

You also can run code style fixes with following commands:
```bash
make php/fix
```

#### Check for missing explicit dependencies
You can check if any explicit dependency is missing with the following command:
```bash
make php/deps
```

#### Static Analysis
To perform a static analyze of your code (with phpstan, lvl 9 at default), you can use the following command:
```bash
make php/analyse
```

To ensure you code still compatible with current supported version at Deezer and futures versions of php, you need to
run the following commands (both are required for full support):

Minimal supported version:
```bash
make php/min-compatibility
```

Maximal supported version:
```bash
make php/max-compatibility
```

#### CI Simulation
And the last "helper" commands, you can run before commit and push, is:
```bash
make ci  
```

## License

This project is currently under The MIT License (MIT). See [LICENSE](LICENSE) file for more information.
