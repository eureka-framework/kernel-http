<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Controller;

use Eureka\Kernel\Http\Traits\HttpFactoryAwareTrait;
use Eureka\Kernel\Http\Traits\LoggerAwareTrait;
use Eureka\Kernel\Http\Traits\RouterAwareTrait;
use Eureka\Kernel\Http\Traits\ServerRequestAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Controller implements ControllerInterface
{
    use HttpFactoryAwareTrait;
    use RouterAwareTrait;
    use ServerRequestAwareTrait;
    use LoggerAwareTrait;

    /**
     * @var string[] List of official Http Code (excluding WebDAV official codes)
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     */
    protected const array HTTP_CODE_MESSAGES = [
        //~ 2xx Success
        200 => 'Success',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        //~ 3xx Redirect
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // Previously "Moved temporarily",
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        //~ 4xx Client errors
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // For an easter egg :)
        421 => 'Misdirected Request',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        //~ 5xx Server errors
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];


    private bool $debug = false;
    private string $environment = 'prod';

    public function setEnvironment(string $environment, bool $isDebug = false): ControllerInterface
    {
        $this->environment = $environment;
        $this->debug       = $isDebug;

        return $this;
    }

    public function preAction(?ServerRequestInterface $serverRequest = null): void
    {
        //~ Automatically add server request to controller in pre-action
        if ($serverRequest !== null) {
            $this->setServerRequest($serverRequest);
        }
    }

    public function postAction(?ServerRequestInterface $serverRequest = null): void {}

    protected function isDebug(): bool
    {
        return $this->debug;
    }

    protected function isDev(): bool
    {
        return $this->environment === 'dev';
    }

    protected function isProd(): bool
    {
        return $this->environment === 'prod';
    }

    protected function getEnvironment(): string
    {
        return $this->environment;
    }

    protected function getResponse(string $content, int $code = 200): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse($code);
        $response->getBody()->write($content);

        return $response;
    }

    protected function getResponseJson(mixed $content, int $code = 200, bool $jsonEncode = true): ResponseInterface
    {
        if ($jsonEncode || (!\is_string($content) && !\is_numeric($content))) {
            $content = \json_encode($content);
        }

        return $this->getResponse((string) $content, $code)->withAddedHeader('Content-Type', 'application/json');
    }

    /**
     * @codeCoverageIgnore
     */
    protected function redirect(string $url, int $status = 301): never
    {
        if ($url !== '') {
            $params = $this->getServerRequest()->getServerParams();
            $serverProtocol  = !isset($params['SERVER_PROTOCOL']) || !\is_string($params['SERVER_PROTOCOL']) ? '1.1' : $params['SERVER_PROTOCOL'];
            $protocolVersion = \str_replace('HTTP/', '', $serverProtocol);

            \header('HTTP/' . $protocolVersion . ' ' . $status . ' Redirect');
            \header('Status: ' . $status . ' Redirect');
            \header('Location: ' . $url);
            \header('Pragma: no-cache');
            exit(0);
        } else {
            throw new \InvalidArgumentException('Url is empty !');
        }
    }

    /**
     * @param array<string, string|int|bool|float|bool|null> $params
     * @codeCoverageIgnore
     */
    protected function redirectToRoute(string $routeName, array $params = [], int $status = 200): never
    {
        $this->redirect($this->getRouteUri($routeName, $params), $status);
    }
}
