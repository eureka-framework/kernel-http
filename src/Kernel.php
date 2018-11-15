<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Kernel
{
    /** @var string CONFIG_EXTENSIONS */
    private const CONFIG_EXTENSIONS = '.{php,xml,yaml,yml}';

    /** @var ContainerBuilder $container */
    private $container;

    /** @var string $rootDirectory Root directory */
    private $rootDirectory = '';

    /** @var string $environment Environment */
    private $environment = 'dev';

    /** @var bool $debug Debug */
    private $debug = false;

    /** @var string $name */
    protected $name = 'src';

    /** @var string $varDirectory */
    protected $varDirectory = '';

    /**
     * Kernel constructor.
     *
     * @param $rootDirectory
     * @param $environment
     * @param bool $debug
     * @throws \Exception
     */
    public function __construct(string $rootDirectory, string $environment, bool $debug = false)
    {
        $this->environment   = $environment;
        $this->debug         = $debug;
        $this->rootDirectory = $rootDirectory;
        $this->varDirectory  = $rootDirectory . DIRECTORY_SEPARATOR . 'var';

        $this
            ->initErrorReporting(E_ALL, true) // report & display all
            ->initVarSubDir()
            ->initContainer()
            ->initErrorReporting() // report & display according to the config
        ;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return Kernel
     * @throws \Exception
     */
    protected function initContainer(): self
    {
        $file = $this->getCacheContainerFile();

        $containerConfigCache = new ConfigCache($file, $this->debug);

        if (!$containerConfigCache->isFresh()) {
            $this->container = new ContainerBuilder();
            $this->loadConfig();
            $this->dumpContainer();
        }

        require_once $file;

        $className = $this->getContainerClass();
        $this->container = new $className();

        return $this;
    }

    /**
     * Initialize error reporting & display.
     *
     * @param null $reporting
     * @param null $display
     * @return Kernel
     */
    protected function initErrorReporting($reporting = null, $display = null): self
    {
        error_reporting($reporting !== null ? $reporting : $this->container->getParameter('kernel.error.reporting'));
        ini_set('display_errors', $display !== null ? $display : $this->container->getParameter('kernel.error.display'));

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function loadConfig(): self
    {
        //~ Get loader
        $loader = $this->getContainerLoader($this->container);

        //~ Load packages config files
        $loader->load($this->getConfigDir() . '/{packages}/*' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{packages}/' . $this->environment . '/**/*' . self::CONFIG_EXTENSIONS,'glob');

        //~ Load services config files
        $loader->load($this->getConfigDir() . '/{services}' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{services}_' . $this->environment . self::CONFIG_EXTENSIONS, 'glob');

        //~ Load kernel config files
        $loader->load($this->getConfigDir() . '/{kernel}' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{kernel}_' . $this->environment . self::CONFIG_EXTENSIONS, 'glob');

        return $this;
    }

    /**
     * Dump container in cache files if necessary.
     *
     * @return void
     */
    protected function dumpContainer(): void
    {
        $file                 = $this->getCacheContainerFile();
        $containerConfigCache = new ConfigCache($file, $this->debug);

        if (!$containerConfigCache->isFresh()) {

            $this->container->compile();

            $dumper = new PhpDumper($this->container);
            $containerConfigCache->write(
                $dumper->dump(
                    [
                        'class' => $this->getContainerClass(),
                    ]
                ),
                $this->container->getResources()
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return DelegatingLoader
     */
    protected function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $locator  = new FileLocator();
        $resolver = new LoaderResolver(
            [
                new XmlFileLoader($container, $locator),
                new YamlFileLoader($container, $locator),
                new IniFileLoader($container, $locator),
                new PhpFileLoader($container, $locator),
                new GlobFileLoader($container, $locator),
                new DirectoryLoader($container, $locator),
                new ClosureLoader($container),
            ]
        );

        return new DelegatingLoader($resolver);
    }

    /**
     * Gets the container class.
     *
     * @return string The container class
     */
    protected function getContainerClass(): string
    {
        return $this->name . ucfirst($this->environment) . ($this->debug ? 'Debug' : '') . 'ProjectContainer';
    }

    /**
     * @return string
     */
    protected function getCacheContainerFile(): string
    {
        return $this->getCacheDir() . DIRECTORY_SEPARATOR . 'container.php';
    }

    /**
     * @return string
     */
    protected function getConfigDir(): string
    {
        return $this->rootDirectory . DIRECTORY_SEPARATOR . 'config';
    }

    /**
     * @return string
     */
    protected function getCacheDir(): string
    {
        return $this->varDirectory . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $this->environment;
    }

    /**
     * @return string
     */
    protected function getLogDir(): string
    {
        return $this->varDirectory . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $this->environment;
    }

    /**
     * @return $this
     */
    private function initVarSubDir(): self
    {
        $dirs = [
            'cache' => $this->getCacheDir(),
            'logs'  => $this->getLogDir(),
        ];

        foreach ($dirs as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir));
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir));
            }
        }

        return $this;
    }
}
