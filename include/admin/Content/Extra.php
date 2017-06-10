<?php

namespace gp\admin\Content;

defined('is_running') or die('Not an entry point...');

class Extra extends \gp\Page\Edit{

	public $folder;
	public $areas = array();
	protected $page;
	protected $area_info;

	public function __construct($args){
		global $dataDir;

		$this->page = $args['page'];

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


			/* inline editing */
			$this->cmds['save']					= 'SectionEdit';
			$this->cmds['save_inline']			= 'SectionEdit';
			$this->cmds['preview']				= 'SectionEdit';
			$this->cmds['include_dialog']		= 'SectionEdit';
			$this->cmds['InlineEdit']			= 'SectionEdit';


		}


		$this->cmds['gallery_folder']			= 'GalleryImages';
		$this->cmds['gallery_images']			= 'GalleryImages';
		$this->cmds['new_dir']					= '\\gp\\tool\\Editing::NewDirForm';


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



		$area_info				= $this->ExtraExists($_REQUEST['file']);

		if( is_null($area_info) ){
			message($langmessage['OOPS'].' (Invalid File)');
			return;
		}

		$this->area_info		= $area_info;
		$this->file				= $area_info['file_path'];
		$this->title			= \gp\tool\Editing::CleanTitle($area_info['title']);
		$this->draft_file		= $area_info['draft_path'];

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
			$this->AddArea($file);
		}

		uksort($this->areas,'strnatcasecmp');
	}


	/**
	 * Add $file to the list of areas
	 *
	 */
	private function AddArea($title){

		$title	= self::AreaExists($title);

		if( $title == false ){
			return;
		}

		$this->areas[$title]					= array();
		$this->areas[$title]['title']			= $title;
		$this->areas[$title]['file_path']		= $this->folder.'/'.$title.'/page.php';
		$this->areas[$title]['draft_path']		= $this->folder.'/'.$title.'/draft.php';
		$this->areas[$title]['legacy_path']		= $this->folder.'/'.$title.'.php';
	}

	/**
	 * Return the area name if valid
	 *
	 */
	public static function AreaExists($title){
		global $dataDir;

		if( $title ==  '.' || $title == '..' ){
			return false;
		}

		$legacy	= $dataDir.'/data/_extra/'.$title;
		$new	= $dataDir.'/data/_extra/'.$title.'/page.php';
		$php	= (substr($title,-4) === '.php');

		if( !$php && is_dir($legacy) && \gp\tool\Files::Exists($new) ){ //is_dir() used to prevent open_basedir notice http://www.typesettercms.com/Forum?show=t2110
			return $title;
		}

		if( $php && \gp\tool\Files::Exists($legacy) ){
			return substr($title,0,-4);
		}

		return false;
	}


	/**
	 * Delete an extra content area
	 *
	 */
	public function DeleteArea(){
		global $langmessage;

		if( $this->_DeleteArea() ){
			unset($this->areas[$this->title]);
		}else{
			message($langmessage['OOPS']);
		}

	}

	private function _DeleteArea(){

		//legacy path
		if( \gp\tool\Files::Exists($this->area_info['legacy_path']) && !unlink($this->area_info['legacy_path']) ){
			return false;
		}

		//remove directory
		$dir = dirname($this->area_info['draft_path']);
		if( file_exists($dir) && !\gp\tool\Files::RmAll($dir) ){
			return false;
		}


		return true;
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

		return $this->areas[$file];
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

		foreach($this->areas as $file => $info){
			$this->ExtraRow($info, $types);
		}

		echo '</tbody>';
		echo '</table>';

		$this->NewExtraForm();
	}


	/**
	 * Display extra content row
	 *
	 */
	public function ExtraRow($info, $types){
		global $langmessage;


		$sections		= \gp\tool\Output::ExtraContent($info['title']);
		$section		= $sections[0];


		echo '<tr><td style="white-space:nowrap">';
		echo str_replace('_',' ',$info['title']);
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
		echo \gp\tool::Link('Admin/Extra',$langmessage['preview'],'cmd=PreviewText&file='.rawurlencode($info['title']));
		echo ' &nbsp; ';


		//publish
		if( \gp\tool\Files::Exists($info['draft_path']) ){
			echo \gp\tool::Link('Admin/Extra',$langmessage['Publish Draft'],'cmd=PublishDraft&file='.rawurlencode($info['title']),array('data-cmd'=>'creq'));
		}else{
			echo '<span class="text-muted">'.$langmessage['Publish Draft'].'</span>';
		}
		echo ' &nbsp; ';


		//edit
		if( $section['type'] == 'text' ){
			echo \gp\tool::Link('Admin/Extra',$langmessage['edit'],'cmd=EditExtra&file='.rawurlencode($info['title']));
		}else{
			echo '<span class="text-muted">'.$langmessage['edit'].'</span>';
		}
		echo ' &nbsp; ';


		$title = sprintf($langmessage['generic_delete_confirm'],htmlspecialchars($info['title']));
		echo \gp\tool::Link('Admin/Extra',$langmessage['delete'],'cmd=DeleteArea&file='.rawurlencode($info['title']),array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));
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
		global $langmessage, $page;

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra',$langmessage['theme_content']);
		echo ' &#187; '.str_replace('_',' ',$this->title).'</h2>';

		echo '<form action="'.\gp\tool::GetUrl('Admin/Extra','file='.$this->title).'" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveText" />';

		\gp\tool\Editing::UseCK( $this->file_sections[0]['content'] );

		$page->jQueryCode .= '
			$(function(){
				CKEDITOR.instances.gpcontent.on("change", function(){
					if( CKEDITOR.instances.gpcontent.checkDirty() ){
						$(".gp_publish_extra").hide();
						$(".gp_save_extra").show();
					}else{
						$(".gp_publish_extra").show();
						$(".gp_save_extra").hide();
					}
				});
				$(".gp_save_extra").on("click", function(){
					CKEDITOR.instances.gpcontent.resetDirty();
				});
			});
			$(window).on("beforeunload", function(){
				if( CKEDITOR.instances.gpcontent.checkDirty() ){
					return "Content was changed! Proceed anyway?";
				}
			});
		';

		if( $this->draft_exists ){
			echo '<input style="display:none;" type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit gp_save_extra" />';
			echo '<button type="submit" name="cmd" class="gpsubmit gp_publish_extra" value="PublishDraft">' . $langmessage['Publish Draft'] . '</button>';
		}else{
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit gp_save_extra" />';
		}
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

		$title = str_replace( array('\\','/'), '', $_REQUEST['new_title']);
		$title = \gp\tool\Editing::CleanTitle($title);

		if( empty($title) ){
			message($langmessage['OOPS'].' (Invalid Title)');
			return false;
		}

		$types = \gp\tool\Output\Sections::GetTypes();
		$type = htmlspecialchars($_POST['type']);

		if( !array_key_exists($type, $types) ){
			message($langmessage['OOPS'].' (Invalid Type)');
			return false;
		}

		$file					= $this->folder.'/'.$title.'/page.php';

		$section				= \gp\tool\Editing::DefaultContent($type);
		$section['created']		= time();
		$section['created_by']	= $gpAdmin['username'];

		$sections				= array($section);


		if( !\gp\tool\Files::SaveData( $file, 'file_sections', $sections ) ){
			message($langmessage['OOPS'].' (Not Saved)');
			return false;
		}


		message($langmessage['SAVED']);

		$this->AddArea($title);
	}


	/**
	 * Perform various section editing commands
	 *
	 */
	public function SectionEdit(){
		global $langmessage;

		$this->page->file_sections	=& $this->file_sections; //hack so the SaveSection filter works
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
