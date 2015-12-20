<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');

class LayoutEdit extends Layout{

	protected $layout_request = true;

	public function __construct(){
		global $page, $gpLayouts;

		parent::__construct();


		//layout request
		$parts = explode('/',$page->requested);
		if( isset($parts[2]) && isset($gpLayouts[$parts[2]]) ){
			$this->EditLayout($parts[2]);
			return;
		}

		//default layout
		if( empty($parts[2]) ){
			$this->EditLayout($this->curr_layout);
			return;
		}

		//redirect
		$url = \common::GetUrl('Admin_Theme_Content','',false);
		\common::Redirect($url,302);
	}



	/**
	 * Edit layout properties
	 * 		Layout Identification
	 * 		Content Arrangement
	 * 		Gadget Visibility
	 *
	 */
	public function EditLayout($layout){
		global $page,$gpLayouts,$langmessage,$config;

		$cmd = \common::GetCommand();

		$GLOBALS['GP_ARRANGE_CONTENT']	= true;
		$page->head_js[]				= '/include/js/inline_edit/inline_editing.js';
		$this->curr_layout				= $layout;

		$this->SetLayoutArray();
		$page->SetTheme($layout);

		$this->LoremIpsum();

		\gpOutput::TemplateSettings();

		\gpPlugin::Action('edit_layout_cmd',array($layout));

		switch($cmd){

			/**
			 * Inline image editing
			 *
			 */
			case 'inlineedit':
				$this->InlineEdit();
			return;
			case 'gallery_folder':
			case 'gallery_images':
				$this->GalleryImages();
			return;
			case 'image_editor':
				includeFile('tool/editing.php');
				\gp_edit::ImageEditor($this->curr_layout);
			return;
			case 'save_inline':
				$this->SaveHeaderImage();
			return;

			case 'theme_images':
				$this->ShowThemeImages();
			return;

			case 'drag_area':
				$this->Drag();
			break;

			//insert
			case 'insert':
				$this->SelectContent();
			return;

			case 'addcontent':
				$this->AddContent();
			break;

			//remove
			case 'rm_area':
				$this->RemoveArea();
			break;

		}

		if( $this->LayoutCommands($cmd) ){
			return;
		}


		//control what is displayed
		switch( $cmd ){

			//show the layout (displayed within an iframe)
			case 'save_css':
			case 'preview_css':
			case 'addcontent':
			case 'rm_area':
			case 'drag_area':
			case 'in_iframe':
				$this->ShowInIframe($cmd);
			return;
		}



		$layout_info = \common::LayoutInfo($layout,false);
		$handlers_count = 0;
		if( isset($layout_info['handlers']) && is_array($layout_info['handlers']) ){
			foreach($layout_info['handlers'] as $val){
				$int = count($val);
				if( $int === 0){
					$handlers_count++;
				}
				$handlers_count += $int;
			}
		}

		$page->label = $langmessage['layouts'] . ' » '.$layout_info['label'];

		$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document
		$page->get_theme_css = false;


		ob_start();
		$this->LayoutEditor($layout, $layout_info );
		$page->admin_html = ob_get_clean();
	}



	/**
	 * Load the inline editor for a theme image
	 *
	 */
	public function InlineEdit(){

		$section = array();
		$section['type'] = 'image';
		includeFile('tool/ajax.php');
		\gpAjax::InlineEdit($section);
		die();
	}

	public function GalleryImages(){
		$_GET += array('dir'=>'/headers');
		includeFile('admin/admin_uploaded.php');
		\admin_uploaded::InlineList($_GET['dir']);
	}



