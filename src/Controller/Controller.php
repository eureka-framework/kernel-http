<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Eureka\Component\Http\HttpFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\Routing\Router;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
abstract class Controller implements ControllerInterface
{
    /** @var array $route Route parameters */
    private $route = [];

    /** @var ContainerInterface $container */
    private $container;

    /** @var bool $debug */
    private $debug = false;

    /** @var string $environment */
    private $environment = 'prod';

    /**
     * This method is executed before the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function preAction(?ServerRequestInterface $request = null): void
    {
    }

    /**
     * This method is executed after the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function postAction(?ServerRequestInterface $request = null): void
    {
    }

    /**
     * @param ContainerInterface $container
     * @return ControllerInterface
     */
    public function setContainer(ContainerInterface $container): ControllerInterface
    {
        /** @var SymfonyContainerInterface $container */
        $this->container   = $container;
        $this->debug       = $container->getParameter('kernel.debug');
        $this->environment = $container->getParameter('kernel.environment');

        return $this;
    }

    /**
     * Set route parameters.
     *
     * @param array $route
     * @return $this
     */
    public function setRoute(array $route): ControllerInterface
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return bool
     */
    protected function isDev(): bool
    {
        return $this->environment === 'dev';
    }

    /**
     * @return bool
     */
    protected function isProd(): bool
    {
        return $this->environment === 'prod';
    }

    /**
     * @return string
     */
    protected function environment(): string
    {
        return $this->environment;
    }

    /**
     * @return Router
     */
    protected function getRouter(): Router
    {
        return $this->getContainer()->get('router');
    }

    /**
     * @return HttpFactory
     */
    protected function getHttpFactory(): HttpFactory
    {
        return $this->getContainer()->get('http_factory');
    }

    /**
     * @param string $content
     * @param int $code
     * @return ResponseInterface
     */
    protected function getResponse(string $content, int $code = 200): ResponseInterface
    {
        $response = $this->getHttpFactory()->createResponse($code);
        $response->getBody()->write($content);

        return $response;
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
        return $this->getRouter()->generate($name, $params);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isAjax(ServerRequestInterface $request): bool
    {
        $server = $request->getServerParams();

        return !empty($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Redirect on specified url.
     *
     * @param  string $url
     * @param  int    $status
     * @return void
     */
    protected function redirect($url, $status = 301): void
    {
        $status = (int) $status;

        if (!empty($url)) {
            $protocolVersion = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

            header('HTTP/' . $protocolVersion . ' ' . $status . ' Redirect');
            header('Status: ' . $status . ' Redirect');
            header('Location: ' . $url);
            exit(0);
        } else {
            throw new \InvalidArgumentException('Url is empty !');
        }
    }

    /**
     * @param $routeName
     * @param array $params
     * @param int $status
     * @return void
     */
    protected function redirectToRoute($routeName, $params = [], $status = 200): void
    {
        $this->redirect($this->getUri($routeName, $params), $status);
    }

    /**
     * @return ContainerInterface
     */
    final private function getContainer(): ContainerInterface
    {
        return $this->container;
    }

}
