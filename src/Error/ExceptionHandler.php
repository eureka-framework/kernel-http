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
     * @return void
     */
    public static function register(string $class = ExceptionHandler::class, string $method = 'handle'): void
    {
        $handler = $class . '::' . $method;

        set_exception_handler($handler);
    }

    /**
     * Exception handler when exception have not been caught.
     *
     * @param \Exception $exception
     * @return void
     */
    public static function handle(\Exception $exception): void
    {
        echo '/!\ Uncaught exception:', PHP_EOL;
        echo '[', $exception->getCode(), ']', $exception->getMessage(), PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * Restore previous exception handler.
     *
     * @return bool
     */
    public static function restore(): bool
    {
        return restore_exception_handler();
    }
}
