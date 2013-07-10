<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class SimpleBlogArchives{

	function SimpleBlogArchives(){
		global $addonPathData;

		SimpleBlogCommon::AddCSS();

		$gadget_file = $addonPathData.'/gadget_archive.php';
		if( file_exists($gadget_file) ){
			$contents = file_get_contents($gadget_file);
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
		echo gpOutput::GetAddonText('Archives');
		echo '</span>';
		echo $content;
		echo '</div></div>';

	}

}
