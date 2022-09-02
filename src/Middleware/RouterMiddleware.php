<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Exception\HttpMethodNotAllowedException;
use Eureka\Kernel\Http\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

/**
 * Class RouterMiddleware
 * Exception Code Range: 900-909
 *
 * @author Romain Cottard
 */
class RouterMiddleware implements MiddlewareInterface
{
    /** @var Router $router */
    protected Router $router;

    /**
     * RouterMiddleware constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws HttpNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //~ Try to match url
        try {
            $this->router->setContext($this->getRequestContext($request));
            $route = $this->router->match((string) $request->getUri()->getPath());
        } catch (ResourceNotFoundException | RouteNotFoundException $exception) {
            throw new HttpNotFoundException($exception->getMessage(), 900, $exception);
        } catch (MethodNotAllowedException $exception) {
            $message = 'Allowed method(s): ' . implode(',', $exception->getAllowedMethods());
            throw new HttpMethodNotAllowedException($message, 901, $exception);
        }

        //~ Add route param to request
        $serverRequest = $request->withAttribute('route', $route);

        //~ Add route element to requests
        foreach ($route as $key => $value) {
            if ($key[0] === '_') {
                continue;
            }

            $serverRequest = $serverRequest->withAttribute($key, $value);
        }

        return $handler->handle($serverRequest);
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @return RequestContext
     */
    private function getRequestContext(ServerRequestInterface $serverRequest): RequestContext
    {
        $uri = $serverRequest->getUri();

        return new RequestContext(
            '',
            $serverRequest->getMethod(),
            $uri->getHost(),
            $uri->getScheme(),
            80,
            443,
            $uri->getPath(),
            $uri->getQuery()
        );
    }
}
