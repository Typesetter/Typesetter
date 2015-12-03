<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php');

class SimpleBlogCategories{

	function __construct(){
		global $addonPathData;

		SimpleBlogCommon::AddCSS();

		$gadget_file = $addonPathData.'/gadget_categories.php';
		$content = '';
		if( file_exists($gadget_file) ){
			$content = file_get_contents($gadget_file);
		}


		//fix edit links
		if( strpos($content,'simple_blog_gadget_label') ){
			new SimpleBlogCommon();
			$content = file_get_contents($gadget_file);
		}

		if( empty($content) ){
			return;
		}

		echo '<div class="simple_blog_gadget"><div>';
		echo '<span class="simple_blog_gadget_label">';
		echo gpOutput::GetAddonText('Categories');
		echo '</span>';
		echo $content;
		echo '</div></div>';
	}
}
