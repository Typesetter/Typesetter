<?php

/**
 * @param string $classname The name of the class to be loaded
 */
function JShrink_autoload($classname) {
	$filename = __DIR__ . DIRECTORY_SEPARATOR . $classname . '.php';
	if (is_readable($filename)) {
		require $filename;
	}
}

spl_autoload_register('JShrink_autoload', true, true);
