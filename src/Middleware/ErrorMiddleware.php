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
use Eureka\Kernel\Http\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ErrorMiddleware
 *
 * @author Romain Cottard
 */
class ErrorMiddleware implements MiddlewareInterface
{
    /** @var ErrorControllerInterface $container */
    protected ErrorControllerInterface $controller;

    /**
     * ErrorMiddleware constructor.
     *
     * @param ErrorControllerInterface $errorController
     */
    public function __construct(ErrorControllerInterface $errorController)
    {
        $this->controller = $errorController;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $serverRequest
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws
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
     *
     * @param ServerRequestInterface $serverRequest
     * @param \Throwable $exception
     * @return ResponseInterface
     * @throws
     */
    private function getErrorResponse(ServerRequestInterface $serverRequest, \Throwable $exception): ResponseInterface
    {
        if ($exception instanceof \Error) {
            $exception = new HttpInternalServerErrorException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->controller->preAction($serverRequest);
        $response = $this->controller->error($serverRequest, $exception);
        $this->controller->postAction();

        return $response;
    }
}
