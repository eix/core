<?php
/**
 * This class deals with operations that help Eix execute in the current PHP
 * environment.
 */

namespace Nohex\Eix\Core;

class ClassLoader
{
    /**
     * Set up some autoloaders that can deal with Eix classes.
     */
    public static function init()
    {
        // Add an autoloader for Eix classes.
        // Assuming the ClassLoader is in the Nohex\Eix\Core namespace, the root is
        // exactly three folders up.
        self::addClassPath(__DIR__ . '/../../../');
    }

    /**
     * Set up an autoloader for classes under the specified path.
     * @param string $classPath the path that holds the classes.
     */
    public static function addClassPath($classPath)
    {
        if (is_readable($classPath)) {
            /**
             * This autoloader is needed because PHP's built-in autoloader is
             * broken. See https://bugs.php.net/bug.php?id=53065
             *
             * When that bug is fixed, this class loader can be removed, and
             * Eix will be autoloadable just by existing in the include path.
             */
            spl_autoload_register(function($class) use ($classPath) {
                $classFile = $classPath . '/' . strtr($class, '_\\', '//') . '.php';
                if (file_exists($classFile)) require $classFile;
            });
        } else {
            throw new \Exception(sprintf(
                'Class path %s is not accessible.',
                $classPath
            ));
        }
    }

    /**
     * Checks whether a class can be loaded.
     *
     * @param string $className the name of the class.
     */
    // TODO: Move to ClassLoader?
    public static function isClassAvailable($className)
    {
        return class_exists($className);
    }

    /**
     * Checks whether a namespace exists.
     *
     * @param string $namespace the name of the class.
     */
    // TODO: Move to ClassLoader?
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
