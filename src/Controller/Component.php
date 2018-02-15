<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Controller;

use Eureka\Component\Config\Config;
use Psr\Container\ContainerInterface;

/**
 * Controller class
 *
 * @author Romain Cottard
 */
abstract class Component
{
    /** @var DataCollection $dataCollection Data collection object. */
    protected $dataCollection = null;

    /** @var \Psr\Container\ContainerInterface $container */
    protected $container = null;

    /**
     * Class constructor
     *
     * @param  \Psr\Container\Container $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container      = $container;
        $this->dataCollection = new DataCollection();
    }

    /**
     * This method is executed before the main run() method.
     *
     * @return void
     */
    public function runBefore()
    {
    }

    /**
     * This method is executed after the main run() method.
     *
     * @return void
     */
    public function runAfter()
    {
    }

    /**
     * Get theme name.
     *
     * @return string
     */
    protected function getThemeName()
    {
        return $this->themeName;
    }

    /**
     * Render template
     *
     * @param  string $templateName
     * @return string
     */
    protected function render($templateName)
    {
        $template = new Template($this->getModulePath() . '/Template/' . $this->getThemeName() . '/' . $templateName);
        $template->setVars($this->dataCollection->toArray());

        return $template->render();
    }

    /**
     * @param  string $key
     * @param  mixed $value
     * @return self
     */
    protected function addData($key, $value)
    {
        $this->dataCollection->add($key, $value);

        return $this;
    }

    /**
     * Get data collection.
     *
     * @return DataCollection
     */
    protected function getData()
    {
        return $this->dataCollection;
    }

    /**
     * Get module path.
     *
     * @return string
     */
    protected function getModulePath()
    {
        return $this->modulePath;
    }

    /**
     * Set module path.
     *
     * @param  string $modulePath
     * @return $this
     */
    protected function setModulePath($modulePath)
    {
        $this->modulePath = $modulePath;

        return $this;
    }
}
