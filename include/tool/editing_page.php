<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/editing.php');
includeFile('tool/SectionContent.php');

class editing_page extends display{


	function editing_page($title,$type){
		parent::display($title,$type);
	}

	function RunScript(){
		global $langmessage,$page;
		$cmd = common::GetCommand();

		//prevent overwriting the content to maintain overlay editin links
		//$page->ajaxReplace = array();

		if( !$this->SetVars() ){
			return;
		}

		$this->GetFile();

		//original alpha versions of 1.8 didn't maintain the file_type
		if( !isset($this->meta_data['file_type']) ){
			$this->ResetFileTypes();
		}


		//admin toolbar links
		$menu_permissions = admin_tools::HasPermission('Admin_Menu');
		$can_edit = admin_tools::CanEdit($this->gp_index);
		if( $menu_permissions ){
			$page->admin_links[] = common::Link($this->title,$langmessage['rename/details'],'cmd=renameform','data-cmd="gpajax"');

			// Having the layout link here complicates things.. would need layout link for special pages
			$page->admin_links[] = common::Link('Admin_Menu',$langmessage['current_layout'],'cmd=layout&from=page&index='.urlencode($this->gp_index),array('title'=>$langmessage['current_layout'],'data-cmd'=>'gpabox'));
			$page->admin_links[] = common::Link('Admin_Menu',$langmessage['Copy'],'cmd=copypage&redir=redir&title='.urlencode($this->title),array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));
		}

		if( admin_tools::HasPermission('Admin_User') ){
			$page->admin_links[] = common::Link('Admin_Users',$langmessage['permissions'],'cmd=file_permissions&index='.urlencode($this->gp_index),array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox'));
		}

		if( $can_edit ){
			$page->admin_links[] = common::Link($this->title,$langmessage['Revision History'],'cmd=view_history',array('title'=>$langmessage['Revision History'],'data-cmd'=>'gpabox'));
		}


		if( $menu_permissions ){
			$page->admin_links[] = common::Link('Admin_Menu',$langmessage['delete_file'],'cmd=trash_page&index='.urlencode($this->gp_index),array('data-cmd'=>'postlink','title'=>$langmessage['delete_page'],'class'=>'gpconfirm'));

		}


		//allow addons to effect page actions and how a page is displayed
		$cmd_after = gpPlugin::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		//admin actions
		if( $menu_permissions ){
			switch($cmd){
				// rename & details
				case 'renameform':
					$this->RenameForm();
				return;
				case 'renameit':
					if( $this->RenameFile() ){
						return;
					}
				break;
			}
		}


		//file editing actions
		if( $can_edit ){

			switch($cmd){

				//section editing
				case 'move_up':
					$this->MoveUp();
				break;

				case 'new_section':
					$this->NewSectionPrompt();
				return;

				case 'section_options_save':
				case 'section_options':
					$this->SectionOptions($cmd);
				return;

				case 'add_section':
					$this->AddNewSection();
				break;

				case 'rm_section':
					$this->RmSection();
				break;

				case 'rawcontent':
					$this->RawContent();
				break;


				/* gallery editing */
				case 'gallery_folder':
				case 'gallery_images':
					$this->GalleryImages();
				return;

				case 'new_dir':
					$this->contentBuffer = gp_edit::NewDirForm();
				return;

				/* inline editing */
				case 'save':
				case 'preview':
				case 'inlineedit':
				case 'include_dialog':
					$this->SectionEdit($cmd);
				return;


				/* revision history */
				case 'view_revision':
					$this->ViewRevision();
				return;
				case 'use_revision':
					$this->UseRevision();
				break;
				case 'view_history';
					$this->ViewHistory();
				return;

			}
		}

		$this->contentBuffer = $this->GenerateContent_Admin();
	}


