<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http;

use Eureka\Component\Config\Config;
use Eureka\Component\Container\Container;

class Kernel
{
    /** @var \Psr\Container\ContainerInterface $container */
    private $container = null;

    /** @var \Eureka\Component\Config\Config $container */
    private $config = null;

    /** @var string $root Root directory */
    private $root = '';

    /** @var string $env Environment */
    private $env = '';

    /** @var bool $debug Debug */
    private $debug = '';

    /**
     * Kernel constructor.
     *
     * @param string $env
     */
    public function __construct($root, $env, $debug = false, $config = 'config')
    {
        $this->env   = $env;
        $this->debug = $debug;
        $this->root  = $root;

        $this
            ->initDebug(false)
            ->initConfig($root . DIRECTORY_SEPARATOR . $config)
            ->initConfigPackages()
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
     * @param  string $path
     * @return $this
     */
    protected function initConfig($path)
    {
        $this->config = (new Config($this->env))->loadYamlFromDirectory($path);
        $this->config->add('kernel.root', $this->root);
        $this->config->add('kernel.env', $this->env);
        $this->config->add('kernel.debug', $this->debug);

        return $this;
    }

    /**
     * Load configs from packages.
     *
     * @return $this
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
     * @return $this
     */
    protected function initContainer()
    {
        $this->container = Container::makeFromArray($this->config->get('app.services'));

        return $this;
    }
}
