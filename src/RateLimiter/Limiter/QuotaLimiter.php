<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Limiter;

use Eureka\Kernel\Http\RateLimiter\Counter\CounterInterface;
use Eureka\Kernel\Http\RateLimiter\Exception\QuotaExceededException;

readonly class QuotaLimiter implements LimiterInterface
{
    /**
     * @param CounterInterface $counters Auto resetting counter
     * @param string $counterId
     * @param int $quota Number of allowed calls during the above time slot
     */
    public function __construct(
        private CounterInterface $counters,
        private string $counterId,
        private int $quota,
    ) {}

    /**
     * Assert usage is valid or throws exception
     *
     * @return void
     * @throws QuotaExceededException
     */
    public function assertQuotaNotReached(): void
    {
        $count = $this->counters->increment($this->counterId);

        if ($count > $this->quota) {
            throw new QuotaExceededException(sprintf(
                'Too many requests. Quota: %d per %d seconds, got %d',
                $this->quota,
                $this->counters->getTTL(),
                $count,
            ));
        }
    }
}
