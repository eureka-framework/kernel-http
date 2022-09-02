<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests;

use Eureka\Kernel\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class KernelTest
 *
 * @author Romain Cottard
 */
class KernelTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testCanInstantiateKernel(): void
    {
        $root  = (string) realpath(__DIR__ . '/..');
        $env   = 'dev';
        $debug = true;

        $kernel = new Kernel($root, $env, $debug);

        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanGetContainer(): void
    {
        $root  = (string) realpath(__DIR__ . '/..');
        $env   = 'dev';
        $debug = true;

        $kernel = new Kernel($root, $env, $debug);

        $this->assertInstanceOf(ContainerInterface::class, $kernel->getContainer());
    }
}
