<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Component\Config\Config;
use Eureka\Component\Http\Message\Response;
use Eureka\Psr\Http\Server\MiddlewareInterface;
use Eureka\Psr\Http\Server\RequestHandlerInterface;
use Eureka\Kernel\Http\Controller\ControllerInterface;
use Eureka\Kernel\Http\Middleware\Exception\RouteNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorMiddleware implements MiddlewareInterface
{
    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /** @var Config config */
    protected $config = null;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param Config $config
     */
    public function __construct(ContainerInterface $container, Config $config)
    {
        $this->container = $container;
        $this->config    = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        try {
            $response = $handler->handle($request);
        } catch (\Exception $exception) {
            $response = $this->getErrorResponse($request, $exception);
        }

        return $response;
    }

    /**
     * Get Error response.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     * @param  \Exception $exception
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Eureka\Component\Routing\Exception\RoutingException
     */
    private function getErrorResponse(ServerRequestInterface $request, \Exception $exception)
    {
        $httpCode  = ($exception instanceof RouteNotFoundException ? 404 : 500);
        $isAjax    = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $isDisplay = $this->config->get('app.error.display');

        $response = new Response($httpCode);

        $exceptionDetail = ($isDisplay ? $exception->getTraceAsString() : '');

        if ($isAjax) {

            //~ Ajax response error
            $content = new \stdClass();
            $content->message = $exception->getMessage();
            $content->code    = $exception->getCode();
            $content->trace   = $exceptionDetail;

            $content = json_encode($content);

        } elseif (null !== $request->getAttribute('twigLoader', null)) {

            //~ Twig response error
            $twigLoader      = $request->getAttribute('twigLoader');
            $twig            = new \Twig_Environment($twigLoader);
            $exceptionDetail = PHP_EOL . $exception->getMessage() . PHP_EOL . $exceptionDetail;

            $content = $twig->render('@template/Content/Page' . $httpCode . '.twig', ['exceptionDetail' => $exceptionDetail]);

        } else {

            /** @var \Eureka\Component\Routing\Router $router */
            $router = $this->container->get('router');

            if ($exception instanceof RouteNotFoundException) {
                $route = $router->get('page404');
            } else {
                $route = $router->get('page500');
            }

            $controller = $route->getControllerName();
            $action     = $route->getActionName();

            if (!class_exists($controller)) {
                throw new \DomainException('Controller does not exists! (controller: ' . $controller . ')');
            }

            $controller = new $controller($this->container, $this->config, $route, $request);

            if (!($controller instanceof ControllerInterface)) {
                throw new \LogicException('Controller does not implement Controller Interface! (controller: ' . get_class($controller) . ')');
            }

            if (!method_exists($controller, $action)) {
                throw new \DomainException('Action controller does not exists! (' . get_class($controller) . '::' . $action);
            }

            $controller->runBefore();
            $response = $controller->$action($request, $exception);
            $controller->runAfter();

            return $response;

            //~ Basic html response error
            //$content = '<pre>exception: ' . PHP_EOL . $exception->getMessage() . PHP_EOL . $exceptionDetail. '</pre>';
        }

        //~ Write content
        $response->getBody()->write($content);

        return $response;
    }
}
