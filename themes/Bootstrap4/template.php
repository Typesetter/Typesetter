<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * Master template
 * See settings.php for configuration options
 */


/**
 * Load SmartMenus
 *
 */
// $page->head_js[] = dirname(rawurldecode($page->theme_path)) . '/_assets/js/jquery.smartmenus.bs4.min.js';


/**
 * Load theme javascript, if it exists
 *
 */
$theme_js = $page->theme_dir . '/script.js';
if( file_exists($theme_js) ){
	$page->head_js[] = dirname(rawurldecode($page->theme_path)) . '/script.js';
}


/**
 * Include template.php from the current layout subdirectory
 *
 */
include($page->theme_dir . '/' . $page->theme_color . '/template.php');
