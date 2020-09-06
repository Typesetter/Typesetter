<?php

namespace gp\Page;

defined('is_running') or die('Not an entry point...');

class Edit extends \gp\Page{

	protected	$draft_file;
	protected	$draft_exists = false;

	protected	$permission_edit;
	protected	$permission_menu;

	private		$checksum;


	public function __construct($title, $type){
		parent::__construct($title, $type);
	}



	public function RunScript(){
		global $langmessage;

		if( !$this->SetVars() ){
			return;
		}

		ob_start();

		$this->GetFile();
		$cmd = \gp\tool::GetCommand();
		$this->RunCommands($cmd);

		$this->contentBuffer .= ob_get_clean();
	}



	/**
	 * Run Commands
	 *
	 */
	public function RunCommands($cmd){

		//allow addons to effect page actions and how a page is displayed
		$cmd = \gp\tool\Plugins::Filter('PageRunScript', [$cmd]);
		if( $cmd === 'return' ){
			return;
		}

		parent::RunCommands($cmd);
	}



	/**
	 * SetVars
	 *
	 */
	public function SetVars(){
		global $dataDir;

		if( !parent::SetVars() ){
			return false;
		}

		$this->permission_edit	= \gp\admin\Tools::CanEdit($this->gp_index);
		$this->permission_menu	= \gp\admin\Tools::HasPermission('Admin_Menu');
		$this->draft_file		= dirname($this->file) . '/draft.php';

		//admin actions
		if( $this->permission_menu ){
			$this->cmds['RenameForm']				= '\\gp\\Page\\Rename::RenameForm';
			$this->cmds['RenameFile']				= '\\gp\\Page\\Rename::RenamePage';
			$this->cmds['ToggleVisibility']			= ['\\gp\\Page\\Visibility::TogglePage', 'DefaultDisplay'];
		}

		if( $this->permission_edit ){
			/* gallery/image editing */
			$this->cmds['Gallery_Folder']			= 'GalleryImages';
			$this->cmds['Gallery_Images']			= 'GalleryImages';
			$this->cmds['Image_Editor']				= '\\gp\\tool\\Editing::ImageEditor';
			$this->cmds['New_Dir']					= '\\gp\\tool\\Editing::NewDirForm';

			$this->cmds['ManageSections']			= '';
			$this->cmds['SaveSections']				= '';
			$this->cmds['SaveToClipboard']			= '';
			$this->cmds['RemoveFromClipboard']		= '';
			$this->cmds['RelabelClipboardItem']		= '';
			$this->cmds['ReorderClipboardItems']	= '';
			$this->cmds['AddFromClipboard']			= '';

			$this->cmds['ViewRevision']				= '';
			$this->cmds['ViewCurrent']				= '';
			$this->cmds['PublishDraft']				= 'DefaultDisplay';

			/* inline editing */
			$this->cmds['Save']						= 'SectionEdit';
			$this->cmds['Save_Inline']				= 'SectionEdit';
			$this->cmds['Preview']					= 'SectionEdit';
			$this->cmds['Include_Dialog']			= 'SectionEdit';
			$this->cmds['InlineEdit']				= 'SectionEdit';
		}

		if( !\gp\tool\Files::Exists($this->draft_file) ){
			return true;
		}

		$this->draft_exists = true;

		return true;
	}


	/**
	 * Display after commands have been executed
	 *
	 */
	public function DefaultDisplay(){

		//add to all pages in case a user adds a gallery
		\gp\tool\Plugins::Action('GenerateContent_Admin');
		\gp\tool::ShowingGallery();

		$sections_count			= count($this->file_sections);
		$this->file_sections	= array_values($this->file_sections);
		$section_num			= 0;

		while( $section_num < $sections_count ){
			echo $this->GetSection($section_num);
		}
	}


	/**
	 * Get the data file, get draft file if it exists
	 *
	 */
	public function GetFile(){

		parent::GetFile();

		if( $this->draft_exists ){
			$this->file_sections	= \gp\tool\Files::Get($this->draft_file, 'file_sections');
			$this->meta_data		= \gp\tool\Files::$last_meta;
			$this->file_stats		= \gp\tool\Files::$last_stats;
		}

		$this->checksum				= $this->Checksum();
	}


	/**
	 * Generate admin toolbar links
	 *
	 */
	public function AdminLinks(){
		global $langmessage, $config;

		$admin_links = [];

		// HideAdminUI
		$admin_links[] = \gp\tool::Link(
			$this->title,
			'<i class="fa fa-eye-slash"></i>',
			'',
			[
				'title'		=> $langmessage['Hide Admin UI'],
				'class'		=> 'admin-link admin-link-hide-ui',
				'data-cmd'	=> 'hide_ui',
			]
		);

		// page options: less frequently used links that don't have to do with editing the content of the page
		$option_links = [];
		if( $this->permission_menu ){
			$option_links[] = \gp\tool::Link(
				$this->title,
				$langmessage['rename/details'],
				'cmd=renameform&index=' . urlencode($this->gp_index),
				[
					'class'		=> 'admin-link admin-link-rename-details',
					'data-cmd'	=> 'gpajax',
				]
			);
			$option_links[] = \gp\tool::Link(
				'Admin/Menu',
				$langmessage['current_layout'],
				'cmd=layout&from=page&index=' . urlencode($this->gp_index),
				[
					'title'		=> $langmessage['current_layout'],
					'class'		=> 'admin-link admin-link-current-laout',
					'data-cmd'	=> 'gpabox',
				]
			);
			$option_links[] = \gp\tool::Link(
				'Admin/Menu/Ajax',
				$langmessage['Copy'],
				'cmd=CopyForm&redir=redir&index=' . urlencode($this->gp_index),
				[
					'title'		=> $langmessage['Copy'],
					'class'		=> 'admin-link admin-link-copy-page',
					'data-cmd'	=> 'gpabox'
				]
			);
		}

		if( \gp\admin\Tools::HasPermission('Admin_User') ){
			$option_links[] = \gp\tool::Link(
				'Admin/Permissions',
				$langmessage['permissions'],
				'index=' . urlencode($this->gp_index),
				[
					'title'		=> $langmessage['permissions'],
					'class'		=> 'admin-link admin-link-permissions',
					'data-cmd'	=> 'gpabox',
				]
			);
		}

		if( $this->permission_menu ){
			$option_links[]	= self::ToggleVisibilityLink($this->gp_index, $this->visibility != 'private');
		}

		if( $this->permission_menu ){
			$option_links[] = \gp\tool::Link(
				'Admin/Menu/Ajax',
				$langmessage['delete_file'],
				'cmd=MoveToTrash&index=' . urlencode($this->gp_index),
				[
					'title'		=> $langmessage['delete_page'],
					'class'		=>'gpconfirm admin-link admin-link-delete-page',
					'data-cmd'	=> 'postlink',
				]
			);
		}

		if( !empty($option_links) ){
			$admin_links[$langmessage['options']] = $option_links;
		}

		//publish draft
		if( $this->permission_edit && $this->draft_exists ){
			$admin_links[] = \gp\tool::Link(
				$this->title,
				'<i class="fa fa-check"></i> ' . $langmessage['Publish Draft'],
				'cmd=PublishDraft',
				[
					'class'		=> 'msg_publish_draft admin-link admin-link-publish-draft',
					'data-cmd'	=> 'creq',
				]
			)
			. '<a class="msg_publish_draft_disabled admin-link admin-link-publish-draft-disabled">'
			.   '<i class="fa fa-minus-circle"></i> ' . $langmessage['Publish Draft']
			. '</a>'
			. '<a class="msg_saving_draft admin-link admin-link-publish-draft-saving">'
			.   '<i class="fa fa-spinner fa-pulse"></i> ' . $langmessage['Saving'] . ' &hellip;'
			. '</a>';
		}

		return array_merge($admin_links, $this->admin_links);
	}


