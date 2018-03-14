<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http;

use Eureka\Component\Config\Config;
use Eureka\Component\Container\ServicesContainer;

/**
 * Container class.
 *
 * @author Romain Cottard
 */
class ServicesContainerFactory
{
    /**
     * Create new container & init it from an array.
     *
     * @param  Config $config
     * @return ServicesContainer
     * @throws \Eureka\Component\Container\Exception\ContainerException
     */
    public static function makeFromConfig(Config $config)
    {
        $services    = $config->get('app.services');
        $cachePath   = $config->get('app.cache.container.path');
        $cacheFile   = $config->get('app.cache.container.file');
        $cacheClass  = $config->get('app.cache.container.class');
        $environment = $config->get('kernel.environment');

        ServicesContainer::checkCache($services, $cacheClass, $cacheFile, $cachePath, $environment);

        $container = new ServicesContainer($cacheClass);

        foreach ($services as $name => $service) {
            $container->attach($name, 'service::' . ServicesContainer::formatServiceName($name));
        }

        return $container;
    }
}

