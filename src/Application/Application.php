<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Application;

use Eureka\Component\Http;
use Eureka\Kernel\Http\Kernel;

/**
 * Application class
 *
 * @author Romain Cottard
 */
class Application implements ApplicationInterface
{
    /** @var \Psr\Http\Server\MiddlewareInterface[] $middleware */
    protected $middleware = [];

    /** @var Kernel $container */
    protected $kernel = null;

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
     * Run application based on the route.
     *
     * @return ApplicationInterface
     */
    public function run(): ApplicationInterface
    {
        $httpFactory = $this->kernel->getContainer()->get(Http\HttpFactory::class);

        //~ Default response
        $response = $httpFactory->createResponse();
        $method   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri      = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        try {
            $this->loadMiddleware();

            //~ Get response
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($httpFactory->createServerRequest($method, $uri, $_SERVER));

        } catch (\Exception $exception) {

            $body = '<h3>' . $exception->getMessage() . '</h3>';
            if ($this->kernel->getContainer()->getParameter('kernel.debug') === true) {
                 $body .= '<pre>' . var_export($exception->getTraceAsString(), true) . '</pre>';
            }
            $response->getBody()->write($body);
        }

        //~ Send response
        (new Http\Message\ResponseSender($response))->send();

        return $this;
    }

    /**
     * Load middleware
     *
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function loadMiddleware()
    {
        $this->middleware = [];

        $list = $this->kernel->getContainer()->getParameter('app.middleware');

        foreach ($list as $service) {
            $this->middleware[] = $this->kernel->getContainer()->get($service);
        }
    }
}