	/**
	 * Return Toggle Page Visibility option link
	 * @param string page index
	 * @param bool $visisbility of current page
	 * @return string formatted link
	 */
	public static function ToggleVisibilityLink($index, $visibility){
		global $langmessage;
		$q				= 'cmd=ToggleVisibility&index=' . urlencode($index);
		$label			= $langmessage['Visibility'] . ': ' . $langmessage['Private'];
		$attrs			= [
							'class'		=> 'admin-link admin-link-toggle-visibility',
							'data-cmd'	=> 'postlink',
						];
		if( $visibility ){
			$q			.= '&visibility=private';
			$label		= $langmessage['Visibility'] . ': ' . $langmessage['Public'];
		}else{
			$attrs['class'] .= ' admin-link-visibility-private';
		}
		return \gp\tool::Link('Admin/Menu/Ajax', $label, $q, $attrs);
	}


	/**
	 * Send js to client for managing content sections
	 *
	 */
	public static function ManageSections(){
		global $langmessage, $page;

		$scripts = [];

		//output links
		ob_start();
		if( $page->pagetype == 'display' ){
			echo '<div id="section_sorting_wrap" class="inline_edit_area">';
			echo '<ul id="section_sorting" class="section_drag_area" title="Organize"></ul>';

			echo '<div id="section-clipboard">';
			echo '<ul id="section-clipboard-items">';
			echo self::SectionClipboardLinks();
			echo '</ul>';
			echo '</div>';

			// echo '<div>'.$langmessage['add'].'</div>';
			echo '<div id="new_section_links">';
			self::NewSections();
			echo '</div>';
			echo '</div>';
		}

		echo '<div id="ck_editable_areas" class="inline_edit_area">';
		echo '<ul></ul>';
		echo '</div>';

		$scripts[]				= ['code' => 'var section_types = ' . json_encode(ob_get_clean()) . ';'];

		// selectable classes moved to \gp\tool\Output::GetHead_InlineJS() because we have more info there
		// $avail_classes			= \gp\admin\Settings\Classes::GetClasses();
		// $avail_classes			= \gp\tool\Plugins::Filter('AvailableClasses', [$avail_classes]);
		// $scripts[]				= ['code' => 'var gp_avail_classes = ' . json_encode($avail_classes) . ';'];

		$scripts[]				= ['object' => 'gp_editing', 'file' => '/include/js/inline_edit/inline_editing.js'];

		if( empty($_REQUEST['mode']) ){
			$scripts[]			= ['object' => 'gp_editing', 'code' => 'gp_editing.is_extra_mode = false;'];
		}else{
			$scripts[]			= ['object' => 'gp_editing', 'code' => 'gp_editing.is_extra_mode = true;'];
		}

		$scripts[]				= ['file' => '/include/js/inline_edit/manage_sections.js'];

		\gp\tool\Output\Ajax::SendScripts($scripts);
		die();
	}


	/**
	 * Send multiple sections to the client
	 *
	 */
	public function NewNestedSection($types, $wrapper_data){
		global $langmessage;

		$new_section = \gp\tool\Editing::DefaultContent('wrapper_section');

		if( is_array($wrapper_data) ){
			// Typesetter > 5.0.3: $wrapper_data may be defined as array by plugins
			$new_section = array_merge($new_section, $wrapper_data);
		}else{
			// Typesetter <= 5.0.3: $wrapper_data is a string (wrapper class)
			$new_section['attributes']['class'] .= ' ' . $wrapper_data;
		}
		$orig_attrs = $new_section['attributes'];

		$output = $this->SectionNode($new_section, $orig_attrs);
		foreach($types as $type){
			if( is_array($type) ){
				$_wrapper_data = isset($type[1]) ? $type[1] : '';
				$output .= $this->NewNestedSection($type[0], $_wrapper_data);
			}else{
				$output .= $this->GetNewSection($type);
			}

		}
		$output .= '</div>';

		return $output;
	}


	public function GetNewSection($type){

		$class			= self::TypeClass($type);
		$num			= time().rand(0, 10000);
		$new_section	= \gp\tool\Editing::DefaultContent($type);
		$content		= \gp\tool\Output\Sections::RenderSection($new_section, $num, $this->title, $this->file_stats);

		$new_section['attributes']['class']	.= ' ' . $class;
		$new_section['gp_type']				= $type;
		$orig_attrs							= $new_section['attributes'];

		if( !isset($new_section['nodeName']) ){
			return $this->SectionNode($new_section, $orig_attrs) . $content . '</div>';
		}

		return $this->SectionNode($new_section, $orig_attrs) .
			$content .
			\gp\tool\Output\Sections::EndTag($new_section['nodeName']);
	}


	public function SectionNode($section, $orig_attrs){

		//if image type, make sure the src is a complete path
		if( $section['type'] == 'image' ){
			$orig_attrs['src'] = \gp\tool::GetDir($orig_attrs['src']);
		}

		$orig_attrs			= json_encode($orig_attrs);

		if( \gp\tool\Output::ShowEditLink() && \gp\admin\Tools::CanEdit($this->gp_index) ){
			$section['attributes']['class']		.= ' editable_area';
		}

		$section_attrs		= ['gp_label', 'gp_color', 'gp_collapse', 'gp_type', 'gp_hidden', 'gp_file_under'];
		foreach($section_attrs as $attr){
			if( !empty($section[$attr]) ){
				$section['attributes']['data-' . $attr] = $section[$attr];
			}
		}

		$attributes			= \gp\tool\Output\Sections::SectionAttributes($section['attributes'], $section['type']);
		$attributes			.= ' data-gp-attrs=\'' . htmlspecialchars($orig_attrs, ENT_QUOTES & ~ENT_COMPAT) . '\'';


		if( !isset($section['nodeName']) ){
			return '<div' . $attributes . '>';
		}

		return '<' . $section['nodeName'] . $attributes . '>';
	}


