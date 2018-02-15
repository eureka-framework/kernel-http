<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Middleware;

use Eureka\Component\Config\Config;
use Eureka\Component\Container\Container;
use Eureka\Component\Http\Message\Response;
use Eureka\Component\Psr\Http\Middleware\DelegateInterface;
use Eureka\Component\Psr\Http\Middleware\ServerMiddlewareInterface;
use Eureka\Middleware\Routing\Exception\RouteNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Eureka\Component\Routing\Route;

class RouterMiddleware implements ServerMiddlewareInterface
{
    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /** @var Config config */
    protected $config = null;

    /** @var Routing\RouteCollection $collection */
    private $collection = null;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param Config $config
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
     * @param ServerRequestInterface  $request
     * @param DelegateInterface $frame
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $frame)
    {
        $route = $this->collection->match((string) $request->getUri(), false);

        if (!($route instanceof Route)) {
            throw new Exception\RouteNotFoundException('Route not found', 10001);
        }

        $request = $request->withAttribute('route', $route);

        return $frame->next($request);
    }
}
