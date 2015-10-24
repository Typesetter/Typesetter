<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Common.php');

class MultiLang_Admin extends MultiLang_Common{

	function MultiLang_Admin(){
		global $page;

		$this->Init();


		$cmd = common::GetCommand();
		switch($cmd){
			case 'title_settings_add':
				$this->TitleSettingsSave($cmd);
			return;
			case 'title_settings':
				$this->TitleSettings();
			return;
			case 'rmtitle':
				$this->RemoveTitle();
				$this->TitleSettings();
			return;

			case 'not_translated':
				$this->NotTranslated();
			break;

			case 'save_languages':
				$this->SaveLanguages();
			case 'languages':
				$this->SelectLanguages();
			break;

			case 'PrimaryLanguage':
				$this->PrimaryLanguage();
			break;
			case 'PrimaryLanguageSave':
				$this->PrimaryLanguageSave();
				$this->DefaultDisplay();
			break;

			default:
				$this->DefaultDisplay();
			break;
		}
	}

	function DefaultDisplay(){
		$this->ShowStats();
		$this->AllMenus();
		$this->SmLinks();
	}

	/**
	 * Display for for selecting the primary language
	 *
	 */
	function PrimaryLanguage(){
		global $ml_languages, $langmessage;

		echo '<div>';
		echo '<form method="post" action="'.common::GetUrl('Admin_MultiLang').'">';
		echo '<input type="hidden" name="cmd" value="PrimaryLanguageSave" />';
		echo '<h3>Select Primary Language</h3>';

		echo '<select class="gpselect" name="primary">';
		foreach($ml_languages as $lang => $language){
			if( $lang == $this->lang ){
				echo '<option value="'.$lang.'" selected>'.htmlspecialchars($language).'</option>';
			}else{
				echo '<option value="'.$lang.'">'.htmlspecialchars($language).'</option>';
			}
		}
		echo '</select>';

		echo '<hr/>';

		echo '<input type="submit" class="gpsubmit" value="'.$langmessage['save'].'"  />';
		echo '<input type="button" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';

		echo '</form>';
		echo '</div>';
	}

	function PrimaryLanguageSave(){
		global $ml_languages, $langmessage;

		$primary = $_REQUEST['primary'];

		if( !isset($ml_languages[$primary]) ){
			message($langmessage['OOPS'].' (Invalid Language)');
			return;
		}


		$this->config['primary']	= $primary;

		if( $this->SaveConfig() ){
			message($langmessage['SAVED']);
			$this->lang				= $primary;
			$this->language			= $ml_languages[$this->lang];
		}else{
			message($langmessage['OOPS']);
		}
	}

	/**
	 * Save the list of languages to be used
	 *
	 */
	function SaveLanguages(){
		global $ml_languages, $langmessage;

		$langs = array();
		foreach($_POST['langs'] as $code => $on){
			if( !isset($ml_languages[$code]) ){
				message($langmessage['OOPS'].' (Invalid Language)');
				return false;
			}
			$langs[$code] = $ml_languages[$code];
		}

		if( !count($langs) ){
			message($langmessage['OOPS'].' (Can not be empty)');
			return false;
		}

		$this->config['langs'] = $langs;

		if( $this->SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS']);
		}
	}

	function SelectLanguages(){
		global $ml_languages, $langmessage;

		echo '<h2>Languages</h2>';

		echo '<form method="post" action="'.common::GetUrl('Admin_MultiLang').'">';
		echo '<input type="hidden" name="cmd" value="save_languages" /> ';
		echo '<table class="bordered checkbox_table">';
		echo '<tr><th>&nbsp;</th><th>Code</th><th>Language</th></tr>';
		$i = 1;
		foreach($ml_languages as $code => $label){
			$class = ($i % 2 ? '' : 'even');
			$attr = '';
			if( isset($this->config['langs'][$code]) ){ // so that if $this->langs isn't set, all of the entries won't be checked
				$class .= ' checked';
				$attr = ' checked="checked"';
			}
			echo '<tr class="'.trim($class).'">';
			echo '<td><span class="sm">'.$i.'</span> ';
			echo '<input type="checkbox" name="langs['.$code.']" '.$attr.' />';
			echo '</td><td>'.$code.'</td>';
			echo '<td>'.$label.'</td></tr>';
			$i++;
		}
		echo '</table>';


		echo '<p>';
		echo '<input type="submit" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
		echo '<input type="button" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';
		echo '</form>';


	}


