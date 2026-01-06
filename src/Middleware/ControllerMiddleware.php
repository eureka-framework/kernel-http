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

readonly class ControllerMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container) {}

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $serverRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null === $serverRequest->getAttribute('route')) {
            throw new HttpNotFoundException('Route not defined'); // @codeCoverageIgnore
        }

        $response = $this->handle($serverRequest);

        $otherResponse = $handler->handle($serverRequest);
        $response->getBody()->write($otherResponse->getBody()->getContents());

        return $response;
    }

    /**
     * Run application middleware.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handle(ServerRequestInterface $serverRequest): ResponseInterface
    {
        /** @var array<string, string|int|bool|float|bool|null> $route */
        $route = $serverRequest->getAttribute('route') ?? [];

        [$controllerName, $action] = explode('::', (string) $route['_controller']);

        /** @var ControllerInterface|object $controller */
        $controller = $this->container->get($controllerName);

        if (!method_exists($controller, $action)) {
            throw new \DomainException(
                'Action controller does not exists! (' . get_class($controller) . '::' . $action,
            );
        }

        if (!$controller instanceof ControllerInterface) {
            throw new \DomainException('Controller must implement ControllerInterface'); // @codeCoverageIgnore
        }

        $controllerParameters = [$serverRequest];

        //~ Add route element to controller parameters to pass it directly.
        // /!\ Params from url are always strings
        foreach ($route as $key => $value) {
            if ($key[0] === '_') {
                continue;
            }

            $controllerParameters[] = $value;
        }

        //~ Set context action
        $controller->setRoute($route);

        //~ Call controller pre action, action & post action.
        $controller->preAction($serverRequest);
        /** @var ResponseInterface $response */
        $response = $controller->$action(...$controllerParameters);
        $controller->postAction($serverRequest);

        return $response;
    }
}
