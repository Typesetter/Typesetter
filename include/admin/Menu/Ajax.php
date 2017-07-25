<?php

namespace gp\admin\Menu;

defined('is_running') or die('Not an entry point...');

class Ajax extends \gp\admin\Menu{

	public function RunScript(){

		if( $this->cmd === 'return' ){
			return;
		}

		switch($this->cmd){

			//adding new files
			case 'AddHidden':
				$this->AddHidden();
			return;

			case 'CopyForm':
				$this->CopyForm();
			return;

			case 'CopyPage':
				$this->CopyPage();
			break;


			// Page Insertion
			case 'insert_before':
			case 'insert_after':
			case 'insert_child':
				$this->InsertDialog();
			return;

			case 'NewFile':
				$this->NewFile();
			break;

			case 'InsertFromHidden';
				$this->InsertFromHidden();
			break;

			case 'RestoreFromTrash':
				$this->RestoreFromTrash();
			break;


			//external links
			case 'NewExternal':
				$this->NewExternal();
			break;
			case 'EditExternal':
				$this->EditExternal();
			return;
			case 'SaveExternal':
				$this->SaveExternal();
			break;


			//extra area
			case 'InsertExtra':
				$this->InsertExtra();
			break;


			//edit classes
			case 'ClassesForm':
				$this->ClassesForm();
			break;
			case 'SaveClasses':
				$this->SaveClasses();
			break;


			//menu editing
			case 'hide':
				$this->Hide();
			break;
			case 'MoveToTrash':
				$this->MoveToTrash();
			break;


			//rename
			case 'renameform':
				$this->RenameForm(); //will die()
			return;
			case 'RenameFile':
				$this->RenameFile();
			break;

			//visibility
			case 'ToggleVisibility':
				$this->ToggleVisibility();
			break;

			//homepage
			case 'HomepageSelect':
				$this->HomepageSelect();
			return;
			case 'HomepageSave':
				$this->HomepageSave();
			return;
		}

		parent::RunScript();
	}


	/**
	 * Display a user form for adding a new page that won't be immediately added to a menu
	 *
	 */
	public function AddHidden(){
		global $langmessage, $gp_index;

		$_REQUEST += array('title'=>'');
		$_REQUEST['gpx_content'] = 'gpabox';

		//reusable format
		ob_start();
		echo '<p>';
		echo '<button type="submit" name="cmd" value="%s" class="gpsubmit gpvalidate" data-cmd="gppost">%s</button>';
		echo '<button class="admin_box_close gpcancel">'.$langmessage['cancel'].'</button>';
		echo '</p>';
		echo '</td></tr>';
		echo '</tbody>';
		$format_bottom = ob_get_clean();


		echo '<div class="inline_box">';

		echo '<div class="layout_links" style="float:right">';
		echo '<a href="#gp_new_copy" data-cmd="tabs" class="selected">'. $langmessage['Copy'] .'</a>';
		echo '<a href="#gp_new_type" data-cmd="tabs">'. $langmessage['Content Type'] .'</a>';
		echo '</div>';


		echo '<h3>'.$langmessage['new_file'].'</h3>';


		echo '<form action="'.$this->GetUrl('Admin/Menu/Ajax').'" method="post">';
		if( isset($_REQUEST['redir']) ){
			echo '<input type="hidden" name="redir" value="redir" />';
		}


		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">'.$langmessage['options'].'</th></tr>';

		//title
		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<input type="text" name="title" maxlength="100" size="50" value="'.htmlspecialchars($_REQUEST['title']).'" class="gpinput full_width" required/>';
		echo '</td></tr>';

		//copy
		echo '<tbody id="gp_new_copy">';
		echo '<tr><td>';
		echo $langmessage['Copy'];
		echo '</td><td>';
		$gp_index_no_special = array();
		foreach( $gp_index as $title => $index ){
			if( strpos(strtolower($index),'special_') !== 0 ){
				$gp_index_no_special[$title] = $index;
			}
		}
		\gp\admin\Menu\Tools::ScrollList($gp_index_no_special);
		echo sprintf($format_bottom,'CopyPage',$langmessage['create_new_file']);


		//content type
		echo '<tr id="gp_new_type" style="display:none"><td>';
		echo str_replace(' ','&nbsp;',$langmessage['Content Type']);
		echo '</td><td>';
		echo '<div id="new_section_links">';
		\gp\Page\Edit::NewSections(true);
		echo '</div>';

		echo sprintf($format_bottom,'NewFile',$langmessage['create_new_file']);
		echo '</form>';
		echo '</div>';
	}


