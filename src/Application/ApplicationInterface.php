<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Application;

/**
 * Application interface
 *
 * @author Romain Cottard
 */
interface ApplicationInterface
{
    /**
     * Run Application
     *
     * @return void
     */
    public function run();
}
