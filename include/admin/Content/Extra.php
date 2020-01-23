<?php

namespace gp\admin\Content;

defined('is_running') or die('Not an entry point...');

class Extra extends \gp\Page\Edit{

	public $folder;
	public $areas = array();
	protected $page;
	protected $area_info;
	protected $vis;
	protected $extra_part;


	public function __construct($args){
		global $dataDir;

		if( array_key_exists('page',$args) ){
			$this->page = $args['page'];
		}

		$this->folder = $dataDir . '/data/_extra';

		if( !empty($args['path_parts']) ){
			$this->extra_part = $args['path_parts'][0];
		}

		$this->SetVars();
	}


	public function RunScript(){

		// area specific commands
		if( !is_null($this->file) ){

			$this->cmds['PublishAjax']			= '';
			$this->cmds['EditExtra']			= '';
			$this->cmds['PreviewText']			= '';
			$this->cmds['EditVisibility']		= '';
			$this->cmds['PublishDraft']			= 'Redirect';
			$this->cmds['DismissDraft']			= 'Redirect';


			$this->cmds_post['SaveText']				= 'Redirect';
			$this->cmds_post['SaveVisibilityExtra'] 	= 'Redirect';
			$this->cmds_post['DeleteArea']				= 'DefaultDisplay';


			// inline editing
			$this->cmds['save']					= 'SectionEdit';
			$this->cmds['save_inline']			= 'SectionEdit';
			$this->cmds['preview']				= 'SectionEdit';
			$this->cmds['include_dialog']		= 'SectionEdit';
			$this->cmds['InlineEdit']			= 'SectionEdit';
		}


		$this->cmds['gallery_folder']			= 'GalleryImages';
		$this->cmds['gallery_images']			= 'GalleryImages';
		$this->cmds['new_dir']					= '\\gp\\tool\\Editing::NewDirForm';
		$this->cmds['Image_Editor']				= '\\gp\\tool\\Editing::ImageEditor';

		$this->cmds['NewSection'] 				= 'DefaultDisplay';

		$cmd = \gp\tool::GetCommand();
		$this->RunCommands($cmd);
	}



	/**
	 * Get a list of all extra edit areas
	 *
	 */
	public function SetVars(){
		global $langmessage;

		$this->GetAreas();

		if( !$this->extra_part ){
			return;
		}


		// is there a specific file being requested
		$area_info = $this->ExtraExists($this->extra_part);

		if( is_null($area_info) ){
			msg($langmessage['OOPS'] . ' (Invalid File)');
			return;
		}

		$this->area_info		= $area_info;
		$this->file				= $area_info['file_path'];
		$this->title			= \gp\tool\Editing::CleanTitle($area_info['title']);
		$this->draft_file		= $area_info['draft_path'];

		$this->file_sections	= \gp\tool\Output\Extra::ExtraContent($this->title);
		$this->meta_data		= \gp\tool\Files::$last_meta;
		$this->fileModTime		= \gp\tool\Files::$last_modified;
		$this->file_stats		= \gp\tool\Files::$last_stats;

		$this->vis				= \gp\tool\Files::Get('_extra/' . $this->title . '/visibility', 'data');
		$this->vis				+= ['visibility_type'=>'0','pages'=>[]];

		$this->draft_exists		= \gp\tool\Files::Exists($this->draft_file);

	}



	/**
	 * Get a list of all extra edit areas
	 *
	 */
	public function GetAreas(){

		$this->areas	= [];
		$files			= scandir($this->folder);

		foreach( $files as $file ){

			$title = self::AreaExists($file);

			if( $title === false ){
				continue;
			}

			$this->areas[$title] = [
									'title'			=> $title,
									'file_path'		=> \gp\tool\Files::FilePath($this->folder . '/' . $title . '/page.php'),
									'draft_path'	=> \gp\tool\Files::FilePath($this->folder . '/' . $title . '/draft.php'),
									'legacy_path'	=> \gp\tool\Files::FilePath($this->folder . '/' . $title . '.php'),
								];

		}

		uksort($this->areas, 'strnatcasecmp');
	}