	public function SaveHeaderImage(){
		global $page, $dataDir, $dirPrefix, $langmessage;
		includeFile('tool/Images.php');
		$page->ajaxReplace = array();


		//source file
		$source_file_rel = $_REQUEST['file'];
		if( !empty($_REQUEST['src']) ){
			$source_file_rel = rawurldecode($_REQUEST['src']);
			if( !empty($dirPrefix) ){
				$len = strlen($dirPrefix);
				$source_file_rel = substr($source_file_rel,$len);
			}
		}
		$source_file_rel = '/'.ltrim($source_file_rel,'/');
		$source_file_full = $dataDir.$source_file_rel;
		if( !file_exists($source_file_full) ){
			message($langmessage['OOPS'].' (Source file not found)');
			return;
		}
		$src_img = \thumbnail::getSrcImg($source_file_full);
		if( !$src_img ){
			message($langmessage['OOPS'].' (Couldn\'t create image [1])');
			return;
		}


		//size and position variables
		$orig_w = $width = imagesx($src_img);
		$orig_h = $height = imagesy($src_img);
		$posx = $posy = 0;
		if( isset($_REQUEST['posx']) && is_numeric($_REQUEST['posx']) ){
			$posx = $_REQUEST['posx'];
		}
		if( isset($_REQUEST['posy']) && is_numeric($_REQUEST['posy']) ){
			$posy = $_REQUEST['posy'];
		}
		if( isset($_REQUEST['width']) && is_numeric($_REQUEST['width']) ){
			$width = $_REQUEST['width'];
		}
		if( isset($_REQUEST['height']) && is_numeric($_REQUEST['height']) ){
			$height = $_REQUEST['height'];
		}


		//check to see if the image needs to be resized
		if( $posx == 0
			&& $posy == 0
			&& $width == $orig_w
			&& $height == $orig_h
			){
				$this->SetImage($source_file_rel,$width,$height);
				return;
		}

		//destination file
		$name = basename($source_file_rel);
		$parts = explode('.',$name);
		$type = array_pop($parts);
		if( count($parts) > 1 ){
			$time_part = array_pop($parts);
			if( !ctype_digit($time_part) ){
				$parts[] = $time_part;
			}
		}
		$name = implode('.',$parts);
		$time = time();
		if( isset($_REQUEST['time']) && ctype_digit($_REQUEST['time']) ){
			$time = $_REQUEST['time'];
		}
		//$dest_img_rel = '/data/_uploaded/headers/'.$name.'.'.$time.'.'.$type;
		$dest_img_rel = '/data/_uploaded/headers/'.$name.'.'.$time.'.png';
		$dest_img_full = $dataDir.$dest_img_rel;

		//make sure the folder exists
		if( !\gpFiles::CheckDir( dirname($dest_img_full) ) ){
			message($langmessage['OOPS'].' (Couldn\'t create directory)');
			return false;
		}

		if( !\thumbnail::createImg($src_img, $dest_img_full, $posx, $posy, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h, $width, $height) ){
			message($langmessage['OOPS'].' (Couldn\'t create image [2])');
			return;
		}

		if( $this->SetImage($dest_img_rel,$width,$height) ){
			includeFile('admin/admin_uploaded.php');
			\admin_uploaded::CreateThumbnail($dest_img_full);
		}

	}


	public function SetImage($img_rel,$width,$height){
		global $gpLayouts,$langmessage,$page;


		$save_info = array();
		$save_info['img_rel'] = $img_rel;
		$save_info['width'] = $width;
		$save_info['height'] = $height;

		$container = $_REQUEST['container'];
		//$gpLayouts[$this->curr_layout]['images'] = array(); //prevents shuffle - REMOVED to allow images per container to be saved.
		$gpLayouts[$this->curr_layout]['images'][$container] = array(); //prevents shuffle
		$gpLayouts[$this->curr_layout]['images'][$container][] = $save_info;

		if( !\admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (Data not saved)');
			return false;
		}
		$page->ajaxReplace[] = array('ck_saved','','');
		return true;
	}



	/**
	 * Show images available in themes
	 *
	 */
	public function ShowThemeImages(){
		global $page,$langmessage,$dirPrefix;
		$page->ajaxReplace = array();
		$current_theme = false;

		//which theme folder
		if( isset($_REQUEST['theme']) && isset($this->avail_addons[$_REQUEST['theme']]) ){
			$current_theme = $_REQUEST['theme'];
			$current_info = $this->avail_addons[$current_theme];
			$current_label = $current_info['name'];
			$current_dir = $current_info['full_dir'];
			$current_url = \common::GetDir($current_info['rel']);

		//current layout
		}else{
			$layout_info = \common::LayoutInfo($this->curr_layout,false);
			$current_label = $layout_info['theme_name'];
			$current_dir = $layout_info['dir'];
			$current_url = \common::GetDir(dirname($layout_info['path']));
		}


		//list of themes
		ob_start();
		echo '<div class="gp_edit_select ckeditor_control">';
		echo '<a class="gp_selected_folder"><span class="folder"></span>';
		echo $current_label;
		echo '</a>';

		echo '<div class="gp_edit_select_options">';

		foreach($this->avail_addons as $theme_id => $info){
			echo \common::Link('Admin_Theme_Content/Edit/'.rawurlencode($this->curr_layout),'<span class="folder"></span>'.$info['name'],'cmd=theme_images&theme='.rawurlencode($theme_id),' data-cmd="gpajax" class="gp_gallery_folder" ');
		}
		echo '</div>';
		echo '</div>';

		$gp_option_area = ob_get_clean();


		//images in theme
		includeFile('tool/Images.php');
		$images = array();
		self::GetAvailThemeImages( $current_dir, $current_url, $images );
		ob_start();
		foreach($images as $image ){
			echo '<span class="expand_child">'
				. '<a href="'.$image['url'].'" data-cmd="gp_gallery_add" data-width="'.$image['width'].'" data-height="'.$image['height'].'">'
				. '<img src="'.$image['url'].'" alt=""/>'
				. '</a></span>';
		}
		$gp_gallery_avail_imgs = ob_get_clean();


		if( $current_theme ){
			$page->ajaxReplace[] = array('inner','#gp_option_area',$gp_option_area);
			$page->ajaxReplace[] = array('inner','#gp_gallery_avail_imgs',$gp_gallery_avail_imgs);
		}else{
			$content = '<div id="gp_option_area">'.$gp_option_area.'</div>'
						.'<div id="gp_gallery_avail_imgs">'.$gp_gallery_avail_imgs.'</div>';
			$page->ajaxReplace[] = array('inner','#gp_image_area',$content);
		}

		$page->ajaxReplace[] = array('inner','#gp_folder_options',''); //remove upload button
	}


