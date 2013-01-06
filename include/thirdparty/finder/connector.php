<?php

defined('is_running') or die('Not an entry point...');
global $dataDir;

includeFile('admin/admin_uploaded.php');
includeFile('thirdparty/finder/php/Finder.class.php');


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
	if( strpos($path,'/image/thumbnails/') === false ){
		return null;
	}
	switch($attr){
		case 'write':
		case 'locked':
		return false;
	}

	return null;
}


/**
 * Check files by extension if gp_restrict_uploads
 *
 */
function upload_check( $event, $args, $finder ){

	if( !gp_restrict_uploads ){
		return $args;
	}

	$files =& $args['FILES']['upload'];
	if( !is_array($files) ){
		return $args;
	}

	foreach( $files['name'] as $i => $name ){
		if( !admin_uploaded::AllowedExtension($name) ){
			return false;
		}
	}

	return $args;
}


function SaveFinderData($data){
	global $config;
	$config['finder_data'] = $data;
	admin_tools::SaveConfig();
}

function ReturnFinderData(){
	global $config;
	if( isset($config['finder_data']) ){
		return $config['finder_data'];
	}
	return false;
}


$opts = array(
	'debug' => gpdebug,
	'saveData' => 'SaveFinderData',
	'returnData' => 'ReturnFinderData',
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => $dataDir.'/data/_uploaded/',
			'URL'           => common::GetDir('data/_uploaded'),
			'accessControl' => 'access',
			//'uploadMaxSize' => '1G',
			'tmbPath'		=> $dataDir.'/data/_elthumbs',
			'tmbURL'		=> common::GetDir('data/_elthumbs'),
			'separator'		=> '/',
			'tmbBgColor'	=> 'transparent',
			'copyOverwrite'	=> false,
			'uploadOverwrite'=> false,
			'tmbPathMode'	=> gp_chmod_dir,
			'dirMode'		=> gp_chmod_dir,
			'fileMode'		=> gp_chmod_file
		),
	),
	'bind' => array(
		'duplicate upload rename rm paste resize' => array('admin_uploaded','FinderChange'),//drag+drop = cut+paste
		'upload-before' => 'upload_check',
	)
);



// run Finder
$connector = new Finder($opts);
$connector->run();