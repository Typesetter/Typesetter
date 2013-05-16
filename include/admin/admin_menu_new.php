<?php
defined('is_running') or die('Not an entry point...');

/*
 * Page/Menu Manager
 *
 * Uses the following other files
 * 		admin_menu_tools.php
 * 		admin_trash.php
 *
 *
 *
 *
 */



defined('gp_max_menu_level') OR define('gp_max_menu_level',6);

includeFile('admin/admin_menu_tools.php');
common::LoadComponents('sortable');


class admin_menu_new extends admin_menu_tools{

	var $cookie_settings = array();
	var $hidden_levels = array();
	var $search_page = 0;
	var $search_max_per_page = 20;
	var $query_string;

	var $avail_menus = array();
	var $curr_menu_id;
	var $curr_menu_array = false;
	var $is_alt_menu = false;
	var $max_level_index = 3;

	var $main_menu_count;
	var $list_displays = array('search'=>true, 'all'=>true, 'hidden'=>true, 'nomenus'=>true );


	function admin_menu_new(){
		global $langmessage,$page,$config;

		$page->ajaxReplace = array();

		$page->css_admin[] = '/include/css/admin_menu_new.css';

		$page->head_js[] = '/include/thirdparty/js/nestedSortable.js';
		$page->head_js[] = '/include/thirdparty/js/jquery_cookie.js';
		$page->head_js[] = '/include/js/admin_menu_new.js';

		$this->max_level_index = max(3,gp_max_menu_level-1);
		$page->head_script .= 'var max_level_index = '.$this->max_level_index.';';

		$cmd = common::GetCommand();

		$this->avail_menus['gpmenu'] = $langmessage['Main Menu'].' / '.$langmessage['site_map'];
		$this->avail_menus['all'] = $langmessage['All Pages'];
		$this->avail_menus['hidden'] = $langmessage['Not In Main Menu'];
		$this->avail_menus['nomenus'] = $langmessage['Not In Any Menus'];
		$this->avail_menus['search'] = $langmessage['search pages'];

		if( isset($config['menus']) ){
			foreach($config['menus'] as $id => $menu_label){
				$this->avail_menus[$id] = $menu_label;
			}
		}

		//early commands
		switch($cmd){
			case 'altmenu_create':
				$this->AltMenu_Create();
			break;

			case 'rm_menu':
				$this->AltMenu_Remove();
			break;
			case 'alt_menu_rename':
				$this->AltMenu_Rename();
			break;

		}


		//read cookie settings
		if( isset($_COOKIE['gp_menu_prefs']) ){
			parse_str( $_COOKIE['gp_menu_prefs'] , $this->cookie_settings );
		}

		$this->SetMenuID();
		$this->SetMenuArray();
		$this->SetCollapseSettings();
		$this->SetQueryInfo();

		$cmd_after = gpPlugin::Filter('MenuCommand',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		switch($cmd){

			case 'rename_menu_prompt':
				$this->RenameMenuPrompt();
			return;

			//menu creation
			case 'newmenu':
				$this->NewMenu();
			return;

			//rename
			case 'renameform':
				$this->RenameForm(); //will die()
			return;

			case 'renameit':
				$this->RenameFile();
			break;

			case 'hide':
				$this->Hide();
			break;

			case 'drag':
				$this->SaveDrag();
			break;

			case 'trash_page';
			case 'trash':
				$this->MoveToTrash($cmd);
			break;

			case 'add_hidden':
				$this->AddHidden();
			return;
			case 'new_hidden':
				$this->NewHiddenFile();
			break;
			case 'new_redir':
				$this->NewHiddenFile_Redir();
			return;

			case 'copyit':
				$this->CopyPage();
			break;
			case 'copypage':
				$this->CopyForm();
			return;

			// Page Insertion
			case 'insert_before':
			case 'insert_after':
			case 'insert_child':
				$this->InsertDialog($cmd);
			return;

			case 'restore':
				$this->RestoreFromTrash();
			break;

			case 'insert_from_hidden';
				$this->InsertFromHidden();
			break;

			case 'new_file':
				$this->NewFile();
			break;

			//layout
			case 'layout':
			case 'uselayout':
			case 'restorelayout':
				includeFile('tool/Page_Layout.php');
				$page_layout = new page_layout($cmd,'Admin_Menu',$this->query_string);
				if( $page_layout->result() ){
					return;
				}
			break;


			//external links
			case 'new_external':
				$this->NewExternal();
			break;
			case 'edit_external':
				$this->EditExternal();
			return;
			case 'save_external':
				$this->SaveExternal();
			break;


		}

		$this->ShowForm($cmd);

	}

	function Link($href,$label,$query='',$attr='',$nonce_action=false){
		$query = $this->MenuQuery($query);
		return common::Link($href,$label,$query,$attr,$nonce_action);
	}

	function GetUrl($href,$query='',$ampersands=true){
		$query = $this->MenuQuery($query);
		return common::GetUrl($href,$query,$ampersands);
	}

	function MenuQuery($query=''){
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

	function SetQueryInfo(){

		//search page
		if( isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ){
			$this->search_page = (int)$_REQUEST['page'];
		}

		//browse query string
		$this->query_string = $this->MenuQuery();
	}

	function SetCollapseSettings(){
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



	//which menu, not the same order as used for $_REQUEST
	function SetMenuID(){

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

	function SetMenuArray(){
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

		$this->curr_menu_array = gpOutput::GetMenuArray($this->curr_menu_id);
		$this->is_alt_menu = true;
	}


	function SaveMenu($menu_and_pages=false){
		global $dataDir;

		if( $this->is_main_menu ){
			return admin_tools::SavePagesPHP();
		}

		if( $this->curr_menu_array === false ){
			return false;
		}

		if( $menu_and_pages && !admin_tools::SavePagesPHP() ){
			return false;
		}

		$menu_file = $dataDir.'/data/_menus/'.$this->curr_menu_id.'.php';
		return gpFiles::SaveArray($menu_file,'menu',$this->curr_menu_array);
	}




	/*
	 * Primary Display
	 *
	 *
	 */
	function ShowForm(){
		global $langmessage,$page;


		$replace_id = '';
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

			if( isset($_REQUEST['menus']) ){
				$this->GetMenus();
			}

			$page->ajaxReplace[] = array('inner',$replace_id,$content);
			$page->ajaxReplace[] = array('gp_menu_refresh','','');
			return;
		}


		// search form
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post" id="page_search">';
		$_REQUEST += array('q'=>'');
		echo '<input type="text" name="q" size="10" value="'.htmlspecialchars($_REQUEST['q']).'" class="gptext gpinput" /> ';
		echo '<input type="submit" name="cmd" value="'.$langmessage['search pages'].'" class="gpbutton" />';
		echo '<input type="hidden" name="menu" value="search" />';
		echo '</form>';


		$menus = $this->GetAvailMenus('menu');
		$lists = $this->GetAvailMenus('display');


		//heading
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post" id="gp_menu_select_form">';
		echo '<input type="hidden" name="curr_menu" id="gp_curr_menu" value="'.$this->curr_menu_id.'" />';

		echo '<h2>';
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
				}else{
					echo '<option value="'.$menu_id.'">';
				}
				echo $menu_label.'</option>';
			}
		echo '</optgroup>';
		echo '</select>';
		echo '</h2>';

		echo '</form>';


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

		}else{
			echo '<div id="gp_menu_available">';
			echo $content;
			echo '</div>';
		}

		echo '</div>';


		echo '<div class="admin_footnote">';

		echo '<div>';
		echo '<b>'.$langmessage['Menus'].'</b>';
		foreach($menus as $menu_id => $menu_label){
			if( $menu_id == $this->curr_menu_id ){
				echo '<span>'.$menu_label.'</span>';
			}else{
				echo '<span>'.common::Link('Admin_Menu',$menu_label,'menu='.$menu_id,' data-cmd="cnreq"').'</span>';
			}

		}
		echo '<span>'.common::Link('Admin_Menu','+ '.$langmessage['Add New Menu'],'cmd=newmenu','data-cmd="gpabox"').'</span>';
		echo '</div>';

		echo '<div>';
		echo '<b>'.$langmessage['Lists'].'</b>';
		foreach($lists as $menu_id => $menu_label){
			if( $menu_id == $this->curr_menu_id ){
			}else{
			}
			echo '<span>'.common::Link('Admin_Menu',$menu_label,'menu='.$menu_id,'data-cmd="creq"').'</span>';
		}
		echo '</div>';


		//options for alternate menu
		if( $this->is_alt_menu ){
			echo '<div>';
			$label = $menus[$this->curr_menu_id];
			echo '<b>'.$label.'</b>';
			echo '<span>'.common::Link('Admin_Menu',$langmessage['rename'],'cmd=rename_menu_prompt&id='.$this->curr_menu_id,'data-cmd="gpabox"').'</span>';
			$title_attr = sprintf($langmessage['generic_delete_confirm'],'&quot;'.$label.'&quot;');
			echo '<span>'.common::Link('Admin_Menu',$langmessage['delete'],'cmd=rm_menu&id='.$this->curr_menu_id,array('data-cmd'=>'creq','class'=>'gpconfirm','title'=>$title_attr)).'</span>';

			echo '</div>';
		}


		echo '</div>';

		echo '<div class="gpclear"></div>';


	}

