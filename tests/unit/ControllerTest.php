<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit;

use Eureka\Kernel\Http\Controller\ControllerInterface;
use Eureka\Kernel\Http\Kernel;
use Eureka\Kernel\Http\Tests\Unit\Mock\TestController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Class ControllerTest
 *
 * @author Romain Cottard
 */
class ControllerTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testKernelCanAutowireAController(): void
    {
        self::assertInstanceOf(ControllerInterface::class, $this->getTestController());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testControllerTraitHttpFactories(): void
    {
        $controller = $this->getTestController();

        self::assertTrue($controller->assertHasAllFactories());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testControllerHasLogger(): void
    {
        $controller = $this->getTestController();

        self::assertTrue($controller->assertHasLogger());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testControllerHasRoutingHelperAvailable(): void
    {
        $controller = $this->getTestController();

        self::assertTrue($controller->assertHasRoutingHelperAvailable());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testControllerHasServerRequestHelperAvailable(): void
    {
        $controller = $this->getTestController();

        //~ Ajax Request
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';
        $_SERVER['REQUEST_METHOD']        = 'POST';

        /** @var ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = $this->getKernel()->getContainer()->get('server_request_factory');
        $serverRequest        = $serverRequestFactory->createServerRequest('POST', '/test/json', $_SERVER);
        $serverRequest        = $serverRequest
            ->withAddedHeader('Content-Type', 'application/json')
            ->withAddedHeader('Accept', 'application/json')
        ;

        self::assertTrue($controller->assertHasServerRequestHelperAvailable($serverRequest));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testControllerAbstractMethods(): void
    {
        $controller = $this->getTestController();
        $controller->setEnvironment('dev');

        self::assertTrue($controller->assertHasPropertiesCorrectlySet());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testICanCheckWhenRequestIsNotJsonNorAjax(): void
    {
        $controller = $this->getTestController();

        /** @var ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = $this->getKernel()->getContainer()->get('server_request_factory');
        $serverRequest        = $serverRequestFactory->createServerRequest('POST', '/test/json', $_SERVER);

        self::assertTrue($controller->assertIsNotJsonNorAjaxRequest($serverRequest));
    }

    /**
     * @return TestController
     * @throws \Exception
     */
    private function getTestController(): TestController
    {
        $controller = $this->getKernel()->getContainer()->get('Eureka\Kernel\Http\Tests\Unit\Mock\TestController');

        if (!($controller instanceof TestController)) {
            throw new \LogicException();
        }

        return $controller;
    }

    /**
     * @return Kernel
     * @throws \Exception
     */
    private function getKernel(): Kernel
    {
        $root  = (string) realpath(__DIR__ . '/../..');

        //~ Overridden by conf
        $env   = 'dev';
        $debug = true;

        return new Kernel($root, $env, $debug);
    }
}