	/**
	 * Message or redirect when file is saved
	 *
	 */
	public function HiddenSaved($new_index){
		global $langmessage;

		$this->search_page = 0; //take user back to first page where the new page will be displayed

		if( isset($_REQUEST['redir']) ){
			$title	= \gp\tool::IndexToTitle($new_index);
			$url	= \gp\tool::AbsoluteUrl($title,'',true,false,true);
			msg(sprintf($langmessage['will_redirect'],\gp\tool::Link_Page($title)));
			$this->page->ajaxReplace[] = array('location',$url,15000);
		}else{
			msg($langmessage['SAVED']);
		}
	}



	/**
	 * Display a form for copying a page
	 *
	 */
	public function CopyForm(){
		global $langmessage, $gp_index;


		$index = $_REQUEST['index'];
		$from_title = \gp\tool::IndexToTitle($index);

		if( !$from_title ){
			msg($langmessage['OOPS_TITLE']);
			return false;
		}

		$from_label = \gp\tool::GetLabel($from_title);
		$from_label = \gp\tool::LabelSpecialChars($from_label);

		echo '<div class="inline_box">';
		echo '<form method="post" action="'.\gp\tool::GetUrl('Admin/Menu/Ajax').'">';
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
		echo '<input type="hidden" name="cmd" value="CopyPage"/> ';
		echo '<input type="submit" name="" value="'.$langmessage['continue'].'" class="gpsubmit" data-cmd="gppost"/>';
		echo '<input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Perform a page copy
	 *
	 */
	public function CopyPage(){
		global $gp_index, $gp_titles, $langmessage, $users, $gpAdmin, $dataDir;

		$this->CacheSettings();

		if( !isset($_POST['from_title']) ){
			msg($langmessage['OOPS'].' (Copy from not selected)');

			if( isset($_POST['insert_how']) ){
				$this->InsertDialog($_POST['insert_how']);
			}else{
				$this->AddHidden();
			}

			return false;
		}

		//existing page info
		$from_title = $_POST['from_title'];
		if( !isset($gp_index[$from_title]) ){
			msg($langmessage['OOPS_TITLE']);
			return false;
		}
		$from_index		= $gp_index[$from_title];
		$info			= $gp_titles[$from_index];


		//check the new title
		$title			= $_POST['title'];
		$title			= \gp\admin\Tools::CheckPostedNewPage($title,$message);
		if( $title === false ){
			msg($message);
			return false;
		}

		//get the existing content
		$from_file		= \gp\tool\Files::PageFile($from_title);
		$contents		= file_get_contents($from_file);


		//add to $gp_index first!
		$index				= \gp\tool::NewFileIndex();
		$gp_index[$title]	= $index;
		$file				= \gp\tool\Files::PageFile($title);

		if( !\gp\tool\Files::Save($file,$contents) ){
			msg($langmessage['OOPS'].' (File not saved)');
			return false;
		}

		//set permissions for copied page
		// msg('gpAdmin = ' . pre($gpAdmin));
		$users = \gp\tool\Files::Get('_site/users');
		$username = $gpAdmin['username'];
		$user_file = $dataDir . '/data/_sessions/' . $users[$username]['file_name'];
		$editing_values = $gpAdmin['editing'];
		if( $editing_values != 'all' && strpos($editing_values, ','.$index.',') === false ){
			$editing_values .= $index.',';
			$gpAdmin['editing'] = $editing_values;
			// save to user session file
			\gp\tool\Files::SaveData($user_file, 'gpAdmin', $gpAdmin);
			// save to users.php
			$users[$username]['editing'] = $editing_values;
			\gp\tool\Files::SaveData('_site/users', 'users', $users);
		}

		//add to gp_titles
		$new_titles						= array();
		$new_titles[$index]['label']	= \gp\admin\Tools::PostedLabel($_POST['title']);
		$new_titles[$index]['type']		= $info['type'];
		$gp_titles						+= $new_titles;


		//add to menu
		$insert = array();
		$insert[$index] = array();

		if( !$this->SaveNew($insert) ){
			$this->RestoreSettings();
			return false;
		}


		$this->HiddenSaved($index);

		return true;
	}



	/**
	 * Display the dialog for inserting pages into a menu
	 *
	 */
	public function InsertDialog($cmd = null){
		global $langmessage, $gp_index;

		if( is_null($cmd) ){
			$cmd = $this->cmd;
		}

		$_REQUEST['gpx_content'] = 'gpabox';


		//create format of each tab
		ob_start();
		echo '<div id="%s" class="%s">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu/Ajax').'" method="post">';
		echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_REQUEST['insert_where']).'" />';
		echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';

