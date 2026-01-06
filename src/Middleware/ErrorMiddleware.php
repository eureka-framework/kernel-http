<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Controller\ErrorControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private ErrorControllerInterface $controller) {}

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $serverRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($serverRequest);
        } catch (\Throwable $exception) {
            $response = $this->getErrorResponse($serverRequest, $exception);
        }

        return $response;
    }

    /**
     * Get Error response.
     */
    private function getErrorResponse(ServerRequestInterface $serverRequest, \Throwable $exception): ResponseInterface
    {
        $this->controller->preAction($serverRequest);
        $response = $this->controller->error($serverRequest, $exception);
        $this->controller->postAction();

        return $response;
    }
}