	/**
	 * Save new/rearranged sections
	 *
	 */
	public function SaveSections(){
		global $langmessage, $dataDir;

		if( isset($_POST['sections_json']) ){
			// section data is posted as JSON
			$section_data = json_decode(rawurldecode($_POST['sections_json']), true);
			$_POST['section_data_is'] = 'json';
			$_POST += $section_data;
			unset($_POST['sections_json']);
		}

		$this->ajaxReplace		= [];
		$original_sections		= $this->file_sections;
		$unused_sections		= $this->file_sections; //keep track of sections that aren't used
		$new_sections			= [];

		//make sure section_order isn't empty
		if( empty($_POST['section_order']) ){
			msg($langmessage['OOPS'] . ' (Invalid Request)');
			return false;
		}

		foreach($_POST['section_order'] as $i => $arg ){
			$new_section 		= $this->SaveSection($i, $arg, $unused_sections);
			if( $new_section === false ){
				return false;
			}
			$new_sections[$i] = $new_section;
		}

		//make sure there's at least one section
		if( empty($new_sections) ){
			msg($langmessage['OOPS'] . ' (1 Section Minimum)');
			return false;
		}

		$this->file_sections = array_values($new_sections);

		// save a send message to user
		if( !$this->SaveThis() ){
			$this->file_sections = $original_sections;
			msg($langmessage['OOPS'] . '(4)');
			return;
		}

		$this->ajaxReplace[] = ['ck_saved', '', ''];

		//update gallery info
		$this->GalleryEdited();

		//update usage of resized images
		foreach($unused_sections as $section_data){
			if( isset($section_data['resized_imgs']) ){
				includeFile('image.php');
				\gp_resized::SetIndex();
				\gp\tool\Editing::ResizedImageUse($section_data['resized_imgs'], []);
			}
		}
	}


	protected function SaveSection($i, $arg, &$unused_sections){
		global $langmessage;

		$section_attrs			= ['gp_label', 'gp_color', 'gp_collapse', 'gp_type', 'gp_hidden', 'gp_file_under'];

		// moved / copied sections
		if( ctype_digit($arg) ){
			$arg = (int)$arg;

			if( !isset($this->file_sections[$arg]) ){
				msg($langmessage['OOPS'] . ' (Invalid Section Number)');
				return false;
			}

			unset($unused_sections[$arg]);
			$new_section				= $this->file_sections[$arg];
			$new_section['attributes']	= [];

		// otherwise, new sections
		}else{
			$new_section	= \gp\tool\Editing::DefaultContent($arg);
		}

		// attributes
		$this->PostedAttributes($new_section,$i);

		// wrapper section 'contains_sections'
		if( $new_section['type'] == 'wrapper_section' ){
			$new_section['contains_sections'] = isset($_POST['contains_sections']) ? $_POST['contains_sections'][$i] : '0';
		}

		// section attributes
		foreach($section_attrs as $attr){
			unset($new_section[$attr]);
			if( !empty($_POST[$attr][$i]) ){
				$new_section[$attr]		= $_POST[$attr][$i];
			}
		}

		return $new_section;
	}


	/**
	 * Save a section or wrapper with sections to the Clipboard
	 *
	 */
	protected function SaveToClipboard(){
		global $langmessage;
		$this->ajaxReplace = [];

		if( !isset($_POST['section_number']) || !ctype_digit($_POST['section_number']) ){
			msg($langmessage['OOPS'] . ' (SaveToClipboard: Invalid Request)');
			return false;
		}

		$section_num = $_POST['section_number'];
		if( !isset($this->file_sections[$section_num]) ){
			msg($langmessage['OOPS'] . ' (SaveToClipboard: Invalid Section Number (' . $section_num . ')');
		}

		$file_sections = self::ExtractSections($section_num);
		// msg('SaveToClipboard: $file_sections = ' . pre($file_sections) );

		$first_section = $file_sections[0];
		$new_clipboard_item = [
			'type'			=> $first_section['type'],
			'label'			=> isset($first_section['gp_label'])		? $first_section['gp_label']		:	ucfirst($first_section['type']),
			'color'			=> isset($first_section['gp_color'])		? $first_section['gp_color']		:	'#aabbcc',
			'hidden'		=> isset($first_section['gp_hidden'])		? $first_section['gp_hidden']		:	false,
			'hidden'		=> isset($first_section['gp_file_under'])	? $first_section['gp_file_under']	:	'',
			'content'		=> $this->GetSectionForClipboard($section_num),
			'file_sections'	=> $file_sections,
		];

		$clipboard_data = \gp\tool\Files::GetSectionClipboard();
		// msg("GetSectionClipboard returns " . pre($clipboard_data));
		array_unshift($clipboard_data, $new_clipboard_item);
		if( \gp\tool\Files::SaveSectionClipboard($clipboard_data) ){
			// msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'] . ' (Section Clipboard: Save data failed)');
		}

		$clipboard_links = self::SectionClipboardLinks($clipboard_data);
		$this->ajaxReplace[] = ['inner', '#section-clipboard-items', $clipboard_links];
		$this->ajaxReplace[] = ['clipboard_init', '', ''];
		$this->ajaxReplace[] = ['loaded', '', ''];

		return true;
	}


	/**
	 * Add (append) a Clipboard Item to the page's file sections
	 *
	 */
	protected function AddFromClipboard($item_num=false){
		global $langmessage;
		$this->ajaxReplace = [];

		if( !$item_num ){
			if( !isset($_POST['item_number']) || !ctype_digit($_POST['item_number']) ){
				msg($langmessage['OOPS'] . ' (Section Clipboard - Add Item: Invalid Request');
				return false;
			}
			$item_num = $_POST['item_number'];
		}

		$clipboard_data = \gp\tool\Files::GetSectionClipboard();

		if( !isset($clipboard_data[$item_num]) ){
			msg($langmessage['OOPS'] . ' (Section Clipboard - Add Item: Invalid Item Number (' . $item_num . ')');
			return false;
		}

		$clipboard_sections		= $clipboard_data[$item_num]['file_sections'];
		$this->file_sections	= array_merge($this->file_sections, $clipboard_sections);

		if( !$this->SaveThis() ){
			msg($langmessage['OOPS'] . ' (Section Clipboard - Add Item: Save Page Failed)');
			$this->ajaxReplace[] = ['loaded', '', ''];
			return false;
		}

		// $this->ajaxReplace[] = ['ck_saved', '', ''];

		// include updated Admin Toolbar
		ob_start();
		\gp\admin\Tools::AdminToolbar();
		$admin_toolbar = ob_get_clean();
		if( !empty($admin_toolbar) ){
			$this->ajaxReplace[] = ['replace', '#admincontent_panel', $admin_toolbar];
		}
		$this->ajaxReplace[] = ['loaded', '', ''];
		return;
	}


