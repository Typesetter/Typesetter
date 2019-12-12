<?php

/**
 * Save coverage from the current request
 *
 */
if( $cov_obj ){
	$cov_file	= dirname(__DIR__).'/x_coverage/request-'.microtime(true).'-'.rand(1,999999).'.json';
	$data = $cov_obj->getData();
	file_put_contents( $cov_file, json_encode($data));
}