	/**
	 * Perform various section editing commands
	 *
	 */
	function SectionEdit($cmd){
		global $page, $langmessage;

		$section_num = $_REQUEST['section'];
		if( !is_numeric($section_num) || !isset($this->file_sections[$section_num])){
			echo 'false';
			return false;
		}

		$page->ajaxReplace = array();
		$check_before = serialize($this);
		$check_before = sha1( $check_before ) . md5( $check_before );

		if( !gp_edit::SectionEdit( $cmd, $this->file_sections[$section_num], $section_num, $this->title, $this->file_stats ) ){
			return;
		}

		//save if the file was changed
		$check_after = serialize($this);
		$check_after = sha1( $check_after ) . md5( $check_after );
		if( $check_before != $check_after && !$this->SaveThis() ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);


		//update gallery information
		switch($this->file_sections[$section_num]['type']){
			case 'gallery':
				$this->GalleryEdited();
			break;
		}

		return true;
	}


	/*
	 * Send the raw content of the section to the gpResponse handler
	 *
	 */
	function RawContent(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();

		$section = $_REQUEST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$page->ajaxReplace[] = array('rawcontent','',$this->file_sections[$section]['content']);
	}


	/**
	 * Recalculate the file_type string for this file
	 * Used by AddNewSection(), RmSection()
	 * Updates $this->meta_data and $gp_titles
	 *
	 */
	function ResetFileTypes($save = true){
		global $gp_titles;

		$original_types = array();
		if( isset($this->meta_data['file_type']) ){
			$original_types = explode(',',$this->meta_data['file_type']);
		}

		$new_types = array();
		foreach($this->file_sections as $section){
			$new_types[] = $section['type'];
		}
		$new_types = array_unique($new_types);
		$new_types = array_diff($new_types,array(''));
		sort($new_types);

		$new_types = implode(',',$new_types);
		$this->meta_data['file_type'] = $new_types;

		if( !isset($gp_titles[$this->gp_index]) ){
			return;
		}

		$gp_titles[$this->gp_index]['type'] = $new_types;
		admin_tools::SavePagesPHP();
		if( $save ){
			$this->SaveThis();
		}
	}

	function RenameFile(){
		global $langmessage, $gp_index, $page;

		includeFile('tool/Page_Rename.php');
		$new_title = gp_rename::RenameFile($this->title);
		if( ($new_title !== false) && $new_title != $this->title ){
			message(sprintf($langmessage['will_redirect'],common::Link_Page($new_title)));
			$page->head .= '<meta http-equiv="refresh" content="15;url='.common::GetUrl($new_title).'">';
			return true;
		}
		return false;
	}


	function RenameForm(){
		global $page,$gp_index;

		includeFile('tool/Page_Rename.php');
		$action = common::GetUrl($this->title);
		gp_rename::RenameForm($this->title,$action);
	}


	function MoveUp(){
		global $langmessage;


		$move_key =& $_REQUEST['section'];
		if( !isset($this->file_sections[$move_key]) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !common::verify_nonce('move_up'.$move_key) ){
			message($langmessage['OOPS']);
			return false;
		}


		$move_content = $this->file_sections[$move_key];

		$file_keys = array_keys($this->file_sections);
		$file_values = array_values($this->file_sections);
		$insert_key = array_search($move_key,$file_keys);
		if( ($insert_key === null) || ($insert_key === false) || ($insert_key === 0) ){
			message($langmessage['OOPS']);
			return false;
		}

		$prev_key = $insert_key-1;

		if( !isset($file_keys[$prev_key]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$old_sections = $this->file_sections;

		//rebuild
		$new_sections = array();
		foreach($file_values as $temp_key => $file_value){

			if( $temp_key === $prev_key ){
				$new_sections[] = $move_content;
			}elseif( $temp_key === $insert_key ){
				//moved section
				continue;
			}
			$new_sections[] = $file_value;
		}

		$this->file_sections = $new_sections;

		if( !$this->SaveThis() ){
			$this->file_sections = $old_sections;
			message($langmessage['OOPS'].'(4)');
			return;
		}
	}

	/**
	 * Remove a content area from a page
	 *
	 */
	function RmSection(){
		global $langmessage,$page;

		if( !isset($_POST['total']) || $_POST['total'] != count($this->file_sections) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['section']) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}

		$section = $_POST['section'];

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(2)');
			return;
		}

		$section_data = $this->file_sections[$section];

		array_splice( $this->file_sections , $section , 1 );

		$this->ResetFileTypes(false);

		if( !$this->SaveThis() ){
			message($langmessage['OOPS'].'(4)');
			return;
		}

		if( $section_data['type'] == 'gallery' ){
			$this->GalleryEdited();
		}

		//update usage of resized images
		if( isset($section_data['resized_imgs']) ){
			includeFile('image.php');
			gp_resized::SetIndex();
			gp_edit::ResizedImageUse($section_data['resized_imgs'],array());
		}

		message($langmessage['SAVED']);
	}


