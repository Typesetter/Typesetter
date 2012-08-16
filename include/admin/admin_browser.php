<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_uploaded.php');

class admin_browser extends admin_uploaded{
	function admin_browser(){
		global $page;

		$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document
		$page->get_theme_css = false;
		$this->elFinder();
	}
	function elFinderPrep(){
		global $page, $gpAdmin, $GP_INLINE_VARS;

		$el_opts['url'] = common::GetUrl('Admin_Finder');
		$el_opts['lang'] = 'en';
		$el_opts['getFileCallback'] = true;
		$page->head_script .= "\n".'var elfinder_opts = '.json_encode($el_opts).';';


		$page->head .= '<style type="text/css">';
		$page->head .= 'html,body{padding:0;margin:0;background-color:#ededed !important;background-image:none !important;border:0 none !important;}';
		$page->head .= '#gp_admin_html{padding:5px 0 !important;}';
		$page->head .= '</style>';

	}
}
