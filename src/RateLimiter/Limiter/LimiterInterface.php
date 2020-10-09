<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Limiter;

use Eureka\Kernel\Http\RateLimiter\Exception\QuotaExceededException;

/**
 * Interface LimiterInterface
 *
 * @author Romain Cottard
 */
interface LimiterInterface
{
    /**
     * Assert usage is valid or throws exception
     *
     * @return void
     * @throws QuotaExceededException
     */
    public function assertQuotaNotReached(): void;
}
