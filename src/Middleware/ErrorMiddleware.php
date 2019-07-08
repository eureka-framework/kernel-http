<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Controller\ErrorController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorMiddleware implements MiddlewareInterface
{
    /** @var ErrorController $container */
    protected $controller;

    /**
     * ErrorMiddleware constructor.
     *
     * @param ErrorController $errorController
     */
    public function __construct(ErrorController $errorController)
    {
        $this->controller = $errorController;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (\Exception $exception) {
            $response = $this->getErrorResponse($request, $exception);
        }

        return $response;
    }

    /**
     * Get Error response.
     *
     * @param ServerRequestInterface $request
     * @param \Exception $exception
     * @return ResponseInterface
     * @throws
     */
    private function getErrorResponse(ServerRequestInterface $request, \Exception $exception): ResponseInterface
    {
        $this->controller->preAction($request);
        $response = $this->controller->errorAction($request, $exception);
        $this->controller->postAction();

        return $response;
    }
}
