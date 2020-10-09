<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\LimiterProvider;

/**
 * Class RouteQuotaLimiterProvider
 *
 * @author Romain Cottard
 */
class RouteQuotaLimiterProvider extends AbstractQuotaLimiterProvider
{
    /** @const PARAM_EMAIL string*/
    public const PARAM_ROUTE = 'route';

    /** @const PARAM_CLIENT_IP string*/
    public const PARAM_CLIENT_IP = 'ip';

    /**
     * Implement your validation rules here (the mandatory keys in the $parameters with their allowed types).
     *
     * @param array $parameters
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateParameters(array $parameters): void
    {
        if (empty($parameters[self::PARAM_ROUTE]) || !is_string($parameters[self::PARAM_ROUTE])) {
            throw new \InvalidArgumentException('Parameters should contain an "route" index');
        }


        if (!isset($parameters[self::PARAM_CLIENT_IP]) || !is_string($parameters[self::PARAM_CLIENT_IP])) {
            throw new \InvalidArgumentException('Parameters should contain an "ip" index');
        }
    }

    /**
     * Returns the built cache key from the initial parameters.
     *
     * @param array $parameters
     * @return string
     */
    protected function buildCounterId(array $parameters): string
    {
        $hashId = md5($parameters[self::PARAM_ROUTE] . '_' . $parameters[self::PARAM_CLIENT_IP]);
        return 'rate_limiter.counter.route.' . $hashId;
    }
}
