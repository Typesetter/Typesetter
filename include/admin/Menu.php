<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');
defined('gp_max_menu_level') or define('gp_max_menu_level',6);

\gp\tool::LoadComponents('sortable');


class Menu extends \gp\special\Base{

	public $cookie_settings			= array();
	public $hidden_levels			= array();
	public $search_page				= 0;
	public $search_max_per_page		= 20;
	public $query_string;

	public $avail_menus				= array();
	public $curr_menu_id;
	public $curr_menu_array;
	protected $is_alt_menu			= false;
	public $is_main_menu			= false;
	public $max_level_index			= 3;
	protected $settings_cache		= array();
	protected $inherit_info;


	public $main_menu_count;
	public $list_displays			= array('search'=>true, 'all'=>true, 'hidden'=>true, 'nomenus'=>true );

	public $section_types;

	protected $cmd;


	public function __construct($args){
		global $langmessage, $config;

		parent::__construct($args);

		$this->section_types				= \gp\tool\Output\Sections::GetTypes();

		$this->page->ajaxReplace			= array();

		$this->page->css_admin[]			= '/include/css/admin_menu_new.css';

		$this->page->head_js[]				= '/include/thirdparty/js/nestedSortable.js';
		$this->page->head_js[]				= '/include/thirdparty/js/jquery_cookie.js';
		$this->page->head_js[]				= '/include/js/admin_menu_new.js';

		$this->max_level_index				= max(3,gp_max_menu_level-1);
		$this->page->head_script			.= 'var max_level_index = '.$this->max_level_index.';';


		$this->avail_menus['gpmenu']	= $langmessage['Main Menu'].' / '.$langmessage['site_map'];
		$this->avail_menus['all']		= $langmessage['All Pages'];
		$this->avail_menus['hidden']	= $langmessage['Not In Main Menu'];
		$this->avail_menus['nomenus']	= $langmessage['Not In Any Menus'];
		$this->avail_menus['search']	= $langmessage['search pages'];

		if( isset($config['menus']) ){
			foreach($config['menus'] as $id => $menu_label){
				$this->avail_menus[$id] = $menu_label;
			}
		}

		//read cookie settings
		if( isset($_COOKIE['gp_menu_prefs']) ){
			parse_str( $_COOKIE['gp_menu_prefs'] , $this->cookie_settings );
		}

		$this->SetMenuID();
		$this->SetMenuArray();
		$this->SetCollapseSettings();
		$this->SetQueryInfo();

		$cmd		= \gp\tool::GetCommand();
		$this->cmd	= \gp\tool\Plugins::Filter('MenuCommand',array($cmd));

	}


	public function RunScript(){ 

		if( $this->cmd === 'return' ){
			return;
		}

		switch($this->cmd){

			case 'drag':
				$this->SaveDrag();
			break;


			//layout
			case 'layout':
			case 'uselayout':
			case 'restorelayout':
				$page_layout = new \gp\Page\Layout($this->cmd,'Admin/Menu',$this->query_string);
				if( $page_layout->result() ){
					return;
				}
			break;
		}

		$this->ShowForm();

	}

	/**
	 * @param string $href
	 * @param string $label
	 * @param string $query
	 * @param string|array $attr
	 * @param mixed $nonce_action
	 *
	 */
	public function Link($href,$label,$query='',$attr='',$nonce_action=false){
		$query = $this->MenuQuery($query);
		return \gp\tool::Link($href,$label,$query,$attr,$nonce_action);
	}

	public function GetUrl($href,$query='',$ampersands=true){
		$query = $this->MenuQuery($query);
		return \gp\tool::GetUrl($href,$query,$ampersands);
	}

	public function MenuQuery($query=''){
		if( !empty($query) ){
			$query .= '&';
		}
		$query .= 'menu='.$this->curr_menu_id;
		if( strpos($query,'page=') !== false ){
			//do nothing
		}elseif( $this->search_page > 0 ){
			$query .= '&page='.$this->search_page;
		}

		//for searches
		if( !empty($_REQUEST['q']) ){
			$query .= '&q='.urlencode($_REQUEST['q']);
		}

		return $query;
	}

	public function SetQueryInfo(){

		//search page
		if( isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ){
			$this->search_page = (int)$_REQUEST['page'];
		}

		//browse query string
		$this->query_string = $this->MenuQuery();
	}

	public function SetCollapseSettings(){
		$gp_menu_collapse =& $_COOKIE['gp_menu_hide'];

		$search = '#'.$this->curr_menu_id.'=[';
		$pos = strpos($gp_menu_collapse,$search);
		if( $pos === false ){
			return;
		}

		$gp_menu_collapse = substr($gp_menu_collapse,$pos+strlen($search));
		$pos = strpos($gp_menu_collapse,']');
		if( $pos === false ){
			return;
		}
		$gp_menu_collapse = substr($gp_menu_collapse,0,$pos);
		$gp_menu_collapse = trim($gp_menu_collapse,',');
		$this->hidden_levels = explode(',',$gp_menu_collapse);
		$this->hidden_levels = array_flip($this->hidden_levels);
	}



	/**
	 * Get the id for the current menu
	 * Not the same order as used for $_REQUEST
	 *
	 */
	public function SetMenuID(){

		if( isset($this->curr_menu_id) ){
			return;
		}

		if( isset($_POST['menu']) ){
			$this->curr_menu_id = $_POST['menu'];
		}elseif( isset($_GET['menu']) ){
			$this->curr_menu_id = $_GET['menu'];
		}elseif( isset($this->cookie_settings['gp_menu_select']) ){
			$this->curr_menu_id = $this->cookie_settings['gp_menu_select'];
		}

		if( !isset($this->curr_menu_id) || !isset($this->avail_menus[$this->curr_menu_id]) ){
			$this->curr_menu_id = 'gpmenu';
		}

	}

