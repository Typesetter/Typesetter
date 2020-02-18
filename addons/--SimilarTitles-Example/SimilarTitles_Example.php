<?php
/**
 * Similar Titles Example plugin
 * illustrates the use of the new plugin filter hook 'SimilarTitles'
 *
 */

defined('is_running') or die('Not an entry point...');

class SimilarTitles_Example{

	static function SimilarTitles($similar_titles=[]){
		global $gp_index, $gp_titles, $gp_menu;

		// uncomment the following line for debugging
		// msg('SimilarTitles Example plugin. BEFORE: $similar_titles = ' . pre($similar_titles));

		$blacklist = [];


		// Blacklist Example 1: remove all pages not in the Main Menu
		/*/ <-- remove the * to uncomment this code block
		foreach( \gp\admin\Menu\Tools::GetAvailable() as $index => $title ){
			$blacklist[] = $index;
		}
		//*/


		// Blacklist Example 2: remove arbitrary pages by their index
		/*/ <-- remove the * to uncomment this code block
		$blacklist = ['a', 'special_site_map'];
		//*/


		// Blacklist Example 3: remove all special pages
		/*/ <-- remove the * to uncomment this code block
		foreach( $gp_index as $title => $index ){
			if( strpos($index, 'special_') === 0 ){
				$blacklist[] = $index;
			}
		}
		//*/


		// Blacklist Example 4: remove all pages using the robots metatag with 'noindex'
		/*/ <-- remove the * to uncomment this code block
		foreach( $gp_titles as $index => $data ){
			if( isset($data['rel']) && strpos($data['rel'], 'noindex') !== false ){
				$blacklist[] = $index;
			}
		}
		//*/


		// Only prevent auto-redirection for a certain page
		// This example changes the percent value of the Contact page (if included) to zero:
		/*/ <-- remove the * to uncomment this code block
		if( isset($similar_titles['special_contact']) ){
			$similar_titles['special_contact']['percent'] = 0;
		}
		//*/


		// filter(remove) blacklisted pages
		foreach( $similar_titles as $index => $similar ){
			if( in_array($index, $blacklist) ){
				unset($similar_titles[$index]);
			}
		}


		// uncomment the following line for debugging
		// msg('SimilarTitles Example plugin. AFTER: $similar_titles = ' . pre($similar_titles));

		return $similar_titles;
	}

}