	/**
	 * Remove a Clipboard Item
	 *
	 */
	protected function RemoveFromClipboard($item_num=false){
		global $langmessage;
		$this->ajaxReplace = [];

		if( !$item_num ){
			if( !isset($_POST['item_number']) || !ctype_digit($_POST['item_number']) ){
				msg($langmessage['OOPS'] . ' (Section Clipboard - Remove Item: Invalid Request');
				return false;
			}
			$item_num = $_POST['item_number'];
		}

		$clipboard_data = \gp\tool\Files::GetSectionClipboard();

		if( !isset($clipboard_data[$item_num]) ){
			msg($langmessage['OOPS'] . ' (Section Clipboard - Remove Item: Invalid Item Number (' . $item_num . ')');
			return false;
		}
		unset($clipboard_data[$item_num]);
		$clipboard_data = array_values($clipboard_data);

		if( \gp\tool\Files::SaveSectionClipboard($clipboard_data) ){
			// msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'] . ' (Section Clipboard - Remove Item: Save data failed)');
			return false;
		}

		$clipboard_links = self::SectionClipboardLinks($clipboard_data);
		$this->ajaxReplace[] = ['inner', '#section-clipboard-items', $clipboard_links];
		$this->ajaxReplace[] = ['clipboard_init', '', ''];
		$this->ajaxReplace[] = ['loaded', '', ''];

		return true;
	}


	/**
	 * Change the Clipboard Items' order
	 *
	 */
	protected function ReorderClipboardItems($order=false){
		global $langmessage;
		$this->ajaxReplace = [];

		if( !is_array($order) ){
			if( !is_array($_POST['order']) ){
				msg($langmessage['OOPS'] . ' (Section Clipboard - Reorder Items: Invalid Request');
				return false;
			}
			$order = $_POST['order'];
		}

		$clipboard_data = \gp\tool\Files::GetSectionClipboard();

		if( count($order) != count($clipboard_data) ){
			msg($langmessage['OOPS'] . ' (Section Clipboard - Reorder Items: Item count mismatch');
			return false;
		}

		foreach( $order as $key => $index ){
			$new_clipboard_data[$key] = $clipboard_data[$index];
		}

		$clipboard_data = $new_clipboard_data;
		unset($new_clipboard_data);

		if( \gp\tool\Files::SaveSectionClipboard($clipboard_data) ){
			// msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'] . ' (Section Clipboard - Reorder Items: Save data failed)');
			return false;
		}

		$clipboard_links = self::SectionClipboardLinks($clipboard_data);
		$this->ajaxReplace[] = ['inner', '#section-clipboard-items', $clipboard_links];
		$this->ajaxReplace[] = ['loaded', '', ''];

		return true;
	}


	/**
	 * Change the label of a Clipboard Item
	 *
	 */
	protected function RelabelClipboardItem($item_num=false,$new_label=false){
		global $langmessage;
		$this->ajaxReplace = [];

		if( !$item_num ){
			if( !isset($_POST['item_number']) || !ctype_digit($_POST['item_number']) ){
				msg($langmessage['OOPS'] . ' (Section Clipboard - Remove Item: Invalid Request');
				return false;
			}
			$item_num = $_POST['item_number'];
		}

		if( !$new_label ){
			if( !isset($_POST['new_label']) ){
				msg($langmessage['OOPS'] . ' (Section Clipboard - Relabel Item: Invalid Label');
				return false;
			}
			$new_label = htmlspecialchars($_POST['new_label']);
		}

		$clipboard_data = \gp\tool\Files::GetSectionClipboard();

		if( !isset($clipboard_data[$item_num]) ){
			msg($langmessage['OOPS'] . ' (Section Clipboard - Relabel Item: Invalid Item Number (' . $item_num . ')');
			return false;
		}

		$clipboard_data = array_values($clipboard_data);

		$clipboard_data[$item_num]['label'] = $new_label;

		if( \gp\tool\Files::SaveSectionClipboard($clipboard_data) ){
			// msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'] . ' (Section Clipboard - Remove Item: Save data failed)');
			return false;
		}

		$clipboard_links = self::SectionClipboardLinks($clipboard_data);
		$this->ajaxReplace[] = ['inner', '#section-clipboard-items', $clipboard_links];
		$this->ajaxReplace[] = ['loaded', '', ''];

		return true;
	}


	/**
	 * Extract sections from the current page to be stored in the Clipboard
	 * TS 5.1.1-b1: fix nesting error, changing from recursion to while iteration + counter
	 */
	public function ExtractSections($section_num){
		$counter = 0;
		$sections = [];
		while( $counter >= 0 ){
			$section_data = $this->file_sections[$section_num];

			if( $section_data['type'] == 'wrapper_section' && isset($section_data['contains_sections']) ){
				$counter += $section_data['contains_sections'];
			}

			// remove possible hidden state
			$section_data['gp_hidden'] = false;

			// add section
			$sections[] = $section_data;

			$section_num++;
			$counter--;
		}

		return $sections;
	}



