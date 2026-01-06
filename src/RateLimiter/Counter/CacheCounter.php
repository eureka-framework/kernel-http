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

class CacheCounter implements CounterInterface
{
    private int $stepTTL;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $cacheTTL,
    ) {
        $this->stepTTL = (int) ceil($cacheTTL / 10);
    }

    /**
     * Increment counter and returns its new value
     *
     * @throws InvalidArgumentException
     */
    public function increment(string $id, int $value = 1): int
    {
        //~ Retrieve counter from cache
        $item    = $this->cache->getItem($id);
        /** @var array<int, int> $counter */
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
     * @throws InvalidArgumentException
     */
    public function current(string $id): int
    {
        //~ Retrieve counter from cache
        $item    = $this->cache->getItem($id);
        /** @var array<int, int> $counter */
        $counter = $item->isHit() ? $item->get() : [];

        //~ Clean older values
        $counter = $this->clean($counter);

        return array_sum($counter);
    }

    /**
     * Deletes a counter
     *
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
     */
    public function getTTL(): int
    {
        return $this->cacheTTL;
    }

    /**
     * @param array<int, int> $counter
     * @return array<int, int>
     */
    private function clean(array $counter): array
    {
        $minTimeStep = (int) ceil((time() - $this->cacheTTL) / $this->stepTTL);
        foreach ($counter as $timeStep => $oldValue) {
            if ($minTimeStep > $timeStep) {
                unset($counter[$timeStep]);
            }
        }

        return $counter;
    }

    /**
     * @param array<int, int> $counter
     * @return array<int, int>
     */
    private function add(array $counter, int $value): array
    {
        $timeStep = (int) ceil(time() / $this->stepTTL);
        if (!isset($counter[$timeStep])) {
            $counter[$timeStep] = 0;
        }

        $counter[$timeStep] += $value;

        return $counter;
    }
}
