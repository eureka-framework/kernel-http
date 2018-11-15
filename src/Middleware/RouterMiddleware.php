<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class RouterMiddleware implements MiddlewareInterface
{
    /** @var ContainerInterface $container */
    protected $container;

    /**
     * ControllerMiddleware constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $route = $this->getRouter()->match((string) $request->getUri()->getPath());

        //~ Add route param to request
        $request = $request->withAttribute('route', $route);

        return $handler->handle($request);
    }

    /**
     * @return \Symfony\Component\Routing\Router
     */
    private function getRouter()
    {
        $fileLocator = new FileLocator([$this->container->getParameter('kernel.directory.config')]);
        $router = new Router(
            new YamlFileLoader($fileLocator),
            'routes.yaml',
            ['cache_dir' => $this->container->getParameter('kernel.directory.cache')],
            $this->getContext()
        );

        //~ Add router to the container
        $this->container->set(Router::class, $router);

        return $router;
    }

    /**
     * @return RequestContext
     */
    private function getContext()
    {
        return new RequestContext('/');
    }
}
