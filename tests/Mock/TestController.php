<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Mock;

use Eureka\Kernel\Http\Controller\Controller;
use Eureka\Kernel\Http\Exception\HttpBadRequestException;
use Eureka\Kernel\Http\Exception\HttpConflictException;
use Eureka\Kernel\Http\Exception\HttpForbiddenException;
use Eureka\Kernel\Http\Exception\HttpInternalServerErrorException;
use Eureka\Kernel\Http\Exception\HttpServiceUnavailableException;
use Eureka\Kernel\Http\Exception\HttpUnauthorizedException;
use Eureka\Kernel\Http\Service\DataCollection;
use Eureka\Kernel\Http\Service\Session;
use Eureka\Kernel\Http\Traits\SessionAwareTrait;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
class TestController extends Controller
{
    /**
     * @return ResponseInterface
     */
    public function testJsonAction(): ResponseInterface
    {
        return $this->getResponseJson('ok');
    }

    /**
     * @return ResponseInterface
     */
    public function testHtmlAction(): ResponseInterface
    {
        return $this->getResponse('ok');
    }

    /**
     * @return ResponseInterface
     */
    public function testInternalServerErrorHtmlAction(): ResponseInterface
    {
        throw new HttpInternalServerErrorException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testBadRequestErrorHtmlAction(): ResponseInterface
    {
        throw new HttpBadRequestException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testUnauthorizedErrorHtmlAction(): ResponseInterface
    {
        throw new HttpUnauthorizedException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testForbiddenErrorHtmlAction(): ResponseInterface
    {
        throw new HttpForbiddenException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testServiceUnavailableErrorHtmlAction(): ResponseInterface
    {
        throw new HttpServiceUnavailableException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testConflictErrorHtmlAction(): ResponseInterface
    {
        throw new HttpConflictException('throw an error (html)', 99);
    }

    /**
     * @return ResponseInterface
     */
    public function testErrorTypeHtmlAction(): ResponseInterface
    {
        throw new \Error('throw an error (html)', 99);
    }

    /**
     * @return bool
     */
    public function assertHasAllFactories(): bool
    {
        if (!($this->getServerRequestFactory() instanceof ServerRequestFactoryInterface)) {
            throw new \RuntimeException('ServerRequest Factory not available!');
        }

        if (!($this->getResponseFactory() instanceof ResponseFactoryInterface)) {
            throw new \RuntimeException('Response Factory not available!');
        }

        if (!($this->getStreamFactory() instanceof StreamFactoryInterface)) {
            throw new \RuntimeException('Stream Factory not available!');
        }

        if (!($this->getUriFactory() instanceof UriFactoryInterface)) {
            throw new \RuntimeException('Uri Factory not available!');
        }

        if (!($this->getRequestFactory() instanceof RequestFactoryInterface)) {
            throw new \RuntimeException('Request Factory not available!');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function assertHasLogger(): bool
    {
        if (!($this->getLogger() instanceof LoggerInterface)) {
            throw new \RuntimeException('Request Factory not available!');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function assertHasRoutingHelperAvailable(): bool
    {
        if (!($this->getRouter() instanceof RouterInterface)) {
            throw new \RuntimeException('Router not available!');
        }

        //~ Not defined when controller is not called from middleware, so just call to check method availability
        $this->getRoute();

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

    /**
     * @param ServerRequestInterface $serverRequest
     * @return bool
     */
    public function assertHasServerRequestHelperAvailable(ServerRequestInterface $serverRequest): bool
    {
        $this->setServerRequest($serverRequest);

        if (!($this->getServerRequest() instanceof ServerRequestInterface)) {
            throw new \RuntimeException('Invalid server request!');
        }

        if (!($this->getQueryParameters() instanceof DataCollection)) {
            throw new \RuntimeException('Invalid query parameters server request!');
        }

        if (!($this->getBodyParameters() instanceof DataCollection)) {
            throw new \RuntimeException('Invalid body parameters from server request!');
        }

        if ($this->isHttpGetMethod() !== false) {
            throw new \RuntimeException('Invalid Http method GET! Should be a POST method!');
        }

        if ($this->isHttpPatchMethod() !== false) {
            throw new \RuntimeException('Invalid Http method PATCH! Should be a POST method!');
        }

        if ($this->isHttpPutMethod() !== false) {
            throw new \RuntimeException('Invalid Http method PUT! Should be a POST method!');
        }

        if ($this->isHttpDeleteMethod() !== false) {
            throw new \RuntimeException('Invalid Http method DELETE! Should be a POST method!');
        }

        if ($this->isHttpPostMethod() !== true) {
            throw new \RuntimeException('Invalid Http method! Should be a POST method!');
        }

        if ($this->isAjax() === false) {
            throw new \RuntimeException('Invalid request. Should be an ajax request!');
        }

        if ($this->isJsonRequest() === false) {
            throw new \RuntimeException('Invalid request. Should be a json request!');
        }

        if ($this->acceptJsonResponse() === false) {
            throw new \RuntimeException('Invalid request. Should be accept json response!');
        }

        return true;
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @return bool
     */
    public function assertIsNotJsonNorAjaxRequest(ServerRequestInterface $serverRequest): bool
    {
        $this->setServerRequest($serverRequest);

        if ($this->isJsonRequest() === true) {
            throw new \RuntimeException('Invalid request. Should not be a json request!');
        }

        if ($this->acceptJsonResponse() === true) {
            throw new \RuntimeException('Invalid request. Should not be accept json response!');
        }

        if ($this->isAjax() === true) {
            throw new \RuntimeException('Invalid request. Should not be an ajax request!');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function assertHasPropertiesCorrectlySet(): bool
    {
        if ($this->isDev() === false) {
            throw new \RuntimeException('Should be prod environment!');
        }

        if ($this->isProd() === true) {
            throw new \RuntimeException('Should be prod environment!');
        }

        if ($this->getEnvironment() !== 'dev') {
            throw new \RuntimeException('Should be prod environment!');
        }

        if ($this->isDebug() === true) {
            throw new \RuntimeException('Should have debug disabled!');
        }

        return true;
    }
}
