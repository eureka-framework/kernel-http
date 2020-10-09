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
     * @param int $reporting
     * @param bool $display
     * @return $this
     */
    public function init(int $reporting, bool $display): self
    {
        //~ Init errors
        error_reporting($reporting);
        ini_set('display_errors', $display);

        return $this;
    }

    /**
     * Define Error Handler
     *
     * @param  string $class Class Name.
     * @param  string $method Class method.
     * @return $this
     */
    public function register(string $class = ErrorHandler::class, string $method = 'handle'): self
    {
        set_error_handler([$class, $method]);

        return $this;
    }

    /**
     * Error handler. Throw new Eureka ErrorException
     *
     * @param  int $severity Severity code Error.
     * @param  string $message Error message.
     * @param  string $file File name for Error.
     * @param  int $line File line for Error.
     * @return void
     * @throws ErrorException
     */
    public function handle(int $severity, string $message, string $file, int $line): void
    {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Restore previous Error handler.
     *
     * @return bool
     */
    public function restore(): bool
    {
        return restore_error_handler();
    }
}
