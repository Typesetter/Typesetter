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
			case 'renameit':
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
		global $langmessage, $page, $gp_index;

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
		\gp\admin\Menu\Tools::ScrollList($gp_index);
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
		global $langmessage, $page;

		$this->search_page = 0; //take user back to first page where the new page will be displayed

		if( isset($_REQUEST['redir']) ){
			$title	= \common::IndexToTitle($new_index);
			$url	= \common::AbsoluteUrl($title,'',true,false);
			msg(sprintf($langmessage['will_redirect'],\common::Link_Page($title)));
			$page->ajaxReplace[] = array('location',$url,15000);
		}else{
			msg($langmessage['SAVED']);
		}
	}



	/**
	 * Display a form for copying a page
	 *
	 */
	public function CopyForm(){
		global $langmessage, $gp_index, $page;


		$index = $_REQUEST['index'];
		$from_title = \common::IndexToTitle($index);

		if( !$from_title ){
			msg($langmessage['OOPS_TITLE']);
			return false;
		}

		$from_label = \common::GetLabel($from_title);
		$from_label = \common::LabelSpecialChars($from_label);

		echo '<div class="inline_box">';
		echo '<form method="post" action="'.\common::GetUrl('Admin/Menu/Ajax').'">';
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
		global $gp_index, $gp_titles, $page, $langmessage;

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
		$from_file		= \gpFiles::PageFile($from_title);
		$contents		= file_get_contents($from_file);


		//add to $gp_index first!
		$index				= \common::NewFileIndex();
		$gp_index[$title]	= $index;
		$file = \gpFiles::PageFile($title);

		if( !\gpFiles::Save($file,$contents) ){
			msg($langmessage['OOPS'].' (File not saved)');
			return false;
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
		global $langmessage, $page, $gp_index;

		if( is_null($cmd) ){
			$cmd = $this->cmd;
		}

		$_REQUEST['gpx_content'] = 'gpabox';


		//create format of each tab
		ob_start();
		echo '<div id="%s" class="%s">';
		echo '<form action="'.\common::GetUrl('Admin/Menu/Ajax').'" method="post">';
		echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_REQUEST['insert_where']).'" />';
		echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
		echo '<table class="bordered full_width">';
		echo '<thead><tr><th>&nbsp;</th></tr></thead>';
		echo '</table>';
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
			echo ' <a href="#gp_Insert_Copy" data-cmd="tabs" class="selected">'. $langmessage['Copy'] .'</a>';
			echo ' <a href="#gp_Insert_New" data-cmd="tabs">'. $langmessage['new_file'] .'</a>';
			echo ' <a href="#gp_Insert_Hidden" data-cmd="tabs">'. $langmessage['Available'] .'</a>';
			echo ' <a href="#gp_Insert_External" data-cmd="tabs">'. $langmessage['External Link'] .'</a>';
			echo ' <a href="#gp_Insert_Deleted" data-cmd="tabs">'. $langmessage['trash'] .'</a>';
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
			\gp\admin\Menu\Tools::ScrollList($gp_index);
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

			if( $avail ){
				echo sprintf($format_top,'gp_Insert_Hidden','nodisplay');
				$avail = array_flip($avail);
				\gp\admin\Menu\Tools::ScrollList($avail,'keys[]','checkbox',true);
				echo sprintf($format_bottom,'InsertFromHidden',$langmessage['insert_into_menu']);
			}



			// Insert Deleted / Restore from trash
			$trashtitles = \gp\admin\Content\Trash::TrashFiles();
			if( $trashtitles ){
				echo sprintf($format_top,'gp_Insert_Deleted','nodisplay');

				echo '<div class="gpui-scrolllist">';
				echo '<input type="text" name="search" value="" class="gpsearch" placeholder="'.$langmessage['Search'].'" autocomplete="off" />';
				foreach($trashtitles as $title => $info){
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
				echo '</div>';
				echo sprintf($format_bottom,'RestoreFromTrash',$langmessage['restore_from_trash']);
			}


			//Insert External
			echo '<div id="gp_Insert_External" class="nodisplay">';
			$args['insert_how']		= $cmd;
			$args['insert_where']	= $_REQUEST['insert_where'];
			$this->ExternalForm('NewExternal',$langmessage['insert_into_menu'],$args);
			echo '</div>';


		echo '</div>';

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
	}


	/**
	 * Insert pages into the current menu from existing pages that aren't in the menu
	 *
	 */
	public function InsertFromHidden(){
		global $langmessage, $gp_index;

		if( $this->curr_menu_array === false ){
			msg($langmessage['OOPS'].' (Menu not set)');
			return false;
		}

		$this->CacheSettings();

		//get list of titles from submitted indexes
		$titles = array();
		if( isset($_POST['keys']) ){
			foreach($_POST['keys'] as $index){
				if( $title = \common::IndexToTitle($index) ){
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


		if( $this->curr_menu_array === false ){
			msg($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['titles']) ){
			msg($langmessage['OOPS'].' (Nothing Selected)');
			return false;
		}

		$this->CacheSettings();

		$titles_lower	= array_change_key_case($gp_index,CASE_LOWER);
		$titles			= array();
		$menu			= \gp\admin\Content\Trash::RestoreTitles($_POST['titles']);


		if( !$menu ){
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

		echo '<tr>';
		echo '<th>&nbsp;</th>';
		echo '<th>&nbsp;</th>';
		echo '</tr>';

		echo '<tr><td>';
		echo $langmessage['Target URL'];
		echo '</td><td>';
		echo '<input type="text" name="url" value="'.$args['url'].'" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<input type="text" name="label" value="'.\common::LabelSpecialChars($args['label']).'" class="gpinput"/>';
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
		echo '<input type="submit" name="" value="'.$submit.'" class="gpsubmit" data-cmd="gppost"/> ';
		echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '</form>';
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
		$array['url'] = htmlspecialchars($_POST['url']);

		if( !empty($_POST['label']) ){
			$array['label'] = \gp\admin\Tools::PostedLabel($_POST['label']);
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

			if( !$this->MenuInsert($titles,$_POST['insert_where'],$_POST['insert_how']) ){
				msg($langmessage['OOPS'].' (Insert Failed)');
				return false;
			}

			if( !$this->SaveMenu(true) ){
				msg($langmessage['OOPS'].' (Menu Not Saved)');
				return false;
			}

			return true;
		}


		if( !\gp\admin\Tools::SavePagesPHP() ){
			msg($langmessage['OOPS'].' (Page index not saved)');
			return false;
		}

		return true;
	}


	/**
	 * Insert titles into the current menu if needed
	 *
	 */
	public function MenuInsert($titles,$neighbor,$insert_how){
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
	 * Remove from the menu
	 *
	 */
	public function Hide(){
		global $langmessage;

		if( $this->curr_menu_array === false ){
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
		$delete_files	= array();


		foreach($indexes as $index){

			$title	= \common::IndexToTitle($index);

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

		$link = \common::GetUrl('Admin/Trash');
		msg(sprintf($langmessage['MOVED_TO_TRASH'],$link));


		\gp\tool\Plugins::Action('MenuPageTrashed',array($indexes));

		return true;
	}


	/**
	 * Rename
	 *
	 */
	public function RenameForm(){
		global $langmessage, $gp_index;

		//prepare variables
		$title =& $_REQUEST['index'];
		$action = $this->GetUrl('Admin/Menu/Ajax');
		\gp\Page\Rename::RenameForm( $_REQUEST['index'], $action );
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
		echo '<form action="'.\common::GetUrl('Admin/Menu/Ajax').'" method="post">';

		echo '<h3><i class="gpicon_home"></i>';
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
		global $langmessage, $config, $gp_index, $gp_titles, $page;

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
		$config['homepath']		= \common::IndexToTitle($config['homepath_key']);
		if( !\gp\admin\Tools::SaveConfig() ){
			msg($langmessage['OOPS']);
			return;
		}

		//update the display
		ob_start();
		$this->HomepageDisplay();
		$content = ob_get_clean();

		$page->ajaxReplace[] = array('inner','.homepage_setting',$content);
	}




}
