# kernel-http

[![Current version](https://img.shields.io/packagist/v/eureka/kernel-http.svg?logo=composer)](https://packagist.org/packages/eureka/kernel-http)
[![Supported PHP version](https://img.shields.io/static/v1?logo=php&label=PHP&message=%5E7.4&color=777bb4)](https://packagist.org/packages/eureka/kernel-http)
[![codecov](https://codecov.io/gh/eureka-framework/kernel-http/branch/master/graph/badge.svg)](https://codecov.io/gh/eureka-framework/kernel-http)
[![Build Status](https://travis-ci.org/eureka-framework/kernel-http.svg?branch=master)](https://travis-ci.org/eureka-framework/kernel-http)
![CI](https://github.com/eureka-framework/kernel-http/workflows/CI/badge.svg)

Kernel Http for any Eureka Framework application.

Define global Application, Controller &amp; Component kernel versions


## Installation

You can install the kernel (for testing) with the following command:
```bash
make install
```

## Update

You can update the kernel (for testing) with the following command:
```bash
make update
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



## Testing

You can test the kernel with the following commands:
```bash
make tests
make testdox
```