		// echo '<table class="bordered full_width">';
		// echo '<thead><tr><th>&nbsp;</th></tr></thead>';
		// echo '</table>';

		$format_top = ob_get_clean();

		ob_start();
		echo '<p>';
		echo '<button type="submit" name="cmd" value="%s" class="gpsubmit" data-cmd="gppost">%s</button>';
		echo '<button class="admin_box_close gpcancel">'.$langmessage['cancel'].'</button>';
		echo '</p>';
		echo '</form>';
		echo '</div>';
		$format_bottom = ob_get_clean();



		echo '<div class="inline_box">';

			//tabs
			echo '<div class="layout_links">';
			echo   '<a href="#gp_Insert_Copy" data-cmd="tabs" class="selected">'. $langmessage['Copy'] .'</a> ';
			echo   '<a href="#gp_Insert_New" data-cmd="tabs">'. $langmessage['new_file'] .'</a> ';
			echo   '<a href="#gp_Insert_Hidden" data-cmd="tabs">'. $langmessage['Available'] .'</a> ';
			echo   '<a href="#gp_Insert_External" data-cmd="tabs">'. $langmessage['External Link'] .'</a> ';
			echo   '<a href="#gp_Insert_Deleted" data-cmd="tabs">'. $langmessage['trash'] .'</a> ';
			echo   '<a href="#gp_Insert_Extra" data-cmd="tabs">'. $langmessage['theme_content'] .'</a> ';
			echo '</div>';


			// Copy
			echo sprintf($format_top,'gp_Insert_Copy','');
			echo '<table class="bordered full_width">';
			echo '<tr><td>';
			echo $langmessage['label'];
			echo '</td><td>';
			echo '<input type="text" name="title" maxlength="100" size="50" value="" class="gpinput full_width" required/>';
			echo '</td></tr>';
			echo '<tr><td>';
			echo $langmessage['Copy'];
			echo '</td><td>';
			$copy_list = array();
			foreach($gp_index as $k => $v){
				if( strpos($v,'special_') === 0 ){
					continue;
				}
				$copy_list[$k] = $v;
			}
			\gp\admin\Menu\Tools::ScrollList($copy_list);
			echo '</td></tr>';
			echo '</table>';
			echo sprintf($format_bottom,'CopyPage',$langmessage['Copy']);


			// Insert New
			echo sprintf($format_top,'gp_Insert_New','nodisplay');
			echo '<table class="bordered full_width">';
			echo '<tr><td>';
			echo $langmessage['label'];
			echo '</td><td>';
			echo '<input type="text" name="title" maxlength="100" value="" size="50" class="gpinput full_width" required />';
			echo '</td></tr>';

			echo '<tr><td>';
			echo $langmessage['Content Type'];
			echo '</td><td>';
			echo '<div id="new_section_links">';
			\gp\Page\Edit::NewSections(true);
			echo '</div>';
			echo '</td></tr>';
			echo '</table>';
			echo sprintf($format_bottom,'NewFile',$langmessage['create_new_file']);


			// Insert Hidden
			$avail = $this->GetAvail_Current();

			if( !empty($avail) ){
				echo sprintf($format_top,'gp_Insert_Hidden','nodisplay');
				$avail = array_flip($avail);
				\gp\admin\Menu\Tools::ScrollList($avail,'keys[]','checkbox',true);
				echo sprintf($format_bottom,'InsertFromHidden',$langmessage['insert_into_menu']);
			}



			// Insert Deleted / Restore from trash
			$scroll_list = $this->TrashScrolllist();
			if( !empty($scroll_list) ){
				echo sprintf($format_top,'gp_Insert_Deleted','nodisplay');
				echo $scroll_list;
				echo sprintf($format_bottom,'RestoreFromTrash',$langmessage['restore_from_trash']);
			}


