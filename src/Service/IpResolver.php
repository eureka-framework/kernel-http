<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Basic IP Resolver.
 *
 * @author Romain Cottard
 */
class IpResolver
{
    private const IP_INDICES_TO_CHECK = [
        'HTTP_CLIENT_IP',           // Shared internet/ISP IP
        'HTTP_X_FORWARDED_FOR',     // IPs passing through proxies
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',              // Unreliable IP address since all else failed
    ];

    /**
     * Retrieves the best guess of the client's actual IP address.
     * Takes into account numerous HTTP proxy headers due to variations
     * in how different ISPs handle IP addresses in headers between hops.
     *
     * @param ServerRequestInterface $serverRequest
     * @param bool $excludePrivate
     * @return string
     */
    public function resolve(
        ServerRequestInterface $serverRequest,
        bool $excludePrivate = false
    ): string {
        $server = $serverRequest->getServerParams();
        foreach (self::IP_INDICES_TO_CHECK as $index) {
            if (!isset($server[$index])) {
                continue;
            }
            $ips = $index === 'HTTP_X_FORWARDED_FOR' && isset($server[$index]) ? explode(',', $server[$index]) : [$server[$index]];

            foreach ($ips as $ip) {
                if (!empty($ip) && $this->validate($ip, $excludePrivate)) {
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * @param string $ip
     * @param bool $excludePrivate
     * @return bool
     */
    public function validate(string $ip, bool $excludePrivate = false): bool
    {
        $options = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;

        if ($excludePrivate) {
            $options |= FILTER_FLAG_NO_PRIV_RANGE;
        }

        return (filter_var($ip, FILTER_VALIDATE_IP, $options) !== false);
    }
}
