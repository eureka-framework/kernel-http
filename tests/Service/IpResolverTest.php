<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Service;

use Eureka\Kernel\Http\Service\IpResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class IpTest
 *
 * @author Romain Cottard
 */
class IpResolverTest extends TestCase
{
    /**
     * @return void
     */
    public function testIGetEmptyIpFromUtilsWhenUseLocalhostIp(): void
    {
        $serverRequest = $this->getServerRequest('127.0.0.1');

        $this->assertEmpty((new IpResolver())->resolve($serverRequest));
    }

    /**
     * @return void
     */
    public function testIGetMyIpFromUtilsWhenUseMyIp(): void
    {
        $serverRequest = $this->getServerRequest('1.2.3.4');

        $this->assertEquals('1.2.3.4', (new IpResolver())->resolve($serverRequest));
    }

    /**
     * @return void
     */
    public function testIGetMyPrivateIpFromUtilsWhenUseMyPrivateIp(): void
    {
        $serverRequest = $this->getServerRequest('172.16.1.2');

        $this->assertEquals('172.16.1.2', (new IpResolver())->resolve($serverRequest));
    }

    /**
     * @return void
     */
    public function testIGetEmptyIpFromUtilsWithExcludedPrivateIpWhenUseMyPrivateIp(): void
    {
        $serverRequest = $this->getServerRequest('172.16.1.3');

        $this->assertEmpty((new IpResolver())->resolve($serverRequest, true));
    }

    /**
     * @param string $ip
     * @return ServerRequestInterface
     */
    private function getServerRequest(string $ip): ServerRequestInterface
    {
        $server = $_SERVER;
        $server['HTTP_X_FORWARDED'] = $ip;

        $httpFactory   = new Psr17Factory();
        return $httpFactory->createServerRequest('GET', $httpFactory->createUri('/'), $server);
    }
}
