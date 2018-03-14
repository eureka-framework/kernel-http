<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Eureka\Component\Config\Config;
use Eureka\Component\Http\Message\ServerRequest;
use Eureka\Component\Routing\RouteInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
abstract class Controller implements ControllerInterface
{
    /** @var \Eureka\Component\Routing\RouteInterface $route Route object. */
    protected $route = null;

    /** @var \Eureka\Kernel\Http\Controller\DataCollection $context Data collection object. */
    protected $context = null;

    /** @var \Psr\Http\Message\ServerRequestInterface $request */
    private $request = null;

    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /** @var \Eureka\Component\Config\Config $config */
    protected $config = null;

    /**
     * Class constructor
     *
     * @param  \Psr\Container\ContainerInterface $container
     * @param  Config $config
     * @param  RouteInterface $route
     * @param  ServerRequestInterface $request
     */
    public function __construct(ContainerInterface $container, Config $config, RouteInterface $route, ServerRequestInterface $request = null)
    {
        $this->container = $container;
        $this->config    = $config;
        $this->route     = $route;
        $this->request   = $request;

        $this->context = new DataCollection();
    }

    /**
     * This method is executed before the main run() method.
     *
     * @return void
     */
    public function runBefore()
    {
    }

    /**
     * This method is executed after the main run() method.
     *
     *& @return void
     */
    public function runAfter()
    {
    }

    /**
     * Get container
     *
     * @return \Psr\Container\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Get container
     *
     * @return \Eureka\Component\Config\Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Get current route.
     *
     * @return \Eureka\Component\Routing\RouteInterface
     */
    protected function getCurrentRoute()
    {
        return $this->route;
    }

    /**
     * Get route by name.
     *
     * @param  string $name
     * @return \Eureka\Component\Routing\Route
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getRoute($name)
    {
        return $this->getContainer()->get('router')->get($name);
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest()
    {
        if (!($this->request instanceof ServerRequestInterface)) {
            $this->request = ServerRequest::createFromGlobal();
        }

        return $this->request;
    }

    /**
     * Add data to the data collection.
     *
     * @param  string $key
     * @param  mixed $value
     * @return static
     */
    protected function addContext($key, $value)
    {
        $this->context->add($key, $value);

        return $this;
    }

    /**
     * Get data collection.
     *
     * @return array
     */
    protected function getContext()
    {
        return $this->context->toArray();
    }

    /**
     * Override meta description with given description.
     *
     * @param  string $title
     * @param  string $description
     * @return $this
     */
    protected function setMetas($title = null, $description = null)
    {
        $meta = $this->getConfig()->get('global.meta');

        if ($title !== null) {
            $meta['title'] = strip_tags($title . ' - ' . $meta['title']);
        }

        if ($description !== null) {
            $meta['description'] = strip_tags($description);
        }

        $this->getConfig()->add('app.meta', $meta);

        return $this;
    }

    /**
     * Redirect on specified url.
     *
     * @param  string $url
     * @param  int    $status
     * @return void
     * @throws \Exception
     */
    protected function redirect($url, $status = 301)
    {
        $status = (int) $status;

        if (!empty($url)) {

            header('HTTP/' . $this->getRequest()->getProtocolVersion() . ' ' . $status . ' Redirect');
            header('Status: ' . $status . ' Redirect');
            header('Location: ' . $url);
            exit(0);
        } else {
            throw new \Exception('Url is empty !');
        }
    }

    /**
     * Redirect on specified route name.
     *
     * @param  string $routeName
     * @param  array  $params
     * @param  int    $status
     * @return void
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Exception
     */
    protected function redirectToRoute($routeName, $params = [], $status = 200)
    {
        $this->redirect($this->getRoute($routeName)->getUri($params), $status);
    }
}
