<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Traits;

use Eureka\Kernel\Http\Service\DataCollection;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait ServerRequestAwareTrait
 *
 * @author Romain Cottard
 */
trait ServerRequestAwareTrait
{
    /** @var ServerRequestInterface $serverRequest */
    protected ServerRequestInterface $serverRequest;

    /**
     * @param ServerRequestInterface $serverRequest
     * @return void
     */
    public function setServerRequest(ServerRequestInterface $serverRequest): void
    {
        $this->serverRequest = $serverRequest;
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    /**
     * @return DataCollection
     */
    protected function getQueryParameters(): DataCollection
    {
        return new DataCollection($this->serverRequest->getQueryParams());
    }

    /**
     * @return DataCollection
     */
    protected function getBodyParameters(): DataCollection
    {
        return new DataCollection((array) $this->serverRequest->getParsedBody());
    }

    /**
     * @return bool
     */
    protected function isHttpGetMethod(): bool
    {
        return (strtoupper($this->serverRequest->getMethod()) === 'GET');
    }

    /**
     * @return bool
     */
    protected function isHttpPutMethod(): bool
    {
        return (strtoupper($this->serverRequest->getMethod()) === 'PUT');
    }

    /**
     * @return bool
     */
    protected function isHttpPatchMethod(): bool
    {
        return (strtoupper($this->serverRequest->getMethod()) === 'PATCH');
    }

    /**
     * @return bool
     */
    protected function isHttpPostMethod(): bool
    {
        return (strtoupper($this->serverRequest->getMethod()) === 'POST');
    }

    /**
     * @return bool
     */
    protected function isHttpDeleteMethod(): bool
    {
        return (strtoupper($this->serverRequest->getMethod()) === 'DELETE');
    }

    /**
     * @return bool
     */
    protected function isAjax(): bool
    {
        $server = $this->serverRequest->getServerParams();

        if (empty($server['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        return (strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * @return bool
     */
    protected function isJsonRequest(): bool
    {
        if (!$this->serverRequest->hasHeader('Content-Type')) {
            return false;
        }

        return (strtolower($this->serverRequest->getHeaderLine('Content-Type')) === 'application/json');
    }

    /**
     * @return bool
     */
    protected function acceptJsonResponse(): bool
    {
        if (!$this->serverRequest->hasHeader('Accept')) {
            return false;
        }

        return (strtolower($this->serverRequest->getHeaderLine('Accept')) === 'application/json');
    }
}
