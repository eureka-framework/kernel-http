<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Error;

/**
 * Class to handle error and transfer it into exceptions.
 *
 * @author Romain Cottard
 */
class ErrorHandler
{
    /**
     * Initialize Error handler.
     *
     * @return void
     */
    public function init($reporting, $display)
    {
        //~ Init errors
        error_reporting($reporting);
        ini_set('display_errors', $display);
    }

    /**
     * Define Error Handler
     *
     * @param  string $class Class Name.
     * @param  string $method Class method.
     * @param  string $namespace Class Namespace.
     * @return callback  Previous exception handler.
     */
    public function register($class = 'ErrorHandler', $method = 'handler', $namespace = 'Eureka\Kernel\Http\Error')
    {
        set_error_handler([$namespace . '\\' . $class, $handler]);
    }

    /**
     * Error handler. Throw new Eureka ErrorException
     *
     * @param  int    $severity Severity code Error.
     * @param  string $message Error message.
     * @param  string $file File name for Error.
     * @param  int    $line File line for Error.
     * @return void
     * @throws ErrorException
     */
    public function handler($severity, $message, $file, $line)
    {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Restore previous Error handler.
     *
     * @return   boolean
     */
    public function restore()
    {
        return restore_error_handler();
    }
}