			//Insert External
			echo '<div id="gp_Insert_External" class="nodisplay">';
			$args					= array();
			$args['insert_how']		= $cmd;
			$args['insert_where']	= $_REQUEST['insert_where'];
			$this->ExternalForm('NewExternal',$langmessage['insert_into_menu'],$args);
			echo '</div>';


			//Insert Extra
			$areas = $this->GetExtraAreas();
			// msg("Areas: " . pre($areas));
			if( !empty($areas) ){
				echo sprintf($format_top,'gp_Insert_Extra','nodisplay');
				echo '<p style="padding:6px 10px; background:#f1f1f1;">';
				echo '<i class="fa fa-warning" style="display:block; float:left; font-size:2em; line-height:1.33em; margin:0 0.5em 0 0.2em;"></i>';
				echo 'Outputs an Extra Content Area at the current position <strong>in the menu</strong>. ';
				echo 'This way you may add anything from simple separators to subheads or even images or forms.<br/> ';
				echo 'This is an advanced feature and requires specific custom CSS to be useful.</p>';
				\gp\admin\Menu\Tools::ScrollListExtra($areas);
				echo sprintf($format_bottom, 'InsertExtra', $langmessage['insert']);
			}


		echo '</div>';

	}


	function GetExtraAreas(){
		global $dataDir;
		$areas			= array();
		$folder 		= $dataDir . '/data/_extra';
		$files			= scandir($folder);
		foreach($files as $file){
			$title	= \gp\admin\Content\Extra::AreaExists($file);
			// msg("file = " . $file . " -> title = " . pre($title));
			if( $title == false ){
				continue;
			}
			$areas[$title] = str_replace('_', ' ', $title);
			/* 
			array(
				'title'			=> $title,
				'file_path'		=> $folder . '/' . $title . '/page.php',
				'draft_path'	=> $folder . '/' . $title . '/draft.php',
				'legacy_path'	=> $folder . '/' . $title . '.php',
			);
			*/
		}
		uksort($areas,'strnatcasecmp');
		return $areas;
	}




	/**
	 * Generate a scroll list selector for trash titles
	 *
	 */
	function TrashScrolllist(){
		global $langmessage;

		$trashtitles = \gp\admin\Content\Trash::TrashFiles();
		if( empty($trashtitles) ){
			return '';
		}

		ob_start();
		echo '<div class="gp_scrolllist"><div>';
		echo '<input type="text" name="search" value="" class="gpsearch" placeholder="'.$langmessage['Search'].'" autocomplete="off" />';
		foreach($trashtitles as $title => $info){
			if( empty($info['label']) ){
				continue;
			}
			echo '<label>';
			echo '<input type="checkbox" name="titles[]" value="'.htmlspecialchars($title).'" />';
			echo '<span>';
			echo $info['label'];
			echo '<span class="slug">';
			if( isset($info['title']) ){
				echo '/'.$info['title'];
			}else{
				echo '/'.$title;
			}
			echo '</span>';
			echo '</span>';
			echo '</label>';
		}
		echo '</div></div>';

		return ob_get_clean();
	}


	/**
	 * Create a new file
	 *
	 */
	public function NewFile(){
		global $langmessage;
		$this->CacheSettings();

		$new_index = \gp\admin\Menu\Tools::CreateNew();
		if( $new_index === false ){
			return false;
		}

		$insert = array();
		$insert[$new_index] = array();

		if( !$this->SaveNew($insert) ){
			$this->RestoreSettings();
			return false;
		}

		$this->HiddenSaved($new_index);
	}


	/**
	 * Insert pages into the current menu from existing pages that aren't in the menu
	 *
	 */
	public function InsertFromHidden(){
		global $langmessage, $gp_index;

		if( is_null($this->curr_menu_array) ){
			msg($langmessage['OOPS'].' (Menu not set)');
			return false;
		}

		$this->CacheSettings();

		//get list of titles from submitted indexes
		$titles = array();
		if( isset($_POST['keys']) ){
			foreach($_POST['keys'] as $index){
				if( \gp\tool::IndexToTitle($index) !== false ){
					$titles[$index]['level'] = 0;
				}
			}
		}

		if( count($titles) == 0 ){
			msg($langmessage['OOPS'].' (Nothing selected)');
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveNew($titles) ){
			$this->RestoreSettings();
			return false;
		}

	}


	/**
	 * Add titles to the current menu from the trash
	 *
	 */
	public function RestoreFromTrash(){
		global $langmessage, $gp_index;


		if( is_null($this->curr_menu_array) ){
			msg($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['titles']) ){
			msg($langmessage['OOPS'].' (Nothing Selected)');
			return false;
		}

		$this->CacheSettings();

		$titles			= array();
		$menu			= \gp\admin\Content\Trash::RestoreTitles($_POST['titles']);


		if( empty($menu) ){
			msg($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}


		if( !$this->SaveNew($menu) ){
			$this->RestoreSettings();
			return false;
		}

		\gp\admin\Content\Trash::ModTrashData(null,$titles);
	}


	/**
	 * Form for adding external link
	 *
	 */
	public function ExternalForm($cmd,$submit,$args){
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


		echo '<form action="'.$this->GetUrl('Admin/Menu/Ajax').'" method="post">';
		echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($args['insert_how']).'" />';
		echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($args['insert_where']).'" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($args['key']).'" />';

		echo '<table class="bordered full_width">';

		// echo '<tr>';
		// echo '<th>&nbsp;</th>';
		// echo '<th>&nbsp;</th>';
		// echo '</tr>';

		echo '<tr><td>';
		echo $langmessage['Target URL'];
		echo '</td><td>';
		echo '<input type="text" name="url" value="'.$args['url'].'" class="gpinput" required />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<input type="text" name="label" value="'.\gp\tool::LabelSpecialChars($args['label']).'" class="gpinput" required />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['title attribute'];
		echo '</td><td>';
		echo '<input type="text" name="title_attr" value="'.$args['title_attr'].'" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['New_Window'];
		echo '</td><td>';
		if( isset($args['new_win']) ){
			echo '<input type="checkbox" name="new_win" value="new_win" checked="checked" />';
		}else{
			echo '<input type="checkbox" name="new_win" value="new_win" />';
		}
		echo '</td></tr>';
		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
		echo '<input type="submit" name="" value="'.$submit.'" class="gpsubmit gpvalidate" data-cmd="gppost"/> ';
		echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '</form>';
	}


	/**
	 * Form for adding/editing custom CSS class names a menu item
	 *
	 */
	public function ClassesForm(){
		global $langmessage; // msg('ma = ' .pre($this->curr_menu_array));

		if( !isset($_REQUEST['index']) || !isset($this->curr_menu_array[$_REQUEST['index']]) ){
			msg($langmessage['OOPS'] . ' (Invalid request or menu key)');
			return;
		}

		$key = $_REQUEST['index'];

		$classes_li = '';
		if( isset($this->curr_menu_array[$key]['classes_li']) ){
			$classes_li = $this->curr_menu_array[$key]['classes_li'];
		}

		if( !isset($_REQUEST['no_a_classes']) ){
			$classes_a = '';
			if( isset($this->curr_menu_array[$key]['classes_a']) ){
				$classes_a = $this->curr_menu_array[$key]['classes_a'];
			}
		}

		$classes_child_ul = '';
		if( isset($this->curr_menu_array[$key]['classes_child_ul']) ){
			$classes_child_ul = $this->curr_menu_array[$key]['classes_child_ul'];
		}


		echo '<div class="inline_box">';
		echo '<form action="' . $this->GetUrl('Admin/Menu/Ajax') . '" method="post">';
		echo '<input type="hidden" name="key" value="' . htmlspecialchars($key) . '" />';

		echo '<h2>' . $langmessage['Menu Output'] . ' - ' . $langmessage['Classes'] . '</h2>';

		echo '<table class="bordered full_width">';
		echo '<tr><th style="width:20%;">Menu Element</th><th>' . $langmessage['Classes'] . '</th></tr>';

		echo '<tr>';
		echo '<td><strong>li</strong></td>';
		echo '<td><input type="text" placeholder="some-custom-li-class another-custom-li-class" ';
		echo 'name="classes_li" value="' . htmlspecialchars($classes_li) . '" class="gpinput" style="width:100%;" /></td>';
		echo '</tr>';

		if( !isset($_REQUEST['no_a_classes']) ){
			echo '<tr>';
			echo '<td>li &gt; <strong>a</strong></td>';
			echo '<td><input type="text" placeholder="custom-a-class another-a-class" ';
			echo 'name="classes_a" value="' . htmlspecialchars($classes_a) . '" class="gpinput" style="width:100%;" /></td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>li &gt; <strong>ul</strong></td>';
		echo '<td><input type="text" placeholder="custom-child-ul-class another-child-ul-class" ';
		echo 'name="classes_child_ul" value="' . htmlspecialchars($classes_child_ul) . '" class="gpinput" style="width:100%;" /></td>';
		echo '</tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="SaveClasses" />';
		echo '<input type="submit" name="" value="' . $langmessage['save'] . '" class="gpsubmit" data-cmd="gppost"/> ';
		echo '<input type="submit" value="' . $langmessage['cancel'] . '" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Save posted custom CSS class name(s) for menu item
	 *
	 */
	public function SaveClasses(){
		global $langmessage;

		if( !isset($_POST['key']) || !isset($_POST['classes_li']) || !isset($_POST['classes_a']) || !isset($_POST['classes_child_ul']) ){
			msg($langmessage['OOPS'] . ' (Invalid request)');
			return;
		}

		$key = $_POST['key'];

		if( !isset($this->curr_menu_array[$key]) ){
			msg($langmessage['OOPS'] . ' (Invalid menu key)');
			return;
		}

		$this->CacheSettings();

		$this->curr_menu_array[$key]['classes_li']			= $this->ValidClasses($_POST['classes_li']);
		$this->curr_menu_array[$key]['classes_a']			= $this->ValidClasses($_POST['classes_a']);
		$this->curr_menu_array[$key]['classes_child_ul']	= $this->ValidClasses($_POST['classes_child_ul']);

		if( !$this->SaveMenu(false) ){
			msg($langmessage['OOPS'].' (Menu Not Saved)');
			$this->RestoreSettings();
			return false;
		}

	}



	/**
	 * Removes invalid CSS class names
	 * Returns only valid CSS class names
	 * Displays error/remove msg for invalid class names
	 * @param classes (space separated string or array)
	 * @return valid_classes (string or array, depending on passed argument type)
	 *
	 */
	public function ValidClasses($classes){
		global $langmessage;

		$arg_type = gettype($classes);

		if( $arg_type != 'string' && $arg_type != 'array' ){
			msg($langmessage['OOPS'].' (Wrong type <em>' . $arg_type . '</em>, array or string expected)');
			return false;
		}
		if( empty($classes) ){
			return $classes;
		}

		if( $arg_type == 'string' ){
			$classes = explode(' ', $classes);
		}

 		$valid_classes = array();
		foreach( $classes as $classname ){
			if( $classname == ' ' || empty($classname) ){
				// skip leftovers from multiple space chars
				continue;
			}
			// $classname = trim($classname);
			if( !preg_match("/^([a-z_]|-[a-z_-])[a-z\d_-]*$/i", $classname) ){
				msg('<em>' . htmlspecialchars($classname) . '</em> is not a valid CSS class name and was removed.');
				continue;
			}
			$valid_classes[] = $classname;
		}

		if( $arg_type == 'string' ){
			$valid_classes = implode(' ', $valid_classes);
		}

		return $valid_classes;
	}



	/**
	 * Place an Extra Content Area inside the current menu
	 *
	 */
	public function InsertExtra(){
		global $gp_menu, $langmessage;

		$this->CacheSettings();
		$area = $_POST['from_extra'];

		if( !\gp\admin\Content\Extra::AreaExists($area) ){
			msg($langmessage['OOPS'].' (Extra Area does not exist)');
			return;
		}

		$key			= $this->NewExtraKey();
		$insert			= array();
		$insert[$key]	= array(
			'area' 	=> $area,
			'label'	=> str_replace('_', ' ', $area),
		);

		if( !$this->SaveNew($insert) ){ 
			msg($langmessage['OOPS'].' (Adding Extra Content Area failed)');
			$this->RestoreSettings();
			return false;
		}
	}


	public function NewExtraKey(){
		$num_index = 0;
		do{
			$new_key = '_extra_' . base_convert($num_index, 10, 36);
			$num_index++;
		}while( isset($this->curr_menu_array[$new_key]) );

		return $new_key;
	}


	/**
	 * Save a new external link in the current menu
	 *
	 */
	public function NewExternal(){
		global $langmessage;

		$this->CacheSettings();
		$array = $this->ExternalPost();

		if( !$array ){
			msg($langmessage['OOPS'].' (Invalid Request)');
			return;
		}

		$key			= $this->NewExternalKey();
		$insert			= array();
		$insert[$key]	= $array;

		if( !$this->SaveNew($insert) ){ 
			$this->RestoreSettings();
			return false;
		}
	}


	/**
	 * Edit an external link entry in the current menu
	 *
	 */
	public function EditExternal(){
		global $langmessage;

		$key =& $_REQUEST['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			msg($langmessage['OOPS'].' (Current menu not set)');
			return false;
		}

		$info = $this->curr_menu_array[$key];
		$info['key'] = $key;

		echo '<div class="inline_box">';

		echo '<h3>'.$langmessage['External Link'].'</h3>';

		$this->ExternalForm('SaveExternal',$langmessage['save'],$info);

		echo '</div>';
	}


	/**
	 * Save changes to an external link entry in the current menu
	 *
	 */
	public function SaveExternal(){
		global $langmessage;

		$key =& $_POST['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			msg($langmessage['OOPS'].' (Current menu not set)');
			return false;
		}
		$level = $this->curr_menu_array[$key]['level'];

		$array = $this->ExternalPost();
		if( !$array ){
			msg($langmessage['OOPS'].' (1)');
			return;
		}

		$this->CacheSettings();

		$array['level'] = $level;
		$this->curr_menu_array[$key] = $array;

		if( !$this->SaveMenu(false) ){
			msg($langmessage['OOPS'].' (Menu Not Saved)');
			$this->RestoreSettings();
			return false;
		}

	}


	/**
	 * Check the values of a post with external link values
	 *
	 */
	public function ExternalPost(){

		$array = array();
		if( empty($_POST['url']) || $_POST['url'] == 'http://' ){
			return false;
		}
		$array['url']	= htmlspecialchars($_POST['url']);
		$array['label'] = \gp\admin\Tools::PostedLabel($_POST['label']);

		if( empty($array['label']) ){
			return false;
		}

		if( !empty($_POST['title_attr']) ){
			$array['title_attr'] = htmlspecialchars($_POST['title_attr']);
		}
		if( isset($_POST['new_win']) && $_POST['new_win'] == 'new_win' ){
			$array['new_win'] = true;
		}
		return $array;
	}

	public function NewExternalKey(){

		$num_index = 0;
		do{
			$new_key = '_'.base_convert($num_index,10,36);
			$num_index++;
		}while( isset($this->curr_menu_array[$new_key]) );

		return $new_key;
	}


	/**
	 * Save pages
	 *
	 * @param array $titles
	 * @return bool
	 */
	protected function SaveNew($titles){
		global $langmessage;

		//menu modification
		if( isset($_POST['insert_where']) && isset($_POST['insert_how']) ){

			if( !$this->MenuInsert($titles, $_POST['insert_where'], $_POST['insert_how']) ){
				msg($langmessage['OOPS'].' (Insert Failed)');
				return false;
			}

			if( !$this->SaveMenu(true) ){
				msg($langmessage['OOPS'].' (Menu Not Saved)');
				return false;
			}

			return true;
		}


		if( !\gp\admin\Tools::SavePagesPHP(true) ){
			return false;
		}

		return true;
	}


	/**
	 * Insert titles into the current menu if needed
	 *
	 */
	public function MenuInsert($titles, $neighbor, $insert_how){
		switch($insert_how){
			case 'insert_before':
			return $this->MenuInsert_Before($titles, $neighbor);

			case 'insert_after':
			return $this->MenuInsert_After($titles, $neighbor);

			case 'insert_child':
			return $this->MenuInsert_After($titles, $neighbor, 1);
		}

		return false;
	}


	/**
	 * Remove from the menu
	 *
	 */
	public function Hide(){
		global $langmessage;

		if( is_null($this->curr_menu_array) ){
			msg($langmessage['OOPS'].'(1)');
			return false;
		}

		$this->CacheSettings();

		$_POST		+= array('index'=>'');
		$indexes 	= explode(',',$_POST['index']);

		foreach($indexes as $index ){

			if( count($this->curr_menu_array) == 1 ){
				break;
			}

			if( !isset($this->curr_menu_array[$index]) ){
				msg($langmessage['OOPS'].'(3)');
				return false;
			}

			if( !$this->RmFromMenu($index) ){
				msg($langmessage['OOPS'].'(4)');
				$this->RestoreSettings();
				return false;
			}
		}

		if( $this->SaveMenu(false) ){
			return true;
		}

		msg($langmessage['OOPS'].'(5)');
		$this->RestoreSettings();
		return false;
	}


	/**
	 * Move To Trash
	 * Hide special pages
	 *
	 */
	public function MoveToTrash(){
		global $gp_titles, $gp_index, $langmessage, $gp_menu, $config, $dataDir;

		$this->CacheSettings();

		$_POST			+= array('index'=>'');
		$indexes		= explode(',',$_POST['index']);
		$trash_data		= array();


		foreach($indexes as $index){

			$title	= \gp\tool::IndexToTitle($index);

			// Create file in trash
			if( $title ){
				if( !\gp\admin\Content\Trash::MoveToTrash_File($title,$index,$trash_data) ){
					msg($langmessage['OOPS'].' (Not Moved)');
					$this->RestoreSettings();
					return false;
				}
			}


			// Remove from menu
			if( isset($gp_menu[$index]) ){

				if( count($gp_menu) == 1 ){
					continue;
				}

				if( !$this->RmFromMenu($index,false) ){
					msg($langmessage['OOPS']);
					$this->RestoreSettings();
					return false;
				}
			}

			unset($gp_titles[$index]);
			unset($gp_index[$title]);
		}


		\gp\admin\Menu\Tools::ResetHomepage();


		if( !\gp\admin\Tools::SaveAllConfig() ){
			$this->RestoreSettings();
			return false;
		}

		$link = \gp\tool::GetUrl('Admin/Trash');
		msg(sprintf($langmessage['MOVED_TO_TRASH'],$link));


		\gp\tool\Plugins::Action('MenuPageTrashed',array($indexes));

		return true;
	}


	/**
	 * Rename
	 *
	 */
	public function RenameForm(){
		\gp\Page\Rename::RenameForm();
	}

	public function RenameFile(){
		global $langmessage, $gp_index;

		//prepare variables
		$title =& $_REQUEST['title'];
		if( !isset($gp_index[$title]) ){
			msg($langmessage['OOPS'].' (R0)');
			return false;
		}

		\gp\Page\Rename::RenameFile($title);
	}


	/**
	 * Toggle Page Visibility
	 *
	 */
	public function ToggleVisibility(){
		$_REQUEST += array('index'=>'','visibility'=>'');
		\gp\Page\Visibility::Toggle($_REQUEST['index'], $_REQUEST['visibility']);
	}


	/**
	 * Display a form for selecting the homepage
	 *
	 */
	public function HomepageSelect(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu/Ajax').'" method="post">';

		echo '<h3><i class="fa fa-home"></i> ';
		echo $langmessage['Homepage'];
		echo '</h3>';

		echo '<p class="homepage_setting">';
		echo '<input type="text" class="title-autocomplete gpinput" name="homepage" />';
		echo '</p>';


		echo '<p>';
		echo '<button type="submit" name="cmd" value="HomepageSave" class="gpsubmit" data-cmd="gppost">'.htmlspecialchars($langmessage['save']).'</button> ';
		echo '<button type="submit" class="admin_box_close gpcancel">'.htmlspecialchars($langmessage['cancel']).'</button>';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}


	/**
	 * Save the posted page as the homepage
	 *
	 */
	public function HomepageSave(){
		global $langmessage, $config, $gp_index, $gp_titles;

		$homepage = $_POST['homepage'];
		$homepage_key = false;
		if( isset($gp_index[$homepage]) ){
			$homepage_key = $gp_index[$homepage];
		}else{

			foreach($gp_titles as $index => $title){
				if( $title['label'] === $homepage ){
					$homepage_key = $index;
					break;
				}
			}

			if( !$homepage_key ){
				msg($langmessage['OOPS']);
				return;
			}
		}

		$config['homepath_key'] = $homepage_key;
		$config['homepath']		= \gp\tool::IndexToTitle($config['homepath_key']);
		if( !\gp\admin\Tools::SaveConfig(true) ){
			return;
		}

		//update the display
		ob_start();
		$this->HomepageDisplay();
		$content = ob_get_clean();

		$this->page->ajaxReplace[] = array('inner','.homepage_setting',$content);
	}




}
