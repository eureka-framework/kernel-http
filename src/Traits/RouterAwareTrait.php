<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Traits;

use Symfony\Component\Routing\Router;

/**
 * Trait RouterAwareTrait
 *
 * @author Romain Cottard
 */
trait RouterAwareTrait
{
    /** @var Router $router */
    protected $router;

    /** @var array $route */
    protected $route = [];

    /**
     * @param Router $router
     * @return void
     */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * @param array $route
     * @return void
     */
    public function setRoute(array $route): void
    {
        $this->route = $route;
    }

    /**
     * @return Router
     */
    protected function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @return array
     */
    protected function getRoute(): array
    {
        return $this->route;
    }

    /**
     * @param string $routeName
     * @param array $params
     * @return string
     */
    protected function getRouteUri(string $routeName, array $params = []): string
    {
        return $this->router->generate($routeName, $params);
    }

    /**
     * @param  string $name
     * @return bool
     */
    protected function hasParameter(string $name): bool
    {
        return isset($this->route[$name]);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    protected function getParameter(string $name, $default = null)
    {
        return $this->route[$name] ?? $default;
    }
}