	/**
	 * Get the Section Clipboard links
	 *
	 */
	public static function SectionClipboardLinks($clipboard_data=false){
		global $langmessage;
		$clipboard_data = $clipboard_data ? $clipboard_data : \gp\tool\Files::GetSectionClipboard();

		if( empty($clipboard_data) ){
			return '<li class="clipboard-empty-msg"><i class="fa fa-clipboard"></i> ' . $langmessage['Clipboard'] . '</li>';
		}

		$clipboard_links = '';

		foreach( $clipboard_data as $key => $clipboard_item ){
			$icon_class = $clipboard_item['type'] == 'wrapper_section' ? 'fa fa-fw fa-clone' : 'fa fa-fw fa-square-o';
			// $icon_title = $clipboard_item['label'];
			// $content = \gp\tool\Output\Sections::GetSection($clipboard_item['file_sections'], 0); // private method :-(
			$content = $clipboard_item['content'];
			$response = htmlspecialchars($content);

			$clipboard_links .= '<li style="border-left:4px solid ' . $clipboard_item['color'] . ';" data-item_index="' . $key . '">';

			$clipboard_links .= '<a class="remove-clipboard-item" ';
			$clipboard_links .= 	'title="' . $langmessage['remove'] . '" ';
			$clipboard_links .= 	'data-cmd="RemoveSectionClipboardItem">';
			$clipboard_links .= 	'<i class="fa fa-trash"></i>';
			$clipboard_links .= '</a>';

			$clipboard_links .= '<a class="relabel-clipboard-item" ';
			$clipboard_links .= 	'title="' . $langmessage['label'] . '" ';
			$clipboard_links .= 	'data-cmd="RelabelSectionClipboardItem">';
			$clipboard_links .= 	'<i class="fa fa-i-cursor"></i>'; // fa-pencil-square-o
			$clipboard_links .= '</a>';

			$clipboard_links .= '<a class="preview_section" ';
			$clipboard_links .= 	'title="' . $langmessage['add'] . ' (' . count($clipboard_item['file_sections']) . ')" ';
			$clipboard_links .= 	'data-cmd="AddFromClipboard" ';
			$clipboard_links .= 	'data-response="' . $response . '" >';
			$clipboard_links .= 	'<i class="clipboard-item-icon ' . $icon_class . '"></i> ';
			$clipboard_links .= 	'<i class="clipboard-item-label-wrap">';
			$clipboard_links .= 		'<span class="clipboard-item-label">' . $clipboard_item['label'] . '</span>';
			$clipboard_links .= 	'</i>';
			$clipboard_links .= '</a>';

			$clipboard_links .= '</li>';
		}

		return $clipboard_links;
	}


	/**
	 * Get the posted attributes for a section
	 *
	 */
	protected function PostedAttributes(&$section, $i){
		global $dirPrefix;

		if( isset($_POST['attributes'][$i]) && is_array($_POST['attributes'][$i]) ){
			foreach($_POST['attributes'][$i] as $attr_name => $attr_value){

				$attr_name		= strtolower($attr_name);
				$attr_name		= trim($attr_name);
				$attr_value		= trim($attr_value);

				if( empty($attr_name) || empty($attr_value) || $attr_name == 'id' || substr($attr_name, 0, 7) == 'data-gp' ){
					continue;
				}

				//strip $dirPrefix
				if( $attr_name == 'src' && !empty($dirPrefix) && strpos($attr_value,$dirPrefix) === 0 ){
					$attr_value = substr($attr_value, strlen($dirPrefix));
				}

				$section['attributes'][$attr_name] = $attr_value;
			}
		}
	}


	/**
	 * Perform various section editing commands
	 * @return bool
	 */
	public function SectionEdit(){
		global $langmessage, $page;

		$page->ajaxReplace = [];

		$section_num = $_REQUEST['section'];
		if( !is_numeric($section_num) || !isset($this->file_sections[$section_num])){
			echo 'false;';
			return false;
		}

		$cmd = \gp\tool::GetCommand();

		if( !\gp\tool\Editing::SectionEdit($cmd, $this->file_sections[$section_num], $section_num, $this->title, $this->file_stats) ){
			return false;
		}

		//save if the file was changed
		if( !$this->SaveThis() ){
			msg($langmessage['OOPS'].' (SE3)');
			return false;
		}

		$page->ajaxReplace[] = ['ck_saved', '', ''];

		//update gallery information
		switch($this->file_sections[$section_num]['type']){
			case 'gallery':
				$this->GalleryEdited();
			break;
		}

		\gp\admin\Notifications::UpdateNotifications();

		return true;
	}


	/**
	 * Recalculate the file_type string for this file
	 * Updates $this->meta_data and $gp_titles
	 *
	 */
	public function ResetFileTypes(){
		global $gp_titles;

		$new_types = [];
		foreach($this->file_sections as $section){
			$new_types[] = $section['type'];
		}
		$new_types = array_unique($new_types);
		$new_types = array_diff($new_types, ['']);
		sort($new_types);

		$new_types = implode(',', $new_types);
		$this->meta_data['file_type'] = $new_types;

		if( !isset($gp_titles[$this->gp_index]) ){
			return;
		}

		$gp_titles[$this->gp_index]['type'] = $new_types;
		\gp\admin\Tools::SavePagesPHP();
	}


