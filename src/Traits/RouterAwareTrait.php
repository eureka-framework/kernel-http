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

trait RouterAwareTrait
{
    protected Router $router;

    /** @var array<string, string|int|bool|float|null> */
    protected array $route = [];

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * @param array<string, string|int|bool|float|null> $route
     */
    public function setRoute(array $route): void
    {
        $this->route = $route;
    }

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
     * @param array<string, string|int|bool|float|bool|null> $params
     */
    protected function getRouteUri(string $routeName, array $params = []): string
    {
        return $this->router->generate($routeName, $params);
    }

    protected function hasParameter(string $name): bool
    {
        return isset($this->route[$name]);
    }

    /**
     * @param string|int|bool|float|null $default
     */
    protected function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->route[$name] ?? $default;
    }
}
