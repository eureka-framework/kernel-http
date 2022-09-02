<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\RateLimiter;

use Eureka\Kernel\Http\RateLimiter\Counter\CacheCounter;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class CacheCounterTest
 *
 * @author Romain Cottard
 */
class CacheCounterTest extends TestCase
{
    /** @var string COUNTER_ID */
    private const COUNTER_ID = 'counter.id';

    /**
     * @return void
     */
    public function testICanInstantiateCacheCounterClass(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 5);

        $this->assertInstanceOf(CacheCounter::class, $cacheCounter);
    }

    /**
     * @return void
     */
    public function testICanAddValueOneTwiceAndGetTwoAsValue(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 5);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        $this->assertEquals(2, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetOneAsValueWhenFirstElementIsOutOfTTL(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        sleep(2);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        $this->assertEquals(1, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetZeroAsValueWhenAllElementsAreOutOfTTL(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        sleep(2);

        $this->assertEquals(0, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testICanAddValueOneTwiceAndGetZeroAfterDeletionOfCounter(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 10);
        $cacheCounter->increment(self::COUNTER_ID, 1);
        $cacheCounter->increment(self::COUNTER_ID, 1);

        $this->assertEquals(2, $cacheCounter->current(self::COUNTER_ID));

        $cacheCounter->delete(self::COUNTER_ID);

        $this->assertEquals(0, $cacheCounter->current(self::COUNTER_ID));
    }

    /**
     * @return void
     */
    public function testICanGetCounterTTLValue(): void
    {
        $cacheCounter = new CacheCounter(new ArrayAdapter(100), 10);

        $this->assertEquals(10, $cacheCounter->getTTL());
    }
}