	function ShowStats(){
		global $ml_languages, $gp_index;

		//get some data
		$per_lang = array();
		$list_sizes = array();
		foreach($this->lists as $list_index => $list){

			$list_sizes[$list_index] = count($list);
			foreach($list as $lang => $index){
				//per lang
				if( !isset($per_lang[$lang]) ){
					$per_lang[$lang] = 1;
				}else{
					$per_lang[$lang]++;
				}
			}
		}

		echo '<h2>Statistics</h2>';

		//Page Statistics
		echo '<div class="ml_stats"><div>';
		echo '<table class="bordered"><tr><th colspan="2">Page Statistics</th></tr>';

		//count titles with translations
		echo '<tr><td>Pages with Translations</td><td>'.number_format(count($this->titles)).'</td></tr>';

		//count titles with translations
		echo '<tr><td>'.common::Link('Admin_MultiLang','Pages without Translations','cmd=not_translated').'</td><td>'.number_format(count($gp_index) - count($this->titles)).'</td></tr>';



		// % of titles with translations
		$percentage = count($this->titles)/count($gp_index) * 100;
		echo '<tr><td>Percentage of Pages with Translations</td><td>'.number_format($percentage,1).'%</td></tr>';

		// # of lists
		echo '<tr><td>Number of Page Lists</td><td>'.number_format(count($this->lists)).'</td></tr>';

		// Average pages per list
		$average = 0;
		if( count($this->lists) > 0 ){
			$average = count($this->titles)/count($this->lists);
		}
		echo '<tr><td>Average Pages Per List</td><td>'.number_format($average,1).'</td></tr>';

		echo '</table></div></div>';


		//Language Statistics
		echo '<div class="ml_stats"><div>';
		echo '<table class="bordered"><tr><th>Langauge</th><th>Page Count</th></tr>';

		// # of pages per language
		foreach($per_lang as $lang => $count){
			echo '<tr><td>';

			if( $lang == $this->lang ){
				echo '<b>'.$ml_languages[$lang].'</b><br/>'.common::Link('Admin_MultiLang','Primary Language','cmd=PrimaryLanguage','name="gpabox"').'</i>';
			}else{
				echo ''.$ml_languages[$lang];
			}

			echo '</td><td>'.number_format($count).'</td></tr>';
		}
		echo '</table></div></div>';


		//Show lists
		//$this->PageLists($list_sizes);
	}

	/*
	function PageLists($list_sizes){
		global $ml_languages;

		echo '<h3>Page Lists</h3>';
		$lang_label = $ml_languages[$this->lang];
		asort($list_sizes);
		echo '<table class="bordered full_width"><tr><th>Page in Primary Language</th><th>Number of Associated Pages</th><th>&nbsp;</th></tr>';
		foreach($list_sizes as $list_index => $size){
			$list = $this->lists[$list_index];
			echo '<tr><td>';
			if( isset($list[$this->lang]) ){
				$page_index = $list[$this->lang];
			}else{
				$page_index = current($list);
				$page_lang = key($list);
				echo '('.$ml_languages[$page_lang].') ';
			}
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
			echo '</td><td>';
			echo $size;
			echo '</td><td>';
			echo common::Link('Admin_MultiLang','Options','cmd=title_settings&index='.$page_index,' name="gpabox"');
			echo '</td></tr>';
		}
		echo '</table>';
	}
	*/


	/**
	 * Display all pages and their associated translations
	 *
	 */
	function AllMenus(){
		global $gp_menu, $config;

		//show main menu
		$this->ShowMenu($gp_menu,'','Main Menu');


		//all other menus
		foreach($config['menus'] as $id => $menu_label){

			$array = gpOutput::GetMenuArray($id);
			$this->ShowMenu($array, $id, $menu_label);
		}
	}


