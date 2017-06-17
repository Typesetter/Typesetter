<?php

/**
 * @param string $classname The name of the class to be loaded
 */
function jpeg_icc_autoload($classname) {
	$filename = __DIR__ . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php';
	if (is_readable($filename)) {
		require $filename;
	}
}

spl_autoload_register('jpeg_icc_autoload', true, true);
