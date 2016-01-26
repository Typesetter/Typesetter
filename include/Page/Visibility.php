<?php
namespace gp\Page;

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

		return \gp\admin\Tools::SavePagesPHP(true);
	}

	/**
	 * Toggle the visibility of a page given by the $page object
	 *
	 */
	static function TogglePage( $page ){
		global $gp_titles;

		$_REQUEST += array('visibility'=>'');

		self::Toggle($page->gp_index, $_REQUEST['visibility']);

		$page->visibility = null;
		if( isset($gp_titles[$page->gp_index]['vis']) ){
			$page->visibility = $gp_titles[$page->gp_index]['vis'];
		}
	}



}