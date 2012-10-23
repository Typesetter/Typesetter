<?php

defined('is_running') or die('Not an entry point...');
global $dataDir, $post_quarantine;

if( count($post_quarantine) ){
	$_POST = $post_quarantine;
}
includeFile('admin/admin_uploaded.php');


//include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
//include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
//include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
//include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';
includeFile('thirdparty/elfinder/php/elFinderConnector.class.php');
includeFile('thirdparty/elfinder/php/elFinder.class.php');
includeFile('thirdparty/elfinder/php/elFinderVolumeDriver.class.php');
includeFile('thirdparty/elfinder/php/elFinderVolumeLocalFileSystem.class.php');


/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	//gpEasy thumbnails
	$path = str_replace('\\','/',$path);
	if( $attr == 'write' && strpos($path,'/image/thumbnails') != false ){
		return false;
	}
	return null;
}

$opts = array(
	'debug' => gpdebugjs,
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => $dataDir.'/data/_uploaded/',
			'URL'           => common::GetDir('data/_uploaded'),
			'accessControl' => 'access',             // disable and hide dot starting files (OPTIONAL)
			//'uploadMaxSize' => '1G',
			'tmbPath'		=> $dataDir.'/data/_elthumbs',
			'tmbURL'		=> common::GetDir('data/_elthumbs'),
			'separator'		=> '/',
			'tmbBgColor'	=> 'transparent',
			'copyOverwrite'	=> false,
			'uploadOverwrite'=> false,
		),
	),
	'bind' => array(
		'duplicate upload rename rm paste resize' => array('admin_uploaded','FinderChange'),//drag+drop = cut+paste
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
