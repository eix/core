<?php

namespace Eix\Services\Data;

/**
 * Provides a factory for validators.
 */
class Validators
{
    private static $instances = array();

    public static function get($type)
    {
        if (empty(self::$instances[$type])) {
            $className = __NAMESPACE__ . '\\Validators\\' . $type . 'Validator';
            self::$instances[$type] = new $className;
        }

        return self::$instances[$type];
    }
}