	/**
	 * Return a list of section types
	 *
	 */
	public static function NewSections($checkboxes=false){
		global $langmessage;

		$types_with_icons = ['text', 'image', 'gallery', 'wrapper_section', 'include'];

		$section_types = \gp\tool\Output\Sections::GetTypes();

		$links = [];

		foreach($section_types as $type => $type_info){
			$img = '';
			if( in_array($type, $types_with_icons) ){
				$img = \gp\tool::GetDir('/include/imgs/section-' . $type . '.png');
			}
			$links[] = [$type, $img, '', $type_info['file_under']];
		}

		//section combo: text & image
		$links[] = [
			['text.gpCol-6', 'image.gpCol-6'],
			\gp\tool::GetDir('/include/imgs/section-combo-text-image.png'),
			[
				'gp_label'		=> 'Text &amp; Image',
				'gp_color'		=> '#555',
				'attributes'	=> [
					'class'		=> 'gpRow',
				],
			],
			'default', // file_under, req as of TS 5.2
		];

		//section combo: text & gallery
		$links[] = [
			['text.gpCol-6', 'gallery.gpCol-6'],
			\gp\tool::GetDir('/include/imgs/section-combo-text-gallery.png'),
			[
				'gp_label'		=> 'Text &amp; Gallery',
				'gp_color'		=> '#555',
				'attributes'	=> [
					'class'		=> 'gpRow',
				],
			],
			'default',
		];

		//section combo: 3 text columns
		$links[] = [
			['text.gpCol-4', 'text.gpCol-4', 'text.gpCol-4'],
			\gp\tool::GetDir('/include/imgs/section-combo-3-text-cols.png'),
			[
				'gp_label'		=> '3 Text Columns',
				'gp_color'		=> '#555',
				'attributes'	=> [
					'class'		=> 'gpRow',
				],
			],
			'default',
		];

		/**
		 * FYI when using the 'NewSections' plugin filter hook
		 *
		 * a new section subarray looks like this:
		 *
		 * array(
		 * 	[0]	=>	(string)section type  OR  (array) of (string)section types (or subsequent wrapper arrays)
		 * 				if we pass an array, the new section is a wrapper (combo) containing nested sections
		 * 				while every (string)section type MAY contain one or multiple css classes
		 * 					css classes are everything in (string)section type after a dot
		 * 					multiple css classes are separated by space charaters
		 *
		 * 	[1]	=>	(string) relative path to an admin UI icon for that section type
		 * 				if empty, no icon will appear
		 *
		 * 	[2]	=>	(boolean)false  OR  (string)wrapper class name(s)  OR  (array)wrapper data
		 * 				when passing wrapper classes string, multiple class names are separated by space characters
		 * 				when passing a wrapper data array, it may contain arbitrary section data key-pairs
		 * 					including an array of html attributes like 'class'
		 *
		 * 	[3]	=>	as of Typesetter 5.2, optional (string)file_under
		 * 				use 'hidden' if you do not want the link to appear in the admi UI
		 * 				will become 'plugins' by default if not defined
		 * )
		 *
		 * Furthermore: instead of existing 'real' section types, you can also use pseudo types
		 * 	pseudo types must to be defined via the GetDefaultContent filter hook
		 * 		where a section array must be returned, which has the real section type string in the 'type' key
		 *
		 * This is a grown structure and we apologize that it is so messy
		 *
		 * For handling more complex new sections and even multi-level-nested section combos see e.g.
		 * 	the SliderFactory plugin (https://www.typesettercms.com/Plugins/310_Slider_Factory)
		 *
		 */
		$links = \gp\tool\Plugins::Filter('NewSections', [$links]);

		$new_section_links = [];
		foreach( $links as $link ){
			$link += ['', '', false, 'plugins']; // $link[2] will be replaced in NewSectionLink() if missing
			$type_id = substr(base_convert(md5(json_encode((array)$link[0])), 16, 32), 0, 6); // creates a unique id for every entry
			$new_section_links[$type_id] = $link;
		}

		// last chance to filter, re-arrange or modify new section links
		// before they appear in the admin UI (based on their $type_id or other criteria)
		$new_section_links = \gp\tool\Plugins::Filter('NewSectionLinks', [$new_section_links]);

		$stackers = [];

		foreach( $new_section_links as $type_id => $link ){
			$file_under		= $link[3];
			$stackers[$file_under][$type_id] = $link;
		}

		// remove all section types that are filed under 'hidden'
		unset($stackers['hidden']);

		// if there are more than 12 links and more than 1 remaining stackers, split the output into an accordion
		$use_stackers = count($stackers) > 1 && $new_section_links > 12;

		$stacker_collapsed = false;
		foreach( $stackers as $stacker => $links ){
			if( $use_stackers ){
				$stacker_label = isset($langmessage[$stacker]) ? $langmessage[$stacker] : htmlspecialchars($stacker);
				echo '<section class="collapsible">';
				echo 	'<h4 class="head'. ($stacker_collapsed ? ' gp_collapsed' : '') . '">';
				echo 		'<a data-cmd="collapsible">' . $stacker_label . '</a>';
				echo 	'</h4>';
				echo 	'<div class="collapsearea' . ($stacker_collapsed ? ' nodisplay' : '') . '">';
			}
			foreach( $links as $link ){
				$types			= $link[0];
				$icon			= $link[1];
				$wrapper_data	= $link[2];
				$file_under		= $link[3];

				echo self::NewSectionLink($types, $icon, $wrapper_data, $checkboxes, $type_id, $file_under);
			}
			if( $use_stackers ){
				echo 	'</div>';
				echo '</section>';
				$stacker_collapsed = true;
			}

		}
	}


	/**
	 * Add link to manage section admin for nested section type
	 *
	 */
	public static function NewSectionLink($types, $icon, $wrapper_data=false, $checkbox=false, $type_id='undefined', $file_under=''){
		global $dataDir, $page, $langmessage;

		$types = (array)$types;

		$is_wrapper = count([$types]) > 1 || is_array($types[0]);

		if( $is_wrapper && !$wrapper_data ){
			// add default wrapper data if undefined
			$wrapper_data = [
				'gp_label'		=> $langmessage['Section Wrapper'],
				'gp_color'		=> '#555',
				'attributes'	=> [
					// 'class' => 'gpRow', // we don't use gpRow as default class for new wrappers anymore
				],
			];
		}

		static $fi = 0;

		$text_label = $is_wrapper && isset($wrapper_data['gp_label']) ? $wrapper_data['gp_label'] : self::SectionLabel($types);

		$label = '<span>' . $text_label . '</span>';
		if( !empty($icon) ){
			$label .= '<img src="' . $icon . '"/>';
		}

		//checkbox used for new pages
		if( $checkbox ){

			if( count($types) > 1 || is_array($types[0]) ){ // == nested sections
				$q = ['types' => $types, 'wrapper_data' => $wrapper_data];
				$q = json_encode($q);
			}else{
				$q = $types[0];
			}

			//checked
			$checked = '';
			if( isset($_REQUEST['content_type']) && $_REQUEST['content_type'] == $q ){
				$checked = ' checked="checked"';
			}elseif( empty($_REQUEST['content_type']) && $fi === 0 ){
				$checked = ' checked="checked"';
				$fi++;
			}

			$id = 'checkbox_' . md5($q);
			echo '<div class="new_section_link" data-type-id="' . $type_id . '" data-file-under="' . $file_under . '">';
			echo   '<input name="content_type" type="radio" ';
			echo     'value="' . htmlspecialchars($q) . '" id="' . $id . '" ';
			echo     'required="required" ' . $checked . ' />';
			echo   '<label title="' . $text_label . '" for="' . $id . '">';
			echo     $label;
			echo   '</label>';
			echo '</div>';
			return;
		} // /if $checkboxes

		//links used for new sections
		$attrs = [
			'data-cmd' => 'AddSection',
			'class' => 'preview_section',
		];
		if( count($types) > 1 || is_array($types[0]) ){
			$attrs['data-response'] = $page->NewNestedSection($types, $wrapper_data);
		}else{
			$attrs['data-response'] = $page->GetNewSection($types[0]);
		}

		$return =  '<div class="new_section_link" data-type-id="' . $type_id . '" data-file-under="' . $file_under . '">';
		$return .=   '<a ' . \gp\tool::LinkAttr($attrs, $label) . '>' . $label . '</a>';
		$return .= '</div>';

		return $return;
	}


	/**
	 * Return a readable label for the section
	 *
	 */
	public static function SectionLabel($types){
		$section_types	= \gp\tool\Output\Sections::GetTypes();
		$text_label		= [];

		foreach($types as $type){

			if( is_array($type) ){
				continue;
			}
			self::TypeClass($type);

			if( isset($section_types[$type]) ){
				$text_label[] = $section_types[$type]['label'];
			}else{
				$text_label[] = $type;
			}
		}

		return implode(' &amp; ', $text_label);
	}


	/**
	 * Split the type and class from $type = div.classname into $type = div, $class = classname
	 *
	 */
	public static function TypeClass(&$type){

		$class = '';

		if( !is_array($type) && strpos($type, '.') ){
			list($type,$class) = explode('.', $type, 2);
		}

		return $class;
	}


