<?php

namespace gp\admin\Content;

defined('is_running') or die('Not an entry point...');

class Extra extends \gp\Page\Edit{

	public $folder;
	public $areas = array();

	public function __construct(){
		global $dataDir;

		$this->folder = $dataDir.'/data/_extra';
		$this->SetVars();
	}


	public function RunScript(){

		// area specific commands
		if( !is_null($this->file) ){
			$this->cmds['DeleteArea']			= 'DefaultDisplay';
			$this->cmds['EditExtra']			= '';
			$this->cmds['PublishDraft']			= 'DefaultDisplay';
			$this->cmds['PublishAjax']			= '';
			$this->cmds['PreviewText']			= '';
			$this->cmds['SaveText']				= 'EditExtra';


			$this->cmds['gallery_folder']		= 'GalleryImages';
			$this->cmds['gallery_images']		= 'GalleryImages';
			$this->cmds['new_dir']				= '\\gp\\tool\\Editing::NewDirForm';

			/* inline editing */
			$this->cmds['save']					= 'SectionEdit';
			$this->cmds['save_inline']			= 'SectionEdit';
			$this->cmds['preview']				= 'SectionEdit';
			$this->cmds['include_dialog']		= 'SectionEdit';
			$this->cmds['InlineEdit']			= 'SectionEdit';


		}

		$this->cmds['NewSection']				= 'DefaultDisplay';



		$cmd	= \gp\tool::GetCommand();
		$this->RunCommands($cmd);
	}


	/**
	 * Get a list of all extra edit areas
	 *
	 */
	public function SetVars(){
		global $langmessage;

		$this->GetAreas();


		// is there a specific file being requested
		if( !isset($_REQUEST['file']) ){
			return;
		}

		$this->file				= $this->ExtraExists($_REQUEST['file']);

		if( is_null($this->file) ){
			message($langmessage['OOPS'].' (Invalid File)');
			return;
		}

		$this->title			= \gp\tool\Editing::CleanTitle($_REQUEST['file']);
		$this->draft_file		= dirname($this->file).'/draft.php';

		$this->file_sections	= \gp\tool\Output::ExtraContent($this->title);
		$this->meta_data		= \gp\tool\Files::$last_meta;
		$this->fileModTime		= \gp\tool\Files::$last_modified;
		$this->file_stats		= \gp\tool\Files::$last_stats;


		if( \gp\tool\Files::Exists($this->draft_file) ){
			$this->draft_exists = true;
		}

	}

	/**
	 * Get a list of all extra edit areas
	 *
	 */
	public function GetAreas(){

		$this->areas	= array();
		$files			= scandir($this->folder);

		foreach($files as $file){

			if( $file == '.' || $file == '..' ){
				continue;
			}

			$legacy	= $this->folder.'/'.$file;
			$new	= $this->folder.'/'.$file.'/page.php';

			if( file_exists($new) ){
				$this->areas[$file] = $file;

			}elseif( substr($file,-4) === '.php' && file_exists($legacy) ){
				$file = substr($file,0,-4);
				$this->areas[$file] = $file;

			}

		}

		uasort($this->areas,'strnatcasecmp');
	}


	/**
	 * Delete an extra content area
	 *
	 */
	public function DeleteArea(){
		global $langmessage;

		if( unlink($this->file) ){
			unset($this->areas[$title]);
		}else{
			message($langmessage['OOPS']);
		}
	}


	/**
	 * Check to see if the extra area exists
	 *
	 */
	public function ExtraExists($file){
		global $dataDir;

		if( !isset($this->areas[$file]) ){
			return;
		}

		return $this->folder.'/'.$file.'/page.php';
	}


	/**
	 * Show all available extra content areas
	 *
	 */
	public function DefaultDisplay(){
		global $langmessage;

		$types = \gp\tool\Output\Sections::GetTypes();

		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered full_width striped">';
		echo '<thead><tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Content Type'];
		echo '</th><th>&nbsp;</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';
		echo '</thead><tbody>';

		foreach($this->areas as $file){
			$this->ExtraRow($file);
		}

		echo '</tbody>';
		echo '</table>';

