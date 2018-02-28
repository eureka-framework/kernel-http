<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware\StaticMiddleware;

use Eureka\Component\Config\Config;
use Eureka\Psr\Http\Server\MiddlewareInterface;
use Eureka\Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract Class Static Middleware
 *
 * Need to have those apache's rules:
 *
 * # we check for css
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^static/(.*)\.(css)$ static.php?type=css&file=$1&ext=$2 [L]
 *
 * # we check for js
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^static/(.*)\.(js)$ static.php?type=js&file=$1&ext=$2 [L]
 * # we check for images files
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^static/(.*)\.(jpg|jpeg|png)$ static.php?type=image&file=$1&ext=$2 [L]
 * # we check for fonts files
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^static/(.*)\.(eot|svg|ttf|woff|woff2)$ static.php?type=font&file=$1&ext=$2 [L]
 *
 * @author  Romain Cottard
 */
abstract class AbstractStaticMiddleware implements MiddlewareInterface
{
    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /** @var \Eureka\Component\Config\Config $config Config */
    protected $config = null;

    /**
     * CssMiddleware constructor.
     *
     * @param \Psr\Container\ContainerInterface
     * @param \Eureka\Component\Config\Config $config ;
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
        $response = $handler->handle($request);

        return $this->readFile($request, $response);
    }

    /**
     * Get Mime Type
     *
     * @param  string $file
     * @return string
     */
    protected function getMimeType($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $file);
    }

    /**
     * Read & add content file to the response.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function readFile(ServerRequestInterface $request, ResponseInterface $response)
    {
        $path = trim($request->getQueryParams()['file']);
        $ext  = trim($request->getQueryParams()['ext']);

        //~ Uri form: cache/{vendor}/{name}-{package}-{theme}/{module}/{type}/{filename}
        $pattern = '`(cache)/([a-z0-9_-]+)/([a-z0-9_]+)-([a-z0-9_]+)-([a-z0-9_]+)/([a-z0-9_]+)/([a-z]+)/([a-z0-9_./-]+)`i';
        $matches = [];

        if (!(bool) preg_match($pattern, $path, $matches)) {
            var_export($path);
            echo PHP_EOL;
            var_export($pattern);
            throw new \Exception('Invalid image uri');
        }

        $cache    = $matches[1];
        $vendor   = $matches[2];
        $name     = $matches[3];
        $package  = $matches[4];
        $theme    = $matches[5];
        $module   = $matches[6];
        $type     = $matches[7];
        $filename = $matches[8];

        $basePath   = $this->config->get('kernel.root') . '/vendor';
        $staticPath = $this->config->get('app.theme.path');

        $replace = [
            '{BASE}'     => $basePath,
            '{VENDOR}'   => $vendor,
            '{NAME}'     => $name,
            '{THEME}'    => $theme,
            '{PACKAGE}'  => $package,
            '{MODULE}'   => $module,
            '{TYPE}'     => $type,
            '{FILENAME}' => $filename,
            '{EXT}'      => $ext,
        ];

        echo $staticPath . PHP_EOL;
        if (empty($staticPath)) {
            $staticPath = '{BASE}/{VENDOR}/{NAME}-{PACKAGE}-{THEME}/resources/static/{MODULE}/{TYPE}/{FILENAME}.{EXT}';
        }

        $file = str_replace(array_keys($replace), $replace, $staticPath);

        if (!file_exists($file)) {
            throw new \Exception('File does not exists ! (file: ' . $file . ')');
        }

        $content = file_get_contents($file);

        //~ Write file in cache when is on prod
        if (true === $this->config->get('app.theme.static.cache_enabled')) {
            $this->writeCache(dirname($path), basename($filename . '.' . $ext), $content);
        }

        $response = $response->withHeader('Content-Type', $this->getMimeType($file));
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * Write cache
     *
     * @param  string $path
     * @param  string $filename Cache file
     * @param  string $content File content
     * @return void
     * @throws \Exception
     */
    private function writeCache($path, $filename, $content)
    {
        $path = $this->config->get('app.theme.static.cache_path') . DIRECTORY_SEPARATOR . $path;

        if (!is_dir($path) && !mkdir($path, 0777, true)) {
            throw new \Exception('Unable to create directory');
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $content);
    }
}