	/**
	 * Save the current page
	 * Save a backup if $backup is true
	 *
	 */
	public function SaveThis($backup=true){

		//return true if nothing has changed
		if( $backup && $this->checksum === $this->Checksum() ){
			return true;
		}

		//file count
		if( !isset($this->meta_data['file_number']) ){
			$this->meta_data['file_number'] = \gp\tool\Files::NewFileNumber();
		}

		if( $backup ){
			$this->SaveBackup(); //make a backup of the page file
		}

		if( isset($_POST['prevent_draft']) && !$this->draft_exists ){
			if( !\gp\tool\Files::SaveData($this->file, 'file_sections', $this->file_sections, $this->meta_data) ){
				return false;
			}
		}else{
			if( !\gp\tool\Files::SaveData($this->draft_file, 'file_sections', $this->file_sections, $this->meta_data) ){
				return false;
			}
			$this->draft_exists = true;
		}

		// update notifications
		\gp\admin\Notifications::UpdateNotifications();

		return true;
	}


	/**
	 * Generate a checksum for this page, used to determine if the page content has been edited
	 *
	 */
	public function Checksum(){
		$temp = [];
		foreach($this->file_sections as $section){
			unset($section['modified'], $section['modified_by']);
			$temp[] = $section;
		}
		$checksum = serialize($temp);
		return sha1($checksum) . md5($checksum);
	}


	/**
	 *	Save a backup of the file
	 *
	 */
	public function SaveBackup(){
		global $dataDir, $gpAdmin;

		$dir	= $dataDir . '/data/_backup/pages/' . $this->gp_index;
		$time	= \gp\tool\Editing::ReqTime(); //use the request time

		//just one backup per edit session (auto-saving would create too many backups otherwise)
		$previous_backup	= $this->BackupFile($time);
		if( !empty($previous_backup) ){
			return true;
		}

		// get the raw contents so we can add the size to the backup file name
		if( $this->draft_exists ){
			$contents	= \gp\tool\Files::GetRaw($this->draft_file);
		}else{
			$contents	= \gp\tool\Files::GetRaw($this->file);
		}

		//backup file name
		$len				= strlen($contents);
		$backup_file		= $dir . '/' . $time . '.' . $len . '.' . $gpAdmin['username'];

		//compress
		if( function_exists('gzencode') && function_exists('readgzfile') ){
			$backup_file .= '.gze';
			$contents = gzencode($contents,9);
		}

		if( !\gp\tool\Files::Save($backup_file, $contents) ){
			return false;
		}

		$this->CleanBackupFolder();
		return true;
	}


	/**
	 * Reduce the number of files in the backup folder
	 *
	 */
	public function CleanBackupFolder(){
		global $dataDir, $config;

		$files			= $this->BackupFiles();
		$file_count		= count($files);

		if( $file_count <= $config['history_limit'] ){
			return;
		}
		$delete_count = $file_count - $config['history_limit'];
		$files = array_splice($files, 0, $delete_count);
		foreach($files as $file){
			$full_path = $dataDir . '/data/_backup/pages/' . $this->gp_index . '/' . $file;
			unlink($full_path);
		}
	}


	/**
	 * Make the working draft the live file
	 *
	 */
	public function PublishDraft(){
		global $langmessage, $page;

		if( !$this->draft_exists ){
			msg($langmessage['OOPS'] . ' (Not a draft)');
			return false;
		}

		if( !\gp\tool\Files::SaveData($this->file, 'file_sections', $this->file_sections, $this->meta_data) ){
			msg($langmessage['OOPS'] . ' (Draft not published)');
			return false;
		}

		$draft_file = \gp\tool\Files::FilePath($this->draft_file);
		unlink($draft_file);
		$this->ResetFileTypes();
		$this->draft_exists = false;

		$page->ajaxReplace		= [];
		$page->ajaxReplace[]	= ['DraftPublished'];

		\gp\admin\Notifications::UpdateNotifications();

		return true;
	}


	/**
	 * Display the contents of a past revision
	 *
	 */
	protected function ViewRevision(){

		\gp\admin\Tools::$show_toolbar	= false;
		$revision						=& $_REQUEST['revision'];
		$file_sections					= $this->GetRevision($revision);

		$this->head_js[]				= '/include/js/admin/revision.js';

		if( $file_sections === false ){
			$this->DefaultDisplay();
			return;
		}


		echo \gp\tool\Output\Sections::Render($file_sections, $this->title, \gp\tool\Files::$last_stats);
	}


	/**
	 * View the current public facing version of the file
	 *
	 */
	public function ViewCurrent(){

		\gp\admin\Tools::$show_toolbar		= false;
		$this->head_js[]					= '/include/js/admin/revision.js';

		if( !$this->draft_exists ){
			$this->DefaultDisplay();
			return;
		}

		$file_sections			= \gp\tool\Files::Get($this->file, 'file_sections');
		echo \gp\tool\Output\Sections::Render($file_sections, $this->title, $this->file_stats);
	}


	/**
	 * Get the contents of a revision
	 *
	 */
	protected function GetRevision($time){

		$full_path	= $this->BackupFile($time);

		if( is_null($full_path) ){
			return false;
		}

		//if it's a compressed file, we need an uncompressed version
		if( strpos($full_path, '.gze') !== false ){

			ob_start();
			readgzfile($full_path);
			$contents		= ob_get_clean();

			$full_path		= substr($full_path, 0, -3) . 'php';
			$full_path		= \gp\tool\Files::FilePath($full_path);
			\gp\tool\Files::Save($full_path, $contents);
			$file_sections	= \gp\tool\Files::Get($full_path, 'file_sections');
			unlink($full_path);

		}else{
			$file_sections	= \gp\tool\Files::Get($full_path, 'file_sections');
		}

		return $file_sections;
	}