		$this->NewExtraForm();
	}


	/**
	 *
	 *
	 */
	public function ExtraRow($title){
		global $langmessage;

		$file			= $this->ExtraExists($title);
		$file_draft		= dirname($file).'/draft.php';
		$sections		= \gp\tool\Output::ExtraContent($title);
		$section		= $sections[0];


		echo '<tr><td style="white-space:nowrap">';
		echo str_replace('_',' ',$title);
		echo '</td><td>';
		$type = $section['type'];
		if( isset($types[$type]) && isset($types[$type]['label']) ){
			$type = $types[$type]['label'];
		}
		echo $type;
		echo '</td><td>"<span class="admin_note">';
		$content = strip_tags($section['content']);
		echo substr($content,0,50);
		echo '</span>..."</td><td style="white-space:nowrap">';

		//preview
		echo \gp\tool::Link('Admin/Extra',$langmessage['preview'],'cmd=PreviewText&file='.rawurlencode($title));
		echo ' &nbsp; ';


		//publish
		if( file_exists($file_draft) ){
			echo \gp\tool::Link('Admin/Extra',$langmessage['Publish Draft'],'cmd=PublishDraft&file='.rawurlencode($title),array('data-cmd'=>'creq'));
		}else{
			echo '<span class="text-muted">'.$langmessage['Publish Draft'].'</span>';
		}
		echo ' &nbsp; ';


		//edit
		if( $section['type'] == 'text' ){
			echo \gp\tool::Link('Admin/Extra',$langmessage['edit'],'cmd=EditExtra&file='.rawurlencode($title));
		}else{
			echo '<span class="text-muted">'.$langmessage['edit'].'</span>';
		}
		echo ' &nbsp; ';


		$title = sprintf($langmessage['generic_delete_confirm'],htmlspecialchars($title));
		echo \gp\tool::Link('Admin/Extra',$langmessage['delete'],'cmd=DeleteArea&file='.rawurlencode($title),array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));
		echo '</td></tr>';
	}


	/**
	 * Display form for defining a new extra edit area
	 *
	 */
	public function NewExtraForm(){
		global $langmessage;

		$types = \gp\tool\Output\Sections::GetTypes();
		echo '<p>';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Extra').'" method="post">';
		echo '<input type="hidden" name="cmd" value="NewSection" />';
		echo '<input type="text" name="new_title" value="" size="15" class="gpinput" required/> ';
		echo '<select name="type" class="gpselect">';
		foreach($types as $type => $info){
			echo '<option value="'.$type.'">'.$info['label'].'</option>';
		}
		echo '</select> ';
		echo '<input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gpsubmit gpvalidate" data-cmd="gppost"/>';
		echo '</form>';
		echo '</p>';
	}


	public function EditExtra(){
		global $langmessage;

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra',$langmessage['theme_content']);
		echo ' &#187; '.str_replace('_',' ',$this->title).'</h2>';

		echo '<form action="'.\gp\tool::GetUrl('Admin/Extra','file='.$this->title).'" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveText" />';

		\gp\tool\Editing::UseCK( $this->file_sections[0]['content'] );

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</form>';
	}


	public function SaveText(){
		global $langmessage;
		$_POST['cmd'] = 'save_inline';
		if( $this->SectionEdit() ){
			message($langmessage['SAVED']);
		}
	}


	/**
	 * Preview
	 *
	 */
	public function PreviewText(){
		global $langmessage;

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra',$langmessage['theme_content']);
		echo ' &#187; '.str_replace('_',' ',$this->title).'</h2>';
		echo '</h2>';
		echo '<hr/>';

		echo \gp\tool\Output\Sections::RenderSection($this->file_sections[0],0,'',$this->file_stats);
		echo '<hr/>';
	}


	/**
	 * Create a new extra content section
	 *
	 */
	public function NewSection(){
		global $langmessage, $gpAdmin;

		$title = \gp\tool\Editing::CleanTitle($_REQUEST['new_title']);
		if( empty($title) ){
			message($langmessage['OOPS'].' (Invalid Title)');
			return false;
		}

		$file					= $this->folder.'/'.$title.'/page.php';

		$section				= \gp\tool\Editing::DefaultContent($_POST['type']);
		$section['created']		= time();
		$section['created_by']	= $gpAdmin['username'];

		$sections				= array($section);


		if( !\gp\tool\Files::SaveData( $file, 'file_sections', $sections ) ){
			message($langmessage['OOPS'].' (Not Saved)');
			return false;
		}


		message($langmessage['SAVED']);

		$this->areas[$title] = $title;
	}


	/**
	 * Publish draft of extra content area
	 *
	 */
	public function PublishAjax(){
		global $page;

		$page->ajaxReplace = array();

		if( !$this->PublishDraft() ){
			return;
		}

		$page->ajaxReplace[] = array('DraftPublished');
		msg('published');
	}


	/**
	 * Perform various section editing commands
	 *
	 */
	public function SectionEdit(){
		global $page, $langmessage;

		$page->file_sections	=& $this->file_sections; //hack so the SaveSection filter works
		$_REQUEST['section']	= 0;

		if( !parent::SectionEdit() ){
			return false;
		}

		return true;
	}


	public function SaveBackup(){}
	public function GalleryEdited(){}
	public function ResetFileTypes(){}


}
