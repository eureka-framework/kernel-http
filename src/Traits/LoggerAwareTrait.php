<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Traits;

use Psr\Log\LoggerInterface;

/**
 * Trait LoggerAwareTrait
 *
 * @author Romain Cottard
 */
trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