	/**
	 * Return a list of the available backup for the current file
	 *
	 */
	public function BackupFiles(){
		global $dataDir;
		$dir = $dataDir . '/data/_backup/pages/' . $this->gp_index;

		if( !file_exists($dir) ){
			return [];
		}
		$all_files = scandir($dir);
		$files = [];
		foreach($all_files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$parts = explode('.', $file);
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
	public function BackupFile( $time ){
		global $dataDir;

		$files = $this->BackupFiles();
		if( !isset($files[$time]) ){
			return;
		}

		return $dataDir . '/data/_backup/pages/' . $this->gp_index . '/' . $files[$time];
	}


	/**
	 * Extract information about the gallery from it's html: img_count, icon_src
	 * Call GalleryEdited when a gallery section is removed, edited
	 *
	 */
	public function GalleryEdited(){
		\gp\special\Galleries::UpdateGalleryInfo($this->title, $this->file_sections);
	}


	public function GetSection(&$section_num){
		global $langmessage;

		if( !isset($this->file_sections[$section_num]) ){
			trigger_error('invalid section number');
			return;
		}

		$curr_section_num								= $section_num;
		$section_num++;

		$content										= '';
		$section_data									= $this->file_sections[$curr_section_num];

		//make sure section_data is an array
		$type											= gettype($section_data);
		if( $type !== 'array' ){
			trigger_error('$section_data is ' . $type . '. Array expected');
			return;
		}

		$section_data									+= ['attributes' => [], 'type' => 'text'];
		$section_data['attributes']						+= ['class' => ''];
		$orig_attrs										= $section_data['attributes'];
		$section_data['attributes']['data-gp-section']	= $curr_section_num;
		$section_types									= \gp\tool\Output\Sections::GetTypes();

		if( \gp\tool\Output::ShowEditLink() && \gp\admin\Tools::CanEdit($this->gp_index) ){

			if( isset($section_types[$section_data['type']]) ){
				$title_attr		= $section_types[$section_data['type']]['label'];
			}else{
				$title_attr		= sprintf($langmessage['Section %s'], $curr_section_num+1);
			}

			$attrs	= [
						'title'		=> $title_attr,
						'data-cmd'	=> 'inline_edit_generic',
						'data-arg'	=> $section_data['type'] . '_inline_edit',
					];
			$link	= \gp\tool\Output::EditAreaLink(
						$edit_index,
						$this->title,
						$langmessage['edit'],
						'section='.$curr_section_num,$attrs
					);

			$section_data['attributes']['data-gp-area-id']		= $edit_index;

			//included page target
			$include_link = self::IncludeLink($section_data);


			//section control links
			if( $section_data['type'] != 'wrapper_section' ){
				ob_start();
				echo '<span class="nodisplay" id="ExtraEditLnks' . $edit_index . '">';
				echo $link;
				echo $include_link;
				echo \gp\tool::Link(
						$this->title,
						$langmessage['Manage Sections'],
						'cmd=ManageSections',
						[
							'class'		=> 'manage_sections',
							'data-cmd'	=> 'inline_edit_generic',
							'data-arg'	=> 'manage_sections',
						]
					);
				echo '<span class="gp_separator"></span>';
				echo \gp\tool::Link(
						$this->title,
						$langmessage['rename/details'],
						'cmd=renameform&index=' . urlencode($this->gp_index),
						['data-cmd' => 'gpajax']
					);
				echo \gp\tool::Link(
						'/Admin/Revisions/'.$this->gp_index,
						$langmessage['Revision History'],
						''
					);
				echo '<span class="gp_separator"></span>';
				echo \gp\tool::Link('Admin/Menu', $langmessage['file_manager']);
				echo '</span>';
				\gp\tool\Output::$editlinks .= ob_get_clean();
			}

			$section_data['attributes']['id'] = 'ExtraEditArea' . $edit_index;
		}

		$content 			.= $this->SectionNode($section_data, $orig_attrs);

		if( $section_data['type'] == 'wrapper_section' ){

			for( $cc=0; $cc < $section_data['contains_sections']; $cc++ ){
				$content	.= $this->GetSection($section_num);
			}

		}else{
			\gp\tool\Output::$nested_edit 	= true;
			$content .= \gp\tool\Output\Sections::RenderSection($section_data, $curr_section_num, $this->title, $this->file_stats);
			\gp\tool\Output::$nested_edit 	= false;
		}

		if( !isset($section_data['nodeName']) ){
			$content		.= '<div class="gpclear"></div>';
			$content		.= '</div>';
		}else{
			$content		.= \gp\tool\Output\Sections::EndTag($section_data['nodeName']);
		}

		return $content;
	}


	/**
	 * Return a link to the included page or extra area
	 *
	 * @return string
	 *
	 */
	public static function IncludeLink($section_data){
		global $langmessage;

		if( $section_data['type'] != 'include' || !array_key_exists('include_type',$section_data) ){
			return '';
		}

		if( isset($section_data['index']) ){
			return \gp\tool::Link($section_data['content'], $langmessage['view/edit_page']);
		}

		if( $section_data['include_type'] == 'extra' ){

			// get the real section type of the included extra area
			$extra_sections		= \gp\tool\Output\Extra::ExtraContent($section_data['content']);
			$extra_section_type	= $extra_sections[0]['type'];
			if($extra_section_type != 'text' ){
				// we can only edit text sections on Admin/Extra
				return '';
			}

			return \gp\tool::Link(
				'Admin/Extra/' . rawurlencode($section_data['content']),
				$langmessage['edit'] . ' &raquo; ' . str_replace('_', ' ', htmlspecialchars($section_data['content'])), // $langmessage['theme_content']
				'cmd=EditExtra'
			);
		}

		return '';
	}


	public function GetSectionForClipboard(&$section_num){
		global $langmessage;

		if( !isset($this->file_sections[$section_num]) ){
			trigger_error('invalid section number');
			return;
		}

		$curr_section_num								= $section_num;
		$section_num++;

		$content										= '';
		$section_data									= $this->file_sections[$curr_section_num];

		//make sure section_data is an array
		$type											= gettype($section_data);
		if( $type !== 'array' ){
			trigger_error('$section_data is ' . $type . '. Array expected');
			return;
		}

		$section_data									+= ['attributes' => [], 'type' => 'text'];
		$section_data['attributes']						+= ['class' => ''];
		// $section_data['attributes']['gp_type'] 			= $section_data['type'];
		$section_data['gp_hidden']						= false;
		$orig_attrs										= $section_data['attributes'];

		$content 			.= $this->SectionNode($section_data, $orig_attrs);

		if( $section_data['type'] == 'wrapper_section' ){

			for( $cc=0; $cc < $section_data['contains_sections']; $cc++ ){
				$content	.= $this->GetSectionForClipboard($section_num);
			}

		}else{
			\gp\tool\Output::$nested_edit 	= true;
			$content .= \gp\tool\Output\Sections::RenderSection($section_data, $curr_section_num, $this->title, $this->file_stats);
			\gp\tool\Output::$nested_edit 	= false;
		}

		if( !isset($section_data['nodeName']) ){
			$content		.= '</div>';
		}else{
			$content		.= \gp\tool\Output\Sections::EndTag($section_data['nodeName']);
		}

		return $content;
	}


	public function GalleryImages(){

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

		\gp\admin\Content\Uploaded::InlineList($dir_piece);
	}


	/**
	 * Used by slideshow addons
	 * @deprecated 3.6rc4
	 *
	 */
	public function SaveSection_Text($section){
		return \gp\tool\Editing::SectionFromPost_Text($this->file_sections[$section]);
	}

}
