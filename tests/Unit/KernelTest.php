<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit;

use Eureka\Kernel\Http\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCanInstantiateKernel(): void
    {
        $root  = (string) realpath(__DIR__ . '/../..');
        $env   = 'dev';
        $debug = true;

        new Kernel($root, $env, $debug);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws \Exception
     */
    public function testCanGetContainer(): void
    {
        $root  = (string) realpath(__DIR__ . '/../..');
        $env   = 'dev';
        $debug = true;

        new Kernel($root, $env, $debug);

        $this->expectNotToPerformAssertions();
    }
}
