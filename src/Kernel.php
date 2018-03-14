<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http;

use Eureka\Component\Config\Config;
use Eureka\Component\Config\ConfigCache;

class Kernel
{
    /** @var \Psr\Container\ContainerInterface $container */
    private $container;

    /** @var \Eureka\Component\Config\Config $container */
    private $config;

    /** @var string $root Root directory */
    private $root = '';

    /** @var string $environment Environment */
    private $environment = '';

    /** @var bool $debug Debug */
    private $debug = '';

    /**
     * Kernel constructor.
     *
     * @param  string $root
     * @param  string $environment
     * @param  bool $debug
     * @param  string $configDirectory
     * @param  string $kernelDirectory
     * @param  string $servicesDirectory
     * @throws \Eureka\Component\Config\Exception\ConfigException
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     * @throws \Eureka\Component\Container\Exception\ContainerException
     */
    public function __construct($root, $environment, $debug = false, $configDirectory = 'config', $kernelDirectory = 'kernel', $servicesDirectory = 'services')
    {
        $this->environment = $environment;
        $this->debug       = $debug;
        $this->root        = $root;

        $this
            ->initDebug(false)
            ->initConfig($configDirectory, $kernelDirectory, $servicesDirectory)
            ->initContainer()
            ->initDebug(true)
        ;
    }

    /**
     * @return \Eureka\Component\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Initialize debug.
     *
     * @param  bool $fromConfig
     * @return $this
     */
    protected function initDebug($fromConfig)
    {
        $display   = $this->debug;
        $reporting = -1;

        if ($fromConfig) {
            $display   = $this->config->get('app.error.display');
            $reporting = $this->config->get('app.error.reporting');
        }

        ini_set('display_errors', $display);
        error_reporting($reporting);

        return $this;
    }

    /**
     * Load Config path.
     *
     * @param  string $configDirectory
     * @param  string $kernelDirectory
     * @param  string $servicesDirectory
     * @return $this
     * @throws \Eureka\Component\Config\Exception\ConfigException
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     */
    protected function initConfig($configDirectory, $kernelDirectory, $servicesDirectory)
    {
        $this->config = new Config($this->environment);
        $configCache  = new ConfigCache(
            $this->root . DIRECTORY_SEPARATOR . $configDirectory . DIRECTORY_SEPARATOR . $kernelDirectory,
            $this->environment
        );

        //~ Load config from cache
        if ($configCache->hasCache() && $configCache->isEnabled()) {
            $this->config->set($configCache->loadFromCache());
            return $this;
        }

        $this
            ->initConfigKernel($this->root . DIRECTORY_SEPARATOR . $configDirectory . DIRECTORY_SEPARATOR . $kernelDirectory)
            ->initConfigApplication($this->root . DIRECTORY_SEPARATOR . $configDirectory)
            ->initConfigPackages()
            ->initConfigServices($this->root . DIRECTORY_SEPARATOR . $configDirectory, $servicesDirectory)
        ;

        $configCache->dumpCache($this->config->get());

        return $this;
    }

    /**
     * @param  string $path
     * @return $this
     * @throws \Eureka\Component\Config\Exception\ConfigException
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     */
    protected function initConfigKernel($path)
    {
        $this->config
            ->loadYamlFromDirectory($path)
            ->add('kernel.root', $this->root)
            ->add('kernel.env', $this->environment)
            ->add('kernel.environment', $this->environment)
            ->add('kernel.debug', $this->debug)
        ;

        return $this;
    }

    /**
     * @param  string $path
     * @return $this
     * @throws \Eureka\Component\Config\Exception\ConfigException
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     */
    protected function initConfigApplication($path)
    {
        $this->config->loadYamlFromDirectory($path);

        return $this;
    }

    /**
     * Load configs from packages.
     *
     * @return $this
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     * @throws \Eureka\Component\Config\Exception\ConfigException
     */
    protected function initConfigPackages()
    {
        $list = $this->config->get('app.package');

        if (empty($list) || !is_array($list)) {
            return $this;
        }

        foreach ($list as $name => $data) {
            if (!isset($data['config'])) {
                continue;
            }

            $this->config->loadYamlFromDirectory($data['config']);
        }

        return $this;
    }

    /**
     * @param  string $path
     * @param  string $servicesDirectory
     * @return $this
     * @throws \Eureka\Component\Config\Exception\ConfigException
     * @throws \Eureka\Component\Config\Exception\InvalidConfigException
     */
    protected function initConfigServices($path, $servicesDirectory)
    {
        $this->config->loadYamlFromDirectory($path . DIRECTORY_SEPARATOR . $servicesDirectory);

        $list = $this->config->get('app.package');

        if (!empty($list) && is_array($list)) {
            foreach ($list as $name => $data) {
                if (!isset($data['config'])) {
                    continue;
                }

                $this->config->loadYamlFromDirectory($data['config'] . DIRECTORY_SEPARATOR . $servicesDirectory);
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Eureka\Component\Container\Exception\ContainerException
     */
    protected function initContainer()
    {
        $this->container = ServicesContainerFactory::makeFromConfig($this->getConfig());
        $this->container->attach('config', $this->getConfig());

        return $this;
    }
}
