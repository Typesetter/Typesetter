<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/editing.php');
includeFile('tool/SectionContent.php');

class editing_page extends display{


	function __construct($title,$type){
		parent::__construct($title,$type);
	}

	function RunScript(){
		global $langmessage, $page;
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
			$page->admin_links[] = common::Link('Admin_Menu',$langmessage['Copy'],'cmd=copypage&redir=redir&index='.urlencode($this->gp_index),array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));
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

				/* Manage section */
				case 'ManageSections':
					$this->ManageSections();
				//dies
				case 'NewSectionContent':
					$this->NewSectionContent();
				return;
				case 'SaveSections':
					$this->SaveSections();
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
	 * Send js to client for managing content sections
	 *
	 */
	function ManageSections(){
		global $langmessage;

		includeFile('tool/ajax.php');


		//section types
		$section_types = section_content::GetTypes();
		ob_start();
		echo '<form action="?">';
		echo '<table id="new_section_table"><tr><td>';
		echo '<select name="content_type" class="ckeditor_control">';
		foreach($section_types as $type => $type_info){
			echo '<option value="'.htmlspecialchars($type).'">';
			echo htmlspecialchars($type_info['label']);
			echo '</option>';
		}
		echo '</select>';
		echo '</td><td>';
		echo '<button name="cmd" value="NewSectionContent" class="ckeditor_control" id="add_section" data-cmd="gppost">'.$langmessage['New Section'].'</button>';
		echo '</table>';
		echo '</form>';
		echo 'var section_types = '.json_encode(ob_get_clean()).';';



		$scripts	= array();
		$scripts[]	= '/include/thirdparty/js/nestedSortable.js';
		$scripts[]	= '/include/js/inline_edit/inline_editing.js';
		$scripts[]	= '/include/js/inline_edit/manage_sections.js';

		gpAjax::SendScripts($scripts);
		die();
	}


	/**
	 * Send new section content to the client
	 *
	 */
	function NewSectionContent(){
		global $page;
		$page->ajaxReplace = array();

		$num			= time().rand(0,10000);
		$new_section	= gp_edit::DefaultContent($_REQUEST['content_type']);
		$content		= section_content::RenderSection($new_section,$num,$this->title,$this->file_stats);


		$orig_attrs								= json_encode($new_section['attributes']);
		$new_section['attributes']['id']		= 'rand-'.time().rand(0,10000);
		$new_section['attributes']['class']		.= ' editable_area new_section';


		$content		= '<div'.section_content::SectionAttributes($new_section['attributes'],$new_section['type']).' data-gp-attrs=\''.htmlspecialchars($orig_attrs,ENT_QUOTES & ~ENT_COMPAT).'\'>'.$content.'</div>';

		$page->ajaxReplace[] = array('AddSection','',$content);
	}

	/**
	 * Save new/rearranged sections
	 *
	 */
	function SaveSections(){
		global $page, $langmessage;

		$page->ajaxReplace		= array();
		$original_sections		= $this->file_sections;
		$unused_sections		= $this->file_sections;				//keep track of sections that aren't used
		$new_sections			= array();
		$section_types			= section_content::GetTypes();

		foreach($_POST['section_order'] as $i => $arg ){


			// moved / copied sections
			if( ctype_digit($arg) ){
				$arg = (int)$arg;

				if( !isset($this->file_sections[$arg]) ){
					message($langmessage['OOPS'].' (Invalid Section Number)');
					return false;
				}

				unset($unused_sections[$arg]);
				$new_section = $this->file_sections[$arg];

			// otherwise, new sections
			}else{

				if( !isset($section_types[$arg]) ){
					message($langmessage['OOPS'].' (Unknown Type)');
					return false;
				}
				$new_section = gp_edit::DefaultContent($arg);
			}

			// attributes
			$new_section += array('attributes' => array());
			if( isset($_POST['attributes'][$i]) && is_array($_POST['attributes'][$i]) ){
				foreach($_POST['attributes'][$i] as $attr_name => $attr_value){

					$attr_name		= strtolower($attr_name);
					$attr_name		= trim($attr_name);
					$attr_value		= trim($attr_value);

					if( empty($attr_name) || empty($attr_value) || $attr_name == 'id' || substr($attr_name,0,7) == 'data-gp' ){
						continue;
					}

					$new_section['attributes'][$attr_name] = $attr_value;
				}
			}


			// wrapper section 'contains_sections'
			if( $new_section['type'] == 'wrapper_section' ){
				$new_section['contains_sections'] = isset($_POST['contains_sections']) ? $_POST['contains_sections'][$i] : '0';
			}

			$new_sections[$i] = $new_section;
		}


		//make sure there's at least one section
		if( !$new_sections ){
			message($langmessage['OOPS'].' (1 Section Minimum)');
			return false;
		}


		$this->file_sections = $new_sections;
		$this->ResetFileTypes(false);


		// save a send message to user
		if( !$this->SaveThis() ){
			$this->file_sections = $original_sections;
			message($langmessage['OOPS'].'(4)');
			return;
		}

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);


