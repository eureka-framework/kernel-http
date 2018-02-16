<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Framework\Middleware\StaticMiddleware;

use Eureka\Component\Config\Config;
use Eureka\Component\Media\Image\Image;
use Eureka\Component\Psr\Http\Middleware\DelegateInterface;
use Eureka\Component\Psr\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract Class Static Middleware
 *
 * You must have following apache rules to generate thumbnails
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule "^thumbnail/(.*)$" "thumbnail.php" [QSA,L]
 *
 * @author  Romain Cottard
 */
class ThumbnailMiddleware extends AbstractStaticMiddleware
{
    /** @var string $md5 Original md5 file */
    private $md5 = '';

    /** @var string $extension File extension */
    private $extension = '';

    /** @var int $maxWidth */
    private $maxWidth = 0;

    /** @var int $maxHeight */
    private $maxHeight = 0;

    /** @var string $imageFilepath */
    private $imageFilepath = '';

    /** @var string $thumbnailPath */
    private $thumbnailPath = '';

    /**
     * {@inheritdoc}
     */
    protected function readFile(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->decomposeUri($request);

        $image = new Image($this->imageFilepath);

        if ($this->maxHeight === 0) {
            $image->resizeOnWidth($this->maxWidth);
        } elseif ($this->maxWidth === 0) {
            $image->resizeOnHeight($this->maxHeight);
        } else {
            $image->resize($this->maxWidth, $this->maxHeight, true);
        }

        //~ Save thumbnail
        if ($this->config->get('global.cache.thumbnail.enabled')) {
            switch ($this->extension) {
                case 'jpg':
                    $type = IMAGETYPE_JPEG;
                    $mime = 'image/jpeg';
                    break;
                case 'png':
                    $type = IMAGETYPE_PNG;
                    $mime = 'image/png';
                    break;
            }
            $newImage = $image->saveForCdn($this->config->get('global.media.thumbnail.dir'), $type, 85, '_' . $this->maxWidth . '_' . $this->maxHeight);
            //->withHeader('Content-Length', filesize($newImage->getFilePathname())
        } else {
            $newImage = clone $image;
        }

        $response = $response->withHeader('Content-Type', $mime);
        $response->getBody()->write(readfile($newImage->getFilePathname()));

        return $response;
    }

    /**
     * Decompose thumbnail image uri.
     *
     * @param  ServerRequestInterface $request
     * @return $this
     */
    protected function decomposeUri(ServerRequestInterface $request)
    {
        //~ Remove base path from uri to keep data about image
        $basePath = $this->config->get('global.media.thumbnail.path');
        $path     = str_replace($basePath, '', $request->getUri()->getPath());
        //$data     = explode('/', trim($path, '/'));

        $pattern = $this->config->get('global.media.thumbnail.regexp.pattern');
        $mapping = $this->config->get('global.media.thumbnail.regexp.mapping');
        $matches = [];

        if (!(bool) preg_match($pattern, $path, $matches)) {
            throw new \DomainException('Invalid URL!');
        }

        $data = array_combine($mapping, $matches);

        $this->md5       = $data['md5'];
        $this->extension = $data['extension'];

        $this->maxWidth  = (int) $data['maxWidth'];
        $this->maxHeight = (int) $data['maxHeight'];

        return $this;
    }

    /**
     * Assert the original image exist & thumbnail uri is valid.
     *
     * @return $this
     * @throws \DomainException
     * @throws \UnderflowException
     * @throws \RuntimeException
     */
    protected function assertIsValidThumbnail()
    {
        if (!(bool) preg_match('`^[a-f0-9]{32}$`', $this->md5)) {
            throw new \DomainException('Invalid MD5!');
        }

        if ($this->maxWidth <= 0 && $this->maxHeight <= 0) {
            throw new \UnderflowException('Invalid width & height! (There must be greater than 0)');
        }

        if (!in_array($this->extension, ['jpg', 'png'])) {
            throw new \DomainException('Unsupported file extension!');
        }

        $directory           = $this->config->get('global.media.image.dir');
        $this->imageFilepath = $directory . '/' . $this->md5{0} . '/' . $this->md5{1} . '/' . $this->md5{2} . '/' . $this->md5 . '.' . $this->extension;

        if (!file_exists($this->imageFilepath)) {
            throw new \DomainException('Original file does not exist!');
        }

        $directory           = $this->config->get('global.media.thumbnail.dir');
        $this->thumbnailPath = $directory . '/' . $this->md5{0} . '/' . $this->md5{1} . '/' . $this->md5{2};
        if (!is_dir($this->thumbnailPath) && !mkdir($this->thumbnailPath, 0777, true)) {
            throw new \RuntimeException('Cannot create thumbnail directory! (dir: ' . $this->thumbnailPath . ')');
        }

        return $this;
    }
}
