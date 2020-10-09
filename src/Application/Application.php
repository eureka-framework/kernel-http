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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Application class
 *
 * @author Romain Cottard
 * @noinspection PhpParamsInspection
 */
class Application implements ApplicationInterface
{
    /** @var MiddlewareInterface[] $middleware */
    protected iterable $middleware = [];

    /** @var Kernel $kernel */
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
     */
    public function run(ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        $serverRequest = $serverRequest ?? $this->createServerRequest();
        $response      = $this->createResponse($serverRequest);

        try {
            $this->loadMiddleware();

            //~ Get response through middlewares
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($serverRequest);
        } catch (\Exception $exception) { // @codeCoverageIgnore
            // @codeCoverageIgnoreStart
            //~ Catch not handled exception - Should not happen
            $controller = new ErrorController();
            $controller->setResponseFactory($this->kernel->getContainer()->get('response_factory'));
            $controller->setRequestFactory($this->kernel->getContainer()->get('request_factory'));
            $controller->setServerRequestFactory($this->kernel->getContainer()->get('server_request_factory'));
            $controller->setStreamFactory($this->kernel->getContainer()->get('stream_factory'));
            $controller->setUriFactory($this->kernel->getContainer()->get('uri_factory'));
            $controller->setServerRequest($serverRequest);

            $controller->setEnvironment(
                $this->kernel->getContainer()->getParameter('kernel.environment'),
                (bool) $this->kernel->getContainer()->getParameter('kernel.debug')
            );

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

        $list = $this->kernel->getContainer()->getParameter('app.middleware');

        foreach ($list as $service) {
            $this->middleware[] = $this->kernel->getContainer()->get($service);
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
     * @return ServerRequestInterface
     */
    private function createServerRequest(): ServerRequestInterface
    {
        /** @var ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = $this->kernel->getContainer()->get('server_request_factory');

        $method  = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        //~ Create server request
        $serverRequest = $serverRequestFactory->createServerRequest($method, $this->createUri(), $_SERVER);

        //~ Add global PHP post, get, cookies & files data
        $serverRequest = $serverRequest
            ->withCookieParams($_COOKIE ?? [])
            ->withQueryParams($_GET ?? [])
            ->withUploadedFiles($_FILES ?? [])
        ;

        //~ Add headers
        foreach ($this->getHeaders() as $header => $values) {
            $serverRequest = $serverRequest->withAddedHeader($header, $values);// @codeCoverageIgnore
        }

        //~ Add parsed body (need headers)
        $parsedBody    = $this->getParsedBody($serverRequest->getHeader('Content-Type'));

        return $serverRequest->withParsedBody($parsedBody);
    }

    /**
     * @return UriInterface
     */
    private function createUri(): UriInterface
    {
        /** @var UriFactoryInterface $uriFactory */
        $uriFactory = $this->kernel->getContainer()->get('uri_factory');

        $uri = $uriFactory->createUri();

        //~ Set scheme
        if (isset($_SERVER['HTTPS'])) {
            $uri = $uri->withScheme($_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
        }

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
     * @return array
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
     * @param array $contentTypes
     * @return mixed
     */
    private function getParsedBody(array $contentTypes)
    {
        foreach ($contentTypes as $contentType) {
            // @codeCoverageIgnoreStart
            if (preg_match('/^(application\/x-www-form-urlencoded|multipart\/form-data)/', $contentType)) {
                return $_POST;
            }
            // @codeCoverageIgnoreEnd
        }

        $requestBody = file_get_contents('php://input');
        $parsedBody  = !empty($requestBody) ? json_decode($requestBody, true) : [];

        if (!empty($requestBody) && empty($parsedBody)) {
            parse_str($requestBody, $parsedBody); // @codeCoverageIgnore
        }

        return $parsedBody;
    }
}
