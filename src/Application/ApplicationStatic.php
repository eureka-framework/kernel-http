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
use Eureka\Kernel\Http\Middleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application class
 *
 * @author Romain Cottard
 */
class ApplicationStatic implements ApplicationInterface
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
        $httpFactory = $this->kernel->getContainer()->get(Http\Message\HttpFactory::class);

        //~ Default response
        $response = $httpFactory->createResponse();

        try {
            $method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            $uri     = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $request = $httpFactory->createServerRequest($method, $uri, $_SERVER);

            $this->loadMiddleware($request);

            //~ Get response
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($request);
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
     * @param ServerRequestInterface $request
     * @return void
     */
    private function loadMiddleware(ServerRequestInterface $request)
    {
        $this->middleware[] = $this->kernel->getContainer()->get(Middleware\ErrorMiddleware::class);

        //~ Request
        $query = $request->getQueryParams();

        switch ($query['type']) {
            case 'css':
                $this->middleware[] = $this->kernel->getContainer()->get(
                    Middleware\StaticMiddleware\CssMiddleware::class
                )
                ;
                break;
            case 'js':
            case 'map':
                $this->middleware[] = $this->kernel->getContainer()->get(
                    Middleware\StaticMiddleware\JsMiddleware::class
                )
                ;
                break;
            case 'image':
                $this->middleware[] = $this->kernel->getContainer()->get(
                    Middleware\StaticMiddleware\ImageMiddleware::class
                )
                ;
                break;
            case 'font':
                $this->middleware[] = $this->kernel->getContainer()->get(
                    Middleware\StaticMiddleware\FontMiddleware::class
                )
                ;
                break;
        }
    }
}
