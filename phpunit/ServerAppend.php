<?php

/**
 * Save coverage from the current request
 *
 */

$cov_dir	= dirname(__DIR__).'/x_coverage';
$name		= str_replace('/','-','request-'.$_SERVER['HTTP_X_REQ_ID'].'-'.$_SERVER['REQUEST_METHOD'].'-'.$_SERVER['REQUEST_URI']);
$cov_file	= $cov_dir.'/'.$name.'.json';
$data		= xdebug_get_code_coverage();
file_put_contents( $cov_file, json_encode($data,JSON_PRETTY_PRINT));