	public function SetMenuArray(){
		global $gp_menu;

		if( isset($this->list_displays[$this->curr_menu_id]) ){
			return;
		}

		//set curr_menu_array
		if( $this->curr_menu_id == 'gpmenu' ){
			$this->curr_menu_array =& $gp_menu;
			$this->is_main_menu = true;
			return;
		}

		$this->curr_menu_array = \gp\tool\Output\Menu::GetMenuArray($this->curr_menu_id);
		$this->is_alt_menu = true;
	}


	public function SaveMenu($menu_and_pages=false){
		global $dataDir;

		if( $this->is_main_menu ){
			return \gp\admin\Tools::SavePagesPHP();
		}

		if( is_null($this->curr_menu_array) ){
			return false;
		}

		if( $menu_and_pages && !\gp\admin\Tools::SavePagesPHP() ){
			return false;
		}

		$menu_file = $dataDir.'/data/_menus/'.$this->curr_menu_id.'.php';
		return \gp\tool\Files::SaveData($menu_file,'menu',$this->curr_menu_array);
	}




	/**
	 * Primary Display
	 *
	 *
	 */
	public function ShowForm(){
		global $langmessage, $config;

		$menu_output = false;
		ob_start();

		if( isset($this->list_displays[$this->curr_menu_id]) ){
			$this->SearchDisplay();
			$replace_id = '#gp_menu_available';
		}else{
			$menu_output = true;
			$this->OutputMenu();
			$replace_id = '#admin_menu';
		}

		$content = ob_get_clean();


		// json response
		if( isset($_REQUEST['gpreq']) && ($_REQUEST['gpreq'] == 'json') ){
			$this->MenuJsonResponse( $replace_id, $content);
			return;
		}


		// search form
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu').'" method="post" id="page_search">';
		$_REQUEST += array('q'=>'');
		echo '<input type="text" name="q" size="15" value="'.htmlspecialchars($_REQUEST['q']).'" class="gptext gpinput title-autocomplete" /> ';
		echo '<input type="submit" name="cmd" value="'.$langmessage['search pages'].'" class="gpbutton" />';
		echo '<input type="hidden" name="menu" value="search" />';
		echo '</form>';


		$menus = $this->GetAvailMenus('menu');
		$lists = $this->GetAvailMenus('display');


		//heading
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu').'" method="post" id="gp_menu_select_form">';
		echo '<input type="hidden" name="curr_menu" id="gp_curr_menu" value="'.$this->curr_menu_id.'" />';

		echo '<h2 class="first-child">';
		echo $langmessage['file_manager'].' &#187;  ';
		echo '<select id="gp_menu_select" name="gp_menu_select" class="gpselect">';

		echo '<optgroup label="'.$langmessage['Menus'].'">';
			foreach($menus as $menu_id => $menu_label){
				if( $menu_id == $this->curr_menu_id ){
					echo '<option value="'.$menu_id.'" selected="selected">';
				}else{
					echo '<option value="'.$menu_id.'">';
				}
				echo $menu_label.'</option>';
			}
		echo '</optgroup>';
		echo '<optgroup label="'.$langmessage['Lists'].'">';
			foreach($lists as $menu_id => $menu_label){

				if( $menu_id == $this->curr_menu_id ){
					echo '<option value="'.$menu_id.'" selected="selected">';
				}elseif( $menu_id == 'search' ){
					continue;
				}else{
					echo '<option value="'.$menu_id.'">';
				}
				echo $menu_label.'</option>';
			}
		echo '</optgroup>';
		echo '</select>';
		echo '</h2>';

		echo '</form>';


		//homepage
		echo '<div class="homepage_setting">';
		$this->HomepageDisplay();
		echo '</div>';
		\gp\tool\Editing::PrepAutoComplete();





		echo '<div id="admin_menu_div">';

		if( $menu_output ){
			echo '<ul id="admin_menu" class="sortable_menu">';
			echo $content;
			echo '</ul><div id="admin_menu_tools" ></div>';

			echo '<div id="menu_info" style="display:none">';
			$this->MenuSkeleton();
			echo '</div>';

			echo '<div id="menu_info_extern" style="display:none">';
			$this->MenuSkeletonExtern();
			echo '</div>';

			echo '<div id="menu_info_extra" style="display:none">';
			$this->MenuSkeletonExtra();
			echo '</div>';
		}else{
			echo '<div id="gp_menu_available">';
			echo $content;
			echo '</div>';
		}

		echo '</div>';


		echo '<div class="admin_footnote">';

		echo '<div>';
		echo '<b>'.$langmessage['Menus'].'</b>';
		$this->MenuList($menus);
		echo '<span>'.\gp\tool::Link('Admin/Menu/Menus','+ '.$langmessage['Add New Menu'],'cmd=NewMenuPrompt','data-cmd="gpabox"').'</span>';
		echo '</div>';

		echo '<div>';
		echo '<b>'.$langmessage['Lists'].'</b>';
		$this->MenuList($lists);
		echo '</div>';


		//options for alternate menu
		if( $this->is_alt_menu ){
			echo '<div>';
			$label = $menus[$this->curr_menu_id];
			echo '<b>'.$label.'</b>';
			echo '<span>'.\gp\tool::Link('Admin/Menu/Menus',$langmessage['rename'],'cmd=MenuRenamePrompt&id='.$this->curr_menu_id,'data-cmd="gpabox"').'</span>';
			$title_attr = sprintf($langmessage['generic_delete_confirm'],'&quot;'.$label.'&quot;');
			echo '<span>'.\gp\tool::Link('Admin/Menu/Menus',$langmessage['delete'],'cmd=MenuRemove&id='.$this->curr_menu_id,array('data-cmd'=>'cnreq','class'=>'gpconfirm','title'=>$title_attr)).'</span>';

			echo '</div>';
		}


		echo '</div>';

		echo '<div class="gpclear"></div>';
	}


