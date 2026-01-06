<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait ServerRequestAwareTrait
{
    protected ServerRequestInterface $serverRequest;

    public function setServerRequest(ServerRequestInterface $serverRequest): void
    {
        $this->serverRequest = $serverRequest;
    }

    protected function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    public function isHttpMethod(string $method): bool
    {
        return \strtoupper($this->serverRequest->getMethod()) === \strtoupper($method);
    }

    protected function isAjaxRequest(): bool
    {
        $server = $this->serverRequest->getServerParams();

        $requestedWith = $server['HTTP_X_REQUESTED_WITH'] ?? '';
        if (!\is_string($requestedWith) || $requestedWith === '') {
            return false;
        }

        return \strtolower($requestedWith) === 'xmlhttprequest';
    }

    protected function isJsonRequest(): bool
    {
        if (!$this->serverRequest->hasHeader('Content-Type')) {
            return false;
        }

        return \strtolower($this->serverRequest->getHeaderLine('Content-Type')) === 'application/json';
    }

    protected function acceptJsonResponse(): bool
    {
        if (!$this->serverRequest->hasHeader('Accept')) {
            return false;
        }

        return \strtolower($this->serverRequest->getHeaderLine('Accept')) === 'application/json';
    }
}