	/**
	 * Display a menu and it's translated pages
	 *
	 */
	function ShowMenu($menu, $id, $menu_label){
		global $ml_languages;


		echo '<h3>';
		echo common::Link('Admin_Menu',$menu_label,'menu='.$id,array('data-arg'=>'cnreq'));
		echo '</h3>';


		$langs = $this->WhichLanguages();
		unset($langs[$this->lang]);

		echo '<table class="bordered full_width">';

		echo '<tr><th width="1">'.$this->language.' (Primary Language) </th>';
		foreach($langs as $lang){
			echo '<th width="1">'.$ml_languages[$lang].'</th>';
		}
		echo '<th width="1">&nbsp;</th></tr>';


		$i = 0;
		foreach($menu as $page_index => $title_info){

			$page_list = $this->GetList($page_index);

			//primary language
			echo '<tr class="'.($i % 2 ? 'even' : '').'"><td>';
			if( isset($page_list[$this->lang]) ){
				$title = common::IndexToTitle($page_list[$this->lang]);
				echo common::Link_Page($title);
			}else{
				$title = common::IndexToTitle($page_index);
				echo common::Link_Page($title);
			}
			echo '</td>';


			foreach($langs as $lang){

				echo '<td>';
				if( isset($page_list[$lang]) ){
					$title = common::IndexToTitle($page_list[$lang]);
					echo common::Link_Page($title);
				}
				echo '</td>';
			}


			echo '<td>';
			echo common::Link('Admin_MultiLang','Options','cmd=title_settings&index='.$page_index,' name="gpabox"');
			echo '</td></tr>';
			$i++;
		}

		echo '</table>';
	}


	/**
	 * Which languages
	 * Return a list of languages being used
	 *
	 */
	function WhichLanguages(){

		$langs = array();

		foreach($this->lists as $list_index => $list){
			foreach($list as $lang => $index){
				if( !isset($per_lang[$lang]) ){
					$langs[$lang] = $lang;
				}
			}
		}

		return $langs;
	}


	/**
	 * Show a list of pages that don't have a translation setting
	 *
	 */
	function NotTranslated(){
		global $gp_index, $config, $gp_menu, $page;

		$page->head_js[] = '/include/thirdparty/tablesorter/tablesorter.js';
		$page->jQueryCode .= '$("table.tablesorter").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"});';


		$menu_info['gp_menu'] = $gp_menu;
		$menu_labels['gp_menu'] = 'Main Menu';
		if( isset($config['menus']) ){
			foreach($config['menus'] as $menu => $label){
				$menu_info[$menu] = gpOutput::GetMenuArray($menu);
				$menu_labels[$menu] = $label;
			}
		}

		echo '<h2>Pages Without Translations</h2>';

		echo '<table class="bordered full_width tablesorter">';
		echo '<thead><tr><th>Page</th><th>Slug</th><th>Menus</th><th>&nbsp;</th></tr></thead>';
		echo '<tbody>';
		foreach($gp_index as $slug => $page_index){
			if( isset($this->titles[$page_index]) ){
				continue;
			}

			echo '<tr><td>';
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
			echo '</td><td>';
			echo $title;
			echo '</td><td>';
			$which_menus = array();
			foreach($menu_info as $menu => $info){
				if( isset($menu[$page_index]) ){
					$which_menus[] = common::Link('Admin_Menu',$menu_labels[$menu],'menu='.$menu, 'name="cnreq"');
				}
			}
			echo implode(', ',$which_menus);

			echo '</td><td>';
			echo common::Link('Admin_MultiLang','Options','cmd=title_settings&index='.$page_index,' name="gpabox"');
			echo '</td></tr>';
		}
		echo '</tbody>';
		echo '</table>';

	}


	/**
	 * Save the current configuration
	 * If successful, reset the lists and titles variables
	 */
	function SaveConfig(){

		if( !gpFiles::SaveArray($this->config_file,'config',$this->config) ){
			return false;
		}

		$this->lists	= $this->config['lists'];
		$this->titles	= $this->config['titles'];
		if( count($this->config['langs']) ){
			$this->langs = $this->config['langs'];
		}

		return true;
	}


	/**
	 * Display drop down menu for selecting a language
	 *
	 */
	function LanguageSelect($name, $default = '', $exclude= array() ){

		echo '<div>';
		echo '<span class="gpinput combobox" data-source="#lang_data">';
		echo '<input type="text" name="'.$name.'" value="'.htmlspecialchars($default).'" class="combobox"/>';
		echo '</span>';
		echo '</div>';
	}

