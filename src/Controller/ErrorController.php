<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Controller;

use Eureka\Kernel\Http\Exception\HttpBadRequestException;
use Eureka\Kernel\Http\Exception\HttpConflictException;
use Eureka\Kernel\Http\Exception\HttpForbiddenException;
use Eureka\Kernel\Http\Exception\HttpMethodNotAllowedException;
use Eureka\Kernel\Http\Exception\HttpNotFoundException;
use Eureka\Kernel\Http\Exception\HttpServiceUnavailableException;
use Eureka\Kernel\Http\Exception\HttpTooManyRequestsException;
use Eureka\Kernel\Http\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorController extends Controller implements ErrorControllerInterface
{
    public function error(ServerRequestInterface $serverRequest, \Throwable $exception): ResponseInterface
    {
        $httpCode = match (true) {
            $exception instanceof HttpBadRequestException => 400,
            $exception instanceof HttpUnauthorizedException => 401,
            $exception instanceof HttpForbiddenException => 403,
            $exception instanceof HttpNotFoundException => 404,
            $exception instanceof HttpMethodNotAllowedException => 405,
            $exception instanceof HttpConflictException => 409,
            $exception instanceof HttpTooManyRequestsException => 429,
            $exception instanceof HttpServiceUnavailableException => 503,
            default => 500,
        };

        if ($this->acceptJsonResponse()) {
            $content = $this->getErrorContentJson($httpCode, $exception); // @codeCoverageIgnore
        } else {
            $content = $this->getErrorContentHtml($serverRequest, $exception);
        }

        return $this->getResponse($content, $httpCode);
    }

    protected function getErrorContentHtml(ServerRequestInterface $request, \Throwable $exception): string
    {
        return
            '<pre>exception[' . $exception::class . ']: ' . PHP_EOL
            . $exception->getMessage() . PHP_EOL
            . ($this->isDebug() ? $exception->getTraceAsString() . PHP_EOL : '') . PHP_EOL
            . '</pre>';
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getErrorContentJson(int $code, \Throwable $exception): string
    {
        //~ Ajax response error - JsonApi.org error object format + trace
        $error = [
            'status' => (string) $code,
            'title'  => self::HTTP_CODE_MESSAGES[$code] ?? 'Unknown',
            'code'   => $exception->getCode() !== 0 ? (string) $exception->getCode() : '99',
            'detail' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Undefined message',
        ];

        if ($this->isDebug()) {
            $error['trace'] = $exception->getTraceAsString();
        }

        try {
            $content = \json_encode($error, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $content = 'json_encode error (' . $exception->getMessage() . ')';
        }

        return $content;
    }
}
