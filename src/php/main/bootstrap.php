<?php
/**
 * This script registers Eix's autoloader. Run this if you intend to use
 * Eix from its source code directly (as opposed to use its phar).
 */

require __DIR__ . '/Nohex/Eix/Core/ClassLoader.php';
\Nohex\Eix\Core\ClassLoader::init();