	/**
	 * Add a new section to the page
	 *
	 */
	function AddNewSection(){
		global $langmessage;

		if( $_POST['last_mod'] != $this->fileModTime ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['section']) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}

		$section = $_POST['section'];

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(2)');
			return;
		}

		if( isset($_POST['copy']) ){
			$start_content = $this->file_sections[$section];
		}else{
			$start_content = gp_edit::DefaultContent($_POST['content_type']);
			if( is_array($start_content) && $start_content['content'] === false ){
				message($langmessage['OOPS'].'(3)');
				return;
			}
		}

		if( isset($_POST['insert']) && $_POST['insert'] == 'before' ){
			array_splice( $this->file_sections , $section , 0, 'temporary' );
			$new_section = $section;
		}else{
			array_splice( $this->file_sections , $section+1 , 0, 'temporary' );
			$new_section = $section+1;
		}

		if( $this->file_sections[$new_section] != 'temporary' ){
			message($langmessage['OOPS'].'(4)');
			return;
		}


		$this->file_sections[$new_section] = $start_content;

		$this->ResetFileTypes(false);

		if( !$this->SaveThis() ){
			message($langmessage['OOPS'].'(4)');
			return;
		}


		message($langmessage['SAVED']);
	}


	/**
	 * Display section options
	 * 	- attributes (style, data-*)
	 *
	 */
	function SectionOptions($cmd){
		global $langmessage;

		$section_num = $_REQUEST['section'];
		if( !array_key_exists($section_num,$this->file_sections) ){
			msg($langmessage['OOPS'].' (Invalid Section)');
			return false;
		}

		$section =& $this->file_sections[$section_num];
		$section += array('attributes' => array() );
		$section['attributes'] += array('class' => '' );


		if( $cmd == 'section_options_save' && $this->SectionOptionsSave($section_num) ){
			return true;
		}

		ob_start();

		echo '<div class="inline_box">';

		echo '<h2>'.$langmessage['options'].'</h2>';
		echo '<form method="post" action="'.common::GetUrl($this->title).'">';
		echo '<table class="bordered full_width">';

		//attributes
		echo '<thead><tr><th>Attributes</th><th>'.$langmessage['Value'].'</th></tr></thead>';
		echo '<tbody>';
		$invalid = self::InvalidAttributes($section['attributes']);
		foreach($section['attributes'] as $attr => $value){
			echo '<tr><td>';
			$class = 'gpinput';
			if( in_array($attr,$invalid) ){
				$class .= ' gpinput_warning';
			}
			echo '<input class="'.$class.'" type="text" name="attr_name[]" value="'.htmlspecialchars($attr).'" size="8" pattern="^([^\s]|i[^d\s]|[^i\s]d|[^i\s][^d\s]|[^\s]{3,})$" />';
			echo '</td><td style="white-space:nowrap">';
			echo '<input class="gpinput" type="text" name="attr_value[]" value="'.htmlspecialchars($value).'" size="40" />';
			if( $attr == 'class' ){
				echo '<div class="class_only admin_note">'.$langmessage['default'].': GPAREA filetype-'.$section['type'].'</div>';
			}
			echo '</td></tr>';
		}

		echo '<tr><td colspan="3">';
		echo '<a name="add_table_row">Add Attribute</a>';
		echo '</td></tr>';
		echo '</tbody>';
		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="last_mod" value="'.$this->fileModTime.'" />';
		echo '<input type="hidden" name="section" value="'.htmlspecialchars($_REQUEST['section']).'" />';
		echo '<input type="hidden" name="cmd" value="section_options_save" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" data-cmd="gpabox" />';
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="gpcancel" data-cmd="admin_box_close" />';
		echo '</p>';



		echo '</form>';
		echo '</div>';
		$this->contentBuffer = ob_get_clean();
		return false;
	}

	/**
	 * Save Section Options
	 * 	- attributes (style, data-*)
	 *
	 */
	function SectionOptionsSave($section_num){
		global $langmessage;

		if( !is_array($_POST['attr_name']) || count($_POST['attr_name']) != count($_POST['attr_value']) ){
			msg($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}


		//build attribute array
		$_POST['attr_name'] = array_map('trim',$_POST['attr_name']);
		$attributes = array('class'=>'');
		foreach($_POST['attr_name'] as $i => $attr_name){
			if( empty($attr_name) ){
				continue;
			}
			$attributes[$attr_name] = $_POST['attr_value'][$i];
		}
		$this->file_sections[$section_num]['attributes'] = $attributes;


		//check for valid attributes
		$invalid = self::InvalidAttributes($attributes);
		if( count($invalid) ){
			msg($langmessage['OOPS'].' (Invalid Attributes)');
			return false;
		}

		//save
		if( !$this->SaveThis(false) ){
			msg($langmessage['OOPS'].' (Not Saved)');
			return false;
		}

		msg($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		return true;
	}

	static function InvalidAttributes($attributes){
		$invalid = array();
		foreach($attributes as $attr_name => $attr_value){
			if( strtolower($attr_name) == 'id' ){
				$invalid[] = 'id';
				continue;
			}
			if( preg_match('#\s#',$attr_name) ){
				$invalid[] = $attr_name;
			}
		}
		return $invalid;
	}


	/**
	 * Display a form for adding a new section to the page
	 *
	 */
	function NewSectionPrompt(){
		global $langmessage;

		ob_start();
		echo '<div class="inline_box">';
		echo '<form method="post" action="'.common::GetUrl($this->title).'">';
		echo '<h2>'.$langmessage['new_section_about'].'</h2>';

		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">'.$langmessage['New Section'].'</th></tr>';

		echo '<tr><td>';
		echo $langmessage['Content Type'];
		echo '</td><td>';
		editing_page::SectionTypes();
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['Insert Location'];
		echo '</td><td>';
		echo '<label><input type="radio" name="insert" value="before" /> ';
		echo $langmessage['insert_before'];
		echo '</label>';
		echo '<label><input type="radio" name="insert" value="after" checked="checked" /> ';
		echo $langmessage['insert_after'];
		echo '</label>';
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="last_mod" value="'.$this->fileModTime.'" />';
		echo '<input type="hidden" name="section" value="'.htmlspecialchars($_GET['section']).'" />';
		echo '<input type="hidden" name="cmd" value="add_section" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" data-cmd="cnreq" />';
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';


		echo '</form>';
		echo '</div>';
		$this->contentBuffer = ob_get_clean();

	}

	/**
	 * Return a list of section types
	 * @static
	 */
	static function SectionTypes(){
		global $langmessage;

		$section_types = section_content::GetTypes();

		$checked = 'checked="checked"';
		foreach($section_types as $type => $type_info){
			echo '<label>';
			echo '<input type="radio" name="content_type" value="'.htmlspecialchars($type).'" '.$checked.'/> ';
			echo htmlspecialchars($type_info['label']);
			echo '</label>';
			$checked = '';
		}
	}


	function SaveThis( $backup = true ){

		if( !is_array($this->meta_data) || !is_array($this->file_sections) ){
			return false;
		}

		//file count
		if( !isset($this->meta_data['file_number']) ){
			$this->meta_data['file_number'] = gpFiles::NewFileNumber();
		}
		if( $backup ){
			$this->SaveBackup(); //make a backup of the page file
		}

		return gpFiles::SaveArray($this->file,'meta_data',$this->meta_data,'file_sections',$this->file_sections);
	}

	/**
	 *	Save a backup of the file
	 *
	 */
	function SaveBackup(){
		global $dataDir;

		$dir = $dataDir.'/data/_backup/pages/'.$this->gp_index;
		gpFiles::CheckDir($dir);

		$time = time();
		if( isset($_REQUEST['revision']) && is_numeric($_REQUEST['revision']) ){
			$time = $_REQUEST['revision'];
		}

		$contents = file_get_contents( $this->file );

		//backup file name
		$len = strlen($contents);
		$backup_file = $dir.'/'.$time.'.'.$len;

		if( isset($this->file_stats['username']) && $this->file_stats['username'] ){
			$backup_file .= '.'.$this->file_stats['username'];
		}

		//compress
		if( function_exists('gzencode') && function_exists('readgzfile') ){
			$backup_file .= '.gze';
			$contents = gzencode($contents,9);
		}

		gpFiles::Save( $backup_file, $contents );
		$this->CleanBackupFolder();
	}


	/**
	 * Reduce the number of files in the backup folder
	 *
	 */
	function CleanBackupFolder(){
		global $dataDir;
		$files = $this->BackupFiles();
		$file_count = count($files);
		if( $file_count <= gp_backup_limit ){
			return;
		}
		$delete_count = $file_count - gp_backup_limit;
		$files = array_splice( $files, 0, $delete_count );
		foreach($files as $file){
			$full_path = $dataDir.'/data/_backup/pages/'.$this->gp_index.'/'.$file;
			unlink($full_path);
		}
	}


	/**
	 * Display the revision history of the current file
	 *
	 */
	function ViewHistory(){
		global $langmessage;

		$files = $this->BackupFiles();
		krsort($files);

		ob_start();
		echo '<h2>'.$langmessage['Revision History'].'</h2>';
		echo '<table class="bordered full_width"><tr><th>'.$langmessage['Modified'].'</th><th>'.$langmessage['File Size'].'</th><th>'.$langmessage['username'].'</th><th>&nbsp;</th></tr>';
		echo '<tbody>';

		$size = filesize($this->file);
		echo '<tr><td>';
		echo common::date($langmessage['strftime_datetime'],$this->fileModTime);
		echo ' &nbsp; ('.$langmessage['Current Page'].')</td><td>';
		echo admin_tools::FormatBytes($size);
		echo '</td><td>'.$this->file_stats['username'].'</td><td>&nbsp;</td></tr>';

		$i = 1;
		foreach($files as $time => $file){

			//remove .gze
			if( strpos($file,'.gze') === (strlen($file)-4) ){
				$file = substr($file,0,-4);
			}

			//get info from filename
			$name = basename($file);
			$parts = explode('.',$name,3);
			$time = array_shift($parts);
			$size = array_shift($parts);
			$username = false;
			if( count($parts) ){
				$username = array_shift($parts);
			}

			//output row
			echo '<tr class="'.($i % 2 ? 'even' : '').'"><td>';
			echo common::date($langmessage['strftime_datetime'],$time);
			echo '</td><td>';
			if( $size && is_numeric($size) ){
				echo admin_tools::FormatBytes($size);
			}
			echo '</td><td>';
			echo $username;
			echo '</td><td>';
			echo common::Link($this->title,$langmessage['preview'],'cmd=view_revision&time='.$time,'data-cmd="cnreq"');
			echo '</td></tr>';
			$i++;
		}
		echo '</tbody>';
		echo '</table>';
		$this->contentBuffer = ob_get_clean();
	}

	/**
	 * Display the contents of a past revision
	 *
	 */
	function ViewRevision(){
		global $langmessage;
		$time = $_REQUEST['time'];
		$full_path = $this->BackupFile($time);
		if( !$full_path ){
			return false;
		}

		$file_sections = $file_stats = array();

		//if it's a compressed file, we need an uncompressed version
		if( strpos($full_path,'.gze') !== false ){
			$dir = common::DirName($full_path);
			ob_start();
			readgzfile($full_path);
			$contents = ob_get_clean();
			$full_path = tempnam($dir,'backup');
			gpFiles::Save( $full_path, $contents );
			include($full_path);
			unlink($full_path);
		}else{
			include($full_path);
		}

		$this->contentBuffer = section_content::Render($file_sections,$this->title,$file_stats);


		$date = common::date($langmessage['strftime_datetime'],$time);
		$message = sprintf($langmessage['viewing_revision'],$date);
		$message .= ' <br/> '.common::Link($this->title,$langmessage['Restore this revision'],'cmd=use_revision&time='.$time,'data-cmd="cnreq"');
		$message .= ' &nbsp; '.common::Link($this->title,$langmessage['Revision History'],'cmd=view_history',array('title'=>$langmessage['Revision History'],'data-cmd'=>'gpabox'));
		message( $message );
	}

	/**
	 * Revert the file data to a previous revision
	 *
	 */
	function UseRevision(){
		global $langmessage, $page;

		$time = $_REQUEST['time'];
		$full_path = $this->BackupFile($time);
		if( !$full_path ){
			return false;
		}
		if( strpos($full_path,'.gze') !== false ){
			ob_start();
			readgzfile($full_path);
			$contents = ob_get_clean();
		}else{
			$contents = file_get_contents($full_path);
		}

		$this->SaveBackup();
		gpFiles::Save( $this->file, $contents );
		$this->GetFile();
		$this->ResetFileTypes(false);
		message($langmessage['SAVED']);
	}

	/**
	 * Return a list of the available backup for the current file
	 *
	 */
	function BackupFiles(){
		global $dataDir;
		$dir = $dataDir.'/data/_backup/pages/'.$this->gp_index;
		if( !file_exists($dir) ){
			return array();
		}
		$all_files = scandir($dir);
		$files = array();
		foreach($all_files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$parts = explode('.',$file);
			$time = array_shift($parts);
			if( !is_numeric($time) ){
				continue;
			}
			$files[$time] = $file;
		}

		ksort($files);
		return $files;
	}

	/**
	 * Return the full path of the saved revision if it exists
	 *
	 */
	function BackupFile( $time ){
		global $dataDir;
		$files = $this->BackupFiles();
		if( !isset($files[$time]) ){
			return false;
		}
		return $dataDir.'/data/_backup/pages/'.$this->gp_index.'/'.$files[$time];
	}


	/**
	 * Extract information about the gallery from it's html: img_count, icon_src
	 * Call GalleryEdited when a gallery section is removed, edited
	 *
	 */
	function GalleryEdited(){
		includeFile('special/special_galleries.php');
		special_galleries::UpdateGalleryInfo($this->title,$this->file_sections);
	}

	function GenerateContent_Admin(){
		global $langmessage,$GP_NESTED_EDIT;

		//add to all pages in case a user adds a gallery
		gpPlugin::Action('GenerateContent_Admin');
		common::ShowingGallery();

		$content = '';
		$section_num = 0;
		foreach($this->file_sections as $section_key => $section_data){
			$content .= "\n";

			$section_data += array('attributes' => array(),'type'=>'text' );
			$section_data['attributes'] += array('class' => '' );

			$type = $section_data['type'];


			if( gpOutput::ShowEditLink() && admin_tools::CanEdit($this->gp_index) ){

				$link_name = 'inline_edit_generic';
				$link_rel = $type.'_inline_edit';


				$title_attr = sprintf($langmessage['Section %s'],$section_key+1);
				$link = gpOutput::EditAreaLink($edit_index,$this->title,$langmessage['edit'],'section='.$section_key.'&amp;revision='.$this->fileModTime,array('title'=>$title_attr,'data-cmd'=>$link_name,'data-arg'=>$link_rel));

				//section control links
				ob_start();
				echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
				echo $link;

				if( $section_num > 0 ){
					echo common::Link($this->title,$langmessage['move_up'],'cmd=move_up&section='.$section_key,' data-cmd="creq"','move_up'.$section_key);
				}

				echo common::Link($this->title,$langmessage['options'].'...','cmd=section_options&section='.$section_key,array('data-cmd'=>'gpabox'));

				echo common::Link($this->title,$langmessage['New Section'].'...','cmd=new_section&section='.$section_key,array('data-cmd'=>'gpabox'));

				$q = 'cmd=add_section&copy=copy&section='.$section_key.'&last_mod='.rawurlencode($this->fileModTime);
				echo common::Link($this->title,$langmessage['Copy'],$q,' data-cmd="creq"');


				//remove section link
				if( count($this->file_sections) > 1 ){
					$title_attr = $langmessage['rm_section_confirm'];
					if( $type != 'include' ){
						$title_attr .= "\n\n".$langmessage['rm_section_confirm_deleting'];
					}

					echo common::Link($this->title,$langmessage['Remove Section'].'...','cmd=rm_section&section='.$section_key.'&total='.count($this->file_sections), array('title'=>$title_attr,'data-cmd'=>'creq','class'=>'gpconfirm'));
				}
				echo '</span>';
				gpOutput::$editlinks .= ob_get_clean();

				$section_data['attributes']['id'] = 'ExtraEditArea'.$edit_index;
				$section_data['attributes']['class'] .= ' editable_area'; // class="edit_area" added by javascript
			}

			$content .= '<div'.section_content::SectionAttributes($section_data['attributes'],$type).'>';

			$GP_NESTED_EDIT = true;
			$content .= section_content::RenderSection($section_data,$section_num,$this->title,$this->file_stats);
			$GP_NESTED_EDIT = false;

			$content .= '<div class="gpclear"></div>';
			$content .= '</div>';
			$section_num++;
		}
		return $content;
	}


	/*
	 * sends image information to gallery editor
	 *
	 *
	 * gallery editor uses this html to create the new gallery html
		<li>
			<a href="'.$imgPath.'" data-cmd="gallery" data-arg="gallery_gallery" title="'.htmlspecialchars($caption).'">
			<img src="'.$thumbPath.'" height="100" width="100" alt=""/>
			</a>
			<div class="caption">
			$caption
			</div>
		</li>
	 *
	 */

	function GalleryImages(){

		if( isset($_GET['dir']) ){
			$dir_piece = $_GET['dir'];
		}elseif( isset($this->meta_data['gallery_dir']) ){
			$dir_piece = $this->meta_data['gallery_dir'];
		}else{
			$dir_piece = '/image';
		}
		//remember browse directory
		$this->meta_data['gallery_dir'] = $dir_piece;
		$this->SaveThis(false);

		includeFile('admin/admin_uploaded.php');
		admin_uploaded::InlineList($dir_piece);
	}


	/**
	 * Used by slideshow addons
	 * @deprecated 3.6rc4
	 *
	 */
	function SaveSection_Text($section){
		global $config;
		$content =& $_POST['gpcontent'];
		gpFiles::cleanText($content);
		$this->file_sections[$section]['content'] = $content;

		if( $config['resize_images'] ){
			gp_edit::ResizeImages($this->file_sections[$section]['content'],$this->file_sections[$section]['resized_imgs']);
		}

		return true;
	}

}
