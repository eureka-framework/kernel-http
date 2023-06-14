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
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

/**
 * Application class
 *
 * @author Romain Cottard
 */
class Application implements ApplicationInterface
{
    /** @var MiddlewareInterface[] $middleware */
    protected array $middleware = [];
    protected Kernel $kernel;

    /**
     * Application constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param ServerRequestInterface|null $serverRequest
     * @return ResponseInterface
     * @throws JsonException
     */
    public function run(ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        try {
            $serverRequest = $serverRequest ?? $this->createServerRequest();
            $response      = $this->createResponse($serverRequest);

            $this->loadMiddleware();

            //~ Get response through middlewares
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($serverRequest);
        } catch (\Exception $exception) { // @codeCoverageIgnore
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

            $controller->setEnvironment((string) $env, (bool) $debug);

            $response = $controller->error($serverRequest, $exception);
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @return $this
     */
    public function send(ResponseInterface $response): ApplicationInterface
    {
        //~ Write Headers
        if (!headers_sent()) {
            // @codeCoverageIgnoreStart
            //~ Base header
            $header = sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            header($header, true, $response->getStatusCode());

            //~ Headers
            foreach ($response->getHeaders() as $header => $values) {
                foreach ($values as $value) {
                    header($header . ': ' . $value, false, $response->getStatusCode());
                }
            }
            // @codeCoverageIgnoreEnd
        }

        //~ Write response
        echo $response->getBody();

        return $this;
    }

    /**
     * Load middleware
     *
     * @return void
     */
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

    /**
     * @param  ServerRequestInterface $serverRequest
     * @return ResponseInterface
     */
    private function createResponse(ServerRequestInterface $serverRequest): ResponseInterface
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->kernel->getContainer()->get('response_factory');

        $response = $responseFactory->createResponse();

        //~ Automatic add "application/json" to response header when client accept "json" in response.
        if (
            $serverRequest->hasHeader('Accept') &&
            in_array('application/json', $serverRequest->getHeader('Accept'))
        ) {
            $response = $response->withAddedHeader('Content-Type', 'application/json'); // @codeCoverageIgnore
        }

        return $response;
    }

    /**
     * @param bool $withBody
     * @return ServerRequestInterface
     */
    private function createServerRequest(bool $withBody = true): ServerRequestInterface
    {
        $serverRequestFactory = $this->getServerRequestFactory();

        $method  = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        //~ Create server request
        $serverRequest = $serverRequestFactory->createServerRequest($method, $this->createUri(), $_SERVER);

        //~ Add global PHP post, get, cookies & files data
        $serverRequest = $serverRequest
            ->withCookieParams(!empty($_COOKIE) ? $_COOKIE : [])
            ->withQueryParams(!empty($_GET) ? $_GET : [])
            ->withUploadedFiles(!empty($_FILES) ? $_FILES : [])
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
            $serverRequest = $serverRequest->withBody($this->getStreamFactory()->createStream(file_get_contents('php://input') ?: ''));
        }
        return $serverRequest;
    }

    /**
     * @return UriInterface
     */
    private function createUri(): UriInterface
    {
        $uriFactory = $this->getUriFactory();

        $uri = $uriFactory->createUri();

        //~ Set scheme
        $uri = $uri->withScheme(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');

        //~ Set host
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = $uri->withHost($_SERVER['HTTP_HOST']);
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $uri->withHost($_SERVER['SERVER_NAME']);
        }

        //~ Set port
        if (isset($_SERVER['SERVER_PORT'])) {
            $uri = $uri->withPort((int) $_SERVER['SERVER_PORT']);
        }

        //~ Set path
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $uri->withPath(current(explode('?', $_SERVER['REQUEST_URI'])));
        }

        //~ Set query string
        if (isset($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * @return array<string, string>
     *
     * @codeCoverageIgnore
     */
    private function getHeaders(): array
    {
        $headers = [];
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if ($headers === false) {
                $headers = [];
            }
        }

        foreach ($headers as $name => $header) {
            if (!is_array($header)) {
                $header = [$header];
            }

            $headers[$name] = $header;
        }

        return $headers;
    }

    /**
     * @param array<string> $contentTypes
     * @return array<string, string|int|float|bool|null>
     */
    private function getParsedBody(array $contentTypes): array
    {
        if ($this->isRequestBodyForm($contentTypes)) {
            return $_POST;
        }

        $requestBody = file_get_contents('php://input');
        try {
            $parsedBody  = !empty($requestBody) ? json_decode($requestBody, true) : [];
        } catch (JsonException $exception) {
            $parsedBody = [];
        }

        if (!empty($requestBody) && empty($parsedBody)) {
            parse_str($requestBody, $parsedBody); // @codeCoverageIgnore
        }

        /** @var array<string, string|int|float|bool|null> $parsedBody */
        return $parsedBody;
    }

    /**
     * @param array<string> $contentTypes
     * @return bool
     */
    private function isRequestBodyForm(array $contentTypes): bool
    {
        foreach ($contentTypes as $contentType) {
            // @codeCoverageIgnoreStart
            if (preg_match('/^(application\/x-www-form-urlencoded|multipart\/form-data)/', $contentType)) {
                return true;
            }
            // @codeCoverageIgnoreEnd
        }
        return false;
    }

    /**
     * @param array<string> $contentTypes
     * @return bool
     */
    private function isRequestBodyJson(array $contentTypes): bool
    {
        foreach ($contentTypes as $contentType) {
            // @codeCoverageIgnoreStart
            if (preg_match('/^(application\/json)/', $contentType)) {
                return true;
            }
            // @codeCoverageIgnoreEnd
        }
        return false;
    }

    /**
     * @return ResponseFactoryInterface
     * @codeCoverageIgnore
     */
    private function getResponseFactory(): ResponseFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('response_factory');
        if (!($factory instanceof ResponseFactoryInterface)) {
            throw new \LogicException('Service "response_factory" not a ' . ResponseFactoryInterface::class);
        }

        return $factory;
    }

    /**
     * @return RequestFactoryInterface
     * @codeCoverageIgnore
     */
    private function getRequestFactory(): RequestFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('request_factory');
        if (!($factory instanceof RequestFactoryInterface)) {
            throw new \LogicException('Service "request_factory" not a ' . RequestFactoryInterface::class);
        }

        return $factory;
    }

    /**
     * @return ServerRequestFactoryInterface
     */
    private function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('server_request_factory');
        if (!($factory instanceof ServerRequestFactoryInterface)) {
            throw new \LogicException('Service "server_request_factory" not a ' . ServerRequestFactoryInterface::class); // @codeCoverageIgnore
        }

        return $factory;
    }

    /**
     * @return StreamFactoryInterface
     * @codeCoverageIgnore
     */
    private function getStreamFactory(): StreamFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('stream_factory');
        if (!($factory instanceof StreamFactoryInterface)) {
            throw new \LogicException('Service "stream_factory" not a ' . StreamFactoryInterface::class);
        }

        return $factory;
    }

    /**
     * @return UriFactoryInterface
     */
    private function getUriFactory(): UriFactoryInterface
    {
        $factory = $this->kernel->getContainer()->get('uri_factory');
        if (!($factory instanceof UriFactoryInterface)) {
            throw new \LogicException('Service "uri_factory" not a ' . UriFactoryInterface::class); // @codeCoverageIgnore
        }

        return $factory;
    }
}
