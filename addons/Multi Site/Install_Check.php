<?php
defined('is_running') or die('Not an entry point...');


/*
 * Install_Check() can be used to check the destination server for required features
 * 		This can be helpful for addons that require PEAR support or extra PHP Extensions
 * 		Install_Check() is called from step1 of the install/upgrade process
 */
function Install_Check(){
	global $config;


	$passed = true;
	if( defined('multi_site_unique') ){
		echo '<p style="color:red">Cannot install this addon. This is not the root installation of gpEasy.</p>';
		$passed = false;
	}


	if( !function_exists('symlink') ){
		echo '<p style="color:red">Cannot install this addon. Your installation of PHP has the symlink() function disabled.</p>';
		$passed = false;
	}


	if( !isset($_SERVER['SCRIPT_FILENAME']) && (GETENV('SCRIPT_FILENAME') === FALSE) ){
		echo '<p style="color:red">Cannot install this addon. $_SERVER[\'SCRIPT_FILENAME\'] and GETENV(\'SCRIPT_FILENAME\') are unavailable.</p>';
		$passed = false;
	}


	if( isset($config['useftp']) ){
		echo '<p style="color:red">Cannot install this addon. Your installation of PHP has safe_mode enabled.</p>';
		$passed = false;
	}

	return $passed;
}
