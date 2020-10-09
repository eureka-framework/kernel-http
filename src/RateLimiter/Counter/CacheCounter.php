<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Counter;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * CacheCounter
 *
 * @author Romain Cottard
 */
class CacheCounter implements CounterInterface
{
    /** @var CacheItemPoolInterface $cache */
    private CacheItemPoolInterface $cache;

    /** @var int $cacheTTL */
    private int $cacheTTL;

    /** @var int $stepTTL */
    private int $stepTTL;

    /**
     * CacheCounter constructor.
     *
     * @param CacheItemPoolInterface $cache
     * @param int $cacheTTL Cache Time to live in second
     */
    public function __construct(CacheItemPoolInterface $cache, int $cacheTTL)
    {
        $this->cache    = $cache;
        $this->cacheTTL = $cacheTTL;
        $this->stepTTL  = (int) ceil($cacheTTL / 10);
    }

    /**
     * Increment counter and returns its new value
     *
     * @param string $id
     * @param int $value
     * @return int
     * @throws InvalidArgumentException
     */
    public function increment(string $id, int $value = 1): int
    {
        //~ Retrieve counter from cache
        $item    = $this->cache->getItem($id);
        $counter = $item->isHit() ? $item->get() : [];

        //~ Clean older values & add new value
        $counter = $this->clean($counter);
        $counter = $this->add($counter, $value);

        //~ Persist in cache
        $item->set($counter);
        $this->cache->save($item);

        return array_sum($counter);
    }

    /**
     * Returns current counter value
     *
     * @param string $id
     * @return int
     * @throws InvalidArgumentException
     */
    public function current(string $id): int
    {
        //~ Retrieve counter from cache
        $item    = $this->cache->getItem($id);
        $counter = $item->isHit() ? $item->get() : [];

        //~ Clean older values
        $counter = $this->clean($counter);

        return array_sum($counter);
    }

    /**
     * Deletes a counter
     *
     * @param string $id
     * @return void
     * @throws InvalidArgumentException
     */
    public function delete(string $id): void
    {
        if ($this->cache->hasItem($id)) {
            $this->cache->deleteItem($id);
        }
    }

    /**
     * Get Counter Time to live
     *
     * @return int
     */
    public function getTTL(): int
    {
        return $this->cacheTTL;
    }

    /**
     * @param array $counter
     * @return array
     */
    private function clean(array $counter): array
    {
        $minTimeStep = ceil((time() - $this->cacheTTL) / $this->stepTTL);
        foreach ($counter as $timeStep => $oldValue) {
            if ($minTimeStep > $timeStep) {
                unset($counter[$timeStep]);
            }
        }

        return $counter;
    }

    /**
     * @param array $counter
     * @param int $value
     * @return array
     */
    private function add(array $counter, int $value): array
    {
        $timeStep = ceil(time() / $this->stepTTL);
        if (!isset($counter[$timeStep])) {
            $counter[$timeStep] = 0;
        }

        $counter[$timeStep] += $value;

        return $counter;
    }
}
