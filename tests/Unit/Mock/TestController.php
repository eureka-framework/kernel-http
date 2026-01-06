<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit\Mock;

use Eureka\Kernel\Http\Controller\Controller;
use Eureka\Kernel\Http\Exception\HttpBadRequestException;
use Eureka\Kernel\Http\Exception\HttpConflictException;
use Eureka\Kernel\Http\Exception\HttpForbiddenException;
use Eureka\Kernel\Http\Exception\HttpInternalServerErrorException;
use Eureka\Kernel\Http\Exception\HttpServiceUnavailableException;
use Eureka\Kernel\Http\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestController extends Controller
{
    private const string EXCEPTION_MESSAGE = 'throw an error (html)';

    public function testJsonAction(): ResponseInterface
    {
        return $this->getResponseJson('ok');
    }

    public function testHtmlAction(): ResponseInterface
    {
        return $this->getResponse('ok');
    }

    public function testUrlAction(
        ServerRequestInterface $serverRequest,
        string $someString,
        bool $someBool,
        int $someInt,
        string $id,
        string $title,
    ): ResponseInterface {
        return $this->getResponse("entity id: $id, title: $title, someString: $someString, someBool: " . ($someBool ? 'true' : 'false') . ", someInt: $someInt");
    }

    public function testInternalServerErrorHtmlAction(): ResponseInterface
    {
        throw new HttpInternalServerErrorException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testBadRequestErrorHtmlAction(): ResponseInterface
    {
        throw new HttpBadRequestException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testUnauthorizedErrorHtmlAction(): ResponseInterface
    {
        throw new HttpUnauthorizedException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testForbiddenErrorHtmlAction(): ResponseInterface
    {
        throw new HttpForbiddenException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testServiceUnavailableErrorHtmlAction(): ResponseInterface
    {
        throw new HttpServiceUnavailableException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testConflictErrorHtmlAction(): ResponseInterface
    {
        throw new HttpConflictException(self::EXCEPTION_MESSAGE, 99);
    }

    public function testTypeErrorHtmlAction(): ResponseInterface
    {
        throw new \TypeError(self::EXCEPTION_MESSAGE, 99);
    }

    public function assertHasAllFactories(): bool
    {
        $serverRequestFactory = $this->getServerRequestFactory();
        $responseFactory      = $this->getResponseFactory();
        $streamFactory        = $this->getStreamFactory();
        $uriFactory           = $this->getUriFactory();
        $requestFactory       = $this->getRequestFactory();

        return true;
    }

    public function assertHasLogger(): bool
    {
        $logger = $this->getLogger();

        return true;
    }

    public function assertHasRoutingHelperAvailable(): bool
    {
        $router = $this->getRouter();

        //~ Not defined when controller is not called from middleware, so just call to check method availability
        $route = $this->getRoute();

        if ($this->getRouteUri('test_json') !== '/test/json') {
            throw new \RuntimeException('Invalid generated route uri!');
        }

        if ($this->hasParameter('test')) {
            throw new \RuntimeException('Parameter test should not be defined!');
        }

        if ($this->getParameter('test', 'default') !== 'default') {
            throw new \RuntimeException('Getting parameter test must return "default" as default value!');
        }

        return true;
    }

    public function assertHasServerRequestHelperAvailable(ServerRequestInterface $serverRequest): bool
    {
        $this->setServerRequest($serverRequest);

        $serverRequest = $this->getServerRequest();

        if ($this->isHttpMethod('POST') !== true) {
            throw new \RuntimeException('Invalid Http method! Should be a POST method!');
        }

        return true;
    }

    public function assertIsNotJsonNorAjaxRequest(ServerRequestInterface $serverRequest): bool
    {
        $this->setServerRequest($serverRequest);

        if ($this->isJsonRequest() === true) {
            throw new \RuntimeException('Invalid request. Should not be a json request!');
        }

        if ($this->acceptJsonResponse() === true) {
            throw new \RuntimeException('Invalid request. Should not be accept json response!');
        }

        if ($this->isAjaxRequest() === true) {
            throw new \RuntimeException('Invalid request. Should not be an ajax request!');
        }

        return true;
    }

    public function assertIsAjaxRequest(ServerRequestInterface $serverRequest): bool
    {
        $this->setServerRequest($serverRequest);

        if ($this->isJsonRequest() !== true) {
            throw new \RuntimeException('Invalid request. Should be a json request!');
        }

        if ($this->acceptJsonResponse() !== true) {
            throw new \RuntimeException('Invalid request. Should accept json response!');
        }

        if ($this->isAjaxRequest() !== true) {
            throw new \RuntimeException('Invalid request. Should be an ajax request!');
        }

        return true;
    }

    public function assertHasPropertiesCorrectlySet(): bool
    {
        if ($this->isDev() === false) {
            throw new \RuntimeException('Should be prod environment (not dev)!');
        }

        if ($this->isProd() === true) {
            throw new \RuntimeException('Should be prod environment (is prod)!');
        }

        if ($this->getEnvironment() !== 'dev') {
            throw new \RuntimeException('Should be prod environment (!== dev)!');
        }

        if ($this->isDebug() === true) {
            throw new \RuntimeException('Should have debug disabled!');
        }

        return true;
    }
}
