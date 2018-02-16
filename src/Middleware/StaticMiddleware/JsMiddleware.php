<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Middleware\StaticMiddleware;

/**
 * Class JsMiddleware
 *
 * @author  Romain Cottard
 */
class JsMiddleware extends AbstractStaticMiddleware
{
    /**
     * Get Mime Type
     *
     * @param  string $file
     * @return string
     */
    protected function getMimeType($file)
    {
        return 'application/javascript';
    }
}
