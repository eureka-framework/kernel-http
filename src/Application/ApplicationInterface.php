<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application interface
 *
 * @author Romain Cottard
 */
interface ApplicationInterface
{
    /**
     * @param ServerRequestInterface|null $serverRequest
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $serverRequest = null): ResponseInterface;

    /**
     * Send response to client
     *
     * @param ResponseInterface $response
     * @return ApplicationInterface
     */
    public function send(ResponseInterface $response): ApplicationInterface;
}
