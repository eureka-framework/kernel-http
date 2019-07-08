<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Application;

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
     * @return ApplicationInterface
     */
    public function run(): ApplicationInterface;
}
