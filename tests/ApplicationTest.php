<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests;

use Eureka\Kernel\Http\Application\Application;
use Eureka\Kernel\Http\Application\ApplicationInterface;
use Eureka\Kernel\Http\Exception\HttpBadRequestException;
use Eureka\Kernel\Http\Exception\HttpConflictException;
use Eureka\Kernel\Http\Exception\HttpForbiddenException;
use Eureka\Kernel\Http\Exception\HttpInternalServerErrorException;
use Eureka\Kernel\Http\Exception\HttpServiceUnavailableException;
use Eureka\Kernel\Http\Exception\HttpTooManyRequestsException;
use Eureka\Kernel\Http\Exception\HttpUnauthorizedException;
use Eureka\Kernel\Http\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * Class ApplicationTest
 *
 * @author Romain Cottard
 */
class ApplicationTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testCanInstantiateApplication(): void
    {
        $this->assertInstanceOf(ApplicationInterface::class, $this->getApplication());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWithJsonResponse(): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI'] = '/test/json';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $this->assertSame('"ok"', $output);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWithHtmlResponse(): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI'] = '/test/html';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $this->assertSame('ok', $output);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWithRouteNotFoundResponse(): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI'] = '/test/error/not-found';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $expected = "<pre>exception[Eureka\Kernel\Http\Exception\HttpNotFoundException]: \nNo routes found for \"/test/error/not-found\".\n\n</pre>";
        $this->assertEquals($expected, $output);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWhichGenerateTooManyRequestsWhenQuotaIsReach(): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI'] = '/test/json/limited';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $this->assertSame('"ok"', $output);

        ob_start();
        $application->send($application->run());
        $output = ob_get_clean();

        $expected = "<pre>exception[" . HttpTooManyRequestsException::class . "]: \nToo Many Requests\n\n</pre>";
        $this->assertEquals($expected, $output);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWhichGenerateAppropriateErrorResponseForNotAllowedMethod(): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI']    = '/test/error/html/internal-server-error';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $expected = "<pre>exception[Eureka\Kernel\Http\Exception\HttpMethodNotAllowedException]: \nAllowed method(s): GET\n\n</pre>";
        $this->assertEquals($expected, $output);
    }

    /**
     * @param string $uri
     * @param string $exceptionClass
     * @return void
     * @throws \Exception
     *
     * @dataProvider uriExceptionDataProvider
     */
    public function testCanRunApplicationWhichGenerateAppropriateErrorResponseForGivenError(string $uri, string $exceptionClass): void
    {
        //~ Define current route
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['SERVER_NAME'] = 'any';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $expected = "<pre>exception[${exceptionClass}]: \nthrow an error (html)\n\n</pre>";
        $this->assertEquals($expected, $output);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanRunApplicationWhichGenerateAppropriateErrorResponseForNotExistingActionMethod(): void
    {
        //~ Define current route
        $_SERVER['HTTPS']        = 'on';
        $_SERVER['HTTP_HOST']    = 'localhost';
        $_SERVER['SERVER_PORT']  = 443;
        $_SERVER['QUERY_STRING'] = 'foo=bar';

        $_SERVER['REQUEST_URI'] = '/test/error/action-not-exists';

        //~ Run Application
        ob_start();
        $application = $this->getApplication();
        $application->send($application->run());
        $output = ob_get_clean();

        $expected = "<pre>exception[DomainException]: \nAction controller does not exists! (Eureka\Kernel\Http\Tests\Mock\TestController::testErrorHtmlActionNotExists\n\n</pre>";
        $this->assertEquals($expected, $output);
    }

    /**
     * @return ApplicationInterface
     * @throws \Exception
     */
    private function getApplication(): ApplicationInterface
    {
        $root  = realpath(__DIR__ . '/..');
        $env   = 'dev';
        $debug = true;

        return new Application(new Kernel($root, $env, $debug));
    }

    /**
     * @return array
     */
    public function uriExceptionDataProvider(): array
    {
        return [
            'BadRequest' => [
                '/test/error/html/bad-request',
                HttpBadRequestException::class,
            ],
            'Unauthorized' => [
                '/test/error/html/unauthorized',
                HttpUnauthorizedException::class,
            ],
            'Forbidden' => [
                '/test/error/html/forbidden',
                HttpForbiddenException::class,
            ],
            'InternalServerError' => [
                '/test/error/html/internal-server-error',
                HttpInternalServerErrorException::class,
            ],
            'ServiceUnavailable' => [
                '/test/error/html/service-unavailable',
                HttpServiceUnavailableException::class,
            ],
            'Conflict' => [
                '/test/error/html/conflict',
                HttpConflictException::class,
            ],
        ];
    }
}
