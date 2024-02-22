<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit\RateLimiter;

use Eureka\Kernel\Http\RateLimiter\Counter\CacheCounter;
use Eureka\Kernel\Http\RateLimiter\Exception\QuotaExceededException;
use Eureka\Kernel\Http\RateLimiter\LimiterProvider\RouteQuotaLimiterProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class LimiterProviderTest
 *
 * @author Romain Cottard
 */
class LimiterProviderTest extends TestCase
{
    private const LOCAL_IP   = '127.0.0.1';

    /**
     * @return void
     */
    public function testICanInstantiateRouteQuotaLimiterProviderClass(): void
    {
        $cacheCounter    = new CacheCounter(new ArrayAdapter(100), 5);
        $limiterProvider = new RouteQuotaLimiterProvider($cacheCounter, 2);

        self::assertInstanceOf(RouteQuotaLimiterProvider::class, $limiterProvider);
    }

    /**
     * @return void
     */
    public function testICanAssertTwiceQuotaIsNotReachedWithTwoAsQuota(): void
    {
        $cacheCounter    = new CacheCounter(new ArrayAdapter(100), 5);
        $limiterProvider = new RouteQuotaLimiterProvider($cacheCounter, 2);

        $parameters = [
            RouteQuotaLimiterProvider::PARAM_ROUTE     => 'route_name',
            RouteQuotaLimiterProvider::PARAM_CLIENT_IP => self::LOCAL_IP,
        ];

        $limiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();
        $limiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();

        self::assertTrue(true);
    }

    /**
     * @return void
     */
    public function testAnExceptionIsThrownWhenTryToAssertTriceWithTwoAsQuota(): void
    {
        $cacheCounter    = new CacheCounter(new ArrayAdapter(100), 5);
        $limiterProvider = new RouteQuotaLimiterProvider($cacheCounter, 2);

        $parameters = [
            RouteQuotaLimiterProvider::PARAM_ROUTE     => 'route_name',
            RouteQuotaLimiterProvider::PARAM_CLIENT_IP => self::LOCAL_IP,
        ];

        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessage('Too many requests. Quota: 2 per 5 seconds, got 3');

        $limiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();
        $limiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();
        $limiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();
    }

    /**
     * @return void
     */
    public function testAnExceptionIsThrownWhenTryToGetQuotaLimiterWithoutRequiredRouteParameters(): void
    {
        $cacheCounter    = new CacheCounter(new ArrayAdapter(100), 5);
        $limiterProvider = new RouteQuotaLimiterProvider($cacheCounter, 2);

        $parameters = [
            RouteQuotaLimiterProvider::PARAM_CLIENT_IP => self::LOCAL_IP,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameters should contain an "route" index');

        $limiterProvider->getQuotaLimiter($parameters);
    }

    /**
     * @return void
     */
    public function testAnExceptionIsThrownWhenTryToGetQuotaLimiterWithoutRequiredIpParameters(): void
    {
        $cacheCounter    = new CacheCounter(new ArrayAdapter(100), 5);
        $limiterProvider = new RouteQuotaLimiterProvider($cacheCounter, 2);

        $parameters = [
            RouteQuotaLimiterProvider::PARAM_ROUTE     => 'route_name',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameters should contain an "ip" index');

        $limiterProvider->getQuotaLimiter($parameters);
    }
}
