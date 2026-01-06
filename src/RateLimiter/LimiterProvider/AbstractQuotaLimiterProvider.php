<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\LimiterProvider;

use Eureka\Kernel\Http\RateLimiter\Counter\CounterInterface;
use Eureka\Kernel\Http\RateLimiter\Limiter\QuotaLimiter;

abstract class AbstractQuotaLimiterProvider
{
    /**
     * Implement your validation rules here (the mandatory keys in the $parameters with their allowed types).
     *
     * @param array<string, string> $parameters
     * @throws \InvalidArgumentException
     */
    abstract protected function validateParameters(array $parameters): void;

    /**
     * Returns the built counter id from the initial parameters.
     *
     * @param array<string, string> $parameters
     */
    abstract protected function buildCounterId(array $parameters): string;

    public function __construct(
        private readonly CounterInterface $counter,
        private readonly int $quota,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function getQuotaLimiter(array $parameters): QuotaLimiter
    {
        $this->validateParameters($parameters);

        return new QuotaLimiter(
            $this->counter,
            $this->buildCounterId($parameters),
            $this->quota,
        );
    }
}