	/**
	 * Title selection input
	 *
	 */
	function TitleSelect($default,$exclude=array()){
		global $gp_index,$gp_titles, $langmessage;

		$exclude = (array)$exclude;

		echo '<div>';
		echo '<span class="gpinput combobox" data-source="#lang_titles">';
		echo '<input type="text" name="to_slug" value="'.htmlspecialchars($default).'" class="combobox" />';
		echo '</span>';

		echo '</div>';
	}


	/**
	 * Save new translations
	 *
	 */
	function TitleSettingsSave($cmd){

		$saved = $this->_TitleSettingsSave($cmd);

		if( $saved ){
			$this->TitleSettings();
		}else{
			$this->TitleSettings($_POST);
		}
	}

	function _TitleSettingsSave($cmd){
		global $gp_titles, $langmessage, $gp_index;


		//check from index
		$from_index = $_POST['index'];
		if( !isset($gp_titles[$from_index]) ){
			message($langmessage['OOPS'].' (Invalid Title - 1)');
			return false;
		}


		//from language?
		$from_lang = $this->lang;
		if( isset($_POST['from_lang']) ){
			$from_lang	= $this->PostedLanguage($_POST['from_lang']);
			if( !$from_lang ){
				message($langmessage['OOPS'].'. (Language not found)');
				return false;
			}
		}


		//check to language
		$to_lang	= $this->PostedLanguage($_POST['to_lang']);
		if( !$to_lang ){
			message($langmessage['OOPS'].'. (Language not found)');
			return false;
		}


		//check to index
		$to_index	= $this->PostedTitle($_POST['to_slug']);
		if( !$to_index ){
			message($langmessage['OOPS'].'. (Title not found)');
			return false;
		}


		// a title can't be a translation of itself
		if( $from_index == $to_index ){
			message($langmessage['OOPS'].' (Same Title)');
			return false;
		}


		// already a part of a list?
		$change_list = $this->GetListIndex($to_index);
		if( $change_list ){

			// don't stop if there's only one title in the list
			$list		= $this->GetList($to_index);
			if( count($list) > 1 ){
				$label = common::GetLabelIndex($to_index);
				$link = common::Link('Admin_MultiLang',$label,'cmd=title_settings&index='.$to_index,' name="gpabox"');
				message('Sorry, '.$link.' is already part of a translation.');
				return false;
			}

		}


		//new or existing list
		$list_index = $this->GetListIndex($from_index);
		if( !$list_index ){
			$list_index = $this->NewListIndex();
		}


		// delete abandoned list
		if( $change_list ){
			unset($this->config['lists'][$change_list]);
		}



		//save data
		$this->config['lists'][$list_index][$from_lang]		= $from_index;
		$this->config['titles'][$from_index]				= $list_index;

		$this->config['lists'][$list_index][$to_lang]		= $to_index;
		$this->config['titles'][$to_index]					= $list_index;


		if( $this->SaveConfig() ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
			return true;
		}

		message($langmessage['OOPS']);
		return false;
	}


	/**
	 * Get the language key from the language name
	 *
	 */
	function PostedLanguage($lang){
		global $ml_languages;

		if( in_array($lang,$ml_languages) ){
			return array_search($lang,$ml_languages);
		}
	}

	/**
	 * Get the title index from the posted title
	 *
	 */
	function PostedTitle($posted_slug){
		global $gp_index;

		foreach($gp_index as $slug => $index){

			if( $slug === $posted_slug ){
				return $index;
			}
		}

	}


