<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Service;

use Eureka\Kernel\Http\Service\Session;
use PHPUnit\Framework\TestCase;

/**
 * Class SessionTest
 *
 * @author Romain Cottard
 */
class SessionTest extends TestCase
{
    /**
     * @return void
     */
    public function testICanInitializeSessionService(): void
    {
        $session = new Session();

        $this->assertInstanceOf(Session::class, $session);
    }

    /**
     * @return void
     */
    public function testICanSetAndRetrieveValueInSession(): void
    {
        $session = new Session();

        $this->assertFalse($session->has('foo'));

        $session->set('foo', 'bar');
        $this->assertTrue($session->has('foo'));
        $this->assertEquals('bar', $session->get('foo'));
        $this->assertEquals('baz', $session->get('foz', 'baz'));

        $session->remove('foo');
        $this->assertFalse($session->has('foo'));
    }

    /**
     * @return void
     */
    public function testICanSetAndRetrieveEphemeralValueInSession(): void
    {
        $session = new Session();

        $this->assertFalse($session->hasEphemeral('foo'));

        $session->setEphemeral('foo', 'bar');
        $this->assertTrue($session->hasEphemeral('foo'));
        $this->assertEquals('bar', $session->getEphemeral('foo'));
        $this->assertEquals('baz', $session->getEphemeral('foz', 'baz'));

        $session->clearEphemeral();
        $session->clearEphemeral();
    }

    /**
     * @return void
     */
    public function testICanCleanEphemeralValueInSession(): void
    {
        $_SESSION = [];

        $session = new Session();

        $session->setEphemeral('foo', 'bar');

        //~ First call render ephemeral "not active" (= removed after the second call)
        $session->clearEphemeral();
        $this->assertTrue($session->hasEphemeral('foo'));

        //~ First call render ephemeral "not active" (= removed after the second call)
        $session->clearEphemeral();
        $this->assertFalse($session->hasEphemeral('foo'));
    }
}