	/**
	 * Return the area name if valid
	 *
	 */
	public static function AreaExists($title){
		global $dataDir;

		if ($title == '.' || $title == '..'){
			return false;
		}

		$legacy		= $dataDir . '/data/_extra/' . $title;
		$new		= $dataDir . '/data/_extra/' . $title . '/page.php';


		if( is_dir($legacy) && \gp\tool\Files::Exists($new) ){ //is_dir() used to prevent open_basedir notice http://www.typesettercms.com/Forum?show=t2110
			return $title;
		}

		if( substr($title, -4) === '.php' ){
			return substr($title, 0, -4);
		}

		return false;
	}



	/**
	 * Delete an extra content area
	 *
	 */
	public function DeleteArea(){
		global $langmessage;

		//legacy path
		if( \gp\tool\Files::Exists($this->area_info['legacy_path']) && !unlink($this->area_info['legacy_path']) ){
			msg($langmessage['OOPS']);
			return false;
		}

		//remove directory
		$dir = dirname($this->area_info['draft_path']);
		if( file_exists($dir) && !\gp\tool\Files::RmAll($dir) ){
			msg($langmessage['OOPS']);
			return false;
		}

		unset($this->areas[$this->title]);

		return true;
	}



	/**
	 * Check to see if the extra area exists
	 *
	 */
	public function ExtraExists($file){

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

		echo '<h2>' . $langmessage['theme_content'] . '</h2>';
		echo '<table class="bordered full_width striped">';
		echo '<thead><tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Content Type'];
		echo '</th><th>&nbsp;</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';
		echo '</thead><tbody>';

		foreach ($this->areas as $file => $info) {
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

		$sections = \gp\tool\Output\Extra::ExtraContent($info['title']);
		$section = $sections[0];

		echo '<tr><td style="white-space:nowrap">';
		echo str_replace('_', ' ', $info['title']);
		echo '</td><td>';
		$type = $section['type'];
		if (isset($types[$type]) && isset($types[$type]['label'])){
			$type = $types[$type]['label'];
		}
		echo $type;
		echo '</td><td>"<span class="admin_note">';
		$content = strip_tags($section['content']);
		echo substr($content, 0, 50);
		echo '</span>..."</td><td style="white-space:nowrap">';

		//preview
		echo \gp\tool::Link('Admin/Extra/'.rawurlencode($info['title']), $langmessage['preview'], 'cmd=PreviewText');
		echo ' &nbsp; ';

		//publish & dismiss
		if (\gp\tool\Files::Exists($info['draft_path'])){
			echo \gp\tool::Link('Admin/Extra/' . rawurlencode($info['title']), $langmessage['Publish Draft'], 'cmd=PublishDraft', array('data-cmd' => 'post'));
			echo ' &nbsp; ';
			echo \gp\tool::Link('Admin/Extra/' . rawurlencode($info['title']), $langmessage['Dismiss Draft'], 'cmd=DismissDraft', array('data-cmd' => 'post'));
		} else {
			echo '<span class="text-muted">' . $langmessage['Publish Draft'] . '</span>';
			echo ' &nbsp; ';
			echo '<span class="text-muted">' . $langmessage['Dismiss Draft'] . '</span>';
		}

		echo ' &nbsp; ';

		//edit
		if ($section['type'] == 'text'){
			echo \gp\tool::Link('Admin/Extra/' . rawurlencode($info['title']), $langmessage['edit'], 'cmd=EditExtra');
		} else {
			echo '<span class="text-muted">' . $langmessage['edit'] . '</span>';
		}
		echo ' &nbsp; ';

		//visibility
		echo \gp\tool::Link('Admin/Extra/' . rawurlencode($info['title']), $langmessage['Visibility'], 'cmd=EditVisibility');
		echo ' &nbsp; ';

		$title = sprintf($langmessage['generic_delete_confirm'], htmlspecialchars($info['title']));
		echo \gp\tool::Link('Admin/Extra/' .  rawurlencode($info['title']), $langmessage['delete'], 'cmd=DeleteArea', array(
			'data-cmd' => 'postlink',
			'title' => $title,
			'class' => 'gpconfirm'));
		echo '</td></tr>';
	}



