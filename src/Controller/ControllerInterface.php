<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Controller;

use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    /**
     * Set current route data.
     *
     * @param array<string, string|int|bool|float|bool|null> $route
     */
    public function setRoute(array $route): void;

    /**
     * This method is executed before the main controller action method.
     */
    public function preAction(?ServerRequestInterface $serverRequest = null): void;

    /**
     * This method is executed after the main controller action method.
     */
    public function postAction(?ServerRequestInterface $serverRequest = null): void;
}
