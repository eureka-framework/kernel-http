<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Eureka\Component\Http\HttpFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Router;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
abstract class Controller implements ControllerInterface
{
    /** @var Router $router */
    private $router;

    /** @var array $route Route parameters */
    private $route;

    /** @var ContainerInterface $container */
    private $container;

    /** @var DataCollection $context Data collection object. */
    protected $context = null;


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->context = new DataCollection();
    }

    /**
     * This method is executed before the main run() method.
     *
     * @return void
     */
    public function preAction(): void
    {
    }

    /**
     * This method is executed after the main run() method.
     *
     *& @return void
     */
    public function postAction(): void
    {
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set route parameters.
     *
     * @param array $route
     * @return $this
     */
    public function setRoute(array $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Set router.
     *
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router): self
    {
        $this->router = $router;

        return $this;
    }

    /**
     * @return HttpFactory
     */
    protected function getHttpFactory(): HttpFactory
    {
        return $this->getContainer()->get(HttpFactory::class);
    }

    /**
     * Get route parameters
     *
     * @return array
     */
    protected function getRoute(): array
    {
        return $this->route;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParameter(string $name, $default = null)
    {
        return isset($this->route[$name]) ? $this->route[$name] : $default;
    }

    /**
     * Get uri by name.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    protected function getUri(string $name, $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Get data collection.
     *
     * @return DataCollection
     */
    protected function getContext()
    {
        return $this->context;
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
            $protocolVersion = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

            header('HTTP/' . $protocolVersion . ' ' . $status . ' Redirect');
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
        $this->redirect($this->getUri($routeName, $params), $status);
    }

    /**
     * @return \Psr\Container\ContainerInterface
     */
    final private function getContainer(): \Psr\Container\ContainerInterface
    {
        return $this->container;
    }

}
