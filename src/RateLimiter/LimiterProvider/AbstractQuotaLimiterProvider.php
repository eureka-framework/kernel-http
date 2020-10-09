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

/**
 * Abstract Class AbstractQuotaLimiterProvider
 *
 * @author Romain Cottard
 */
abstract class AbstractQuotaLimiterProvider
{
    /** @var CounterInterface $counter */
    private CounterInterface $counter;

    /** @var int */
    private int $quota;

    /**
     * Implement your validation rules here (the mandatory keys in the $parameters with their allowed types).
     *
     * @param array $parameters
     * @return void
     * @throws \InvalidArgumentException
     */
    abstract protected function validateParameters(array $parameters): void;

    /**
     * Returns the built counter id from the initial parameters.
     *
     * @param array $parameters
     * @return string
     */
    abstract protected function buildCounterId(array $parameters): string;

    /**
     * AbstractQuotaLimiterProvider constructor.
     *
     * @param CounterInterface $counter
     * @param int $quota
     */
    public function __construct(CounterInterface $counter, int $quota)
    {
        $this->counter = $counter;
        $this->quota   = $quota;
    }

    /**
     * @param array $parameters
     * @return QuotaLimiter
     */
    public function getQuotaLimiter(array $parameters): QuotaLimiter
    {
        $this->validateParameters($parameters);

        return new QuotaLimiter(
            $this->counter,
            $this->buildCounterId($parameters),
            $this->quota
        );
    }
}
