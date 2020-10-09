<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Eureka\Component\Http\HttpFactory;
use Eureka\Kernel\Http\Middleware\Exception\RouteNotFoundException;
use Eureka\Kernel\Http\Middleware\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
class ErrorController extends Controller
{
    /**
     * ErrorController constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function preAction(?ServerRequestInterface $request = null): void
    {
        $httpFactory = new HttpFactory();
        $this->setRequestFactory($httpFactory);
        $this->setServerRequestFactory($httpFactory);
        $this->setResponseFactory($httpFactory);
        $this->setStreamFactory($httpFactory);
        $this->setUriFactory($httpFactory);
        $this->setServerRequest($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param \Exception $exception
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request, \Exception $exception): ResponseInterface
    {
        $code = 500;

        if ($exception instanceof RouteNotFoundException) {
            $code = 404;
        } elseif ($exception instanceof UnauthorizedException) {
            $code = 403;
        }

        if ($this->isAjax($request)) {
            $content = $this->getErrorContentJson($exception);
        } else {
            $content = $this->getErrorContentHtml($request, $exception);
        }

        return $this->getResponse($content, $code);
    }

    /**
     * @param ServerRequestInterface $request
     * @param \Exception $exception
     * @return string
     */
    protected function getErrorContentHtml(ServerRequestInterface $request, \Exception $exception)
    {
        return
            '<pre>exception[' . get_class($exception) . ']: ' . PHP_EOL .
            $exception->getMessage() . PHP_EOL .
            ($this->isDebug() ? $exception->getTraceAsString() . PHP_EOL : '') . PHP_EOL .
            '</pre>';
    }

    /**
     * @param \Exception $exception
     * @return false|string
     */
    protected function getErrorContentJson(\Exception $exception)
    {
        //~ Ajax response error
        $content          = new \stdClass();
        $content->message = $exception->getMessage();
        $content->code    = $exception->getCode();
        $content->trace   = ($this->isDebug() ? $exception->getTraceAsString() : '');

        $content = json_encode($content);

        return $content !== false ? $content : '';
    }
}
