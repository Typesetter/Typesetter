<?php

defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');
gpPlugin::incl('Admin/SimpleBlogPage.php','require_once');


class SimipleBlogAdmin extends AdminSimpleBlogPage{

	function __construct(){
		global $addonFolderName;

		SimpleBlogCommon::Init();

		$page->css_admin[]	= '/include/css/addons.css'; //for hmargin css pre gpEasy 3.6
		$page->head_js[]	= '/data/_addoncode/'.$addonFolderName.'/static/admin.js';


	}

	function Heading($current){
		global $langmessage;

		$options = array(
			'Admin_Blog'			=> 'Posts',
			'Admin_BlogConfig'		=> $langmessage['configuration'],
			'Admin_BlogCategories'	=> 'Categories',
			'Admin_BlogComments'	=> gpOutput::SelectText('Comments'),
			);

		$links = array();
		foreach($options as $slug => $label){
			if( $slug == $current ){
				$links[] = $label;
			}else{
				$links[] = common::Link($slug,$label);
			}
		}

		echo '<h2 class="hmargin">';
		$label = gpOutput::SelectText('Blog');
		echo common::Link('Special_Blog',$label);
		echo ' &#187; ';
		echo implode('<span>|</span>',$links);
		echo '</h2>';
	}





}