		//update gallery info
		$this->GalleryEdited();


		//update usage of resized images
		foreach($unused_sections as $section_data){
			if( isset($section_data['resized_imgs']) ){
				includeFile('image.php');
				gp_resized::SetIndex();
				gp_edit::ResizedImageUse($section_data['resized_imgs'],array());
			}
		}

	}


	/**
	 * Perform various section editing commands
	 *
	 */
	function SectionEdit($cmd){
		global $page, $langmessage;

		$section_num = $_REQUEST['section'];
		if( !is_numeric($section_num) || !isset($this->file_sections[$section_num])){
			echo 'false;';
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
		gp_rename::RenameForm( $this->gp_index, $action );
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


		return gpFiles::SaveData($this->file,'file_sections',$this->file_sections,$this->meta_data);
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

		$contents = gpFiles::GetRaw($this->file);

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
			echo common::Link($this->title,$langmessage['preview'],'cmd=view_revision&time='.$time,array('data-cmd'=>'cnreq'));
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

			ob_start();
			readgzfile($full_path);
			$contents	= ob_get_clean();

			$dir		= common::DirName($full_path);
			$full_path	= tempnam($dir,'backup');

			gpFiles::Save( $full_path, $contents );

			$file_sections	= gpFiles::Get($full_path,'file_sections');

			unlink($full_path);

		}else{
			$file_sections	= gpFiles::Get($full_path,'file_sections');
		}


		$this->contentBuffer = section_content::Render($file_sections,$this->title,gpFiles::$last_stats);


		$date		= common::date($langmessage['strftime_datetime'],$time);
		$message	= sprintf($langmessage['viewing_revision'],$date);
		$message	.= ' <br/> '.common::Link($this->title,$langmessage['Restore this revision'],'cmd=use_revision&time='.$time,array('data-cmd'=>'cnreq'));
		$message	.= ' &nbsp; '.common::Link($this->title,$langmessage['Revision History'],'cmd=view_history',array('title'=>$langmessage['Revision History'],'data-cmd'=>'gpabox'));

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

		//add to all pages in case a user adds a gallery
		gpPlugin::Action('GenerateContent_Admin');
		common::ShowingGallery();

		$content				= '';
		$sections_count			= count($this->file_sections);
		$section_num			= 0;

		do{

			$content .= $this->GetSection( $section_num );
		}while( $section_num < $sections_count );


		return $content;
	}

	function GetSection(&$section_num){
		global $langmessage, $GP_NESTED_EDIT;


		if( !isset($this->file_sections[$section_num]) ){
			trigger_error('invalid section number');
			return;
		}

		$curr_section_num								= $section_num;
		$section_num++;


		$content										= '';
		$section_data									= $this->file_sections[$curr_section_num];
		$section_data									+= array('attributes' => array(),'type'=>'text' );
		$section_data['attributes']						+= array('class' => '' );
		$orig_attrs										= json_encode($section_data['attributes']);
		$section_data['attributes']['data-gp-section']	= $curr_section_num;
		$section_types									= section_content::GetTypes();


		if( gpOutput::ShowEditLink() && admin_tools::CanEdit($this->gp_index) ){


			if( isset($section_types[$section_data['type']]) ){
				$title_attr		= $section_types[$section_data['type']]['label'];
			}else{
				$title_attr		= sprintf($langmessage['Section %s'],$curr_section_num+1);
			}

			$attrs			= array('title'=>$title_attr,'data-cmd'=>'inline_edit_generic','data-arg'=>$section_data['type'].'_inline_edit');
			$link			= gpOutput::EditAreaLink($edit_index,$this->title,$langmessage['edit'],'section='.$curr_section_num.'&amp;revision='.$this->fileModTime,$attrs);


			//section control links
			if( $section_data['type'] != 'wrapper_section' ){
				ob_start();
				echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
				echo $link;
				echo common::Link($this->title,$langmessage['Manage Sections'].'...','cmd=ManageSections',array('data-cmd'=>'inline_edit_generic','data-arg'=>'manage_sections'));
				echo '</span>';
				gpOutput::$editlinks .= ob_get_clean();
			}

			$section_data['attributes']['id']		= 'ExtraEditArea'.$edit_index;
			$section_data['attributes']['class']	.= ' editable_area'; // class="edit_area" added by javascript
		}

		$content			.= "\n".'<div'.section_content::SectionAttributes($section_data['attributes'],$section_data['type']).' data-gp-attrs=\''.htmlspecialchars($orig_attrs,ENT_QUOTES & ~ENT_COMPAT).'\'>';

		if( $section_data['type'] == 'wrapper_section' ){

			for( $cc=0; $cc < $section_data['contains_sections']; $cc++ ){
				$content		.= $this->GetSection($section_num);
			}

		}else{
			$GP_NESTED_EDIT		= true;
			$content			.= section_content::RenderSection($section_data,$curr_section_num,$this->title,$this->file_stats);
			$GP_NESTED_EDIT		= false;
		}

		$content			.= '<div class="gpclear"></div>';
		$content			.= '</div>';


		return $content;
	}


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
