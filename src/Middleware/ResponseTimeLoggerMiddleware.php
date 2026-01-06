<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

readonly class ResponseTimeLoggerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $applicationName,
    ) {}

    /**
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $serverRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        $time = -microtime(true);
        try {
            $response = $handler->handle($serverRequest);
            $httpCode = $response->getStatusCode();
        } catch (\Throwable $exception) {
            $httpCode = $exception->getCode() > 599 ? 500 : $exception->getCode();
        } finally {
            $time += microtime(true);
            $time = (int) ($time * 1000);

            $page = $serverRequest->getUri()->getPath();
            $queryParams = $serverRequest->getQueryParams();

            /** @var Route|null $route */
            $route = $serverRequest->getAttribute('routeInstance');

            //In case of a Redirect Response, we don't log the response time because of RouterMiddleware exit
            $this->logger->info(
                $page . ' took ' . $time . 'ms to respond',
                [
                    'type'        => $this->applicationName . '.page.response_time',
                    'application' => $this->applicationName,
                    'path'        => $page,
                    'route'       => $route?->getPath() ?? '-',
                    'queryParams' => $queryParams,
                    'httpCode'    => $httpCode,
                    'counters'    => [
                        'page_time_ms' => $time,
                    ],
                ],
            );

            //~ Rethrow exception when finally come after a catch of an exception
            if (isset($exception)) {
                throw $exception;
            }
        }

        if (!isset($response)) {
            //~ Should not happen
            throw new HttpInternalServerErrorException('Response not defined!'); // @codeCoverageIgnore
        }

        return $response;
    }
}
