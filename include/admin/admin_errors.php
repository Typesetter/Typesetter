<?php
defined('is_running') or die('Not an entry point...');

class admin_errors{

	function __construct(){

		$error_log = ini_get('error_log');

		echo '<h2>Error Log</h2>';

		if( !file_exists($error_log) ){
			if( !empty($error_log) ){
				echo '<p>Error log at "'.$error_log.'" not found</p>';
			}else{
				echo '<p>Error log not found</p>';
			}
			return;
		}

		if( !is_readable($error_log) ){
			echo '<p>Error log not readable</p>';
			return;
		}

		echo '<pre>';
		echo file_get_contents($error_log);
		echo '</pre>';

	}

}

