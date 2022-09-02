<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ErrorControllerInterface interface
 *
 * @author Romain Cottard
 */
interface ErrorControllerInterface extends ControllerInterface
{
    /**
     * @param ServerRequestInterface $serverRequest
     * @param \Exception $exception
     * @return ResponseInterface
     */
    public function error(ServerRequestInterface $serverRequest, \Exception $exception): ResponseInterface;
}
