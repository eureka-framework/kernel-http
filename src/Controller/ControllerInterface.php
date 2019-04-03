<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller interface
 *
 * @author Romain Cottard
 */
interface ControllerInterface
{
    /**
     * @param ContainerInterface $container
     * @return ControllerInterface
     */
    public function setContainer(ContainerInterface $container): self;

    /**
     * Set route parameters.
     *
     * @param array $route
     * @return ControllerInterface
     */
    public function setRoute(array $route): self;

    /**
     * This method is executed before the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function preAction(?ServerRequestInterface $request = null): void;

    /**
     * This method is executed after the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function postAction(?ServerRequestInterface $request = null): void;
}
