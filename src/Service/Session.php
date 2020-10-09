<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Service;

/**
 * $_SESSION data wrapper class.
 * Can handle ephemeral session variables.
 *
 * @author Romain Cottard
 */
class Session
{
    /** @var string EPHEMERAL Session index name for ephemeral var in Session. */
    private const EPHEMERAL = '_ephemeral';

    /** @var string ACTIVE Session index name for ephemeral var if active or not. */
    private const ACTIVE = 'active';

    /** @var string VARIABLE Session index name for ephemeral var content. */
    private const VARIABLE = 'var';

    /** @var array|null $session */
    protected static ?array $session = null;

    /**
     * Session constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        if (self::$session === null) {
            self::$session = []; // default

            if (isset($_SESSION)) {
                self::$session = &$_SESSION; // @codeCoverageIgnore
            }
        }

        $this->clearEphemeral();
    }

    /**
     * If session have given key.
     *
     * @param  string
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset(self::$session[$key]);
    }

    /**
     * Get session value.
     *
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return self::$session[$key];
    }

    /**
     * Set value for a given key.
     *
     * @param  string $key
     * @param  mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        self::$session[$key] = $value;

        return $this;
    }

    /**
     * Remove key from bag container.
     * If key not exists, must throw an BagKeyNotFoundException
     *
     * @param  string $key
     * @return static
     */
    public function remove(string $key): self
    {
        if ($this->has($key)) {
            unset(self::$session[$key]);
        }

        return $this;
    }

    /**
     * Get Session ephemeral variable specified.
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed  Variable value.
     */
    public function getEphemeral(string $name, $default = null)
    {
        $ephemeral = $this->get(self::EPHEMERAL);
        if (!isset($ephemeral[$name][self::VARIABLE]) && !isset($ephemeral[$name])) {
            return $default;
        }

        return $ephemeral[$name][self::VARIABLE];
    }

    /**
     * Check if have specified ephemeral var in Session.
     *
     * @param  string $name Index Session name.
     * @return bool
     */
    public function hasEphemeral(string $name): bool
    {
        $ephemeral = $this->get(self::EPHEMERAL);

        return isset($ephemeral[$name]);
    }

    /**
     * Initialize Session. Remove old ephemeral var in Session.
     *
     * @return $this
     */
    public function clearEphemeral(): self
    {
        //~ Check ephemeral vars
        if (!$this->has(self::EPHEMERAL)) {
            $this->set(self::EPHEMERAL, []);

            return $this;
        }

        $ephemeral = $this->get(self::EPHEMERAL);
        foreach ($ephemeral as $name => &$var) {
            if (true === $var[self::ACTIVE]) {
                $var[self::ACTIVE] = false;
            } else {
                unset($ephemeral[$name]);
            }
        }

        //~ Save in Session.
        $this->set(self::EPHEMERAL, $ephemeral);

        return $this;
    }

    /**
     * Set ephemeral variable in Session.
     *
     * @param  string $name
     * @param  mixed $value
     * @return $this
     */
    public function setEphemeral(string $name, $value): self
    {
        $ephemeral = $this->get(self::EPHEMERAL);

        $ephemeral[$name][self::ACTIVE]   = true;
        $ephemeral[$name][self::VARIABLE] = $value;

        $this->set(self::EPHEMERAL, $ephemeral);

        return $this;
    }
}
