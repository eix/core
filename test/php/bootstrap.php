<?php

// Define the current environment as TEST.
$_SERVER['SERVER_ENV'] = 'TEST';

// Import Eix.
require 'src/php/main/Nohex/Eix/bootstrap.php';

// Set up an autoloader for the test classes root path.
\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__);
