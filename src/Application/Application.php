<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Application;

use Eureka\Component\Http;
use Eureka\Kernel\Http\Kernel;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Application class
 *
 * @author Romain Cottard
 */
class Application implements ApplicationInterface
{
    /** @var MiddlewareInterface[] $middleware */
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
        /** @var Http\HttpFactory $httpFactory */
        $httpFactory = $this->kernel->getContainer()->get('http_factory');

        /** @var ResponseInterface $response */
        $response = $httpFactory->createResponse();
        $method   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $httpFactory->createServerRequest($method, '', $_SERVER);

        try {
            $this->loadMiddleware();

            //~ Get response
            $handler  = new Http\Server\RequestHandler($response, $this->middleware);
            $response = $handler->handle($serverRequest);

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