	/**
	 * Language selection popup
	 *
	 */
	function TitleSettings( $args = array() ){
		global $gp_titles, $langmessage, $langmessage, $ml_languages, $gp_index;

		$args += array('to_lang'=>'','to_slug'=>'');

		$page_index = $_REQUEST['index'];
		if( !isset($gp_titles[$page_index]) ){
			echo $langmessage['OOPS'].' (Invalid Title - 3)';
			return;
		}

		$list		= $this->GetList($page_index);

		echo '<div>';
		echo '<form method="post" action="'.common::GetUrl('Admin_MultiLang').'">';
		echo '<input type="hidden" name="cmd" value="title_settings_add" />';
		echo '<input type="hidden" name="index" value="'.$page_index.'" />';




		echo '<h3>Page Settings</h3>';
		echo '<table class="bordered"><tr><th>Language</th><th>Title</th><th>Options</th></tr>';


		//not set yet
		if( !$list ){
			$in_menu	= $this->InMenu($page_index);
			echo '<tr><td>';
			if( $in_menu ){
				echo $this->language;
			}else{
				$this->LanguageSelect('from_lang');
			}

			echo '</td><td>';
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
			echo '</td><td>';
			echo '</td></tr>';
		}


		//current settings
		foreach($ml_languages as $lang => $language){
			if( !isset($list[$lang]) ){
				continue;
			}

			$index = $list[$lang];

			echo '<tr><td>';
			echo $language .' ('.$lang.')';
			echo '</td><td>';

			$title = common::IndexToTitle($index);
			echo common::Link_Page($title);
			echo '</td><td>';
			echo common::Link('Admin_MultiLang','Remove','cmd=rmtitle&index='.$page_index.'&rmindex='.$index,'name="gpabox" class="gpconfirm" title="Remove this entry?"');
			echo '</td></tr>';

		}


		//option to add another title
		echo '<tr><td>';
		$this->LanguageSelect('to_lang',$args['to_lang'], $this->lang);
		echo '</td><td>';
		$this->TitleSelect($args['to_slug'],$list);
		echo '</td><td>';
		echo '<input type="submit" value="'.$langmessage['save'].'" class="gpabox gpbutton" /> ';
		echo '</td></tr>';


		echo '</table>';
		echo '</form>';

		$this->SmLinks();


		//add languages as json
		$data = array();
		foreach($this->langs as $code => $label){
			$data[] = array($label,$code);
		}
		echo "\n";
		echo '<span id="lang_data" data-json=\''.htmlspecialchars(json_encode($data),ENT_QUOTES & ~ENT_COMPAT).'\'></span>';


		//add titles as json
		$data = array();
		foreach($gp_index as $slug => $index){
			$label = common::GetLabelIndex($index);
			$data[] = array( $slug, common::LabelSpecialChars($label) );
		}
		echo "\n";
		echo '<span id="lang_titles" data-json=\''.htmlspecialchars(json_encode($data),ENT_QUOTES & ~ENT_COMPAT,'UTF-8',false).'\'></span>';


		echo '</div>';
	}


	/**
	 * Determine if the page is in a menu
	 *
	 */
	function InMenu($page_index){
		global $gp_menu, $config;


		//show main menu
		if( isset($gp_menu[$page_index]) ){
			return true;
		}


		foreach($config['menus'] as $id => $menu_label){
			$array = gpOutput::GetMenuArray($id);
			if( isset($array[$page_index]) ){
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove a title from a translation list
	 *
	 */
	function RemoveTitle(){
		global $gp_titles, $langmessage;

		$page_index = $_REQUEST['rmindex'];
		if( !isset($gp_titles[$page_index]) ){
			echo $langmessage['OOPS'].' (Invalid Title - 4)';
			return;
		}

		//get it's list
		$list_index = $this->GetListIndex($page_index);
		if( $list_index === false ){
			return;
		}

		$page_lang = array_search($page_index,$this->config['lists'][$list_index]);
		if( !$page_lang ){
			return;
		}

		unset($this->config['titles'][$page_index]);
		unset($this->config['lists'][$list_index][$page_lang]);

		/*delete list if there's only one title
		if( count($this->config['lists'][$list_index]) < 2 ){

			$keys = array_keys($this->config['titles'],$list_index);
			foreach($keys as $key){
				unset($this->config['titles'][$key]);
			}
		}
		*/

		if( count($this->config['lists'][$list_index]) < 1 ){
			unset($this->config['lists'][$list_index]);
		}

		if( $this->SaveConfig() ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		}else{
			message($langmessage['OOPS']);
		}
	}

	function SmLinks(){
		echo '<p class="sm">';
		echo common::Link('Admin_MultiLang','Administration');
		echo ' - ';
		echo common::Link('Admin_MultiLang','Languages','cmd=languages');
		echo '</p>';

	}

}


