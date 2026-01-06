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
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheCounterTest extends TestCase
{
    private const string COUNTER_ID = 'counter.id';

    /**
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetTwoAsValue(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 5);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        self::assertSame(2, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetOneAsValueWhenFirstElementIsOutOfTTL(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        sleep(2);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        self::assertSame(1, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetZeroAsValueWhenAllElementsAreOutOfTTL(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        sleep(2);

        self::assertSame(0, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetZeroAfterDeletionOfCounter(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 10);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        self::assertSame(2, $cacheCounter->current(self::COUNTER_ID));

        $cacheCounter->delete(self::COUNTER_ID);

        self::assertSame(0, $cacheCounter->current(self::COUNTER_ID));
    }

    public function testICanGetCounterTTLValue(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 10);

        self::assertSame(10, $cacheCounter->getTTL());
    }
}
