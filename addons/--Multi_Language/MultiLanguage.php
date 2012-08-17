<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Common.php');

class MultiLang extends MultiLang_Common{

	function MultiLang(){
		$this->Init();
	}


	/**
	 * Determine a user's language preference and redirect them to the appropriate homepage if necessary
	 * How do we differentiate between a user requesting the home page (to get the default language content) and a request that should be redirected?
	 * 	... don't create any empty links (set $config['homepath'] to false)
	 * 	... redirect all empty paths?
	 *
	 */
	function WhichPage($path){
		global $config;

		$home_title = $config['homepath'];
		$config['homepath_key'] = false;
		$config['homepath'] = false;

		//only if homepage
		if( !empty($path) ){
			return $path;
		}


		//only if the homepage is translated
		$list = $this->GetList($config['homepath_key']);
		if( !$list ){
			common::Redirect(common::GetUrl($home_title));
			//dies
		}


		//only if user has language settings
		if( empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
			common::Redirect(common::GetUrl($home_title));
			//dies
		}


		//check for appropriate translation
		$langs = $this->RequestLangs();
		foreach($langs as $lang => $importance){
			if( isset($list[$lang]) ){
				$title = common::IndexToTitle($list[$lang]);
				common::Redirect(common::GetUrl($title));
				//dies
			}
		}

		common::Redirect(common::GetUrl($home_title));
	}

	function RequestLangs(){
		$langs = array();
		$temp = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$temp = 'fr;q=0.8,en-us;q=0.5,en;q=0.3';

		// break up string into pieces (languages and q factors)
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $temp, $lang_parse);

		if( count($lang_parse[1]) ){
			// create a list like "en" => 0.8
			$langs = array_combine($lang_parse[1], $lang_parse[4]);

			// set default to 1 for any without q factor
			foreach ($langs as $lang => $val) {
				if ($val === '') $langs[$lang] = 1;
			}

			// sort list based on value
			arsort($langs, SORT_NUMERIC);
		}

		return $langs;

	}



	/**
	 * Gadget Function
	 * Show related titles
	 *
	 */
	function Gadget(){
		global $page, $ml_languages, $config, $addonRelativeCode;

		if( $page->pagetype == 'display' ){
			$page->admin_links[] = common::Link('Admin_MultiLang','Multi Language','cmd=title_settings&index='.$page->gp_index,' name="gpabox"');
		}
		$page->head_js[] = $addonRelativeCode.'/script.js'; //needed for admin pages as well
		$page->css_admin[] = $addonRelativeCode.'/admin.css';


		$list = $this->GetList($page->gp_index);

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		if( !$list && !common::loggedIn() ){
			return;
		}

		//show the list
		echo '<div class="multi_lang_select"><div>';
		echo '<b>Languages</b>';
		echo '<ul>';
		foreach($ml_languages as $lang_code => $lang_label){
			if( !isset($list[$lang_code]) ){
				continue;
			}
			$index = $list[$lang_code];

			if( $index == $page->gp_index ){
				continue;
			}
			$title = common::IndexToTitle($index);

			echo '<li>';
			echo common::Link($title,$lang_label);
			echo '</li>';
		}

		if( common::loggedIn() ){
			echo '<li>';
			echo common::Link('Admin_MultiLang','Add Language','cmd=title_settings&index='.$page->gp_index,' name="gpabox"');
			echo '</li>';
		}
		echo '</ul>';

		echo '</div></div>';
	}


	/**
	 * [GetMenuArray] hook
	 * Translate a menu array using the translation lists
	 *
	 */
	function GetMenuArray($menu){
		global $page, $config;

		//which language is the current page
		$list = $this->GetList($page->gp_index);
		if( !is_array($list) ){
			return $menu;
		}
		$page_lang = array_search($page->gp_index,$list);
		if( !$page_lang ){
			return $menu;
		}

		//if it's the default language, we don't need to change the menu
		if( $page_lang == $config['language'] ){
			return $menu;
		}

		//if we can determine the language of the current page, then we can translate the menu
		$new_menu = array();
		foreach($menu as $key => $value){
			$list = $this->GetList($key);
			if( !isset($list[$page_lang]) ){
				$new_menu[$key] = $value;
				continue;
			}

			$new_menu[$list[$page_lang]] = $value;
		}

		return $new_menu;
	}

}

global $ml_object;
$ml_object = new MultiLang();

