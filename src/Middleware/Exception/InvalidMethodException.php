<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware\Exception;

use Throwable;

/**
 * Class InvalidMethodException
 *
 * @author Romain Cottard
 */
class InvalidMethodException extends HttpException
{
    /**
     * InvalidMethodException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 405, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
