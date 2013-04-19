<?php
/**
 * This is Eix's standard bootstrap, which adds the main/, lib/ and test/
 * folders of the current library/phar/application into the include path, and
 * makes sure the class loader is initialised.
 */

// Get the class loader.
$classLoader = null;
if (class_exists('\\Nohex\\Eix\\Core\\ClassLoader')) {
	$classLoader = \Nohex\Eix\Core\ClassLoader::getInstance();
} else {
	$classLoaderLocation = __DIR__ . '/main/Nohex/Eix/Core/ClassLoader.php';
	if (is_readable($classLoaderLocation)) {
		// This is the Eix library, so a direct class require is tried. Any
		// other library should be using Eix's phar, so a require of that phar
		// should be used here.
		$classLoader = require_once __DIR__ . '/main/Nohex/Eix/Core/ClassLoader.php';
	} else {
		throw new RuntimeException('Eix is not available.');
	}
}
// Add the main source folder to the include path.
$classLoader->addPath(__DIR__ . '/main');
// Add the libraries folder to the include path.
$classLoader->addPath(__DIR__ . '/lib');
// Add the test source folder to the include path.
$classLoader->addPath(__DIR__ . '/test');
// Initialise Eix's class loader.
$classLoader->init();