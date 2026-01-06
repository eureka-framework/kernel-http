<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit\Service;

use Eureka\Kernel\Http\Service\IpResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class IpResolverTest extends TestCase
{
    public function testIGetEmptyIpFromUtilsWhenUseLocalhostIp(): void
    {
        $serverRequest = $this->getServerRequest('127.0.0.1');

        self::assertEmpty((new IpResolver())->resolve($serverRequest));
    }

    public function testIGetMyIpFromUtilsWhenUseMyIp(): void
    {
        $serverRequest = $this->getServerRequest('1.2.3.4');

        self::assertSame('1.2.3.4', (new IpResolver())->resolve($serverRequest));
    }

    public function testIGetMyIpFromUtilsWhenUseMyIpWithXForwardedForIps(): void
    {
        $serverRequest = $this->getServerRequest('1.2.3.4', '1.2.3.5,1.2.3.6');

        self::assertSame('1.2.3.5', (new IpResolver())->resolve($serverRequest));
    }

    public function testIGetMyPrivateIpFromUtilsWhenUseMyPrivateIp(): void
    {
        $serverRequest = $this->getServerRequest('172.16.1.2');

        self::assertSame('172.16.1.2', (new IpResolver())->resolve($serverRequest));
    }

    public function testIGetEmptyIpFromUtilsWithExcludedPrivateIpWhenUseMyPrivateIp(): void
    {
        $serverRequest = $this->getServerRequest('172.16.1.3');

        self::assertEmpty((new IpResolver())->resolve($serverRequest, true));
    }

    private function getServerRequest(string $ip, string $xForwardedFor = ''): ServerRequestInterface
    {
        $server = $_SERVER;
        $server['HTTP_X_FORWARDED'] = $ip;

        if ($xForwardedFor !== '') {
            $server['HTTP_X_FORWARDED_FOR'] = $xForwardedFor;
        }

        $httpFactory   = new Psr17Factory();
        return $httpFactory->createServerRequest('GET', $httpFactory->createUri('/'), $server);
    }
}