	/**
	 * Display form for defining a new extra edit area
	 *
	 */
	public function NewExtraForm(){
		global $langmessage;

		$types	= \gp\tool\Output\Sections::GetTypes();
		$_types	= [];
		foreach( $types as $type => $info ){
			$_types[$type] = $info['label'];
		}

		echo '<p>';
		echo '<form action="' . \gp\tool::GetUrl('Admin/Extra') . '" method="post">';
		echo '<input type="hidden" name="cmd" value="NewSection" />';
		echo '<input type="text" name="new_title" value="" size="15" class="gpinput" required/> ';

		echo \gp\tool\HTML::Select( $_types, key($_types), ' name="type" class="gpselect"');

		echo '<input type="submit" name="" value="' . $langmessage['Add New Area'] . '" class="gpsubmit gpvalidate" data-cmd="gppost"/>';
		echo '</form>';
		echo '</p>';
	}


	public function EditExtra(){
		global $langmessage, $page;


		$action				= \gp\tool::GetUrl('Admin/Extra/' . rawurlencode($this->title), 'cmd=EditExtra');
		$page->head_js[]	= '/include/js/admin/extra_edit.js';

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra', $langmessage['theme_content']);
		echo ' &#187; ' . str_replace('_', ' ', $this->title) . '</h2>';

		echo '<form action="' . $action . '" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveText" />';

		\gp\tool\Editing::UseCK($this->file_sections[0]['content']);

		echo '<button type="submit" class="gpsubmit gp_save_extra">' . $langmessage['save'] .'</button>';

		if( $this->draft_exists ){
			echo '<button type="submit" name="cmd" class="gpsubmit gp_publish_extra" value="DismissDraft">' . $langmessage['Dismiss Draft'] . '</button>';
			echo '<button type="submit" name="cmd" class="gpsubmit gp_publish_extra" value="PublishDraft">' . $langmessage['Publish Draft'] . '</button>';
		}

		echo \gp\tool::Link('Admin/Extra', $langmessage['Close'], '', array('class' => 'gpcancel'));

		echo '</form>';
	}


	public function SaveText(){
		global $langmessage;
		$_POST['cmd'] = 'save_inline';
		if( $this->SectionEdit() ){
			msg($langmessage['SAVED']);
		}
	}


	/**
	 * Preview
	 *
	 */
	public function PreviewText(){
		global $langmessage;

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra', $langmessage['theme_content']);
		echo ' &#187; ' . str_replace('_', ' ', $this->title);
		echo '</h2>';
		echo '<hr/>';

		$section_num = 0;
		\gp\tool\Output\Sections::SetVars('',$this->file_stats);
		echo \gp\tool\Output\Sections::GetSection($this->file_sections, $section_num);
		echo '<hr/>';
	}


	/**
	 * Create a new extra content section
	 *
	 */
	public function NewSection(){
		global $langmessage, $gpAdmin;

		$title = str_replace(['\\','/'], '', $_REQUEST['new_title']);
		$title = \gp\tool\Editing::CleanTitle($title);

		if (empty($title)){
			msg($langmessage['OOPS'] . ' (Invalid Title)');
			return false;
		}

		$types = \gp\tool\Output\Sections::GetTypes();
		$type = htmlspecialchars($_POST['type']);

		if (!array_key_exists($type, $types)){
			msg($langmessage['OOPS'] . ' (Invalid Type)');
			return false;
		}

		$file = $this->folder . '/' . $title . '/page.php';

		$section = \gp\tool\Editing::DefaultContent($type);
		$section['created'] = time();
		$section['created_by'] = $gpAdmin['username'];

		$sections = array($section);


		if (!\gp\tool\Files::SaveData($file, 'file_sections', $sections)){
			msg($langmessage['OOPS'] . ' (Not Saved)');
			return false;
		}


		msg($langmessage['SAVED']);

		$this->GetAreas();
	}


