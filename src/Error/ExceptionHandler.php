<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Error;

/**
 * Class to handle exception
 *
 * @author Romain Cottard
 */
class ExceptionHandler
{
    /**
     * Define Exception Handler
     *
     * @param  string $class Class Name.
     * @param  string $method Class method.
     * @param  string $namespace Class Namespace.
     * @return callback Previous exception handler.
     */
    public static function register($class = 'ExceptionHandler', $method = 'handler', $namespace = '\Eureka\Kernel\Http\Error')
    {
        $handler = $namespace . '\\' . $class . '::' . $method;

        set_exception_handler($handler);
    }

    /**
     * Exception handler when exception have not been caught.
     *
     * @param  Exception $exception
     * @return void
     */
    public static function handler($exception)
    {
        echo '/!\ Uncaught exception:', PHP_EOL;
        echo '[', $exception->getCode(), ']', $exception->getMessage(), PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * Restore previous exception handler.
     *
     * @return boolean
     */
    public static function restore()
    {
        return restore_exception_handler();
    }
}