	function GetAvailMenus($get_type='menu'){

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


	//we do the json here because we're replacing more than just the content
	function GetMenus(){
		global $page, $GP_MENU_LINKS, $GP_MENU_CLASSES;

		foreach($_REQUEST['menus'] as $id => $menu){

			$info = gpOutput::GetgpOutInfo($menu);

			if( !isset($info['method']) ){
				continue;
			}

			$array = array();
			$array[0] = 'replace';
			$array[1] = '#'.$id;
			gpOutput::$edit_area_id = $id;

			if( !empty($_REQUEST['menuh'][$id]) ){
				$GP_MENU_LINKS = rawurldecode($_REQUEST['menuh'][$id]);
			}
			if( !empty($_REQUEST['menuc'][$id]) ){
				$menu_classes = json_decode( rawurldecode($_REQUEST['menuc'][$id]), true );
				if( is_array($menu_classes) ){
					$GP_MENU_CLASSES = $menu_classes;
				}
			}

			ob_start();
			call_user_func($info['method'],$info['arg'],$info);
			$array[2] = ob_get_clean();

			$page->ajaxReplace[] = $array;

		}
	}



	function OutputMenu(){
		global $langmessage, $gp_titles, $gpLayouts, $config;
		$menu_adjustments_made = false;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].' (Current menu not set)');
			return;
		}

		//get array of titles and levels
		$menu_keys = array();
		$menu_values = array();
		foreach($this->curr_menu_array as $key => $info){
			if( !isset($info['level']) ){
				break;
			}

			//remove deleted titles
			if( !isset($gp_titles[$key]) && !isset($info['url']) ){
				unset($this->curr_menu_array[$key]);
				$menu_adjustments_made = true;
				continue;
			}


			$menu_keys[] = $key;
			$menu_values[] = $info;
		}

		//if the menu is empty (because all the files in it were deleted elsewhere), recreate it with the home page
		if( count($menu_values) == 0 ){
			$this->curr_menu_array = $this->AltMenu_New();
			$menu_keys[] = key($this->curr_menu_array);
			$menu_values[] = current($this->curr_menu_array);
			$menu_adjustments_made = true;
		}


		$prev_layout = false;
		$curr_key = 0;

		$curr_level = $menu_values[$curr_key]['level'];
		$prev_level = 0;


		//for sites that don't start with level 0
		if( $curr_level > $prev_level ){
			$piece = '<li><div>&nbsp;</div><ul>';
			while( $curr_level > $prev_level ){
				echo $piece;
				$prev_level++;
			}
		}



		do{

			echo "\n";

			$class = '';
			$menu_value = $menu_values[$curr_key];
			$menu_key = $menu_keys[$curr_key];
			$curr_level = $menu_value['level'];


			$next_level = 0;
			if( isset($menu_values[$curr_key+1]) ){
				$next_level = $menu_values[$curr_key+1]['level'];
			}

			if( $next_level > $curr_level ){
				$class = 'haschildren';
			}
			if( isset($this->hidden_levels[$menu_key]) ){
				$class .= ' hidechildren';
			}
			if( $curr_level >= $this->max_level_index){
				$class .= ' no-nest';
			}



			//
			$style = '';
			if( $this->is_main_menu ){
				if( isset($gp_titles[$menu_key]['gpLayout'])
					&& isset($gpLayouts[$gp_titles[$menu_key]['gpLayout']]) ){
						$color = $gpLayouts[$gp_titles[$menu_key]['gpLayout']]['color'];
						$style = 'background-color:'.$color.';';
				}elseif( $curr_level == 0 ){
					//$color = $gpLayouts[$config['gpLayout']]['color'];
					//$style = 'border-color:'.$color;
				}
			}
			echo '<li class="'.$class.'" style="'.$style.'">';

			if( $curr_level == 0 ){
				$prev_layout = false;
			}

			$this->ShowLevel($menu_key,$menu_value,$prev_layout);

			if( !empty($gp_titles[$menu_key]['gpLayout']) ){
				$prev_layout = $gp_titles[$menu_key]['gpLayout'];
			}

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

			}elseif( $next_level < $curr_level ){

				while( $next_level < $curr_level ){
					echo '</li></ul>';
					$curr_level--;
				}
				echo '</li>';
			}elseif( $next_level == $curr_level ){
				echo '</li>';
			}

			$prev_level = $curr_level;

		}while( ++$curr_key && ($curr_key < count($menu_keys) ) );

		if( $menu_adjustments_made ){
			$this->SaveMenu(false);
		}
	}

	function ShowLevel($menu_key,$menu_value,$prev_layout){
		global $gp_titles, $gpLayouts;

		$layout = admin_menu_tools::CurrentLayout($menu_key);
		$layout_info = $gpLayouts[$layout];

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
		}
		echo '</div>';
	}


	/**
	 * Show a menu entry if it's an external link
	 *
	 */
	function ShowLevel_External($menu_key,$menu_value){

		$data = array(
				'key'		=>	$menu_key
				,'url'		=>	$menu_value['url']
				,'title'	=>	$menu_value['url']
				,'level'	=>	$menu_value['level']
				);

		if( strlen($data['title']) > 30 ){
			$data['title'] = substr($data['title'],0,30).'...';
		}

		echo '<a class="gp_label sort external" data-cmd="menu_info" data-arg="'.str_replace('&','&amp;',$menu_key).'">';
		echo common::LabelSpecialChars($menu_value['label']);
		$this->MenuData($data);
		echo '</a>';
	}

	function MenuSkeletonExtern(){
		global $langmessage;

		echo '<b>'.$langmessage['Target URL'].'</b>';
		echo '<span>';
		$img = '<img alt="" />';
		echo '<a href="[url]" target="_blank">[title]</a>';
		echo '</span>';

		echo '<b>'.$langmessage['options'].'</b>';
		echo '<span>';

		$img = '<span class="menu_icon page_edit_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['edit'],'cmd=edit_external&key=[key]',array('title'=>$langmessage['edit'],'data-cmd'=>'gpabox'));

		$img = '<span class="menu_icon cut_list_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rm_from_menu'],'cmd=hide&key=[key]',array('title'=>$langmessage['rm_from_menu'],'data-cmd'=>'menupost','class'=>'gpconfirm'));

		echo '</span>';

		$this->InsertLinks();
	}


	/**
	 * Show a menu entry if it's an internal page
	 *
	 */
	function ShowLevel_Title($menu_key,$menu_value,$layout_info){
		global $langmessage, $gp_titles;


		$title = common::IndexToTitle($menu_key);
		$label = common::GetLabel($title);
		$isSpecialLink = common::SpecialOrAdmin($title);



		//get the data for this title
		$data = array(
					'key'			=>	$menu_key
					,'url'			=>	common::GetUrl($title)
					,'level'		=>	$menu_value['level']
					,'title'		=>	$title
					,'special'		=>	$isSpecialLink
					,'has_layout'	=>	!empty($gp_titles[$menu_key]['gpLayout'])
					,'layout_color'	=>	$layout_info['color']
					,'layout_label'	=>	$layout_info['label']
					,'types'		=>	$gp_titles[$menu_key]['type']
					,'opts'			=> ''
					);
		if( !$isSpecialLink ){
			$file = gpFiles::PageFile($title);
			$stats = @stat($file);
			if( $stats ){
				$data += array(
						'size'		=>	admin_tools::FormatBytes($stats['size'])
						,'mtime'	=>	common::date($langmessage['strftime_datetime'],$stats['mtime'])
						);
			}
		}

		ob_start();
		gpPlugin::Action('MenuPageOptions',array($title,$menu_key,$menu_value,$layout_info));
		$menu_options = ob_get_clean();
		if( $menu_options ){
			$data['opts'] = $menu_options;
		}

		echo '<a class="gp_label sort" data-cmd="menu_info" data-arg="'.str_replace('&','&amp;',$menu_key).'">';
		echo common::LabelSpecialChars($label);
		$this->MenuData($data);
		echo '</a>';
	}

	function MenuData($data){
		$data = common::JsonEncode($data);

		echo '<span style="display:none">'.htmlspecialchars($data,ENT_NOQUOTES).'</span>';
	}


	function MenuSkeleton(){
		global $langmessage;

		/*
		 * page options
		 */
		echo '<b>'.$langmessage['page_options'].'</b>';

		echo '<span>';

		$img = '<span class="menu_icon icon_page"></span>';
		echo '<a href="[url]" class="view_edit_link">'.$img.htmlspecialchars($langmessage['view/edit_page']).'</a>';

		$img = '<span class="menu_icon page_edit_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rename/details'],'cmd=renameform&title=[title]',array('title'=>$langmessage['rename/details'],'data-cmd'=>'gpajax'));

		$img = '<span class="menu_icon copy_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['Copy'],'cmd=copypage&title=[title]',array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));

		if( admin_tools::HasPermission('Admin_User') ){
			$img = '<span class="menu_icon icon_user"></span>';
			echo $this->Link('Admin_Users',$img.$langmessage['permissions'],'cmd=file_permissions&index=[key]',array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox'));
		}

		$img = '<span class="menu_icon cut_list_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rm_from_menu'],'cmd=hide&key=[key]',array('title'=>$langmessage['rm_from_menu'],'data-cmd'=>'menupost','class'=>'gpconfirm'));

		$img = '<span class="menu_icon bin_icon"></span>';
		echo $this->Link('Admin_Menu',$img.$langmessage['delete'],'cmd=trash&index=[key]',array('title'=>$langmessage['delete_page'],'data-cmd'=>'menupost','class'=>'gpconfirm not_special'));

		echo '[opts]'; //replaced with the contents of gpPlugin::Action('MenuPageOptions',array($title,$menu_key,$menu_value,$layout_info));

		echo '</span>';


		//layout
		if( $this->is_main_menu ){
			echo '<b>'.$langmessage['layout'].'</b>';
			echo '<span>';

			//has_layout
			$img = '<span class="layout_icon"></span>';
			echo $this->Link('Admin_Menu',$img.'[layout_label]','cmd=layout&index=[key]',' title="'.$langmessage['layout'].'" data-cmd="gpabox" class="has_layout"');

			$img = '<span class="menu_icon undo_icon"></span>';
			echo $this->Link('Admin_Menu',$img.$langmessage['restore'],'cmd=restorelayout&index=[key]',array('data-cmd'=>'postlink','title'=>$langmessage['restore'],'class'=>'has_layout'),'restore');

			//no_layout
			$img = '<span class="layout_icon"></span>';
			echo $this->Link('Admin_Menu',$img.'[layout_label]','cmd=layout&index=[key]',' title="'.$langmessage['layout'].'" data-cmd="gpabox" class="no_layout"');
			echo '</span>';
		}

		$this->InsertLinks();


		//file stats
		echo '<b>'.$langmessage['Page Info'].'</b>';
		echo '<span>';
		echo '<a>'.$langmessage['Slug/URL'].': [title]</a>';
		echo '<a>'.$langmessage['Content Type'].': [types]</a>';
		echo '<a class="not_special">'.$langmessage['File Size'].': [size]</a>';
		echo '<a class="not_special">'.$langmessage['Modified'].': [mtime]</a>';
		echo '<a>Data Index: [key]</a>';

		echo '</span>';

	}

	function FileStats($key,$title,$is_special){
		global $langmessage,$gp_titles;

		echo '<a>'.$langmessage['Slug/URL'].': '.htmlspecialchars($title).'</a>';
		echo '<a>'.$langmessage['Content Type'].': '.str_replace(',',', ',$gp_titles[$key]['type']).'</a>';
		if( !$is_special ){
			$file = gpFiles::PageFile($title);
			$stats = @stat($file);
			if( $stats ){
				$mtime = $stats['mtime'];
				$size = $stats['size'];
				echo '<a>'.$langmessage['File Size'].': '.admin_tools::FormatBytes($size).'</a>';
				echo '<a>'.$langmessage['Modified'].': '.common::date($langmessage['strftime_datetime'],$mtime).'</a>';
			}
		}
		echo '<a>Data Index: '.$key.'</a>';

	}

	/*
	 * insert
	 */
	function InsertLinks(){
		global $langmessage;

		echo '<b>'.$langmessage['insert_into_menu'].'</b>';
		echo '<span>';

		$img = '<span class="menu_icon insert_before_icon"></span>';
		$query = 'cmd=insert_before&insert_where=[key]';
		echo $this->Link('Admin_Menu',$img.$langmessage['insert_before'],$query,array('title'=>$langmessage['insert_before'],'data-cmd'=>'gpabox'));


		$img = '<span class="menu_icon insert_after_icon"></span>';
		$query = 'cmd=insert_after&insert_where=[key]';
		echo $this->Link('Admin_Menu',$img.$langmessage['insert_after'],$query,array('title'=>$langmessage['insert_after'],'data-cmd'=>'gpabox'));


		$img = '<span class="menu_icon insert_after_icon"></span>';
		$query = 'cmd=insert_child&insert_where=[key]';
		echo $this->Link('Admin_Menu',$img.$langmessage['insert_child'],$query,array('title'=>$langmessage['insert_child'],'data-cmd'=>'gpabox','class'=>'insert_child'));
		echo '</span>';
	}

	/**
	 * Get a list of titles matching the search criteria
	 *
	 */
	function GetSearchList(){
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

			$label = common::GetLabelIndex($index);
			if( strpos(strtolower($label),$key) !== false ){
				$show_list[$index] = $title;
				continue;
			}
		}
		return $show_list;
	}

	function SearchDisplay(){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu;

		$Inherit_Info = admin_menu_tools::Inheritance_Info();

		switch($this->curr_menu_id){
			case 'search':
				$show_list = $this->GetSearchList();
			break;
			case 'all':
				$show_list = array_keys($gp_index);
			break;
			case 'hidden':
				$show_list = $this->GetAvailable();
			break;
			case 'nomenus':
				$show_list = $this->GetNoMenus();
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

		if( ($start !== 0) || ($stop < $max) ){
			for( $i = 0; ($i*$this->search_max_per_page) < $max; $i++ ){
				$class = '';
				if( $i == $this->search_page ){
					$class = ' class="current"';
				}
				echo $this->Link('Admin_Menu',($i+1),'page='.$i,'data-cmd="gpajax"'.$class);
			}
		}

		echo $this->Link('Admin_Menu',$langmessage['create_new_file'],'cmd=add_hidden',array('title'=>$langmessage['create_new_file'],'data-cmd'=>'gpajax'));
		echo '</div>';
		$links = ob_get_clean();

		echo $links;

		echo '<table class="bordered">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Child Pages'];
		echo '</th>';
		echo '</tr>';
		echo '</thead>';


		echo '<tbody>';

		if( count($show_list) > 0 ){
			for( $i = $start; $i < $stop; $i++ ){
				$title = $show_list[$i];
				$title_index = $gp_index[$title];

				echo '<tr><td>';

				$label = common::GetLabel($title);
				echo common::Link($title,common::LabelSpecialChars($label));


				//area only display on mouseover
				echo '<div>';
				echo '<b>Options:</b>';
				$img = '<span class="menu_icon page_edit_icon"></span>';
				echo $this->Link('Admin_Menu',$img.$langmessage['rename/details'],'cmd=renameform&title='.urlencode($title),array('title'=>$langmessage['rename/details'],'data-cmd'=>'gpajax'));

				$img = '<span class="menu_icon copy_icon"></span>';
				echo $this->Link('Admin_Menu',$img.$langmessage['Copy'],'cmd=copypage&title='.urlencode($title),array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));

				$layout = admin_menu_tools::CurrentLayout($title_index);
				$layout_info = $gpLayouts[$layout];

				$img = '<span style="background-color:'.$layout_info['color'].';" class="layout_icon"></span>';
				echo $this->Link('Admin_Menu',$img.$layout_info['label'],'cmd=layout&index='.urlencode($title_index),array('title'=>$langmessage['layout'],'data-cmd'=>'gpabox'));

				$is_special = common::SpecialOrAdmin($title);
				if( !$is_special ){
					$img = '<span class="menu_icon bin_icon"></span>';
					echo $this->Link('Admin_Menu',$img.$langmessage['delete'],'cmd=trash&index='.urlencode($title_index),array('title'=>$langmessage['delete_page'],'data-cmd'=>'menupost','class'=>'gpconfirm'));
				}

				gpPlugin::Action('MenuPageOptions',array($title,$title_index,false,$layout_info));

				//stats
				echo '<br/>';
				echo '<b>'.$langmessage['Page Info'].':</b>';
				$this->FileStats($title_index,$title,$is_special);

				echo '</div>';

				echo '</td><td>';

				if( isset($Inherit_Info[$title_index]) && isset($Inherit_Info[$title_index]['children']) ){
					echo $Inherit_Info[$title_index]['children'];
				}elseif( isset($gp_menu[$title_index]) ){
					echo '0';
				}else{
					echo $langmessage['Not In Main Menu'];
				}

				echo '</td></tr>';
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
	 * Get an array of titles that is not represented in any of the menus
	 *
	 */
	function GetNoMenus(){
		global $gp_index;


		//first get all titles in a menu
		$menus = $this->GetAvailMenus('menu');
		$all_keys = array();
		foreach($menus as $menu_id => $label){
			$menu_array = gpOutput::GetMenuArray($menu_id);
			$keys = array_keys($menu_array);
			$all_keys = array_merge($all_keys,$keys);
		}
		$all_keys = array_unique($all_keys);

		//then check $gp_index agains $all_keys
		foreach( $gp_index as $title => $index ){
			if( in_array($index, $all_keys) ){
				continue;
			}
			$avail[] = $title;
		}
		return $avail;
	}

	/**
	 * Get a list of pages that are not in the main menu
	 * @return array
	 */
	function GetAvailable(){
		global $gp_index, $gp_menu;

		$avail = array();
		foreach( $gp_index as $title => $index ){
			if( !isset($gp_menu[$index]) ){
				$avail[$index] = $title;
			}
		}
		return $avail;
	}

	/**
	 * Get a list of pages that are not in the current menu array
	 * @return array
	 */
	function GetAvail_Current(){
		global $gp_index;

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
	function SaveDrag(){
		global $langmessage;

		$this->CacheSettings();
		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$key = $_POST['drag_key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].' (Unknown menu key)');
			return false;
		}


		$moved = $this->RmMoved($key);
		if( !$moved ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}


		// if prev (sibling) set
		$inserted = true;
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
			message($langmessage['OOPS'].'(4)');
			return;
		}

		if( !$this->SaveMenu(false) ){
			$this->RestoreSettings();
			common::AjaxWarning();
			return false;
		}

	}


	/*
	 * Get portion of menu that was moved
	 */
	function RmMoved($key){
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
	 * Move To Trash
	 * Hide special pages
	 *
	 */
	function MoveToTrash($cmd){
		global $gp_titles, $gp_index, $langmessage, $gp_menu;

		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			message($langmessage['OOPS'].' (Invalid Request)');
			return;
		}

		includeFile('admin/admin_trash.php');
		admin_trash::PrepFolder();
		$this->CacheSettings();

		$index =& $_POST['index'];
		$title = common::IndexToTitle($index);

		if( !$title ){
			message($langmessage['OOPS'].' (Invalid Index)');
			return;
		}

		$index = $gp_index[$title];

		if( isset($gp_menu[$index]) ){
			if( count($gp_menu) == 1 ){
				message($langmessage['OOPS'].' (The main menu cannot be empty)');
				return;
			}

			if( !$this->RmFromMenu($index,false) ){
				message($langmessage['OOPS']);
				$this->RestoreSettings();
				return false;
			}
		}

		if( !admin_trash::MoveToTrash_File($title,$index,$trash_data) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		unset($gp_titles[$index]);
		unset($gp_index[$title]);

		if( !admin_trash::ModTrashData($trash_data,null) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			$this->RestoreSettings();
			return false;
		}

		if( $cmd == 'trash_page' ){
			$link = common::GetUrl('Admin_Trash');
			message(sprintf($langmessage['MOVED_TO_TRASH'],$link));
		}


		//delete the file in /_pages
		$file = gpFiles::PageFile($title);
		if( file_exists($file) ){
			unlink($file);
		}

		return true;
	}


	/*
	 *	Remove key from curr_menu_array
	 * 	Adjust children levels if necessary
	 */
	function RmFromMenu($search_key,$curr_menu=true){
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

		$curr_info = $values[$insert_key];
		$curr_level = $curr_info['level'];

		unset($keys[$insert_key]);
		$keys = array_values($keys);

		unset($values[$insert_key]);
		$values = array_values($values);


		//adjust levels of children
		$prev_level = -1;
		if( isset($values[$insert_key-1]) ){
			$prev_level = $values[$insert_key-1]['level'];
		}
		$moved_one = true;
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



	/*
	 * Rename
	 *
	 */
	function RenameForm(){
		global $langmessage, $gp_index;

		includeFile('tool/Page_Rename.php');

		//prepare variables
		$title =& $_REQUEST['title'];

		if( !isset($gp_index[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}

		$action = $this->GetUrl('Admin_Menu');
		gp_rename::RenameForm($title,$action);
	}

	function RenameFile(){
		global $langmessage, $gp_index;

		includeFile('tool/Page_Rename.php');


		//prepare variables
		$title =& $_REQUEST['title'];
		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS'].' (R0)');
			return false;
		}

		gp_rename::RenameFile($title);
	}

	function Hide(){
		global $langmessage;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}
		if( count($this->curr_menu_array) == 1 ){
			message($langmessage['OOPS'].' (The menu cannot be empty)');
			return false;
		}

		$this->CacheSettings();
		$key = $_POST['key']; //using gplinks.menupost()
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}

		if( !$this->RmFromMenu($key) ){
			message($langmessage['OOPS'].'(4)');
			$this->RestoreSettings();
			return false;
		}

		if( $this->SaveMenu(false) ){
			return true;
		}

		message($langmessage['OOPS'].'(5)');
		$this->RestoreSettings();
		return false;
	}

	/**
	 * Display a user form for adding a new page that won't be immediately added to a menu
	 *
	 */
	function AddHidden(){
		global $langmessage,$page;

		ob_start();

		$title = '';
		if( isset($_REQUEST['title']) ){
			$title = $_REQUEST['title'];
		}
		echo '<div class="inline_box">';

		echo '<h3>'.$langmessage['new_file'].'</h3>';

		echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
		echo '<table class="bordered full_width">';

		echo '<tr><th colspan="2">'.$langmessage['options'].'</th></tr>';

		echo '<tr><td>'.$langmessage['label'].'</td>';
		echo '<td><input type="text" name="title" maxlength="100" size="50" value="'.htmlspecialchars($title).'" class="gpinput" /></td>';
		echo '</tr>';

		echo '<tr><td>'.$langmessage['Content Type'].'</td>';
			echo '<td>';

			includeFile('tool/editing_page.php');
			editing_page::SectionTypes();

			echo '</td></tr>';

		echo '</table>';

			echo '<p>';

			if( isset($_GET['redir']) ){
				echo '<input type="hidden" name="cmd" value="new_redir" />';
			}else{
				echo '<input type="hidden" name="cmd" value="new_hidden" />';
			}
			echo '<input type="submit" name="aaa" value="'.$langmessage['create_new_file'].'" class="gppost gpsubmit"/> '; //class="menupost" is not needed because we're adding hidden files
			echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
			echo '</p>';

		echo '</form>';
		echo '</div>';

		$content = ob_get_clean();
		$page->ajaxReplace[] = array('admin_box_data','',$content);
	}


	/**
	 * Display the dialog for inserting pages into a menu
	 *
	 */
	function InsertDialog($cmd){
		global $langmessage,$page;

		includeFile('admin/admin_trash.php');

		echo '<div class="inline_box">';

			echo '<div class="layout_links">';
				echo ' <a href="#gp_Insert_New" data-cmd="tabs" class="selected">'. $langmessage['new_file'] .'</a>';
				echo ' <a href="#gp_Insert_Hidden" data-cmd="tabs">'. $langmessage['Available Pages'] .'</a>';
				echo ' <a href="#gp_Insert_Deleted" data-cmd="tabs">'. $langmessage['restore_from_trash'] .'</a>';
				echo ' <a href="#gp_Insert_External" data-cmd="tabs">'. $langmessage['External Link'] .'</a>';
			echo '</div>';

			// Insert New
			echo '<div id="gp_Insert_New">';

				echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
				echo '<table class="bordered full_width">';

				echo '<tr><th>&nbsp;</th><th>&nbsp;</th></tr>';

				echo '<tr>';
					echo '<td>'.$langmessage['label'].'</td>';
					echo '<td><input type="text" name="title" maxlength="100" value="" size="50" class="gpinput" /></td>';
					echo '</tr>';

				echo '<tr>';
					echo '<td>'.$langmessage['Content Type'].'</td>';
					echo '<td>';

					includeFile('tool/editing_page.php');
					editing_page::SectionTypes();

					echo '</td>';
					echo '</tr>';

				echo '</table>';

					echo '<p>';
					echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
					echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';

					echo '<input type="hidden" name="cmd" value="new_file" />';
					echo '<input type="submit" name="aaa" value="'.$langmessage['create_new_file'].'" class="menupost gpsubmit"/> ';
					echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
					echo '</p>';

				echo '</form>';
			echo '</div>';

			// Insert Hidden
			echo '<div id="gp_Insert_Hidden" class="nodisplay">';
			if( $this->is_main_menu ){
				$avail = $this->GetAvailable();
			}else{
				$avail = $this->GetAvail_Current();
			}

			if( count($avail) == 0 ){
				echo '<p>';
				echo $langmessage['Empty'];
				echo '</p>';
			}else{

				echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';

				echo '<table class="bordered full_width">';
				echo '<thead><tr><th>';
				echo $langmessage['title'];
				echo ' &nbsp; <input type="text" name="search" value="" class="gpinput gpsearch" />';
				echo '</th><th class="gp_right">';
				echo $langmessage['insert_into_menu'];
				echo '</th></tr></thead>';
				echo '</table>';
				echo '<ul class="gpui-scrolllist ui-menu ui-widget ui-widget-content ui-corner-all">';

				//sort by label
				$sort_avail = array();
				foreach($avail as $index => $title){
					$sort_avail[$index] = common::GetLabel($title);
				}
				natcasesort($sort_avail);

				foreach($sort_avail as $index => $label){
					echo '<li class="ui-menu-item">';
					echo '<label class="ui-corner-all">';
					echo '<input type="checkbox" name="keys[]" value="'.htmlspecialchars($index).'" />';
					echo common::LabelSpecialChars($label);
					echo '<span class="slug">';
					echo '/'.$avail[$index];
					echo '</span>';
					echo '</label>';
					echo '</li>';
				}

				echo '</ul>';


				echo '<p>';
				echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
				echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';
				echo '<input type="hidden" name="cmd" value="insert_from_hidden"  />';
				echo '<input type="submit" name="" value="'.$langmessage['insert_into_menu'].'" class="menupost gpsubmit" />';
				echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
				echo '</p>';

				echo '</form>';
			}



			echo '</div>';

			// Insert Deleted / Restore from trash
			echo '<div id="gp_Insert_Deleted" class="nodisplay">';

			$trashtitles = admin_trash::TrashFiles();
			if( count($trashtitles) == 0 ){
				echo '<p>'.$langmessage['TRASH_IS_EMPTY'].'</p>';
			}else{

				echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
				echo '<table class="bordered full_width"><thead>';
				echo '<tr><th>'.$langmessage['title'];
				echo ' &nbsp; <input type="text" name="search" value="" class="gpinput gpsearch" />';
				echo '</th><th class="gp_right">';
				echo $langmessage['restore'];
				echo '</th></tr>';
				echo '</thead></table>';


				echo '<ul class="gpui-scrolllist ui-menu ui-widget ui-widget-content ui-corner-all">';
				foreach($trashtitles as $title => $info){
					echo '<li class="ui-menu-item">';
					echo '<label class="ui-corner-all">';
					echo '<input type="checkbox" name="titles[]" value="'.htmlspecialchars($title).'" />';

					echo $info['label'];
					echo '<span class="slug">';
					echo '/'.$title;
					echo '</span>';

					echo '</label></li>';
				}

				echo '</ul>';


				echo '<p>';
				echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
				echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';
				echo '<input type="hidden" name="cmd" value="restore"  />';
				echo '<input type="submit" name="" value="'.$langmessage['restore'].'" class="menupost gpsubmit" />';
				echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
				echo '</p>';

				echo '</form>';
			}
			echo '</div>';


			//Insert External
			echo '<div id="gp_Insert_External" class="nodisplay">';


				$args['insert_how'] = $cmd;
				$args['insert_where'] = $_GET['insert_where'];
				$this->ExternalForm('new_external',$langmessage['insert_into_menu'],$args);

			echo '</div>';


		echo '</div>';

	}

	/**
	 * Insert pages into the current menu from existing pages that aren't in the menu
	 *
	 */
	function InsertFromHidden(){
		global $langmessage, $gp_index;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].' (Menu not set)');
			return false;
		}

		$this->CacheSettings();

		//get list of titles from submitted indexes
		$titles = array();
		if( isset($_POST['keys']) ){
			foreach($_POST['keys'] as $index){
				if( $title = common::IndexToTitle($index) ){
					$titles[$index]['level'] = 0;
				}
			}
		}

		if( count($titles) == 0 ){
			message($langmessage['OOPS'].' (Nothing selected)');
			$this->RestoreSettings();
			return false;
		}

		if( !$this->MenuInsert($titles,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS'].' (Insert Failed)');
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS'].' (Save Failed)');
			$this->RestoreSettings();
			return false;
		}

	}

	/**
	 * Add titles to the current menu from the trash
	 *
	 */
	function RestoreFromTrash(){
		global $langmessage, $gp_index;


		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['titles']) ){
			message($langmessage['OOPS'].' (Nothing Selected)');
			return false;
		}

		$this->CacheSettings();
		includeFile('admin/admin_trash.php');

		$titles_lower = array_change_key_case($gp_index,CASE_LOWER);
		$titles = array();
		$exists = array();

		foreach($_POST['titles'] as $title){

			$new_title = admin_tools::CheckPostedNewPage($title,$message);
			if( !$new_title ){
				$exists[] = $title;
				continue;
			}
			$titles[$title] = array();
		}

		$menu = admin_trash::RestoreTitles($titles);

		if( count($exists) > 0 ){
			message($langmessage['TITLES_EXIST'].implode(', ',$exists));

			if( count($menu) == 0 ){
				return false; //prevent multiple messages
			}
		}

		if( count($menu) == 0 ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}


		if( !$this->MenuInsert($menu,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(true) ){
			message($langmessage['OOPS'].' (Not Saved)');
			$this->RestoreSettings();
			return false;
		}

		admin_trash::ModTrashData(null,$titles);
	}

	function NewHiddenFile_Redir(){
		global $page;

		$new_index = $this->NewHiddenFile();
		if( $new_index === false ){
			return;
		}

		$title = common::IndexToTitle($new_index);

		//redirect to title
		$url = common::AbsoluteUrl($title,'',true,false);
		$page->ajaxReplace[] = array('location',$url,0);
	}


	function NewHiddenFile(){
		global $langmessage;

		$this->CacheSettings();

		$new_index = $this->CreateNew();
		if( $new_index === false ){
			return false;
		}


		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}
		message($langmessage['SAVED']);
		$this->search_page = 0; //take user back to first page where the new page will be displayed
		return $new_index;
	}

	function NewFile(){
		global $langmessage;
		$this->CacheSettings();


		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(0)');
			return false;
		}

		$neighbor = $_POST['insert_where'];
		if( !isset($this->curr_menu_array[$neighbor]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}


		$new_index = $this->CreateNew();
		if( $new_index === false ){
			return false;
		}

		$insert = array();
		$insert[$new_index] = array();

		if( !$this->MenuInsert($insert,$neighbor,$_POST['insert_how']) ){
			message($langmessage['OOPS'].' (Not Inserted)');
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(true) ){
			message($langmessage['OOPS'].' (Not Saved)');
			$this->RestoreSettings();
			return false;
		}
	}

	/**
	 * Create a new page from a user post
	 *
	 */
	function CreateNew(){
		global $gp_index, $gp_titles, $langmessage;
		includeFile('tool/editing_page.php');
		includeFile('tool/editing.php');

		$title = $_POST['title'];
		$title = admin_tools::CheckPostedNewPage($title,$message);
		if( $title === false ){
			message($message);
			return false;
		}

		$type = $_POST['content_type'];
		$section = gp_edit::DefaultContent($type);

		if( $section['content'] === false ){
			return false;
		}

		$label = admin_tools::PostedLabel($_POST['title']);

		if( $type == 'text' ){
			$section['content'] = '<h2>'.strip_tags($_POST['title']).'</h2>'.$section['content'];
		}

		//add to $gp_index first!
		$index = common::NewFileIndex();
		$gp_index[$title] = $index;

		if( !gpFiles::NewTitle($title,$section,$type) ){
			message($langmessage['OOPS'].' (cn1)');
			unset($gp_index[$title]);
			return false;
		}

		//add to gp_titles
		$new_titles = array();
		$new_titles[$index]['label'] = $label;
		$new_titles[$index]['type'] = $type;
		$gp_titles += $new_titles;

		return $index;
	}


	function MenuInsert($titles,$neighbor,$insert_how){
		switch($insert_how){

			case 'insert_before':
			return $this->MenuInsert_Before($titles,$neighbor);

			case 'insert_after':
			return $this->MenuInsert_After($titles,$neighbor);

			case 'insert_child':
			return $this->MenuInsert_After($titles,$neighbor,1);
		}

		return false;
	}



	/**
	 * Insert titles into menu
	 *
	 */
	function MenuInsert_Before($titles,$sibling){

		$old_level = $this->GetRootLevel($titles);

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
	function MenuInsert_After($titles,$sibling,$level_adjustment=0){

		if( !isset($this->curr_menu_array[$sibling]) || !isset($this->curr_menu_array[$sibling]['level']) ){
			return false;
		}

		$sibling_level = $this->curr_menu_array[$sibling]['level'];

		//level adjustment
		$old_level = $this->GetRootLevel($titles);
		$level_adjustment += $sibling_level - $old_level;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);


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
	function MenuInsert_Child($titles,$parent){

		if( !isset($this->curr_menu_array[$parent]) || !isset($this->curr_menu_array[$parent]['level']) ){
			return false;
		}

		$parent_level = $this->curr_menu_array[$parent]['level'];


		//level adjustment
		$old_level = $this->GetRootLevel($titles);
		$level_adjustment = $parent_level - $old_level + 1;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);

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

	function AdjustMovedLevel($titles,$level_adjustment){

		foreach($titles as $title => $info){
			$level = 0;
			if( isset($info['level']) ){
				$level = $info['level'];
			}
			$titles[$title]['level'] = min($this->max_level_index,$level + $level_adjustment);
		}
		return $titles;
	}

	function GetRootLevel($menu){
		reset($menu);
		$info = current($menu);
		if( isset($info['level']) ){
			return $info['level'];
		}
		return 0;
	}




	/*
	 * Alternate Menus
	 *
	 *
	 *
	 */

	function IsAltMenu($id){
		global $config;
		return isset($config['menus'][$id]);
	}

	function AltMenu_Rename(){
		global $langmessage,$config;

		$menu_id =& $_POST['id'];

		if( !$this->IsAltMenu($menu_id) ){
			message($langmessage['OOPS']);
			return;
		}

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$config['menus'][$menu_id] = $menu_name;
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}else{
			$this->avail_menus[$menu_id] = $menu_name;
		}


	}

	/**
	 * Display a form for editing the name of an alternate menu
	 */
	function RenameMenuPrompt(){
		global $langmessage;

		$menu_id =& $_GET['id'];

		if( !$this->IsAltMenu($menu_id) ){
			echo '<div class="inline_box">';
			echo $langmessage['OOPS'];
			echo '</div>';
			return;
		}

		$menu_name = $this->avail_menus[$menu_id];

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="cmd" value="alt_menu_rename" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($menu_id).'" />';

		echo '<h3>';
		echo $langmessage['rename'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" value="'.htmlspecialchars($menu_name).'" class="gpinput" />';
		echo '</p>';


		echo '<p>';
		echo '<input type="submit" name="aa" value="'.htmlspecialchars($langmessage['continue']).'" class="gpsubmit" />';
		echo ' <input type="submit" value="'.htmlspecialchars($langmessage['cancel']).'" class="admin_box_close gpcancel"/> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}

	/**
	 * Display a form for creating a new menu
	 */
	function NewMenu(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="cmd" value="altmenu_create" />';

		echo '<h3>';
		echo $langmessage['Add New Menu'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" class="gpinput" />';
		echo '</p>';

		echo '<p>';

		echo '<input type="submit" name="aa" value="'.htmlspecialchars($langmessage['continue']).'" class="gpsubmit" />';
		echo ' <input type="submit" value="'.htmlspecialchars($langmessage['cancel']).'" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}


	function AltMenu_Create(){
		global $config, $langmessage, $dataDir;

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$new_menu = $this->AltMenu_New();

		//get next index
		$index = 0;
		if( isset($config['menus']) && is_array($config['menus']) ){
			foreach($config['menus'] as $id => $label){
				$id = substr($id,1);
				$index = max($index,$id);
			}
		}
		$index++;
		$id = 'm'.$index;

		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( !gpFiles::SaveArray($menu_file,'menu',$new_menu) ){
			message($langmessage['OOPS'].' (Menu Not Saved)');
			return false;
		}

		$config['menus'][$id] = $menu_name;
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS'].' (Config Not Saved)');
		}else{
			$this->avail_menus[$id] = $menu_name;
			$this->curr_menu_id = $id;
		}
	}

	//create a menu with one file
	function AltMenu_New(){
		global $gp_menu, $gp_titles;

		if( count($gp_menu) ){
			reset($gp_menu);
			$first_index = key($gp_menu);
		}elseif( count($gp_titles ) ){
			reset($gp_titles);
			$first_index = key($gp_titles);
		}

		$new_menu[$first_index] = array('level'=>0);
		return $new_menu;
	}

	function AltMenu_NewName(){
		global $langmessage;

		$menu_name = gp_edit::CleanTitle($_POST['menu_name'],' ');
		if( empty($menu_name) ){
			message($langmessage['OOPS'].' (Empty Name)');
			return false;
		}

		if( array_search($menu_name,$this->avail_menus) !== false ){
			message($langmessage['OOPS'].' (Name Exists)');
			return false;
		}

		return $menu_name;
	}



	function AltMenu_Remove(){
		global $langmessage,$config,$dataDir;

		$menu_id =& $_POST['id'];
		if( !$this->IsAltMenu($menu_id) ){
			message($langmessage['OOPS']);
			return;
		}

		$menu_file = $dataDir.'/data/_menus/'.$menu_id.'.php';

		unset($config['menus'][$menu_id]);
		unset($this->avail_menus[$menu_id]);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}

		message($langmessage['SAVED']);

		//delete menu file
		$menu_file = $dataDir.'/data/_menus/'.$menu_id.'.php';
		if( file_exists($menu_file) ){
			unlink($menu_file);
		}


	}



	/*
	 * External Links
	 *
	 *
	 */
	function ExternalForm($cmd,$submit,$args){
		global $langmessage;

		//these aren't all required for each usage of ExternalForm()
		$args += array(
					'url'=>'http://',
					'label'=>'',
					'title_attr'=>'',
					'insert_how'=>'',
					'insert_where'=>'',
					'key'=>''
					);


		echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($args['insert_how']).'" />';
		echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($args['insert_where']).'" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($args['key']).'" />';

		echo '<table class="bordered full_width">';

		echo '<tr>';
			echo '<th>&nbsp;</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['Target URL'].'</td>';
			echo '<td>';
			echo '<input type="text" name="url" value="'.$args['url'].'" class="gpinput"/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['label'].'</td>';
			echo '<td>';
			echo '<input type="text" name="label" value="'.common::LabelSpecialChars($args['label']).'" class="gpinput"/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['title attribute'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title_attr" value="'.$args['title_attr'].'" class="gpinput"/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['New_Window'].'</td>';
			echo '<td>';
			if( isset($args['new_win']) ){
				echo '<input type="checkbox" name="new_win" value="new_win" checked="checked" />';
			}else{
				echo '<input type="checkbox" name="new_win" value="new_win" />';
			}
			echo '</td>';
			echo '</tr>';


		echo '</table>';

		echo '<p>';

		echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
		echo '<input type="submit" name="" value="'.$submit.'" class="menupost gpsubmit" /> ';
		echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '</form>';
	}


	/**
	 * Edit an external link entry in the current menu
	 *
	 */
	function EditExternal(){
		global $langmessage;

		$key =& $_GET['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].' (Current menu not set)');
			return false;
		}

		$info = $this->curr_menu_array[$key];
		$info['key'] = $key;

		echo '<div class="inline_box">';

		echo '<h3>'.$langmessage['External Link'].'</h3>';

		$this->ExternalForm('save_external',$langmessage['save'],$info);

		echo '</div>';
	}


	/**
	 * Save changes to an external link entry in the current menu
	 *
	 */
	function SaveExternal(){
		global $langmessage;

		$key =& $_POST['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].' (Current menu not set)');
			return false;
		}
		$level = $this->curr_menu_array[$key]['level'];

		$array = $this->ExternalPost();
		if( !$array ){
			message($langmessage['OOPS'].' (1)');
			return;
		}

		$this->CacheSettings();

		$array['level'] = $level;
		$this->curr_menu_array[$key] = $array;

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS'].' (Menu Not Saved)');
			$this->RestoreSettings();
			return false;
		}

	}

	/**
	 * Save a new external link in the current menu
	 *
	 */
	function NewExternal(){
		global $langmessage;

		$this->CacheSettings();
		$array = $this->ExternalPost();

		if( !$array ){
			message($langmessage['OOPS'].' (Invalid Request)');
			return;
		}

		$key = $this->NewExternalKey();
		$insert[$key] = $array;

		if( !$this->MenuInsert($insert,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS'].' (Not inserted)');
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS'].' (Menu not saved)');
			$this->RestoreSettings();
			return false;
		}
	}

	/**
	 * Check the values of a post with external link values
	 *
	 */
	function ExternalPost(){

		$array = array();
		if( empty($_POST['url']) || $_POST['url'] == 'http://' ){
			return false;
		}
		$array['url'] = htmlspecialchars($_POST['url']);

		if( !empty($_POST['label']) ){
			$array['label'] = admin_tools::PostedLabel($_POST['label']);
		}
		if( !empty($_POST['title_attr']) ){
			$array['title_attr'] = htmlspecialchars($_POST['title_attr']);
		}
		if( isset($_POST['new_win']) && $_POST['new_win'] == 'new_win' ){
			$array['new_win'] = true;
		}
		return $array;
	}

	function NewExternalKey(){

		$num_index = 0;
		do{
			$new_key = '_'.base_convert($num_index,10,36);
			$num_index++;
		}while( isset($this->curr_menu_array[$new_key]) );

		return $new_key;
	}

	/**
	 * Display a form for copying a page
	 *
	 */
	function CopyForm(){
		global $langmessage, $gp_index, $page;

		$from_title = $_REQUEST['title'];
		if( !isset($gp_index[$from_title]) ){
			message($langmessage['OOPS_TITLE']);
			return false;
		}

		$from_label = common::GetLabel($from_title);
		$from_label = common::LabelSpecialChars($from_label);

		echo '<div class="inline_box">';
		echo '<form method="post" action="'.common::GetUrl('Admin_Menu').'">';
		if( isset($_REQUEST['redir']) ){
			echo '<input type="hidden" name="redir" value="redir"/> ';
		}
		echo '<input type="hidden" name="from_title" value="'.htmlspecialchars($from_title).'"/> ';
		echo '<table class="bordered full_width" id="gp_rename_table">';

		echo '<thead><tr><th colspan="2">';
		echo $langmessage['Copy'];
		echo '</th></tr></thead>';

		echo '<tr class="line_row"><td>';
		echo $langmessage['from'];
		echo '</td><td>';
		echo $from_label;
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['to'];
		echo '</td><td>';
		echo '<input type="text" name="title" maxlength="100" size="50" value="'.$from_label.'" class="gpinput" />';
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="copyit"/> ';
		echo '<input type="submit" name="" value="'.$langmessage['continue'].'" class="gppost gpsubmit"/>';
		echo '<input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Perform a page copy
	 *
	 */
	function CopyPage(){
		global $gp_index, $gp_titles, $page, $langmessage;

		//existing page info
		$from_title = $_POST['from_title'];
		if( !isset($gp_index[$from_title]) ){
			message($langmessage['OOPS_TITLE']);
			return false;
		}
		$from_index = $gp_index[$from_title];
		$info = $gp_titles[$from_index];


		//check the new title
		$title = $_POST['title'];
		$title = admin_tools::CheckPostedNewPage($title,$message);
		if( $title === false ){
			message($message);
			return false;
		}

		//get the existing content
		$from_file = gpFiles::PageFile($from_title);
		$contents = file_get_contents($from_file);


		//add to $gp_index first!
		$index = common::NewFileIndex();
		$gp_index[$title] = $index;
		$file = gpFiles::PageFile($title);

		if( !gpFiles::Save($file,$contents) ){
			message($langmessage['OOPS'].' (File not saved)');
			return false;
		}

		//add to gp_titles
		$new_titles = array();
		$new_titles[$index]['label'] = admin_tools::PostedLabel($_POST['title']);
		$new_titles[$index]['type'] = $info['type'];
		$gp_titles += $new_titles;

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (CP2)');
			return false;
		}

		message($langmessage['SAVED']);
		if( isset($_REQUEST['redir']) ){
			$url = common::AbsoluteUrl($title,'',true,false);
			$page->ajaxReplace[] = array('location',$url,'15000');
			message(sprintf($langmessage['will_redirect'],common::Link_Page($title)));
		}

		return $index;
	}


}
