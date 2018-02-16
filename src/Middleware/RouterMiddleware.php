<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Framework\Middleware;

use Eureka\Component\Config\Config;
use Eureka\Psr\Http\Server\MiddlewareInterface;
use Eureka\Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Eureka\Component\Routing\Route;

class RouterMiddleware implements MiddlewareInterface
{
    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /** @var Config config */
    protected $config = null;

    /** @var \Eureka\Component\Routing\RouteCollection $collection */
    private $collection = null;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param Config $config
     * @throws \Eureka\Component\Routing\Exception\RoutingException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(ContainerInterface $container, Config $config)
    {
        $this->container = $container;
        $this->config    = $config;

        //~ Pre-load routing fro config
        $this->collection = $this->container->get('routing');
        $this->collection->addFromConfig($config->get('app.routing'));

        $this->container->attach('routes', $this->collection);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Eureka\Psr\Http\Server\RequestHandlerInterface
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Eureka\Kernel\Framework\Middleware\Exception\RouteNotFoundException
     * @throws \Eureka\Component\Routing\Exception\RoutingException
     * @throws \Eureka\Component\Routing\Exception\ParameterException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $route = $this->collection->match((string) $request->getUri(), false);

        if (!($route instanceof Route)) {
            throw new Exception\RouteNotFoundException('Route not found', 10001);
        }

        $request = $request->withAttribute('route', $route);

        return $handler->handle($request);
    }
}
