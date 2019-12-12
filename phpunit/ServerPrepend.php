<?php

/**
 * Start xdebug code coverage
 * Register autoload for merging coverage with php-code-coverage
 * https://stackoverflow.com/questions/10167775/aggregating-code-coverage-from-several-executions-of-phpunit
 *
 */
require dirname(__DIR__) . '/vendor/autoload.php';

$cov_dir	= dirname(__DIR__).'/include';
$cov_obj	= new \SebastianBergmann\CodeCoverage\CodeCoverage();
$cov_obj->filter()->addDirectoryToWhitelist($cov_dir);
$cov_obj->filter()->removeDirectoryFromWhitelist($cov_dir.'/thirdparty');
