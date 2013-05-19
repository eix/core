<?php

namespace Nohex\Eix\Core;

// Return an instance of the class loader when required/included.
return ClassLoader::getInstance();

/**
 * The class loader tells Eix where to look for when a class is requested.
 */
class ClassLoader
{
    private static $instance;
    private static $isInitialised = false;

    private function __construct()
    {
        // Prevent instancing of the class loader using the 'new' operator.
    }

    /**
     * Gets the class loader's only allowed instance.
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Registers Eix autoloader, which searches for the class in the include
     * path.
     *
     * This autoloader is needed because PHP's built-in autoloader is
     * broken. See:
     * * http://bugs.php.net/53065
     * * http://bugs.php.net/48129
     * * http://stackoverflow.com/questions/15027486
     *
     * When that bug is fixed, this class loader can be removed, and
     * Eix will be autoloadable just by existing in the include path.
     */
    public static function init()
    {
        if (!self::$isInitialised) {
            spl_autoload_register(function($class) {
                // Calculate the path to the class.
                $classPath = strtr($class, '_\\', '//') . '.php';
                // Check wether the class is in the include path.
                $classFile = stream_resolve_include_path($classPath);
                if (!empty($classFile)) {
                    require_once $classFile;
                }
            });

            self::$isInitialised = true;
        }
    }

    /**
     * Prepends the specified path to the include path.
     *
     * @param string $path the path to prepend to the include path.
     */
    public static function addPath($path)
    {
        set_include_path($path . PATH_SEPARATOR . get_include_path());
    }

    /**
     * Checks whether a class can be loaded.
     *
     * @param string $className the name of the class.
     */
    public static function isClassAvailable($className)
    {
        return class_exists($className);
    }

    /**
     * Checks whether a namespace exists.
     *
     * @param string $namespace the name of the class.
     */
    public static function isNamespaceAvailable($namespace)
    {
        // Convert the class name to a relative path.
        $namespace = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        // Check the include path for matches.
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            $dirName = $path . DIRECTORY_SEPARATOR . $namespace;
            if (is_dir($dirName)) {
                return true;
            }
        }

        // No luck.
        return false;
    }
}
