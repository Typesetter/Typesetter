<?php
namespace gp\tool;

defined('is_running') or die('Not an entry point...');


class Visibility{

	/**
	 * Toggle the visibility of a page
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



}