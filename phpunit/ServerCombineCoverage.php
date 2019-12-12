<?php

require 'ServerPrepend.php';

$cov_dir	= dirname(__DIR__).'/x_coverage';
$files		= scandir($cov_dir);

foreach($files as $file){
	
	if( strpos($file,'request-') !== 0 ){
		continue;
	}

	$file	= $cov_dir.'/'.$file;
	$data	= json_decode( file_get_contents($file),true );
	$cov_obj->append($data, $file );
}


$cov_file	= $cov_dir.'/requests.xml';
$writer		= new \SebastianBergmann\CodeCoverage\Report\Clover;
$writer->process($cov_obj, $cov_file);
