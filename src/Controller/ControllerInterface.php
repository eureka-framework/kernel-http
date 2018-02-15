<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Controller;

/**
 * Controller interface
 *
 * @author Romain Cottard
 */
interface ControllerInterface
{
    /**
     * This method is executed before the main controller method.
     *
     * @return void
     */
    public function runBefore();

    /**
     * This method is executed after the main run() method.
     *
     * @return void
     */
    public function runAfter();
}
