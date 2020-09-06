<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * plugin hooks used by the theme
 *
 */

defined('is_running') or die('Not an entry point...');

class Theme_Bootstrap4{

	static function AvailableClasses($classes){
		global $page, $addonFolderName;

		$theme_dir = basename(dirname(__FILE__));
		if( $theme_dir != $addonFolderName ){
			return $classes;
		}

		$layout_config = \gp\tool\Output::GetLayoutConfig();
		if( empty($layout_config['use_avail_classes']['value']) ){
			return $classes;
		}

		$classes_file = $page->theme_dir . '/' . $page->theme_color . '/classes.php';

		if( file_exists($classes_file) ){
			include $classes_file; // will overwrite $classes
		}

		return $classes;
	}


}
