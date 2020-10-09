<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Controller;

use Eureka\Kernel\Http\Traits\HttpFactoryAwareTrait;
use Eureka\Kernel\Http\Traits\RouterAwareTrait;
use Eureka\Kernel\Http\Traits\ServerRequestAwareTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
abstract class Controller implements ControllerInterface
{
    use HttpFactoryAwareTrait,
        RouterAwareTrait,
        ServerRequestAwareTrait;

    /** @var ContainerInterface $container */
    private $container;

    /** @var bool $debug */
    private $debug = false;

    /** @var string $environment */
    private $environment = 'prod';

    /**
     * This method is executed before the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function preAction(?ServerRequestInterface $request = null): void
    {
    }

    /**
     * This method is executed after the main controller action method.
     *
     * @param null|ServerRequestInterface $request
     * @return void
     */
    public function postAction(?ServerRequestInterface $request = null): void
    {
    }

    /**
     * @param ContainerInterface $container
     * @return ControllerInterface
     */
    public function setContainer(ContainerInterface $container): ControllerInterface
    {
        /** @var SymfonyContainerInterface $container */
        $this->container   = $container;
        $this->debug       = $container->getParameter('kernel.debug');
        $this->environment = $container->getParameter('kernel.environment');

        return $this;
    }

    /**
     * @return bool
     */
    protected function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return bool
     */
    protected function isDev(): bool
    {
        return $this->environment === 'dev';
    }

    /**
     * @return bool
     */
    protected function isProd(): bool
    {
        return $this->environment === 'prod';
    }

    /**
     * @return string
     */
    protected function environment(): string
    {
        return $this->environment;
    }

    /**
     * @param string $content
     * @param int $code
     * @return ResponseInterface
     */
    protected function getResponse(string $content, int $code = 200): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse($code);
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param mixed $content
     * @param int $code
     * @param bool $jsonEncode
     * @return ResponseInterface
     */
    protected function getResponseJson($content, int $code = 200, bool $jsonEncode = true): ResponseInterface
    {
        if ($jsonEncode || (!is_string($content) && !is_numeric($content)) ) {
            $content = json_encode($content);
        }

        return $this->getResponse($content, $code)->withAddedHeader('Content-Type', 'application/json');
    }

    /**
     * Redirect on specified url.
     *
     * @param  string $url
     * @param  int    $status
     * @return void
     */
    protected function redirect($url, $status = 301): void
    {
        $status = (int) $status;

        if (!empty($url)) {
            $protocolVersion = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

            header('HTTP/' . $protocolVersion . ' ' . $status . ' Redirect');
            header('Status: ' . $status . ' Redirect');
            header('Location: ' . $url);
            exit(0);
        } else {
            throw new \InvalidArgumentException('Url is empty !');
        }
    }

    /**
     * @param $routeName
     * @param array $params
     * @param int $status
     * @return void
     */
    protected function redirectToRoute($routeName, $params = [], $status = 200): void
    {
        $this->redirect($this->getRouteUri($routeName, $params), $status);
    }
}
