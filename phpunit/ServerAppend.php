<?php

/**
 * Save coverage from the current request
 *
 */
$cov_file	= dirname(__DIR__).'/x_coverage/request-'.microtime(true).'-'.rand(1,999999).'.json';

/*
if( file_exists($cov_file) ){
	$previous	= json_decode( file_get_contents($cov_file),true );
	$cov_obj->append($previous, rand(1,999999) );
}
*/

$data = $cov_obj->getData();
file_put_contents( $cov_file, json_encode($data));


//$cov_file	= dirname(__DIR__).'/coverage-requests.xml';
//$writer		= new \SebastianBergmann\CodeCoverage\Report\Clover;
//$writer->process($cov_obj, $cov_file);
