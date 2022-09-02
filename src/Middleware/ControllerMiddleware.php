<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Middleware;

use Eureka\Kernel\Http\Controller\ControllerInterface;
use Eureka\Kernel\Http\Exception\HttpNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ControllerMiddleware
 *
 * @author Romain Cottard
 */
class ControllerMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null === $request->getAttribute('route')) {
            throw new HttpNotFoundException('Route not defined'); // @codeCoverageIgnore
        }

        $response = $this->handle($request);

        $otherResponse = $handler->handle($request);
        $response->getBody()->write($otherResponse->getBody()->getContents());

        return $response;
    }

    /**
     * Run application middleware.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, string|int|bool|float|bool|null> $route */
        $route = $request->getAttribute('route') ?? [];

        //~ Remove route from request
        $request = $request->withoutAttribute('route');

        [$controllerName, $action] = explode('::', (string) $route['_controller']);

        /** @var ControllerInterface|object $controller */
        $controller = $this->container->get($controllerName);

        if (!method_exists($controller, $action)) {
            throw new \DomainException(
                'Action controller does not exists! (' . get_class($controller) . '::' . $action
            );
        }

        if ($controller instanceof ControllerInterface) {
            //~ Set context action
            $controller->setRoute($route);

            //~ Call controller pre action, action & post action.
            $controller->preAction($request);
            $response = $controller->$action($request);
            $controller->postAction($request);
        } else {
            $response = $controller->$action($request); // @codeCoverageIgnore
        }

        return $response;
    }
}