	/**
	 * Generate link list for available menus
	 *
	 */
	public function MenuList($menus){
		foreach($menus as $menu_id => $menu_label){
			if( $menu_id == $this->curr_menu_id ){
				echo '<span>'.$menu_label.'</span>';
			}else{
				echo '<span>'.\gp\tool::Link('Admin/Menu',$menu_label,'menu='.$menu_id, array('data-cmd'=>'cnreq')).'</span>';
			}
		}
	}


	public function GetAvailMenus($get_type='menu'){

		$result = array();
		foreach($this->avail_menus as $menu_id => $menu_label){

			$menu_type = 'menu';
			if( isset($this->list_displays[$menu_id]) ){
				$menu_type = 'display';
			}

			if( $menu_type == $get_type ){
				$result[$menu_id] = $menu_label;
			}
		}
		return $result;
	}


	/**
	 * Send updated page manager content via ajax
	 * we're replacing more than just the content
	 *
	 */
	public function MenuJsonResponse($replace_id, $content){

		$this->page->ajaxReplace[] = array('gp_menu_prep','','');
		$this->page->ajaxReplace[] = array('inner',$replace_id,$content);
		$this->page->ajaxReplace[] = array('gp_menu_refresh','','');

		ob_start();
		\gp\tool\Output::GetMenu();
		$content = ob_get_clean();
		$this->page->ajaxReplace[] = array('inner','#admin_menu_wrap',$content);
	}



	public function OutputMenu(){
		global $langmessage, $gp_titles, $gpLayouts;

		if( is_null($this->curr_menu_array) ){
			msg($langmessage['OOPS'].' (Current menu not set)');
			return;
		}


		$array			= $this->CurrMenuArray();
		$menu_keys		= array_keys($array);
		$menu_values	= array_values($array);

		$curr_level		= $menu_values[0]['level'];


		//for sites that don't start with level 0
		$prev_level		= 0;
		if( $curr_level > 0 ){
			$piece = '<li><div>&nbsp;</div><ul>';
			while( $curr_level > $prev_level ){
				echo $piece;
				$prev_level++;
			}
		}


		foreach($menu_keys as $curr_key => $menu_key){

			echo "\n";

			$class			= '';
			$menu_value		= $menu_values[$curr_key];
			$curr_level		= $menu_value['level'];


			$next_level = 0;
			if( isset($menu_values[$curr_key+1]) ){
				$next_level = $menu_values[$curr_key+1]['level'];
				if( $next_level > $curr_level ){
					$class = 'haschildren';
				}
			}

			if( isset($this->hidden_levels[$menu_key]) ){
				$class .= ' hidechildren';
			}
			if( $curr_level >= $this->max_level_index){
				$class .= ' no-nest';
			}

			$class = \gp\admin\Menu\Tools::VisibilityClass($class, $menu_key);


			//layout
			$style = '';
			if( $this->is_main_menu ){
				if( isset($gp_titles[$menu_key]['gpLayout']) && isset($gpLayouts[$gp_titles[$menu_key]['gpLayout']]) ){
					$color = $gpLayouts[$gp_titles[$menu_key]['gpLayout']]['color'];
					$style = 'background-color:'.$color.';';
				}
			}


			echo '<li class="'.$class.'" style="'.$style.'">';

			$this->ShowLevel($menu_key,$menu_value);

			$this->EqualizeLevels($curr_level, $next_level);
		}

	}


	/**
	 *
	 * @param int $curr_level
	 * @param int $next_level
	 */
	protected function EqualizeLevels($curr_level, $next_level){
		global $langmessage;

		if( $next_level > $curr_level ){

			$piece = '<ul>';
			while( $next_level > $curr_level ){
				echo $piece;
				$curr_level++;
				$piece = '<li class="missing_title"><div>'
						.'<a href="#" class="gp_label" data-cmd="menu_info">'
						.$langmessage['page_deleted']
						.'</a>'
						.'<p><b>'.$langmessage['page_deleted'].'</b></p>'
						.'</div><ul>';
			}

		}elseif( $next_level <= $curr_level ){

			while( $next_level < $curr_level ){
				echo '</li></ul>';
				$curr_level--;
			}
			echo '</li>';
		}

		return $curr_level;
	}


	/**
	 * Check the curr_menu_array
	 * 	Remove missing titles
	 *	Fill with new array if empty
	 *
	 */
	private function CurrMenuArray(){
		global $gp_titles;

		$menu_adjustments	= false;
		$array				= array();

		//get array of titles and levels
		foreach($this->curr_menu_array as $key => $info){
			if( !isset($info['level']) ){
				break;
			}

			//remove deleted titles
			if( !isset($gp_titles[$key]) && !isset($info['url']) && !isset($info['area']) ){
				$menu_adjustments = true;
				continue;
			}

			$array[$key] = $info;
		}

		//if the menu is empty (because all the files in it were deleted elsewhere), recreate it with the home page
		if( count($array) == 0 ){
			$array				= \gp\admin\Menu\Tools::AltMenu_New();
			$menu_adjustments	= true;
		}

		if( $menu_adjustments ){
			$this->curr_menu_array	= $array;
			$this->SaveMenu(false);
		}

		return $array;
	}


	/**
	 * Output a piece of the editable menu
	 *
	 */
	public function ShowLevel($menu_key,$menu_value){
		global $gp_titles, $gpLayouts;

		$layout			= \gp\admin\Menu\Tools::CurrentLayout($menu_key);
		$layout_info	= $gpLayouts[$layout];

		echo '<div id="gp_menu_key_'.$menu_key.'">';

		$style = '';
		$class = 'expand_img';
		if( !empty($gp_titles[$menu_key]['gpLayout']) ){
			$style = 'style="background-color:'.$layout_info['color'].';"';
			$class .= ' haslayout';
		}

		echo '<a href="#" class="'.$class.'" data-cmd="expand_img" '.$style.'></a>';

		if( isset($gp_titles[$menu_key]) ){
			$this->ShowLevel_Title($menu_key,$menu_value,$layout_info);
		}elseif( isset($menu_value['url']) ){
			$this->ShowLevel_External($menu_key,$menu_value);
		}elseif( isset($menu_value['area']) ){
			$this->ShowLevel_Extra($menu_key,$menu_value);
		}
		echo '</div>';
	}



