<?php
/**
 * This script registers Eix's autoloader.
 */

require_once __DIR__ . '/main/Nohex/Eix/Core/ClassLoader.php';

// Classes will be loaded from the main/ folder.
\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/main/');
// If available, classes will be also loaded from the lib/ folder.
\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/lib/', false);
// If available, classes will be also loaded from the test/ folder.
\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/test/', false);
