<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Common.php');

class MultiLang extends MultiLang_Common{

	function __construct(){
		$this->Init();
	}


	static function GetObject(){
		static $object;

		if( !$object ){
			$object = new MultiLang();
		}

		return $object;
	}

	/**
	 * Determine a user's language preference and redirect them to the appropriate homepage if necessary
	 * How do we differentiate between a user requesting the home page (to get the default language content) and a request that should be redirected?
	 * 	... don't create any empty links (set $config['homepath'] to false)
	 * 	... redirect all empty paths?
	 *
	 */
	function _WhichPage($path){
		$object = self::GetObject();
		return $object->WhichPage($path);
	}

	function WhichPage($path){
		global $config;

		$home_title					= $config['homepath'];
		$config['homepath_key']		= false;
		$config['homepath']			= false;

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
	static function _Gadget(){
		$object = self::GetObject();
		$object->Gadget();
	}
	function Gadget(){
		global $page, $ml_languages;

		$this->AddResources();

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		$list = $this->GetList($page->gp_index);

		if( !$list && !common::loggedIn() ){
			return;
		}

		$current_page_lang = array_search($page->gp_index,$list);


		//show the list
		echo '<div class="multi_lang_select"><div>';
		echo '<b>Languages</b>';
		$links = array();
		foreach($ml_languages as $lang_code => $lang_label){

			if( !isset($list[$lang_code]) ){
				continue;
			}

			if( $lang_code == $current_page_lang ){
				continue;
			}

			$index		= $list[$lang_code];
			$title		= common::IndexToTitle($index);
			$links[]	= common::Link($title,$lang_label);
		}

		if( $links ){
			echo '<ul><li>';
			echo implode('</li><li>', $links);
			echo '</li></ul>';
		}

		if( common::loggedIn() ){
			echo '<p>Admin: ';
			echo common::Link('Admin_MultiLang','Add Translation','cmd=title_settings&index='.$page->gp_index,' name="gpabox"');
			echo '</p>';
		}

		echo '</div></div>';
	}


	/**
	 * Add multi language elements to the $page
	 *
	 */
	function AddResources(){
		global $page, $addonRelativeCode;
		static $added = false;

		if( $added ){
			return;
		}


		if( $page->pagetype == 'display' ){
			$page->admin_links[] = common::Link('Admin_MultiLang','Multi Language','cmd=title_settings&index='.$page->gp_index,' name="gpabox"');
		}
		$page->head_js[] = $addonRelativeCode.'/script.js'; //needed for admin pages as well
		$page->css_admin[] = $addonRelativeCode.'/admin.css';

		$added = true;
	}


	/**
	 * [GetMenuArray] hook
	 * Translate a menu array using the translation lists
	 *
	 */
	static function _GetMenuArray($menu){
		$object = self::GetObject();
		return $object->GetMenuArray($menu);
	}

	function GetMenuArray($menu){
		global $page;


		//which language is the current page
		$list = $this->GetList($page->gp_index);
		if( !$list ){
			return $menu;
		}

		$page_lang = array_search($page->gp_index,$list);

		if( !$page_lang ){
			return $menu;
		}

		//if it's the default language, we don't need to change the menu
		// ... if the menu isn't actually in the primary language, we still want to translate it
		//if( $page_lang == $this->lang ){
		//	return $menu;
		//}


		//if we can determine the language of the current page, then we can translate the menu
		$new_menu = array();
		foreach($menu as $key => $value){

			$list = $this->GetList($key);
			if( !isset($list[$page_lang]) ){
				if( !isset($new_menu[$key]) ){
					$new_menu[$key] = $value;
				}
				continue;
			}

			$new_key = $list[$page_lang];
			if( !isset($new_menu[$new_key]) ){
				$new_menu[$new_key] = $value;
			}
		}

		return $new_menu;
	}

}

//for backwards compat
global $ml_object;
$ml_object = MultiLang::GetObject();
