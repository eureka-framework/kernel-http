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
use Safe\Exceptions\JsonException;

use function Safe\json_encode;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
class ErrorController extends Controller implements ErrorControllerInterface
{
    /**
     * @param ServerRequestInterface $serverRequest
     * @param \Exception $exception
     * @return ResponseInterface
     */
    public function error(ServerRequestInterface $serverRequest, \Exception $exception): ResponseInterface
    {
        switch (true) {
            case $exception instanceof HttpBadRequestException:
                $httpCode = 400;
                break;
            case $exception instanceof HttpUnauthorizedException:
                $httpCode = 401;
                break;
            case $exception instanceof HttpForbiddenException:
                $httpCode = 403;
                break;
            case $exception instanceof HttpNotFoundException:
                $httpCode = 404;
                break;
            case $exception instanceof HttpMethodNotAllowedException:
                $httpCode = 405;
                break;
            case $exception instanceof HttpConflictException:
                $httpCode = 409;
                break;
            case $exception instanceof HttpTooManyRequestsException:
                $httpCode = 429;
                break;
            case $exception instanceof HttpServiceUnavailableException:
                $httpCode = 503;
                break;
            default:
                $httpCode = 500;
        }

        if ($this->acceptJsonResponse()) {
            $content = $this->getErrorContentJson($httpCode, $exception); // @codeCoverageIgnore
        } else {
            $content = $this->getErrorContentHtml($serverRequest, $exception);
        }

        return $this->getResponse($content, $httpCode);
    }

    /**
     * @param ServerRequestInterface $request
     * @param \Exception $exception
     * @return string
     */
    protected function getErrorContentHtml(ServerRequestInterface $request, \Throwable $exception): string
    {
        return
            '<pre>exception[' . get_class($exception) . ']: ' . PHP_EOL .
            $exception->getMessage() . PHP_EOL .
            ($this->isDebug() ? $exception->getTraceAsString() . PHP_EOL : '') . PHP_EOL .
            '</pre>';
    }

    /**
     * @param int $code
     * @param \Exception $exception
     * @return string
     * @codeCoverageIgnore
     */
    protected function getErrorContentJson(int $code, \Throwable $exception): string
    {
        //~ Ajax response error - JsonApi.org error object format + trace
        $error = [
            'status' => (string) $code,
            'title'  => self::HTTP_CODE_MESSAGES[$code] ?? 'Unknown',
            'code'   => !empty($exception->getCode()) ? (string) $exception->getCode() : '99',
            'detail' => !empty($exception->getMessage()) ? $exception->getMessage() : 'Undefined message',
        ];

        if ($this->isDebug()) {
            $error['trace'] = $exception->getTraceAsString();
        }

        try {
            $content = json_encode($error);
        } catch (JsonException $exception) {
            $content = 'json_encode error (' . $exception->getMessage() . ')';
        }

        return $content;
    }
}
