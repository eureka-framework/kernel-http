<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Traits;

use Eureka\Kernel\Http\Service\Session;

/**
 * Trait SessionAwareTrait
 *
 * @author Romain Cottard
 */
trait SessionAwareTrait
{
    /** @var Session session */
    protected Session $session;

    /**
     * @param Session $session
     * @return void
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * @return Session
     */
    protected function getSession(): Session
    {
        return $this->session;
    }
}
