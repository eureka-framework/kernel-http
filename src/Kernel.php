<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
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

/**
 * Class Kernel
 *
 * @author Romain Cottard
 */
class Kernel
{
    private const CONFIG_EXTENSIONS = '.{php,xml,yaml,yml}';

    private ContainerInterface $container;
    private ContainerBuilder $containerBuilder;

    private string $rootDirectory;
    private string $environment;
    private bool $debug;
    protected string $name = 'src';
    protected string $varDirectory;

    /**
     * @throws \Exception
     */
    public function __construct(string $rootDirectory, string $environment, bool $debug = false)
    {
        $this->environment   = $environment;
        $this->debug         = $debug;
        $this->rootDirectory = $rootDirectory;
        $this->varDirectory  = $rootDirectory . DIRECTORY_SEPARATOR . 'var';

        $this
            ->initErrorReporting(E_ALL, 'true') // report & display all
            ->initVarSubDir()
            ->initContainer()
            ->overrideErrorReporting() // report & display according to the config
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
            $this->containerBuilder = new ContainerBuilder();
            $this->loadConfig();
            $this->registerCompilerPasses();
            $this->dumpContainer();
        }

        require_once $file;

        /** @var class-string<ContainerInterface> $className */
        $className = $this->getContainerClass();
        $this->container = new $className();

        return $this;
    }

    /**
     * Initialize error reporting & display.
     *
     * @param int $reporting
     * @param string $display
     * @return Kernel
     */
    protected function initErrorReporting(int $reporting, string $display): self
    {
        error_reporting($reporting);
        ini_set('display_errors', $display);

        return $this;
    }

    /**
     * Override error reporting & display from config if exists.
     *
     * @return Kernel
     */
    protected function overrideErrorReporting(): self
    {
        //~ Override reporting value from config
        $reporting  = $this->container->getParameter('kernel.error.reporting');
        $errorLevel = (int) (!is_scalar($reporting) ? error_reporting(0) : $reporting);

        //~ Override display value from config
        $display      = $this->container->getParameter('kernel.error.display');
        $errorDisplay = (string) (!is_scalar($display) ? ini_get('display_errors') : $display);

        error_reporting($errorLevel);
        ini_set('display_errors', $errorDisplay);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function loadConfig(): self
    {
        //~ Get loader
        $loader = $this->getContainerLoader($this->containerBuilder);

        //~ Load kernel config files
        $loader->load($this->getConfigDir() . '/{kernel}' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{kernel}_' . $this->environment . self::CONFIG_EXTENSIONS, 'glob'); // @deprecated

        $this->containerBuilder->setParameter('kernel.environment', $this->environment);
        $this->containerBuilder->setParameter('kernel.directory.root', $this->rootDirectory);

        //~ Load packages config files
        $loader->load($this->getConfigDir() . '/{packages}/*' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{packages}/**/*' . self::CONFIG_EXTENSIONS, 'glob');

        //~ Load specific env config files
        $loader->load($this->getConfigDir() . '/{' . $this->environment . '}/*' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{' . $this->environment . '}/**/*' . self::CONFIG_EXTENSIONS, 'glob');

        //~ Load services config files
        $loader->load($this->getConfigDir() . '/{services}' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{services}_' . $this->environment . self::CONFIG_EXTENSIONS, 'glob'); // @deprecated

        //~ Load secrets config files
        $loader->load($this->getConfigDir() . '/{secrets}/*' . self::CONFIG_EXTENSIONS, 'glob');
        $loader->load($this->getConfigDir() . '/{secrets}/**/*' . self::CONFIG_EXTENSIONS, 'glob');

        $this->containerBuilder->setParameter('kernel.directory.root', $this->rootDirectory);

        return $this;
    }

    /**
     * Register user-defined compiler pass to the container
     *
     * @return $this
     */
    protected function registerCompilerPasses(): self
    {
        if (!$this->containerBuilder->hasParameter('kernel.compiler_pass')) {
            return $this; // @codeCoverageIgnore
        }

        $compilerPasses = (array) $this->containerBuilder->getParameter('kernel.compiler_pass');
        /** @var class-string<CompilerPassInterface> $compilerPass */
        foreach ($compilerPasses as $compilerPass) {
            if (!class_exists($compilerPass)) {
                continue;
            }
            $this->containerBuilder->addCompilerPass(new $compilerPass()); // @codeCoverageIgnore
        }

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
            $this->containerBuilder->compile();

            $dumper = new PhpDumper($this->containerBuilder);

            /** @var string $content */
            $content = $dumper->dump(['class' => $this->getContainerClass()]);
            $containerConfigCache->write($content, $this->containerBuilder->getResources());
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
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir)); // @codeCoverageIgnore
            }
        }

        return $this;
    }
}
