<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ResponseTimeLoggerMiddleware
 *
 * @author Pierre-Olivier Dézard
 */
class ResponseTimeLoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private string $applicationName;

    /**
     * ResponseTimeLoggerMiddleWare constructor.
     *
     * @param LoggerInterface $logger
     * @param string $applicationName
     */
    public function __construct(
        LoggerInterface $logger,
        string $applicationName
    ) {
        $this->logger          = $logger;
        $this->applicationName = $applicationName;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $time = -microtime(true);
        $response = $handler->handle($request);
        $time += microtime(true);
        $time = (int) ($time * 1000);

        $page = $request->getUri()->getPath();

        //In case of a Redirect Response, we don't log the response time because of RouterMiddleware exit
        $this->logger->info(
            $page . ' took ' . $time . 'ms to respond',
            [
                'type' => $this->applicationName . '.page.response_time',
                'application' => $this->applicationName,
                'page' => $page,
                'counters' => [
                    'page_time_ms' => $time,
                ],
            ]
        );

        return $response;
    }
}