	/**
	 * Get a list of all the images available within a theme
	 *
	 */
	public function GetAvailThemeImages( $dir, $url, &$images ){

		$files = scandir($dir);
		$files = array_diff($files,array('.','..'));
		foreach($files as $file){

			$file_full = $dir.'/'.$file;
			$file_url = $url.'/'.$file;
			if( is_dir($file_full) ){
				self::GetAvailThemeImages( $file_full, $file_url, $images );
				continue;
			}

			if( !self::IsImg( $file ) ){
				continue;
			}

			$size = getimagesize($file_full);

			$temp = array();
			$temp['width'] = $size[0];
			$temp['height'] = $size[1];
			$temp['full'] = $file_full;
			$temp['url'] = $file_url;
			$images[] = $temp;
		}
	}


	/**
	 * Determines if the $file is an image based on the file extension
	 * @static
	 * @return bool
	 */
	public static function IsImg($file){
		$img_types = array('bmp','png','jpg','jpeg','gif','tiff','tif');

		$name_parts = explode('.',$file);
		$file_type = array_pop($name_parts);
		$file_type = strtolower($file_type);

		return in_array($file_type,$img_types);
	}


	public function Drag(){
		global $page,$langmessage;

		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutCmd) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$from_container,$from_gpOutCmd);
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}


		$where = array_search($from_gpOutCmd,$handlers[$from_container]);
		$to = array_search($to_gpOutCmd,$handlers[$from_container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(3)');
			return;
		}


		array_splice($handlers[$from_container],$where,1);

		/**
		 * for moving down
		 * if target is the same container
		 * and target is below dragged element
		 * then $offset = 1
		 *
		 */
		$offset = 0;
		if( ($from_container == $to_container)
			&& ($to !== null)
			&& ($to !== false)
			&& $to > $where ){
				$offset = 1;
		}

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$from_gpOutCmd,false,$offset) ){
			return;
		}

		$this->SaveHandlersNew($handlers);

	}




	/**
	 * Display dialog for insterting gadgets/menus/etc into layouts
	 *
	 */
	public function SelectContent(){
		global $langmessage, $config;

		if( !isset($_GET['param']) ){
			message($langmessage['OOPS'].' (Param not set)');
			return;
		}
		$param = $_GET['param'];

		//counts
		$count_gadgets = ( isset($config['gadgets']) && is_array($config['gadgets']) ) ? count($config['gadgets']) : false;
		echo '<div class="inline_box">';

		echo '<div class="layout_links">';
		echo '<a href="#layout_extra_content" class="selected" data-cmd="tabs">'. $langmessage['theme_content'] .'</a>';
		if( $count_gadgets > 0 ){
			echo ' <a href="#layout_gadgets" data-cmd="tabs">'. $langmessage['gadgets'] .'</a>';
		}
		echo ' <a href="#layout_menus" data-cmd="tabs">'. $langmessage['Link_Menus'] .'</a>';

		echo ' <a href="#layout_custom" data-cmd="tabs">'. $langmessage['Custom Menu'] .'</a>';

		echo '</div>';

		$this->SelectContent_Areas($param,$count_gadgets);
		echo '</div>';
	}


	public function SelectContent_Areas($param,$count_gadgets){
		global $dataDir, $langmessage, $config;


		$slug = 'Admin_Theme_Content/Edit/'.rawurlencode($this->curr_layout);
		$addQuery = 'cmd=addcontent&where='.rawurlencode($param);
		echo '<div id="area_lists">';

			//extra content
			echo '<div id="layout_extra_content">';
			echo '<table class="bordered">';

				echo '<tr><th colspan="2">&nbsp;</th></tr>';

				$extrasFolder = $dataDir.'/data/_extra';
				$files = \gpFiles::ReadDir($extrasFolder);
				asort($files);
				foreach($files as $file){
					$extraName = $file;
					echo '<tr><td>';
					echo str_replace('_',' ',$extraName);
					echo '</td><td class="add">';
					echo \common::Link($slug,$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,array('data-cmd'=>'creq'));
					echo '</td></tr>';
				}


				//new extra area
				echo '<tr><td>';
				echo '<form action="'.\common::GetUrl($slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="new_extra" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				echo '<input type="text" name="extra_area" value="" size="15" class="gpinput"/>';
				includeFile('tool/SectionContent.php');
				$types = \section_content::GetTypes();
				echo '<select name="type" class="gpselect">';
				foreach($types as $type => $info){
					echo '<option value="'.$type.'">'.$info['label'].'</option>';
				}
				echo '</select> ';
				echo ' <input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gpbutton"/>';
				echo '</form>';
				echo '</td><td colspan="2" class="add">';
				echo '<form action="'.\common::GetUrl($slug).'" method="post">';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
				echo '</form>';
				echo '</td></tr>';
				echo '</table>';

			echo '</div>';


			//gadgets
			if( $count_gadgets > 0){
				echo '<div id="layout_gadgets" class="nodisplay">';
					echo '<table class="bordered">';
					echo '<tr><th colspan="2">&nbsp;</th></tr>';

					foreach($config['gadgets'] as $gadget => $info){
						echo '<tr>';
							echo '<td>';
							echo str_replace('_',' ',$gadget);
							echo '</td>';
							echo '<td class="add">';
							echo \common::Link($slug,$langmessage['add'],$addQuery.'&insert='.$gadget,array('data-cmd'=>'creq'));
							echo '</td>';
							echo '</tr>';
					}

					echo '<tr><td colspan="2" class="add">';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';

					echo '</table>';
				echo '</div>';
			}

			//menus
			echo '<div id="layout_menus" class="nodisplay">';


				echo '<form action="'.\common::GetUrl($slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="preset_menu" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';


				echo '<table class="bordered">';
					$this->PresetMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" class="gpsubmit" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';
				echo '</table>';
				echo '</form>';


			echo '</div>';


			echo '<div id="layout_custom" class="nodisplay">';

				//custom area
				echo '<form action="'.\common::GetUrl($slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="custom_menu" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				$this->CustomMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" class="gpsubmit" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';
				echo '</table>';

				echo '</form>';
			echo '</div>';
		echo '</div>';
	}


	/**
	 * Insert new content into a layout
	 *
	 */
	public function AddContent(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

		if( !isset($_REQUEST['where']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//prep destination
		if( !$this->GetValues($_REQUEST['where'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].' (Insert location not found)');
			return false;
		}
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//figure out what we're inserting
		$addtype =& $_REQUEST['addtype'];
		switch($_REQUEST['addtype']){

			case 'new_extra':
				$extra_name = $this->NewExtraArea();
				if( $extra_name === false ){
					message($langmessage['OOPS'].'(2)');
					return false;
				}
				$insert = 'Extra:'.$extra_name;
			break;

			case 'custom_menu':
				$insert = $this->NewCustomMenu();
			break;

			case 'preset_menu':
				$insert = $this->NewPresetMenu();
			break;


			default:
				$insert = $_REQUEST['insert'];
			break;
		}

		if( !$insert ){
			message($langmessage['OOPS'].' (Nothing to insert)');
			return false;
		}

		//new info
		$new_gpOutInfo = \gpOutput::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			message($langmessage['OOPS'].' (Nothing to insert)');
			return false;
		}
		$new_gpOutCmd = rtrim($new_gpOutInfo['key'].':'.$new_gpOutInfo['arg'],':');

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$new_gpOutCmd,false) ){
			return false;
		}

		$this->SaveHandlersNew($handlers);

		return true;
	}

	/**
	 * Return the name of the cleansed extra area name, create file if it doesn't already exist
	 *
	 */
	public function NewExtraArea(){
		global $langmessage, $dataDir;

		$title = \gp_edit::CleanTitle($_REQUEST['extra_area']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$data = \gp_edit::DefaultContent($_POST['type']);
		$file = $dataDir.'/data/_extra/'.$title.'.php';

		if( \gpFiles::Exists($file) ){
			return $title;
		}

		if( !\gpFiles::SaveData($file,'extra_content',$data) ){
			message($langmessage['OOPS']);
			return false;
		}

		return $title;
	}



	public function RemoveArea(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

		if( !$this->ParseHandlerInfo($_GET['param'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$gpOutCmd = $curr_info['gpOutCmd'];
		$container = $curr_info['container'];


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$container,$gpOutCmd);


		//remove from $handlers[$container]
		$where = array_search($gpOutCmd,$handlers[$container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}

		array_splice($handlers[$container],$where,1);
		$this->SaveHandlersNew($handlers);

	}

}