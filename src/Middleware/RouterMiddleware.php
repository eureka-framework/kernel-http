<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Middleware\Exception\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;

class RouterMiddleware implements MiddlewareInterface
{
    /** @var Router $router */
    protected $router;

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
     * @throws
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //~ Try to match url
        try {
            $route = $this->router->match((string) $request->getUri()->getPath());
        } catch (ResourceNotFoundException $exception) {
            throw new RouteNotFoundException('Page not found', 404, $exception);
        }

        //~ Add route param to request
        $request = $request->withAttribute('route', $route);

        //~ Add route element to requests
        foreach ($route as $key => $value) {
            if ($key[0] === '_') {
                continue;
            }

            $request = $request->withAttribute($key, $value);
        }

        return $handler->handle($request);
    }
}
