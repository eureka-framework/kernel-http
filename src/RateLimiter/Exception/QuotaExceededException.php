<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\RateLimiter\Exception;

/**
 * Exception QuotaExceededException
 *
 * @author Romain Cottard
 */
class QuotaExceededException extends \OutOfBoundsException {}
