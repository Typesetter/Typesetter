<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/editing.php');
includeFile('tool/SectionContent.php');

class admin_extra{

	var $folder;
	var $areas = array();

	function admin_extra(){
		global $langmessage, $dataDir;

		$this->folder = $dataDir.'/data/_extra';
		$this->Getdata();

		$cmd = common::GetCommand();

		$show = true;
		switch($cmd){


			case 'delete';
				$this->DeleteArea();
			break;

			case 'new_section':
				$this->NewSection();
			break;

			case 'view':
				$this->PreviewText();
				$show = false;
			break;

			case 'edit':
				if( $this->EditExtra() ){
					$show = false;
				}
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
				gp_edit::NewDirForm();
			return;

			/* inline editing */
			case 'save':
			case 'inlineedit':
			case 'include_dialog':
			case 'preview':
				$this->SectionEdit($cmd);
			return;

		}

		if( $show ){
			$this->ShowExtras();
		}
	}

	function Getdata(){
		$this->areas = gpFiles::ReadDir($this->folder);
		asort($this->areas);
	}


	/**
	 * Send the content of the extra area to the client in a json response
	 * @deprecated 3.6
	 *
	 */
	function RawContent(){
		global $page,$langmessage;

		trigger_error('RawContent() is a deprecated function');

		//for ajax responses
		$page->ajaxReplace = array();


		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$data = gpOutput::ExtraContent($title);
		$page->ajaxReplace[] = array('rawcontent','',$data['content']);
	}

	/**
	 * Delete an extra content area
	 *
	 */
	function DeleteArea(){
		global $langmessage;

		$title =& $_POST['file'];
		$file = $this->ExtraExists($title);
		if( !$file ){
			message($langmessage['OOPS']);
			return;
		}

		if( unlink($file) ){
			message($langmessage['SAVED']);
			unset($this->areas[$title]);
		}else{
			message($langmessage['OOPS']);
		}
	}

	/**
	 * Check to see if the extra area exists
	 *
	 */
	function ExtraExists($file){
		global $dataDir;

		if( !isset($this->areas[$file]) ){
			return false;
		}

		return $this->folder.'/'.$file.'.php';
	}


	function ShowExtras(){
		global $langmessage;

		$types = section_content::GetTypes();

		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered full_width">';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Content Type'];
		echo '</th><th>&nbsp;</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';

		$i = 0;
		foreach($this->areas as $file){
			$extraName = $file;
			$data = gpOutput::ExtraContent($file);

			if( $i%2 == 0 ){
				echo '<tr class="even">';
			}else{
				echo '<tr>';
			}

			echo '<td style="white-space:nowrap">';
			echo str_replace('_',' ',$extraName);
			echo '</td><td>';
			$type = $data['type'];
			if( isset($types[$type]) && isset($types[$type]['label']) ){
				$type = $types[$type]['label'];
			}
			echo $type;
			echo '</td><td>"<span class="admin_note">';
			$content = strip_tags($data['content']);
			echo substr($content,0,50);
			echo '</span>..."</td><td style="white-space:nowrap">';

			if( $data['type'] == 'text' ){
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo ' &nbsp; ';
			}

			echo common::Link('Admin_Extra',$langmessage['preview'],'cmd=view&file='.$file);
			echo ' &nbsp; ';

			$title = sprintf($langmessage['generic_delete_confirm'],htmlspecialchars($file));
			echo common::Link('Admin_Extra',$langmessage['delete'],'cmd=delete&file='.$file,array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));
			echo '</td></tr>';
			$i++;
		}

		echo '</table>';

		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Extra').'" method="post">';
		echo '<input type="hidden" name="cmd" value="new_section" />';
		echo '<input type="text" name="file" value="" size="15" class="gpinput"/> ';
		echo '<select name="type" class="gpselect">';
		foreach($types as $type => $info){
			echo '<option value="'.$type.'">'.$info['label'].'</option>';
		}
		echo '</select> ';
		echo '<input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gppost gpsubmit"/>';
		echo '</form>';
		echo '</p>';

	}


	function EditExtra(){
		global $langmessage;

		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$data = gpOutput::ExtraContent($title);

		echo '<form action="'.common::GetUrl('Admin_Extra','file='.$title).'" method="post">';
		echo '<h2>';
		echo common::Link('Admin_Extra',$langmessage['theme_content']);
		echo ' &#187; '.str_replace('_',' ',$title).'</h2>';
		echo '<input type="hidden" name="cmd" value="save" />';

		gp_edit::UseCK( $data['content'] );

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</form>';
		return true;
	}


	/**
	 * Create a new extra content section
	 *
	 */
	function NewSection(){
		global $langmessage;

		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$data = gp_edit::DefaultContent($_POST['type']);
		$file = $this->folder.'/'.$title.'.php';

		if( !gpFiles::SaveArray($file,'extra_content',$data) ){
			message($langmessage['OOPS']);
			$this->EditExtra();
			return false;
		}
		message($langmessage['SAVED']);
		$this->Getdata();
	}


	/**
	 * Preview
	 *
	 */
	function PreviewText(){
		global $langmessage;
		$file = gp_edit::CleanTitle($_REQUEST['file']);

		echo '<h2>';
		echo common::Link('Admin_Extra',$langmessage['theme_content']);
		echo ' &#187; '.str_replace('_',' ',$file).'</h2>';
		echo '</h2>';

		gpOutput::GetExtra($file);
	}

	function GalleryImages(){

		if( isset($_GET['dir']) ){
			$dir_piece = $_GET['dir'];
		//}elseif( isset($this->meta_data['gallery_dir']) ){
		//	$dir_piece = $this->meta_data['gallery_dir'];
		}else{
			$dir_piece = '/image';
		}
		//remember browse directory
		$this->meta_data['gallery_dir'] = $dir_piece;
		//$this->SaveThis();

		includeFile('admin/admin_uploaded.php');
		admin_uploaded::InlineList($dir_piece);
	}


	/**
	 * Perform various section editing commands
	 *
	 */
	function SectionEdit($cmd){
		global $page, $langmessage;

		if( empty($_REQUEST['file']) ){
			message($langmessage['OOPS']);
			return false;
		}

		$page->ajaxReplace = array();

		$file = gp_edit::CleanTitle($_REQUEST['file']);
		$data = gpOutput::ExtraContent( $file, $file_stats );

		$page->file_sections = array( $data ); //hack so the SaveSection filter works
		$page->file_stats = $file_stats;
		if( !gp_edit::SectionEdit( $cmd, $data, 0, '', $file_stats ) ){
			return;
		}

		//save the new content
		$file_full = $this->folder.'/'.$file.'.php';
		if( !gpFiles::SaveArray( $file_full, 'extra_content', $data ) ){
			message($langmessage['OOPS']);
			$this->EditExtra();
			return false;
		}


		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		$this->areas[$file] = $file;
		$this->EditExtra();
		return true;

	}

}
