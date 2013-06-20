<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class SimpleBlogCategories{

	function SimpleBlogCategories(){
		global $addonPathData;

		SimpleBlogCommon::AddCSS();

		$gadget_file = $addonPathData.'/gadget_categories.php';
		if( file_exists($gadget_file) ){
			echo file_get_contents($gadget_file);
		}
	}
}
