<?php

namespace gp\admin\Tools;

defined('is_running') or die('Not an entry point...');

class Iframe{


	/**
	 * Output iframe interface
	 * 
	 * @param /gp/Page $page
	 * @param string $iframe_src
	 * @param string $toolbar
	 * @param string $content
	 *
	 */
	public static function Output( $page, $iframe_src, $toolbar, $content){

		$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document

		\gp\tool::LoadComponents('resizable,gp-admin-css');

		\gp\admin\Tools::$show_toolbar	= false;
		$page->get_theme_css			= false;
		$page->head_js[]				= '/include/js/theme_content_outer.js';
		$page->css_admin[]				= '/include/css/theme_content_outer.scss';


		//show site in iframe
		echo '<div id="gp_iframe_wrap">';
		echo '<iframe src="'.$iframe_src.'" id="gp_layout_iframe" name="gp_layout_iframe"></iframe>';
		echo '</div>';

		ob_start();

		//new
		echo '<div id="theme_editor">';
		echo '<div class="gp_scroll_area">';


		echo '<div>';
		echo $toolbar;
		echo '</div>';


		echo '<div class="separator"></div>';
		echo '<div id="available_wrap"><div>';

		echo $content;

		echo '</div></div>';
		echo '</div>';

		echo '</div>';
		$page->admin_html = ob_get_clean();
	}


}