	/**
	 * Show a menu entry if it's an Extra Content Area
	 *
	 */
	public function ShowLevel_Extra($menu_key,$menu_value){
		$data = array(
				'key'		=>	$menu_key,
				'area'		=>	$menu_value['area'],
				'label'		=>	$menu_value['label'],
				'level'		=>	$menu_value['level'],
			);

		if( strlen($data['label']) > 30 ){
			$data['title'] = substr($data['title'],0,30).'...';
		}

		\gp\admin\Menu\Tools::MenuLink($data,'extra');
		echo \gp\tool::LabelSpecialChars($data['label']);
		echo '</a>';
	}

	public function MenuSkeletonExtra(){
		global $langmessage;

		echo '<b>'.$langmessage['options'].'</b>';
		echo '<span>';

		$img	= '<i class="menu_icon fa fa-css3"></i>';
		$label = $langmessage['Menu Output'] . ' - ' . $langmessage['Classes'];
		$attrs	= array('title'=>$label, 'data-cmd'=>'gpabox');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $label,
			'cmd=ClassesForm&index=[key]&no_a_classes=1',
			$attrs
		);

		$img = '<i class="menu_icon fa fa-scissors"></i>';
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['rm_from_menu'],
			'cmd=hide&index=[key]',
			array('title'=>$langmessage['rm_from_menu'],'data-cmd'=>'postlink','class'=>'gpconfirm')
		);

		echo '</span>';

		$this->InsertLinks();
	}


	/**
	 * Show a menu entry if it's an external link
	 *
	 */
	public function ShowLevel_External($menu_key,$menu_value){

		$data = array(
				'key'		=>	$menu_key
				,'url'		=>	$menu_value['url']
				,'title'	=>	$menu_value['url']
				,'level'	=>	$menu_value['level']
				);

		if( strlen($data['title']) > 30 ){
			$data['title'] = substr($data['title'],0,30).'...';
		}

		\gp\admin\Menu\Tools::MenuLink($data,'external');
		echo \gp\tool::LabelSpecialChars($menu_value['label']);
		echo '</a>';
	}

	public function MenuSkeletonExtern(){
		global $langmessage;

		echo '<b>'.$langmessage['Target URL'].'</b>';
		echo '<span>';
		echo '<a href="[url]" target="_blank">[title]</a>';
		echo '</span>';

		echo '<b>'.$langmessage['options'].'</b>';
		echo '<span>';

		$img = '<i class="menu_icon fa fa-gears"></i>';
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['edit'],
			'cmd=EditExternal&key=[key]',
			array('title'=>$langmessage['edit'],'data-cmd'=>'gpabox')
		);

		$img	= '<i class="menu_icon fa fa-css3"></i>';
		$label = $langmessage['Menu Output'] . ' - ' . $langmessage['Classes'];
		$attrs	= array('title'=>$label, 'data-cmd'=>'gpabox');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $label,
			'cmd=ClassesForm&index=[key]',
			$attrs
		);

		$img = '<i class="menu_icon fa fa-scissors"></i>';
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['rm_from_menu'],
			'cmd=hide&index=[key]',
			array('title'=>$langmessage['rm_from_menu'],'data-cmd'=>'postlink','class'=>'gpconfirm')
		);

		echo '</span>';

		$this->InsertLinks();
	}


	/**
	 * Show a menu entry if it's an internal page
	 *
	 */
	public function ShowLevel_Title($menu_key, $menu_value, $layout_info){

		$title		= \gp\tool::IndexToTitle($menu_key);
		$data		= $this->GetReplaceData($title, $layout_info, $menu_key, $menu_value);
		$label		= \gp\tool::GetLabel($title);

		\gp\admin\Menu\Tools::MenuLink($data);
		echo \gp\tool::LabelSpecialChars($label);
		echo '</a>';
	}


	/**
	 * Get the output formatting data for
	 *
	 */
	public function GetReplaceData($title, $layout_info, $menu_key, $menu_value=array() ){
		global $langmessage, $gp_titles;

		$isSpecialLink				= \gp\tool::SpecialOrAdmin($title);

		//get the data for this title
		$data = array(
					'key'			=>	$menu_key,
					'url'			=>	\gp\tool::GetUrl($title),
					'title'			=>	$title,
					'special'		=>	$isSpecialLink,
					'has_layout'	=>	!empty($gp_titles[$menu_key]['gpLayout']),
					'layout_color'	=>	$layout_info['color'],
					'layout_label'	=>	$layout_info['label'],
					'types'			=>	$gp_titles[$menu_key]['type'],
					'opts'			=>	'',
					'size'			=>	'',
					'mtime'			=> '',
					);


		if( isset($menu_value['level']) ){
			$data['level'] = $menu_value['level'];
		}

		if( $isSpecialLink === false ){
			$file	= \gp\tool\Files::PageFile($title);
			$stats	= @stat($file);
			if( $stats ){
				$data['size']	= \gp\admin\Tools::FormatBytes($stats['size']);
				$data['time']	= \gp\tool::date($langmessage['strftime_datetime'],$stats['mtime']);
			}
		}

		ob_start();
		\gp\tool\Plugins::Action('MenuPageOptions',array($title,$menu_key,$menu_value,$layout_info));
		$menu_options = ob_get_clean();
		if( $menu_options ){
			$data['opts'] = $menu_options;
		}

		return $data;
	}


	/**
	 * Output html for the menu editing options displayed for selected titles
	 *
	 */
	public function MenuSkeleton(){
		global $langmessage;

		//page options
		echo '<b>'.$langmessage['page_options'].'</b>';

		echo '<span>';

		$img	= '<i class="menu_icon fa fa-pencil"></i>';
		echo '<a href="[url]" class="view_edit_link not_multiple">';
		echo  $img . htmlspecialchars($langmessage['view/edit_page']);
		echo '</a>';

		$img	= '<i class="menu_icon fa fa-gears"></i>';
		$attrs	= array('title'=>$langmessage['rename/details'], 'data-cmd'=>'gpajax', 'class'=>'not_multiple');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['rename/details'],
			'cmd=renameform&index=[key]',
			$attrs
		);


		$img	= '<i class="fa fa-eye-slash menu_icon"></i>';
		$q		= 'cmd=ToggleVisibility&index=[key]';
		$label	= $langmessage['Visibility'].': '.$langmessage['Private'];
		$attrs	= array('title'=>$label, 'data-cmd'=>'gpajax', 'class'=>'vis_private');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $label,
			$q,
			$attrs
		);

		$img	= '<i class="fa fa-eye menu_icon"></i>';
		$label	= $langmessage['Visibility'].': '.$langmessage['Public'];
		$attrs	= array('title'=>$label,'data-cmd'=>'gpajax','class'=>'vis_public not_multiple');
		$q		.= '&visibility=private';
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $label,
			$q,
			$attrs
		);


		echo '<a href="[url]?cmd=ViewHistory" class="view_edit_link not_multiple not_special" ';
		echo 'data-cmd="gpabox"><i class="fa fa-history menu_icon"></i>';
		echo  htmlspecialchars($langmessage['Revision History']);
		echo '</a>';


		$img	= '<i class="menu_icon fa fa-files-o"></i>';
		$attrs	= array('title'=>$langmessage['Copy'], 'data-cmd'=>'gpabox', 'class'=>'not_multiple not_special');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['Copy'],
			'cmd=CopyForm&index=[key]',
			$attrs
		);


		if( \gp\admin\Tools::HasPermission('Admin_User') ){
			$img	= '<i class="menu_icon fa fa-user"></i>';
			$attrs	= array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox');
			echo $this->Link(
				'Admin/Users',
				$img . $langmessage['permissions'],
				'cmd=file_permissions&index=[key]',
				$attrs
			);
		}


		$img	= '<i class="menu_icon fa fa-css3"></i>';
		$label = $langmessage['Menu Output'] . ' - ' . $langmessage['Classes'];
		$attrs	= array('title'=>$label, 'data-cmd'=>'gpabox');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $label,
			'cmd=ClassesForm&index=[key]',
			$attrs
		);


		$img	= '<i class="menu_icon fa fa-scissors"></i>';
		$attrs	= array('title'=>$langmessage['rm_from_menu'],'data-cmd'=>'postlink','class'=>'gpconfirm');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['rm_from_menu'],
			'cmd=hide&index=[key]',
			$attrs
		);


		$img	= '<i class="menu_icon fa fa-trash"></i>';
		$attrs	= array('title'=>$langmessage['delete_page'], 'data-cmd'=>'postlink', 'class'=>'gpconfirm not_special');
		echo $this->Link(
			'Admin/Menu/Ajax',
			$img . $langmessage['delete'],
			'cmd=MoveToTrash&index=[key]',
			$attrs
		);


		echo '[opts]'; //replaced with the contents of \gp\tool\Plugins::Action('MenuPageOptions',array($title,$menu_key,$menu_value,$layout_info));

		echo '</span>';


		//layout
		if( $this->is_main_menu ){
			echo '<div class="not_multiple">';
			echo '<b>'.$langmessage['layout'].'</b>';
			echo '<span>';

			//has_layout
			$img = '<span class="layout_icon"></span>';
			echo $this->Link(
				'Admin/Menu',
				$img . '[layout_label]',
				'cmd=layout&index=[key]',
				array('data-cmd'=>'gpabox', 'title'=>$langmessage['layout'], 'class'=>'has_layout')
			);

			$img = '<i class="menu_icon fa fa-undo"></i>';
			echo $this->Link(
				'Admin/Menu',
				$img . $langmessage['restore'],
				'cmd=restorelayout&index=[key]',
				array('data-cmd'=>'postlink', 'title'=>$langmessage['restore'], 'class'=>'has_layout'),
				'restore'
			);

			//no_layout
			$img = '<span class="layout_icon"></span>';
			echo $this->Link(
				'Admin/Menu',
				$img . '[layout_label]',
				'cmd=layout&index=[key]',
				array('data-cmd'=>'gpabox', 'title'=>$langmessage['layout'], 'class'=>'no_layout')
			);
			echo '</span>';
			echo '</div>';
		}

		$this->InsertLinks();


		//file stats
		echo '<div>';
		echo '<b>'.$langmessage['Page Info'].'</b>';
		echo '<span>';
		echo '<a class="not_multiple">'.$langmessage['Slug/URL'].': [title]</a>';
		echo '<a class="not_multiple">'.$langmessage['Content Type'].': [types]</a>';
		echo '<a class="not_special only_multiple">'.sprintf($langmessage['%s Pages'],'[files]').'</a>';
		echo '<a class="not_special">'.$langmessage['File Size'].': [size]</a>';
		echo '<a class="not_special not_multiple">'.$langmessage['Modified'].': [mtime]</a>';
		echo '<a class="not_multiple">Data Index: [key]</a>';
		echo '</span>';
		echo '</div>';

	}


	/**
	 * Output Insert links displayed with page options
	 *
	 */
	public function InsertLinks(){
		global $langmessage;

		echo '<div class="not_multiple">';
		echo '<b>'.$langmessage['insert_into_menu'].'</b>';
		echo '<span>';

		$img = '<span class="menu_icon insert_before_icon"></span>';
		$query = 'cmd=insert_before&insert_where=[key]';
		echo $this->Link('Admin/Menu/Ajax',$img.$langmessage['insert_before'],$query,array('title'=>$langmessage['insert_before'],'data-cmd'=>'gpabox'));


		$img = '<span class="menu_icon insert_after_icon"></span>';
		$query = 'cmd=insert_after&insert_where=[key]';
		echo $this->Link('Admin/Menu/Ajax',$img.$langmessage['insert_after'],$query,array('title'=>$langmessage['insert_after'],'data-cmd'=>'gpabox'));


		$img = '<span class="menu_icon insert_after_icon"></span>';
		$query = 'cmd=insert_child&insert_where=[key]';
		echo $this->Link('Admin/Menu/Ajax',$img.$langmessage['insert_child'],$query,array('title'=>$langmessage['insert_child'],'data-cmd'=>'gpabox','class'=>'insert_child'));
		echo '</span>';
		echo '</div>';
	}




	public function SearchDisplay(){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu;

		$this->inherit_info = \gp\admin\Menu\Tools::Inheritance_Info();

		switch($this->curr_menu_id){
			case 'search':
				$show_list = $this->GetSearchList();
			break;
			case 'hidden':
				$show_list = \gp\admin\Menu\Tools::GetAvailable();
			break;
			case 'nomenus':
				$show_list = $this->GetNoMenus();
			break;
			default:
				$show_list = array_keys($gp_index);
			break;
		}

		$show_list = array_values($show_list); //to reset the keys
		$show_list = array_reverse($show_list); //show newest first
		$max = count($show_list);
		while( ($this->search_page * $this->search_max_per_page) > $max ){
			$this->search_page--;
		}
		$start = $this->search_page*$this->search_max_per_page;
		$stop = min( ($this->search_page+1)*$this->search_max_per_page, $max);


		ob_start();
		echo '<div class="gp_search_links">';
		echo '<span class="showing">';
		echo sprintf($langmessage['SHOWING'],($start+1),$stop,$max);
		echo '</span>';

		echo '<span>';

		if( ($start !== 0) || ($stop < $max) ){
			for( $i = 0; ($i*$this->search_max_per_page) < $max; $i++ ){
				$class = '';
				if( $i == $this->search_page ){
					$class = ' class="current"';
				}
				echo $this->Link('Admin/Menu',($i+1),'page='.$i,'data-cmd="gpajax"'.$class);
			}
		}

		echo $this->Link('Admin/Menu/Ajax',$langmessage['create_new_file'],'cmd=AddHidden',array('title'=>$langmessage['create_new_file'],'data-cmd'=>'gpabox'));
		echo '</span>';
		echo '</div>';
		$links = ob_get_clean();

		echo $links;

		echo '<table class="bordered striped">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Content Type'];
		echo '</th><th>';
		echo $langmessage['Child Pages'];
		echo '</th><th>';
		echo $langmessage['File Size'];
		echo '</th><th>';
		echo $langmessage['Modified'];
		echo '</th></tr>';
		echo '</thead>';


		echo '<tbody>';

		if( count($show_list) > 0 ){
			for( $i = $start; $i < $stop; $i++ ){
				$title = $show_list[$i];
				$this->SearchDisplayRow($title);
			}
		}

		echo '</tbody>';
		echo '</table>';

		if( count($show_list) == 0 ){
			echo '<p>';
			echo $langmessage['Empty'];
			echo '</p>';
		}

		echo '<br/>';
		echo $links;
	}



	/**
	 * Get a list of titles matching the search criteria
	 *
	 */
	public function GetSearchList(){
		global $gp_index;


		$key =& $_REQUEST['q'];

		if( empty($key) ){
			return array();
		}

		$key = strtolower($key);
		$show_list = array();
		foreach($gp_index as $title => $index ){

			if( strpos(strtolower($title),$key) !== false ){
				$show_list[$index] = $title;
				continue;
			}

			$label = \gp\tool::GetLabelIndex($index);
			if( strpos(strtolower($label),$key) !== false ){
				$show_list[$index] = $title;
				continue;
			}
		}
		return $show_list;
	}



	/**
	 * Get an array of titles that is not represented in any of the menus
	 *
	 */
	public function GetNoMenus(){
		global $gp_index;


		//first get all titles in a menu
		$menus = $this->GetAvailMenus('menu');
		$all_keys = array();
		foreach($menus as $menu_id => $label){
			$menu_array = \gp\tool\Output\Menu::GetMenuArray($menu_id);
			$keys = array_keys($menu_array);
			$all_keys = array_merge($all_keys,$keys);
		}
		$all_keys = array_unique($all_keys);

		//then check $gp_index agains $all_keys
		$avail = array();
		foreach( $gp_index as $title => $index ){
			if( in_array($index, $all_keys) ){
				continue;
			}
			$avail[] = $title;
		}
		return $avail;
	}


	/**
	 * Display row
	 *
	 */
	public function SearchDisplayRow($title){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu, $gp_titles;

		$menu_key			= $gp_index[$title];
		$layout				= \gp\admin\Menu\Tools::CurrentLayout($menu_key);
		$layout_info		= $gpLayouts[$layout];
		$label				= \gp\tool::GetLabel($title);
		$data				= $this->GetReplaceData($title, $layout_info, $menu_key);



		echo '<tr><td>';
		echo \gp\tool::Link($title,\gp\tool::LabelSpecialChars($label));


		//area only display on mouseover
		echo '<div><div>';

		echo $this->Link('Admin/Menu/Ajax',$langmessage['rename/details'],'cmd=renameform&index='.urlencode($menu_key),array('title'=>$langmessage['rename/details'],'data-cmd'=>'gpajax'));


		$label	= $langmessage['Visibility'].': '.$langmessage['Private'];
		$q		= 'cmd=ToggleVisibility&index='.urlencode($menu_key);
		if( !isset($gp_titles[$menu_key]['vis']) ){
			$label	= $langmessage['Visibility'].': '.$langmessage['Public'];
			$q		.= '&visibility=private';
		}

		echo $this->Link('Admin/Menu/Ajax',$label,$q,'data-cmd="gpajax"');

		if( $data['special'] === false ){
			echo \gp\tool::Link($title,$langmessage['Revision History'],'cmd=ViewHistory','class="view_edit_link not_multiple" data-cmd="gpabox"');
			echo $this->Link('Admin/Menu/Ajax',$langmessage['Copy'],'cmd=CopyForm&index='.urlencode($menu_key),array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));
		}


		echo '<span>';
		echo $langmessage['layout'].': ';
		echo $this->Link('Admin/Menu',$layout_info['label'],'cmd=layout&index='.urlencode($menu_key),array('title'=>$langmessage['layout'],'data-cmd'=>'gpabox'));
		echo '</span>';

		if( $data['special'] === false ){
			echo $this->Link('Admin/Menu/Ajax',$langmessage['delete'],'cmd=MoveToTrash&index='.urlencode($menu_key),array('title'=>$langmessage['delete_page'],'data-cmd'=>'postlink','class'=>'gpconfirm'));
		}

		echo $data['opts'];

		//stats
		if( gpdebug ){
			echo '<span>Data Index: '.$menu_key.'</span>';
		}
		echo '</div>&nbsp;</div>';

		//types
		echo '</td><td>';
		$this->TitleTypes($menu_key);

		//children
		echo '</td><td>';
		if( isset($this->inherit_info[$menu_key]) && isset($this->inherit_info[$menu_key]['children']) ){
			echo $this->inherit_info[$menu_key]['children'];
		}elseif( isset($gp_menu[$menu_key]) ){
			echo '0';
		}else{
			echo $langmessage['Not In Main Menu'];
		}

		//size, modified
		echo '</td><td>';
		echo $data['size'];
		echo '</td><td>';
		echo $data['mtime'];
		echo '</td></tr>';
	}



	/**
	 * List section types
	 *
	 */
	public function TitleTypes($title_index){
		global $gp_titles;

		$types		= explode(',',$gp_titles[$title_index]['type']);
		$types		= array_filter($types);
		$types		= array_unique($types);

		foreach($types as $i => $type){
			if( isset($this->section_types[$type]) && isset($this->section_types[$type]['label']) ){
				$types[$i] = $this->section_types[$type]['label'];
			}
		}

		echo implode(', ',$types);
	}


	/**
	 * Get a list of pages that are not in the current menu array
	 * @return array
	 */
	protected function GetAvail_Current(){
		global $gp_index;

		if( $this->is_main_menu ){
			return \gp\admin\Menu\Tools::GetAvailable();
		}

		$avail = array();
		foreach( $gp_index as $title => $index ){
			if( !isset($this->curr_menu_array[$index]) ){
				$avail[$index] = $title;
			}
		}
		return $avail;
	}


	/**
	 * Save changes to the current menu array after a drag event occurs
	 * @return bool
	 */
	public function SaveDrag(){
		global $langmessage;

		$this->CacheSettings();
		if( is_null($this->curr_menu_array) ){
			msg($langmessage['OOPS'].'(1)');
			return false;
		}

		$key = $_POST['drag_key'];
		if( !isset($this->curr_menu_array[$key]) ){
			msg($langmessage['OOPS'].' (Unknown menu key)');
			return false;
		}


		$moved = $this->RmMoved($key);
		if( !$moved ){
			msg($langmessage['OOPS'].'(3)');
			return false;
		}


		// if prev (sibling) set
		if( !empty($_POST['prev']) ){

			$inserted = $this->MenuInsert_After( $moved, $_POST['prev']);

		// if parent is set
		}elseif( !empty($_POST['parent']) ){

			$inserted = $this->MenuInsert_Child( $moved, $_POST['parent']);

		// if no siblings, no parent then it's the root
		}else{
			$inserted = $this->MenuInsert_Before( $moved, false);

		}

		if( !$inserted ){
			$this->RestoreSettings();
			msg($langmessage['OOPS'].'(4)');
			return;
		}

		if( !$this->SaveMenu(false) ){
			$this->RestoreSettings();
			\gp\tool::AjaxWarning();
			return false;
		}

	}


	/**
	 * Get portion of menu that was moved
	 */
	public function RmMoved($key){
		if( !isset($this->curr_menu_array[$key]) ){
			return false;
		}

		$old_level = false;
		$moved = array();

		foreach($this->curr_menu_array as $menu_key => $info){

			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			if( $old_level === false ){

				if( $menu_key != $key ){
					continue;
				}

				$old_level = $level;
				$moved[$menu_key] = $info;
				unset($this->curr_menu_array[$menu_key]);
				continue;
			}

			if( $level <= $old_level ){
				break;
			}

			$moved[$menu_key] = $info;
			unset($this->curr_menu_array[$menu_key]);
		}
		return $moved;
	}


	/**
	 * Remove key from curr_menu_array
	 * Adjust children levels if necessary
	 *
	 */
	protected function RmFromMenu($search_key,$curr_menu=true){
		global $gp_menu;

		if( $curr_menu ){
			$keys = array_keys($this->curr_menu_array);
			$values = array_values($this->curr_menu_array);
		}else{
			$keys = array_keys($gp_menu);
			$values = array_values($gp_menu);
		}

		$insert_key = array_search($search_key,$keys);
		if( ($insert_key === null) || ($insert_key === false) ){
			return false;
		}

		unset($keys[$insert_key]);
		$keys = array_values($keys);

		unset($values[$insert_key]);
		$values = array_values($values);


		//adjust levels of children
		$prev_level = -1;
		if( isset($values[$insert_key-1]) ){
			$prev_level = $values[$insert_key-1]['level'];
		}

		do{
			$moved_one = false;
			if( isset($values[$insert_key]) ){
				$curr_level = $values[$insert_key]['level'];
				if( ($prev_level+1) < $curr_level ){
					$values[$insert_key]['level']--;
					$prev_level = $values[$insert_key]['level'];
					$moved_one = true;
					$insert_key++;
				}
			}
		}while($moved_one);

		//shouldn't happen
		if( count($keys) == 0 ){
			return false;
		}

		//rebuild
		if( $curr_menu ){
			$this->curr_menu_array = array_combine($keys, $values);
		}else{
			$gp_menu = array_combine($keys, $values);
		}

		return true;
	}


	/**
	 * Insert titles into menu
	 *
	 */
	protected function MenuInsert_Before($titles,$sibling){

		$old_level = \gp\admin\Menu\Tools::GetRootLevel($titles);

		//root install
		if( $sibling === false ){
			$level_adjustment = 0 - $old_level;
			$titles = $this->AdjustMovedLevel($titles,$level_adjustment);
			$this->curr_menu_array = $titles + $this->curr_menu_array;
			return true;
		}


		//before sibling
		if( !isset($this->curr_menu_array[$sibling]) || !isset($this->curr_menu_array[$sibling]['level']) ){
			return false;
		}

		$sibling_level = $this->curr_menu_array[$sibling]['level'];
		$level_adjustment = $sibling_level - $old_level;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);

		$new_menu = array();
		foreach($this->curr_menu_array as $menu_key => $menu_info ){

			if( $menu_key == $sibling ){
				foreach($titles as $titles_key => $titles_info){
					$new_menu[$titles_key] = $titles_info;
				}
			}
			$new_menu[$menu_key] = $menu_info;
		}
		$this->curr_menu_array = $new_menu;
		return true;
	}

	/*
	 * Insert $titles into $menu as siblings of $sibling
	 * Place
	 *
	 */
	protected function MenuInsert_After($titles,$sibling,$level_adjustment=0){

		if( !isset($this->curr_menu_array[$sibling]) || !isset($this->curr_menu_array[$sibling]['level']) ){
			return false;
		}

		$sibling_level = $this->curr_menu_array[$sibling]['level'];

		//level adjustment
		$old_level			= \gp\admin\Menu\Tools::GetRootLevel($titles);
		$level_adjustment	+= $sibling_level - $old_level;
		$titles				= $this->AdjustMovedLevel($titles,$level_adjustment);


		// rebuild menu
		//	insert $titles after sibling and it's children
		$new_menu = array();
		$found_sibling = false;
		foreach($this->curr_menu_array as $menu_key => $menu_info){

			$menu_level = 0;
			if( isset($menu_info['level']) ){
				$menu_level = $menu_info['level'];
			}

			if( $found_sibling && ($menu_level <= $sibling_level) ){
				foreach($titles as $titles_key => $titles_info){
					$new_menu[$titles_key] = $titles_info;
				}
				$found_sibling = false; //prevent multiple insertions
			}

			$new_menu[$menu_key] = $menu_info;

			if( $menu_key == $sibling ){
				$found_sibling = true;
			}
		}

		//if it's added to the end
		if( $found_sibling ){
			foreach($titles as $titles_key => $titles_info){
				$new_menu[$titles_key] = $titles_info;
			}
		}
		$this->curr_menu_array = $new_menu; 

		return true;
	}

	/*
	 * Insert $titles into $menu as children of $parent
	 *
	 */
	protected function MenuInsert_Child($titles,$parent){

		if( !isset($this->curr_menu_array[$parent]) || !isset($this->curr_menu_array[$parent]['level']) ){
			return false;
		}

		$parent_level = $this->curr_menu_array[$parent]['level'];


		//level adjustment
		$old_level			= \gp\admin\Menu\Tools::GetRootLevel($titles);
		$level_adjustment	= $parent_level - $old_level + 1;
		$titles				= $this->AdjustMovedLevel($titles,$level_adjustment);

		//rebuild menu
		//	insert $titles after parent
		$new_menu = array();
		foreach($this->curr_menu_array as $menu_title => $menu_info){
			$new_menu[$menu_title] = $menu_info;

			if( $menu_title == $parent ){
				foreach($titles as $titles_title => $titles_info){
					$new_menu[$titles_title] = $titles_info;
				}
			}
		}

		$this->curr_menu_array = $new_menu;
		return true;
	}

	protected function AdjustMovedLevel($titles,$level_adjustment){

		foreach($titles as $title => $info){
			$level = 0;
			if( isset($info['level']) ){
				$level = $info['level'];
			}
			$titles[$title]['level'] = min($this->max_level_index,$level + $level_adjustment);
		}
		return $titles;
	}


	/**
	 * Display the current homepage setting
	 *
	 */
	public function HomepageDisplay(){
		global $langmessage, $config;


		if( \gp\admin\Menu\Tools::ResetHomepage() ){
			\gp\admin\Tools::SaveConfig();
		}

		$label = \gp\tool::GetLabelIndex($config['homepath_key']);


		echo '<span class="fa fa-home"></span> ';
		echo $langmessage['Homepage'].': ';
		echo \gp\tool::Link('Admin/Menu/Ajax',$label,'cmd=HomepageSelect','data-cmd="gpabox"');
	}


	public function CacheSettings(){
		global $gp_index, $gp_titles, $gp_menu;

		$this->settings_cache['gp_index'] = $gp_index;
		$this->settings_cache['gp_titles'] = $gp_titles;
		$this->settings_cache['gp_menu'] = $gp_menu;

		if( !$this->is_main_menu ){
			$this->settings_cache['curr_menu_array'] = $this->curr_menu_array;
		}
	}

	public function RestoreSettings(){
		global $gp_index, $gp_titles, $gp_menu;


		if( isset($this->settings_cache['gp_titles']) ){
			$gp_titles = $this->settings_cache['gp_titles'];
		}

		if( isset($this->settings_cache['gp_menu']) ){
			$gp_menu = $this->settings_cache['gp_menu'];
		}

		if( isset($this->settings_cache['gp_index']) ){
			$gp_index = $this->settings_cache['gp_index'];
		}

		if( isset($this->settings_cache['curr_menu_array']) ){
			$this->curr_menu_array = $this->settings_cache['curr_menu_array'];
		}
	}


}
