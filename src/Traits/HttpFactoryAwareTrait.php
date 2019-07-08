<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Traits;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Trait HttpFactoryAwareTrait
 *
 * @author Romain Cottard
 */
trait HttpFactoryAwareTrait
{
    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /**
     * @return UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }

    /**
     * @param UriFactoryInterface $uriFactory
     */
    public function setUriFactory(UriFactoryInterface $uriFactory): void
    {
        $this->uriFactory = $uriFactory;
    }

    /**
     * @return RequestFactoryInterface
     */
    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    /**
     * @param RequestFactoryInterface $requestFactory
     */
    public function setRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return ServerRequestFactoryInterface
     */
    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->serverRequestFactory;
    }

    /**
     * @param ServerRequestFactoryInterface $serverRequestFactory
     */
    public function setServerRequestFactory(ServerRequestFactoryInterface $serverRequestFactory): void
    {
        $this->serverRequestFactory = $serverRequestFactory;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @param StreamFactoryInterface $streamFactory
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }
}
