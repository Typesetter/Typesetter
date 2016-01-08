<?php
namespace gp\tool\Page;

defined('is_running') or die('Not an entry point...');


class Visibility{

	/**
	 * Toggle the visibility of a page given by $index
	 *
	 */
	static function Toggle( $index, $visibility = '' ){
		global $gp_titles, $langmessage;

		if( !isset($gp_titles[$index]) ){
			msg($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}

		if( $visibility == 'private' ){
			$gp_titles[$index]['vis'] = 'private';
		}else{
			unset($gp_titles[$index]['vis']);
		}

		if( !\admin_tools::SavePagesPHP() ){
			msg($langmessage['OOPS'].' (VT1)');
			return false;
		}

		return true;
	}

	/**
	 * Toggle the visibility of a page given by the $page object
	 *
	 */
	static function TogglePage( $page, $visibility ){
		global $gp_titles;


		self::Toggle($page->gp_index, $visibility);

		$page->visibility = null;
		if( isset($gp_titles[$page->gp_index]['vis']) ){
			$page->visibility = $gp_titles[$page->gp_index]['vis'];
		}
	}



}