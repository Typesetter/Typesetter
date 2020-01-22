<?php

/**
 * Start xdebug code coverage
 * Register autoload for merging coverage with php-code-coverage
 * https://stackoverflow.com/questions/10167775/aggregating-code-coverage-from-several-executions-of-phpunit
 *
 */

defined('gp_unit_testing') or define('gp_unit_testing',true);
defined('gpdebug') or define('gpdebug',true); // sets error_reporting(ALL)

if( function_exists('xdebug_start_code_coverage') ){
	xdebug_start_code_coverage();

	function SaveXdebugCoverage(){
		$cov_dir	= dirname(__DIR__).'/x_coverage';
		$name		= str_replace('/','-','request-'.$_SERVER['HTTP_X_REQ_ID'].'-'.$_SERVER['REQUEST_METHOD'].'-'.$_SERVER['REQUEST_URI']);
		$cov_file	= $cov_dir.'/'.$name.'.json';
		$data		= xdebug_get_code_coverage();
		file_put_contents( $cov_file, json_encode($data,JSON_PRETTY_PRINT));
	}

	register_shutdown_function('SaveXdebugCoverage');
	ob_start(function($buffer){
		SaveXdebugCoverage();
		return $buffer;
	});
}
