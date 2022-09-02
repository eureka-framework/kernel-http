<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Traits;

use Symfony\Component\Routing\Router;

/**
 * Trait RouterAwareTrait
 *
 * @author Romain Cottard
 */
trait RouterAwareTrait
{
    protected Router $router;

    /** @var array<string, string|int|bool|float|null> */
    protected array $route = [];

    /**
     * @param Router $router
     * @return void
     */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * @param array<string, string|int|bool|float|null> $route
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
     * @return array<string, string|int|bool|float|bool|null>
     */
    protected function getRoute(): array
    {
        return $this->route;
    }

    /**
     * @param string $routeName
     * @param array<string, string|int|bool|float|bool|null> $params
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
     * @param string|int|bool|float|null $default
     * @return mixed|null
     */
    protected function getParameter(string $name, $default = null)
    {
        return $this->route[$name] ?? $default;
    }
}
