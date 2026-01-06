<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Application;

use Eureka\Component\Http;
use Eureka\Kernel\Http\Controller\ErrorController;
use Eureka\Kernel\Http\Kernel;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @phpstan-type ServerParams array{
 *     REQUEST_METHOD?: string,
 *     HTTPS?: string,
 *     HTTP_HOST?: string,
 *     SERVER_NAME?: string,
 *     SERVER_PORT?: string,
 *     REQUEST_URI?: string,
 *     QUERY_STRING?: string,
 * }
 */
class Application implements ApplicationInterface
{
    /** @var MiddlewareInterface[] $middleware */
    protected array $middleware = [];

    public function __construct(protected readonly Kernel $kernel) {}

    public function run(?ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        try {
            $serverRequest = $serverRequest ?? $this->createServerRequest();
            $response      = $this->createResponse($serverRequest);

            $this->loadMiddleware();

            //~ Get response through middlewares
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($serverRequest);
        } catch (\Throwable $exception) { // @codeCoverageIgnore
            // @codeCoverageIgnoreStart
            //~ Catch not handled exception - Should not happen
            $serverRequest = $serverRequest ?? $this->createServerRequest(false);

            $controller = new ErrorController();
            $controller->setResponseFactory($this->getResponseFactory());
            $controller->setRequestFactory($this->getRequestFactory());
            $controller->setServerRequestFactory($this->getServerRequestFactory());
            $controller->setStreamFactory($this->getStreamFactory());
            $controller->setUriFactory($this->getUriFactory());
            $controller->setServerRequest($serverRequest);

            /** @var string $env */
            $env = $this->kernel->getContainer()->getParameter('kernel.environment');
            /** @var bool $debug */
            $debug = $this->kernel->getContainer()->getParameter('kernel.debug');

            $controller->setEnvironment($env, $debug);

            $response = $controller->error($serverRequest, $exception);
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    public function send(ResponseInterface $response): ApplicationInterface
    {
        //~ Write Headers
        if (!\headers_sent()) {
            // @codeCoverageIgnoreStart
            //~ Base header
            $header = \sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            \header($header, true, $response->getStatusCode());

            //~ Headers
            foreach ($response->getHeaders() as $keyHeader => $values) {
                foreach ($values as $value) {
                    \header($keyHeader . ': ' . $value, false, $response->getStatusCode());
                }
            }
            // @codeCoverageIgnoreEnd
        }

        //~ Write response
        echo $response->getBody();

        return $this;
    }

    private function loadMiddleware(): void
    {
        $this->middleware = [];

        /** @var string[] $list */
        $list = $this->kernel->getContainer()->getParameter('app.middleware');

        foreach ($list as $service) {
            /** @var MiddlewareInterface $middleware */
            $middleware = $this->kernel->getContainer()->get($service);

            $this->middleware[] = $middleware;
        }
    }

    private function createResponse(ServerRequestInterface $serverRequest): ResponseInterface
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->kernel->getContainer()->get('response_factory');

        $response = $responseFactory->createResponse();

        //~ Automatic add "application/json" to response header when client accept "json" in response.
        if (
            $serverRequest->hasHeader('Accept')
            && \in_array('application/json', $serverRequest->getHeader('Accept'), true) // @codeCoverageIgnore
        ) {
            $response = $response->withAddedHeader('Content-Type', 'application/json'); // @codeCoverageIgnore
        }

        return $response;
    }

    private function createServerRequest(bool $withBody = true): ServerRequestInterface
    {
        $serverRequestFactory = $this->getServerRequestFactory();

        /** @var ServerParams $params */
        $params = $_SERVER;
        $method = ($params['REQUEST_METHOD'] ?? '') !== '' ? $params['REQUEST_METHOD'] : 'GET';

        //~ Create server request
        $serverRequest = $serverRequestFactory->createServerRequest($method, $this->createUri(), $_SERVER);

        //~ Add global PHP post, get, cookies & files data
        $serverRequest = $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withUploadedFiles($_FILES)
        ;

        //~ Add headers
        foreach ($this->getHeaders() as $header => $values) {
            $serverRequest = $serverRequest->withAddedHeader($header, $values); // @codeCoverageIgnore
        }

        if (!$withBody) {
            return $serverRequest; // @codeCoverageIgnore
        }

        //~ Add parsed body
        $contentTypes  = $serverRequest->getHeader('Content-Type');
        $serverRequest = $serverRequest->withParsedBody($this->getParsedBody($contentTypes));

        // Add raw body if not form data nor json
        if (!$this->isRequestBodyForm($contentTypes) && !$this->isRequestBodyJson($contentTypes)) {
            $content = \file_get_contents('php://input');
            $serverRequest = $serverRequest
                ->withBody($this->getStreamFactory()->createStream($content !== false ? $content : ''))
            ;
        }
        return $serverRequest;
    }

    private function createUri(): UriInterface
    {
        $uriFactory = $this->getUriFactory();

        $uri = $uriFactory->createUri();

        /** @var ServerParams $params */
        $params = $_SERVER;

        //~ Set scheme
        $uri = $uri->withScheme(isset($params['HTTPS']) && $params['HTTPS'] === 'on' ? 'https' : 'http');

        //~ Set host
        if (isset($params['HTTP_HOST'])) {
            $uri = $uri->withHost($params['HTTP_HOST']);
        } elseif (isset($params['SERVER_NAME'])) {
            $uri = $uri->withHost($params['SERVER_NAME']);
        }

        //~ Set port
        if (isset($params['SERVER_PORT'])) {
            $uri = $uri->withPort((int) $params['SERVER_PORT']);
        }

        //~ Set path
        if (isset($params['REQUEST_URI'])) {
            $uri = $uri->withPath(current(explode('?', $params['REQUEST_URI'])));
        }

        //~ Set query string
        if (isset($params['QUERY_STRING'])) {
            $uri = $uri->withQuery($params['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * @return array<string, string[]>
     *
     * @codeCoverageIgnore
     */
    private function getHeaders(): array
    {
        $headers = [];
        if (\function_exists('apache_request_headers')) {
            $headers = \apache_request_headers();
        }

        foreach ($headers as $name => $header) {
            if (!\is_array($header)) {
                $header = [$header];
            }

            $headers[$name] = $header;
        }

        /** @var array<string, string[]> $headers */
        return $headers;
    }

    /**
     * @param array<string> $contentTypes
     * @return array<string, string|int|float|bool|null>
     */
    private function getParsedBody(array $contentTypes): array
    {
        if ($this->isRequestBodyForm($contentTypes)) {
            // @codeCoverageIgnoreStart
            /** @phpstan-var array<string, string|int|float|bool|null> $parsedBody */
            $parsedBody = $_POST;

            return $parsedBody;
            // @codeCoverageIgnoreEnd
        }

        $requestBody = \file_get_contents('php://input');
        $isEmptyBody = $requestBody === false || $requestBody === '';
        try {
            $parsedBody  = !$isEmptyBody ? \json_decode($requestBody, true, 512, \JSON_THROW_ON_ERROR) : [];
            // @codeCoverageIgnoreStart
        } catch (\JsonException) {
            $parsedBody = [];
        }
        // @codeCoverageIgnoreEnd

        if (!$isEmptyBody && $parsedBody === []) {
            \parse_str($requestBody, $parsedBody); // @codeCoverageIgnore
        }

        /** @var array<string, string|int|float|bool|null> $parsedBody */
        return $parsedBody;
    }

    /**
     * @param array<string> $contentTypes
     */
    private function isRequestBodyForm(array $contentTypes): bool
    {
        foreach ($contentTypes as $contentType) {
            // @codeCoverageIgnoreStart
            if (\preg_match('/^(application\/x-www-form-urlencoded|multipart\/form-data)/', $contentType) > 0) {
                return true;
            }
            // @codeCoverageIgnoreEnd
        }
        return false;
    }

    /**
     * @param array<string> $contentTypes
     */
    private function isRequestBodyJson(array $contentTypes): bool
    {
        foreach ($contentTypes as $contentType) {
            // @codeCoverageIgnoreStart
            if (\preg_match('/^(application\/json)/', $contentType) > 0) {
                return true;
            }
            // @codeCoverageIgnoreEnd
        }
        return false;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('response_factory');
        if (!($factory instanceof ResponseFactoryInterface)) {
            throw new \LogicException('Service "response_factory" not a ' . ResponseFactoryInterface::class); // @codeCoverageIgnore
        }

        return $factory;
    }

    private function getRequestFactory(): RequestFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('request_factory');
        if (!($factory instanceof RequestFactoryInterface)) {
            throw new \LogicException('Service "request_factory" not a ' . RequestFactoryInterface::class); // @codeCoverageIgnore
        }

        return $factory;
    }

    private function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('server_request_factory');
        if (!($factory instanceof ServerRequestFactoryInterface)) {
            // @codeCoverageIgnoreStart
            throw new \LogicException(
                'Service "server_request_factory" not a ' . ServerRequestFactoryInterface::class,
            );
            // @codeCoverageIgnoreEnd
        }

        return $factory;
    }

    private function getStreamFactory(): StreamFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('stream_factory');
        if (!($factory instanceof StreamFactoryInterface)) {
            throw new \LogicException('Service "stream_factory" not a ' . StreamFactoryInterface::class); // @codeCoverageIgnore
        }

        return $factory;
    }

    private function getUriFactory(): UriFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('uri_factory');
        if (!($factory instanceof UriFactoryInterface)) {
            // @codeCoverageIgnoreStart
            throw new \LogicException(
                'Service "uri_factory" not a ' . UriFactoryInterface::class,
            );
            // @codeCoverageIgnoreEnd
        }

        return $factory;
    }
}
