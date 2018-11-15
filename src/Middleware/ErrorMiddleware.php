<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Component\Http\HttpFactory;
use Eureka\Kernel\Http\Controller\ControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Router;

class ErrorMiddleware implements MiddlewareInterface
{
    /** @var ContainerInterface $container */
    protected $container;

    /**
     * ControllerMiddleware constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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
     * @param ServerRequestInterface $request
     * @param \Exception $exception
     * @return ResponseInterface
     * @throws
     */
    private function getErrorResponse(ServerRequestInterface $request, \Exception $exception): ResponseInterface
    {
        $httpCode  = ($exception instanceof RouteNotFoundException ? 404 : 500);
        $isDisplay = $this->container->getParameter('kernel.debug');
        $isAjax    = false;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
            ) === 'xmlhttprequest') {
            $isAjax = true;
        }

        $response = $this->container->get(HttpFactory::class)->createResponse($httpCode);

        $exceptionDetail = ($isDisplay ? $exception->getTraceAsString() : '');

        if ($isAjax) {

            //~ Ajax response error
            $content          = new \stdClass();
            $content->message = $exception->getMessage();
            $content->code    = $exception->getCode();
            $content->trace   = $exceptionDetail;

            $content = json_encode($content);
        } else {

            /*$router = $this->container->get(Router::class);

            if ($exception instanceof RouteNotFoundException) {
                $route = $router->get('page404');
            } else {
                $route = $router->get('page500');
            }

            $controllerClass = $route->getControllerName();
            $action          = $route->getActionName();

            $controller = $this->container->get($controllerClass);

            if (!($controller instanceof ControllerInterface)) {
                throw new \LogicException(
                    'Controller does not implement Controller Interface! (controller: ' . get_class($controller) . ')'
                );
            }

            if (!method_exists($controller, $action)) {
                throw new \DomainException(
                    'Action controller does not exists! (' . get_class($controller) . '::' . $action
                );
            }

            $controller->preAction();
            $response = $controller->$action($request, $exception);
            $controller->postAction();

            return $response;
            */
            //~ Basic html response error
            $content = '<pre>exception: ' . PHP_EOL . $exception->getMessage() . PHP_EOL . $exceptionDetail. '</pre>';
        }

        //~ Write content
        $response->getBody()->write($content);

        return $response;
    }
}
