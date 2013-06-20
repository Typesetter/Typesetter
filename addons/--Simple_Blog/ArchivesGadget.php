<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class SimpleBlogArchives{

	function SimpleBlogArchives(){
		global $addonPathData;

		SimpleBlogCommon::AddCSS();

		$gadget_file = $addonPathData.'/gadget_archive.php';
		if( file_exists($gadget_file) ){
			echo file_get_contents($gadget_file);
		}
	}

}
