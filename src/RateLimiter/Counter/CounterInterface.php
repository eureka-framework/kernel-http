<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Counter;

interface CounterInterface
{
    /**
     * Increment counter and returns its new value
     */
    public function increment(string $id, int $value = 1): int;

    /**
     * Returns current counter value
     */
    public function current(string $id): int;

    /**
     * Deletes a counter
     */
    public function delete(string $id): void;

    /**
     * Get Counter Time to live
     */
    public function getTTL(): int;
}
