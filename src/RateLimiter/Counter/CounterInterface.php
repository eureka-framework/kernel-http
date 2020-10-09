<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Counter;

/**
 * Abstract Class CounterInterface
 *
 * @author Romain Cottard
 */
interface CounterInterface
{
    /**
     * Increment counter and returns its new value
     *
     * @param string $id
     * @param int $value
     * @return int
     */
    public function increment(string $id, int $value = 1): int;

    /**
     * Returns current counter value
     *
     * @param string $id
     * @return int
     */
    public function current(string $id): int;

    /**
     * Deletes a counter
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Get Counter Time to live
     *
     * @return int
     */
    public function getTTL(): int;
}
