<?php

require 'ServerPrepend.php';

$cov_dir		= dirname(__DIR__).'/x_coverage';
$files			= scandir($cov_dir);
$file_count		= 0;

foreach($files as $file){

	if( $file === '.' || $file === '..' ){
		continue;
	}

	if( strpos($file,'request-') !== 0 ){
		echo "\n - invalid coverage file: ".$file;
		continue;
	}

	echo "\n - coverage file: ".$file;

	$file	= $cov_dir.'/'.$file;
	$data	= json_decode( file_get_contents($file),true );
	$cov_obj->append($data, $file );
	$file_count++;
}

echo "\n - ".$file_count.' coverage files combined in '.$cov_dir;
echo "\n";

$cov_file	= $cov_dir.'/requests.clover';
$writer		= new \SebastianBergmann\CodeCoverage\Report\Clover;
$writer->process($cov_obj, $cov_file);