	/**
	 * Perform various section editing commands
	 *
	 */
	public function SectionEdit(){
		global $langmessage;

		$this->page->file_sections =& $this->file_sections; //hack so the SaveSection filter works
		$_REQUEST['section'] = 0;

		return parent::SectionEdit();
	}


	public function SaveBackup(){
	}

	public function GalleryEdited(){
	}

	public function ResetFileTypes(){
	}

	public function DismissDraft(){
		global $page;
		if( \gp\tool\Files::Exists($this->draft_file) && unlink($this->draft_file) ){
			$this->draft_exists = false;
		}

		$page->ajaxReplace		= array();
		$page->ajaxReplace[]	= array('DraftDismissed');

		return !$this->draft_exists;
	}


	public function EditVisibility(){
		global $langmessage, $page, $gp_index, $gp_titles;

		$action				= \gp\tool::GetUrl('Admin/Extra/' . rawurlencode($this->title), 'cmd=EditVisibility');
		$page->head_js[]	= '/include/thirdparty/tablesorter/tablesorter.js';
		$page->head_js[]	= '/include/js/admin/extra_visibility.js';

		echo '<h2>';
		echo \gp\tool::Link('Admin/Extra', $langmessage['theme_content']);
		echo ' &#187; ' . str_replace('_', ' ', $this->title);
		echo ' &#187; ' . $langmessage['Visibility'] . '</h2>';

		echo '<form action="' . $action . '" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveVisibilityExtra" />';

		echo '<p>';
		echo $langmessage['Visibility'] . ':&nbsp;&nbsp;&nbsp;';

		$sel_dat = array(
			'0' => $langmessage['Show on all pages'],
			'1' => $langmessage['Hide on all pages'],
			'2' => $langmessage['Show only on selected pages'],
			'3' => $langmessage['Hide on selected pages'],
		);

		echo \gp\tool\HTML::Select( $sel_dat, $this->vis['visibility_type'], ' name="visibility_type" id="vis_type" class="gpselect"');
		echo '</p>';

		echo '<div class="pages">';
		echo '<table id="myTable" class="bordered full_width striped tablesorter tp-tablesorter">';
		echo '<thead>';
		echo '<tr>';
		echo '<th><input name="check_all" id="check_all" type="checkbox"></th>';
		echo '<th>' . $langmessage["Pages"] . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach( $gp_index as $title => $index ){
			echo '<tr><td>';

			$check = '';
			if( array_key_exists($index, $this->vis['pages']) ){
				$check = 'checked';
			}

			echo '<input class="check_page" name="pages[' . $index . ']" type="checkbox" ' . $check . '>';
			echo '</td><td>';

			$label = \gp\tool::GetLabelIndex($index);
			echo '<a href="' . \gp\tool::AbsoluteUrl($title) . ' " target="_blank">' . $label . '</a>';
			echo '</td></tr> ';
		}

		echo '</tbody></table>';
		echo '</div>';
		echo '<br/>';
		echo '<p>';
		echo '<input type="submit" name="" value="' . $langmessage['save'] . '" class="gpsubmit gp_save_extra" />';
		echo \gp\tool::Link('Admin/Extra', $langmessage['Close'], '', array('class' => 'gpcancel'));
		echo '</p>';
		echo '</form>';

	}


	/**
	 * Save extra area visibility
	 *
	 */
	public function SaveVisibilityExtra(){
		global $langmessage, $gp_titles;

		$file						= '_extra/' . $this->title . '/visibility';
		$data						= [];
		$data['visibility_type']	= $_REQUEST['visibility_type'];

		if( isset($_REQUEST['pages']) && is_array($_REQUEST['pages']) ){
			$data['pages']			= array_intersect_key($_REQUEST['pages'], $gp_titles);
		}

		if( !\gp\tool\Files::SaveData($file, 'data', $data) ){
			msg($langmessage['OOPS']);
			return false;
		}

		msg($langmessage['SAVED']);
		return true;
	}


	/**
	 * Redirect the user request
	 *
	 */
	public function Redirect(){

		$req_type = \gp\tool::RequestType();

		if( $req_type != 'json' ){
			\gp\tool::Redirect(['Admin/Extra',$_GET]);
		}
	}

}
