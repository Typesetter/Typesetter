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
			case 'title_settings_save':
				$this->TitleSettingsSave($cmd);
				$this->TitleSettings();
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

			default:
				$this->ShowStats();
			break;
		}
	}

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
		global $ml_languages, $gp_index, $config;

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

		echo '<h3>Statistics</h3>';

		//Page Statistics
		echo '<div class="ml_stats">';
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

		echo '</table></div>';


		//Language Statistics
		echo '<div class="ml_stats">';
		echo '<table class="bordered"><tr><th colspan="2">Langauge Statistics</th></tr>';

		// # of pages per language
		foreach($per_lang as $lang => $count){
			echo '<tr><td>';
			$label = 'Pages in '.$ml_languages[$lang];
			if( $lang == $this->lang ){
				$label = '<b>'.$label.'</b><i> ('.common::Link('Admin_Configuration','Primary Language','','name="gpabox"').')</i>';
			}
			echo $label;

			echo '</td><td>'.number_format($count).'</td></tr>';
		}
		echo '</table></div>';


		//Show lists
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

		$this->SmLinks();

	}



	/**
	 * Show a list of pages that don't have a translation setting
	 *
	 */
	function NotTranslated(){
		global $ml_languages, $gp_index, $config, $gp_menu;

		$menu_info['gp_menu'] = $gp_menu;
		$menu_labels['gp_menu'] = 'Main Menu';
		if( isset($config['menus']) ){
			foreach($config['menus'] as $menu => $label){
				$menu_info[$menu] = gpOutput::GetMenuArray($menu);
				$menu_labels[$menu] = $label;
			}
		}

		echo '<h2>Pages Without Translations</h2>';

		echo '<table class="bordered full_width"><tr><th>Page</th><th>Menus</th><th>&nbsp;</th></tr>';
		foreach($gp_index as $slug => $page_index){
			if( isset($this->titles[$page_index]) ){
				continue;
			}

			echo '<tr><td>';
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
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

		$this->lists = $this->config['lists'];
		$this->titles = $this->config['titles'];
		if( count($this->config['langs']) ){
			$this->langs = $this->config['langs'];
		}

		return true;
	}


	/**
	 * Display drop down menu for selecting a language
	 *
	 */
	function LanguageSelect($name,$default,$exclude= array()){
		global $ml_languages, $langmessage;

		echo '<div>';

		$data = array();
		foreach($this->langs as $code => $label){
			$data[] = array($label,$code);
		}
		echo '<span class="data" style="display:none">';
		$data = json_encode($data);
		echo htmlspecialchars($data,ENT_NOQUOTES);
		echo '</span>';

		$default_label = '';
		if( $default ){
			$default_label = $ml_languages[$default];
		}
		echo '<span class="gpinput combobox">';
		echo '<input type="text" name="'.$name.'" value="'.htmlspecialchars($default_label).'" class="combobox"/>';
		echo '</span>';

		echo '</div>';
	}

	function TitleSelect($default_index,$exclude=array()){
		global $gp_index,$gp_titles, $langmessage;

		$exclude = (array)$exclude;

		echo '<div>';

		$data = array();
		foreach($gp_index as $url => $index){
			if( in_array($index,$exclude) ){
				continue;
			}
			$label = common::GetLabelIndex($index);
			$data[] = array( common::LabelSpecialChars($label), $url );
		}

		//$data = array_slice($data,107,1);
		echo '<span class="data" style="display:none">';
		$data = json_encode($data);
		echo htmlspecialchars($data,ENT_NOQUOTES);
		echo '</span>';


		$default_url = '';
		if( $default_index ){
			$default_url = common::IndexToTitle($default_index);
		}
		echo '<span class="gpinput combobox">';
		echo '<input type="text" name="title" value="'.htmlspecialchars($default_url).'" class="combobox"/>';
		echo '</span>';

		echo '</div>';
	}


	/**
	 *
	 *
	 */
	function TitleSettingsSave($cmd){
		global $gp_titles, $ml_languages, $langmessage, $gp_index;

		$index_a = $_POST['index'];
		if( !isset($gp_titles[$index_a]) ){
			message($langmessage['OOPS'].' (Invalid Title - 1)');
			return;
		}

		$lang_a = array_search($_POST['ml_lang'],$ml_languages);
		if( !$lang_a ){
			message($langmessage['OOPS'].' (Invalid Language)');
			return;
		}

		$index_b = $this->WhichTitle($_POST['title']);
		if( !$index_b ){
			message($langmessage['OOPS'].' (Invalid Title - 2)');
			return;
		}


		//a title can't be a translation of  itself
		if( $index_b == $index_a ){
			message($langmessage['OOPS'].' (Same Title)');
			return;
		}


		//adding to a list
		$list_index = false;
		if( $cmd == 'title_settings_add' ){
			$temp = $index_a;
			$index_a = $index_b;
			$index_b = $temp;

			$list_index = $this->GetListIndex($index_b);
			if( !$list_index ){
				message($langmessage['OOPS'].' (List Not Found)');
				return;
			}

		//new list
		}else{

			if( $this->GetListIndex($index_b) ){
				message($langmessage['OOPS'].' (Already Translated)');
				return;
			}

			$lang_b = array_search($_POST['ml_lang_b'],$ml_languages);
			if( !$lang_b ){
				message($langmessage['OOPS'].' (Invalid Language)');
				return;
			}

		}

		$list_index_a = $this->GetListIndex($index_a);
		if( $list_index_a ){
			message($langmessage['OOPS'].' (Already Translated)');
			return;

		}

		//new list
		if( !$list_index ){
			$list_index = $this->NewListIndex();
			$this->config['lists'][$list_index][$lang_b] = $index_b;
			$this->config['titles'][$index_b] = $list_index;
		}

		$this->config['lists'][$list_index][$lang_a] = $index_a;
		$this->config['titles'][$index_a] = $list_index;

		//echo '<h3>New configuration</h3>';
		//echo showArray($this->config);
		//echo '<hr/>';
		//echo showArray($_POST);
		//return;

		if( $this->SaveConfig() ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		}else{
			message($langmessage['OOPS']);
		}
	}

	function WhichTitle($title){
		global $gp_index, $gp_titles;
		includeFile('tool/editing.php');
		$cleaned_title = gp_edit::CleanTitle($title);

		if( isset($gp_index[$cleaned_title]) ){
			return $gp_index[$cleaned_title];
		}
		foreach($gp_titles as $index => $info){
			if( isset($info['label']) && $info['label'] = $title ){
				return $index;
			}
		}

		return $false;
	}


	/**
	 * Set the language of the title
	 *
	 */
	function TitleSettings(){
		global $gp_titles, $langmessage;

		$index = $_REQUEST['index'];
		if( !isset($gp_titles[$index]) ){
			echo $langmessage['OOPS'].' (Invalid Title - 3)';
			return;
		}

		//make sure it's not already in a list
		$list_index = $this->GetListIndex($index);
		if( $list_index !== false ){
			$this->ListSettings($index);
			return;
		}


		echo '<div>';
		echo '<form method="post" action="'.common::GetUrl('Admin_MultiLang').'">';
		echo '<input type="hidden" name="cmd" value="title_settings_save" />';
		echo '<input type="hidden" name="index" value="'.$index.'" />';

		echo '<table cellpadding="10" border="0"><tr><td rowspan="2">';
		echo '<h3>1) Language of Current Page</h3>';
		echo 'What language is this page in?';
		echo '</td><td></td></tr>';
		echo '<tr><td style="vertical-align:bottom">';
		$this->LanguageSelect('ml_lang',$this->lang);
		echo '</td></tr>';


		echo '<tr><td rowspan="2">';
		echo '<h3>2) Corresponding Page</h3>';
		echo 'What page is this page a translation of?';
		echo '</td><td></td></tr>';
		echo '<tr><td style="vertical-align:bottom">';
		$this->TitleSelect(false);
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'What language is this page in?';
		echo '</td><td style="vertical-align:bottom">';
		$this->LanguageSelect('ml_lang_b',false);
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="submit" value="'.$langmessage['save'].'" class="gpabox gpsubmit" /> ';
		echo '<input type="button" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';

		echo '</form>';

		$this->SmLinks();

		echo '</div>';
	}

	/**
	 * Give users the option of removing the settings for a page
	 *
	 */
	function ListSettings($page_index){
		global $langmessage, $ml_languages;

		$list = $this->GetList($page_index);

		echo '<div>';
		echo '<form method="post" action="'.common::GetUrl('Admin_MultiLang').'">';
		echo '<input type="hidden" name="cmd" value="title_settings_add" />';
		echo '<input type="hidden" name="index" value="'.$page_index.'" />';

		echo '<h3>Settings</h3>';
		echo '<p>This page has been associated with the following list of pages.</p>';

		echo '<table class="bordered"><tr><th>Language</th><th>Title</th><th>Options</th></tr>';

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
		$this->LanguageSelect('ml_lang',false,$this->lang);
		echo '</td><td>';
		$this->TitleSelect(false,$list);
		echo '</td><td>';
		echo '<input type="submit" value="'.$langmessage['save'].'" class="gpabox gpbutton" /> ';
		echo '</td></tr>';


		echo '</table>';
		echo '</form>';

		$this->SmLinks();

		echo '</div>';

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


