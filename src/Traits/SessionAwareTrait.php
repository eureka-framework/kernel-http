<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Kernel\Http\Traits;

use Eureka\Component\Http\Session\Session;

/**
 * Trait SessionAwareTrait
 *
 * @author Romain Cottard
 */
trait SessionAwareTrait
{
    /** @var Session session */
    protected $session;

    /**
     * @param Session $session
     * @return void
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * @return SessionBagInterface
     */
    protected function getSession(): Session
    {
        return $this->session;
    }
}
