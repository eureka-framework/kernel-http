# kernel-http

[![Current version](https://img.shields.io/packagist/v/eureka/kernel-http.svg?logo=composer)](https://packagist.org/packages/eureka/kernel-http)
[![Supported PHP version](https://img.shields.io/static/v1?logo=php&label=PHP&message=7.4%20-%208.3&color=777bb4)](https://packagist.org/packages/eureka/kernel-http)
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
make tests
```

You can run functional tests (without coverage) on your side with following command:
```bash
make integration
```

For prettier output (but without coverage), you can use the following command:
```bash
make testdox # run tests without coverage reports but with prettified output
```

#### Code Style
You also can run code style check with following commands:
```bash
make phpcs
```

You also can run code style fixes with following commands:
```bash
make phpcsf
```

#### Static Analysis
To perform a static analyze of your code (with phpstan, lvl 9 at default), you can use the following command:
```bash
make analyze
```

To ensure you code still compatible with current supported version at Deezer and futures versions of php, you need to
run the following commands (both are required for full support):

Minimal supported version:
```bash
make php74compatibility
```

Maximal supported version:
```bash
make php83compatibility
```

#### CI Simulation
And the last "helper" commands, you can run before commit and push, is:
```bash
make ci  
```

## License

This project is currently under The MIT License (MIT). See [LICENCE](LICENSE) file for more information.
