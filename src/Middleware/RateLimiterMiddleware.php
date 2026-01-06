<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Exception\HttpTooManyRequestsException;
use Eureka\Kernel\Http\RateLimiter\Counter\CacheCounter;
use Eureka\Kernel\Http\RateLimiter\Exception\QuotaExceededException;
use Eureka\Kernel\Http\RateLimiter\LimiterProvider\RouteQuotaLimiterProvider;
use Eureka\Kernel\Http\Service\IpResolver;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Exception Code Range: 910-919
 */
readonly class RateLimiterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private IpResolver $ipResolver,
    ) {}

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @throws HttpTooManyRequestsException
     */
    public function process(ServerRequestInterface $serverRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array<string, string|int|bool|float|bool|null>|null $route */
        $route = $serverRequest->getAttribute('route', null);

        if (\is_array($route) && $route !== []) {
            $this->assertQuotaNotReached(
                $route,
                $this->ipResolver->resolve($serverRequest),
            );
        }

        return $handler->handle($serverRequest);
    }

    /**
     * @param array<string, string|int|bool|float|bool|null> $route
     * @throws HttpTooManyRequestsException
     */
    private function assertQuotaNotReached(array $route, string $ip): void
    {
        $quota = (int) ($route['rateLimiterQuota'] ?? 0);
        $ttl   = (int) ($route['rateLimiterTTL'] ?? 0);

        if ($ttl === 0 || $quota === 0) {
            return;
        }

        $cacheCounter              = new CacheCounter($this->cache, $ttl);
        $routeQuotaLimiterProvider = new RouteQuotaLimiterProvider($cacheCounter, $quota);

        /** @var array<string, string> $parameters */
        $parameters = [
            'route' => $route['_route'],
            'ip'    => $ip,
        ];

        try {
            $routeQuotaLimiterProvider->getQuotaLimiter($parameters)->assertQuotaNotReached();
        } catch (QuotaExceededException) {
            throw new HttpTooManyRequestsException('Too Many Requests', 429);
        }
    }
}
