<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Utils;

/**
 * Class Helper
 *
 * @author Romain Cottard
 */
class Helper
{
    /**
     * Display with basic html formating.
     *
     * @param  mixed $var
     * @param  string $title
     * @return void
     */
    public static function debug($var, $title = '$var')
    {
        echo '<pre>' . $title . ' = ' . var_export($var, true) . '</pre>';
    }

    /**
     * Check if var is set or get default value.
     * @param  mixed $var
     * @param  string $default
     * @return string
     */
    public static function issetget(&$var, $default = '')
    {
        if ($var !== null) {
            return $var;
        }

        return $default;
    }
}
