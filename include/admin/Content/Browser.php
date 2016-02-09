<?php

namespace gp\admin\Content{

	defined('is_running') or die('Not an entry point...');

	class Browser extends \gp\admin\Content\Uploaded{

		function __construct($args){

			parent::__construct($args);

			$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document
			$this->page->get_theme_css = false;

			$this->page->head .= '<style type="text/css">';
			$this->page->head .= 'html,body{padding:0;margin:0 !important;background-color:#ededed !important;background-image:none !important;border:0 none !important;}';
			$this->page->head .= '#gp_admin_html{padding:5px 0 !important;}';
			$this->page->head .= '</style>';

			$this->Finder();
		}

		function FinderPrep(){
			$this->finder_opts['url']				= \gp\tool::GetUrl('Admin_Finder');
			$this->finder_opts['getFileCallback']	= true;
			$this->finder_opts['resizable'] 		= false;
		}

	}
}

namespace{
	class admin_browser extends \gp\admin\Content\Browser{}
}
