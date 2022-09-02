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
 * Class RateLimiterMiddleware
 * Exception Code Range: 910-919
 *
 * @author Romain Cottard
 */
class RateLimiterMiddleware implements MiddlewareInterface
{
    protected CacheItemPoolInterface $cache;
    protected IpResolver $ipResolver;

    /**
     * RateLimiterMiddleware constructor.
     *
     * @param CacheItemPoolInterface $cache
     * @param IpResolver $ipResolver
     */
    public function __construct(CacheItemPoolInterface $cache, IpResolver $ipResolver)
    {
        $this->cache      = $cache;
        $this->ipResolver = $ipResolver;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws HttpTooManyRequestsException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array<string, string|int|bool|float|bool|null>|null $route */
        $route = $request->getAttribute('route', null);

        if (!empty($route)) {
            $this->assertQuotaNotReached(
                $route,
                $this->ipResolver->resolve($request)
            );
        }

        return $handler->handle($request);
    }

    /**
     * @param array<string, string|int|bool|float|bool|null> $route
     * @param string $ip
     * @return void
     * @throws HttpTooManyRequestsException
     */
    private function assertQuotaNotReached(array $route, string $ip): void
    {
        $quota = (int) ($route['rateLimiterQuota'] ?? 0);
        $ttl   = (int) ($route['rateLimiterTTL'] ?? 0);

        if (empty($ttl) || empty($quota)) {
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
        } catch (QuotaExceededException $exception) {
            throw new HttpTooManyRequestsException('Too Many Requests', 429);
        }
    }
}
