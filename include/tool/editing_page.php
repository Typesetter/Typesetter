<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/editing.php');

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

				case 'new_dir':
					$this->NewDirForm();
				return;

				//section editing
				case 'move_up':
					$this->MoveUp();
				break;

				case 'new_section':
					$this->NewSectionPrompt();
				return;

				case 'add_section':
					$this->AddNewSection();
				break;

				case 'rm_section':
					$this->RmSection();
				break;

				case 'save':
					$this->SaveSection();
				return;

				case 'rawcontent':
					$this->RawContent();
				break;


				/* gallery editing */
				case 'gallery_folder':
				case 'gallery_images':
					$this->GalleryImages();
				return;

				/* include editing */
				case 'preview':
					$this->PreviewSection();
				return;
				case 'include_dialog':
					$this->IncludeDialog();
				return;

				/* inline editing */
				case 'inlineedit':
					$this->InlineEdit();
				die();

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

	function InlineEdit(){
		$section = $_REQUEST['section'];
		if( !is_numeric($section) || !isset($this->file_sections[$section])){
			echo 'false';
			return false;
		}

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($this->file_sections[$section]);
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
			$start_content['type'] = $_POST['content_type'];
			$start_content['content'] = editing_page::GetDefaultContent($start_content['type']);
			if( $start_content['content'] === false ){
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
	 * Get the default content for the specified content type
	 *
	 * @static
	 *
	 */
	static function GetDefaultContent($type){
		global $langmessage;

		switch($type){
			case 'include':
				$default_content = '';
			break;

			case 'gallery':
				$default_content = '<ul class="gp_gallery"><li class="gp_to_remove"></li></ul>';
			break;

			case 'text':
			default:
				$default_content = '<p>'.$langmessage['New Section'].'</p>';
			break;
		}

		$default_content = gpPlugin::Filter('GetDefaultContent',array($default_content,$type));

		return $default_content;
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
		echo '<input type="hidden" name="section" value="'.$_GET['section'].'" />';
		echo '<input type="hidden" name="cmd" value="add_section" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';


		echo '</form>';
		echo '</div>';
		$this->contentBuffer = ob_get_clean();

	}

	/*
	 * @static
	 */
	static function SectionTypes(){
		global $langmessage;
		$section_types['text']['label']		= $langmessage['editable_text'];
		$section_types['gallery']['label']	= $langmessage['Image Gallery'];
		$section_types['include']['label']	= $langmessage['File Include'];

		$section_types = gpPlugin::Filter('SectionTypes',array($section_types));

		$checked = 'checked="checked"';
		foreach($section_types as $type => $type_info){
			echo '<label>';
			echo '<input type="radio" name="content_type" value="'.htmlspecialchars($type).'" '.$checked.'/> ';
			echo htmlspecialchars($type_info['label']);
			echo '</label>';
			$checked = '';
		}
	}


	function PreviewSection(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();

		$section = $_POST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$type = $this->file_sections[$section]['type'];


		switch($type){
			case 'include':
				$data = array();
				$data['type'] = $type;
				if( !empty($_POST['gadget_include']) ){
					$data['include_type'] = 'gadget';
					$data['content'] = $_POST['gadget_include'];
				}else{
					$data['content'] = $_POST['file_include'];
				}


				includeFile('tool/SectionContent.php');
				$content = section_content::RenderSection($data,$section,$this->title,$this->file_stats);
				$page->ajaxReplace[] = array('gp_include_content','',$content);
			break;
			default:
				message($langmessage['OOPS'].'(2)');
			return false;
		}
	}

	function SaveSection(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();


		//check
		$section =& $_POST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$type = $this->file_sections[$section]['type'];
		$check_before = serialize($this);
		$check_before = sha1( $check_before ) . md5( $check_before );

		$save_this = false;
		switch($type){
			case 'text':
				$save_this = true;
				$this->SaveSection_Text($section);
			break;
			case 'gallery':
				$save_this = true;
				$this->SaveSection_Text($section);
				$this->GalleryEdited();
			break;
			case 'include':
				$save_this = $this->SaveSection_Include($section);
			break;
		}

		$save_this = gpPlugin::Filter('SaveSection',array($save_this,$section,$type));
		if( $save_this !== true ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}

		//save if the file was changed
		$check_after = serialize($this);
		$check_after = sha1( $check_after ) . md5( $check_after );
		if( $check_before != $check_after ){
			if( !$this->SaveThis() ){
				message($langmessage['OOPS'].'(3)');
				return false;
			}
		}

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		return true;
	}

	function SaveThis(){

		if( !is_array($this->meta_data) || !is_array($this->file_sections) ){
			return false;
		}

		//file count
		if( !isset($this->meta_data['file_number']) ){
			$this->meta_data['file_number'] = gpFiles::NewFileNumber();
		}
		$this->SaveBackup(); //make a backup of the page file

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
		echo '<table class="bordered full_width"><tr><th>'.$langmessage['Modified'].'</th><th>'.$langmessage['File Size'].'</th><th>&nbsp;</th></tr>';
		echo '<tbody>';

		$size = filesize($this->file);
		echo '<tr><td>';
		echo common::date($langmessage['strftime_datetime'],$this->fileModTime);
		echo ' &nbsp; ('.$langmessage['Current Page'].')</td><td>';
		echo admin_tools::FormatBytes($size);
		echo '</td><td>&nbsp;</td></tr>';

		$i = 1;
		foreach($files as $time => $file){

			//get info from filename
			$name = basename($file);
			$parts = explode('.',$name);
			$time = array_shift($parts);
			$size = array_shift($parts);

			//output row
			echo '<tr class="'.($i % 2 ? 'even' : '').'"><td>';
			echo common::date($langmessage['strftime_datetime'],$time);
			echo '</td><td>';
			if( $size && is_numeric($size) ){
				echo admin_tools::FormatBytes($size);
			}
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

		includeFile('tool/SectionContent.php');
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


	function SaveSection_Include($section){
		global $page, $langmessage, $gp_index, $config;


		$section_data = $this->file_sections[$section];
		unset($section_data['index']);

		if( !empty($_POST['gadget_include']) ){
			$gadget = $_POST['gadget_include'];
			if( !isset($config['gadgets'][$gadget]) ){
				message($langmessage['OOPS_TITLE']);
				return false;
			}

			$section_data['include_type'] = 'gadget';
			$section_data['content'] = $gadget;
		}else{
			$title = $_POST['file_include'];
			if( !isset($gp_index[$title]) ){
				message($langmessage['OOPS_TITLE']);
				return false;
			}
			$section_data['include_type'] = common::SpecialOrAdmin($title);
			$section_data['index'] = $gp_index[$title];
			$section_data['content'] = $title;
		}

		$this->file_sections[$section] = $section_data;

		//send replacement content
		includeFile('tool/SectionContent.php');
		$content = section_content::RenderSection($section_data,$section,$this->title,$this->file_stats);
		$page->ajaxReplace[] = array('gp_include_content','',$content);
		return true;
	}


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
		includeFile('tool/SectionContent.php');
		common::ShowingGallery();

		$content = '';
		$section_num = 0;
		foreach($this->file_sections as $section_key => $section_data){
			$content .= "\n";
			$type = isset($section_data['type']) ? $section_data['type'] : 'text';

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

				echo common::Link($this->title,$langmessage['New Section'],'cmd=new_section&section='.$section_key,array('data-cmd'=>'gpabox'));

				$q = 'cmd=add_section&copy=copy&section='.$section_key.'&last_mod='.rawurlencode($this->fileModTime);
				echo common::Link($this->title,$langmessage['Copy'],$q,' data-cmd="creq"');


				//remove section link
				if( count($this->file_sections) > 1 ){
					$title_attr = $langmessage['rm_section_confirm'];
					if( $type != 'include' ){
						$title_attr .= "\n\n".$langmessage['rm_section_confirm_deleting'];
					}

					echo common::Link($this->title,$langmessage['Remove Section'],'cmd=rm_section&section='.$section_key.'&total='.count($this->file_sections), array('title'=>$title_attr,'data-cmd'=>'creq','class'=>'gpconfirm'));
				}
				echo '</span>';
				gpOutput::$editlinks .= ob_get_clean();

				$content .= '<div class="editable_area GPAREA filetype-'.$type.'" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
			}else{
				$content .= '<div class="GPAREA filetype-'.$type.'">';
			}

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
		$this->SaveThis();

		includeFile('admin/admin_uploaded.php');
		admin_uploaded::InlineList($dir_piece);
	}


	function NewDirForm(){
		global $langmessage;
		includeFile('admin/admin_uploaded.php');

		ob_start();

		echo '<div class="inline_box">';
		$img = '<img src="'.common::GetDir('/include/imgs/folder.png').'" height="16" width="16" alt=""/> ';
		echo '<h2>'.$img.$langmessage['create_dir'].'</h2>';
		echo '<form action="'.common::GetUrl($this->title).'" method="post" >';
		echo '<p>';
		echo htmlspecialchars($_GET['dir']).'/';
		echo ' <input type="text" class="gpinput" name="newdir" size="30" />';
		echo '</p>';
		echo '<p>';
		if( !empty($_GET['dir']) ){
			echo ' <input type="hidden" name="dir" value="'.htmlspecialchars($_GET['dir']).'" />';
		}
		echo '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="gp_gallery_folder_add gpsubmit"/>';
		echo ' <input type="submit" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';
		echo '</div>';

		$this->contentBuffer = ob_get_clean();
	}

	/*
	 * Include Editing
	 */
	function IncludeDialog(){
		global $page,$langmessage,$config;

		$page->ajaxReplace = array();

		$section =& $_GET['section'];
		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS']);
			return;
		}

		$include_type =& $this->file_sections[$section]['include_type'];

		$gadget_content = '';
		$file_content = '';
		switch($include_type){
			case 'gadget':
				$gadget_content =& $this->file_sections[$section]['content'];
			break;
			default:
				$file_content =& $this->file_sections[$section]['content'];
			break;
		}

		ob_start();

		echo '<form id="gp_include_form">';

		echo '<div class="gp_inlude_edit">';
		echo '<span class="label">';
		echo $langmessage['File Include'];
		echo '</span>';
		echo '<input type="text" size="" id="gp_file_include" name="file_include" class="autocomplete" value="'.htmlspecialchars($file_content).'" />';
		echo '</div>';

		echo '<div class="gp_inlude_edit">';
		echo '<span class="label">';
		echo $langmessage['gadgets'];
		echo '</span>';
		echo '<input type="text" size="" id="gp_gadget_include" name="gadget_include" class="autocomplete" value="'.htmlspecialchars($gadget_content).'" />';
		echo '</div>';

		echo '<div id="gp_option_area">';
		echo '<a data-cmd="gp_include_preview" class="ckeditor_control full_width">Preview</a>';
		echo '</div>';

		echo '</form>';


		$content = ob_get_clean();
		$page->ajaxReplace[] = array('gp_include_dialog','',$content);


		//file include autocomplete
		$options['admin_vals'] = false;
		$options['var_name'] = 'source';
		$file_includes = gp_edit::AutoCompleteValues(false,$options);
		$page->ajaxReplace[] = array('gp_autocomplete_include','file',$file_includes);


		//gadget include autocomplete
		$code = 'var source=[';
		if( isset($config['gadgets']) ){
			foreach($config['gadgets'] as $uniq => $info){
				$code .= '["'.addslashes($uniq).'","'.addslashes($uniq).'"],';
			}
		}
		$code .= ']';

		$page->ajaxReplace[] = array('gp_autocomplete_include','gadget',$code);

	}

}
