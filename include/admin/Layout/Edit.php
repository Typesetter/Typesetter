<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Edit extends \gp\admin\Layout{

	protected $layout_request = true;
	protected $layout_slug;

	public function __construct(){
		global $page, $gpLayouts, $config;

		parent::__construct();


		//layout request
		$parts = explode('/',$page->requested);
		if( isset($parts[2]) && isset($gpLayouts[$parts[2]]) ){
			$this->EditLayout($parts[2]);
			return;
		}

		//default layout
		if( empty($parts[2]) ){
			$this->EditLayout($config['gpLayout']);
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
		$this->layout_slug				= 'Admin_Theme_Content/Edit/'.rawurlencode($layout);

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

			//links
			case 'LayoutMenu':
				$this->LayoutMenu();
			return;
			case 'LayoutMenuSave':
				$this->LayoutMenuSave();
			return;

			//css
			case 'SaveCSS':
				$this->SaveCSS();
			break;
			case 'PreviewCSS':
				$this->PreviewCSS();
			break;



		}

		if( $this->LayoutCommands($cmd) ){
			return;
		}


		//control what is displayed
		switch( $cmd ){

			//show the layout (displayed within an iframe)
			case 'SaveCSS':
			case 'PreviewCSS':
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
	 * Prepare the page for css editing
	 *
	 */
	public function ShowInIframe($cmd){
		global $page,$dirPrefix;

		$page->show_admin_content = false;
		\admin_tools::$show_toolbar = false;

		// <head>
		$page->head .= '<script type="text/javascript">parent.$gp.iframeloaded();</script>';
		if( $cmd != 'PreviewCSS' ){
			$page->head .= '<script type="text/javascript">var gpLayouts=true;</script>';
		}
	}


	/**
	 * Display the toolbar for layout editing
	 *
	 */
	public function LayoutEditor($layout, $layout_info ){
		global $page,$langmessage,$config;
		$page->show_admin_content = false;

		$page->head_js[] = '/include/thirdparty/codemirror/lib/codemirror.js';
		$page->head_js[] = '/include/thirdparty/codemirror/mode/less/less.js';
		$page->css_user[] = '/include/thirdparty/codemirror/lib/codemirror.css';



		echo '<div id="theme_editor">';
		echo '<form action="'.\common::GetUrl('Admin_Theme_Content/Edit/'.$this->curr_layout,'cmd=in_iframe').'" method="post" class="full_height" target="gp_layout_iframe">';
		echo '<table border="0">';
		echo '<tr><td>';



		echo '<div>';
		echo \common::Link('Admin_Theme_Content','&#171; '.$langmessage['layouts']);
		echo '<div class="layout_select">';
		$this->LayoutSelect($layout,$layout_info);
		echo '</div>';


		//options
		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$langmessage['Layout Options'].'</a>';
		echo '<div class="dd_list">';
		echo '<ul>';
		$this->LayoutOptions($layout,$layout_info);
		echo '</ul>';
		echo '</div>';
		echo '</div></div>';


		//css textarea
		echo '</div>';
		echo '<div class="separator"></div>';


		echo '</td></tr><tr><td class="full_height"><div class="full_height">';


		//custom css
		$css = $this->layoutCSS($this->curr_layout);
		if( empty($css) ){
			$var_file = $layout_info['dir'].'/'.$layout_info['theme_color'].'/variables.less';
			if( file_exists($var_file) ){
				$css = file_get_contents($var_file);
			}
		}

		echo '<textarea name="css" id="gp_layout_css" class="gptextarea" placeholder="'.htmlspecialchars($langmessage['Add your LESS and CSS here']).'" wrap="off">';
		echo htmlspecialchars($css);
		echo '</textarea>';


		//save button
		echo '</div></td></tr><tr><td><div>';

		echo ' <button name="cmd" type="submit" value="PreviewCSS" class="gpsubmit" data-cmd="preview_css" />'.$langmessage['preview'].'</button>';
		echo ' <button name="cmd" type="submit" value="SaveCSS" class="gpsubmit" data-cmd="reset_css" />'.$langmessage['save'].'</button>';
		//echo ' <input type="reset" class="gpsubmit" data-cmd="reset_css" />';


		echo '</div></td></tr>';
		echo '</table>';
		echo '</form>';


		//show site in iframe
		echo '<div id="gp_iframe_wrap">';
		$url = \common::GetUrl('Admin_Theme_Content/Edit/'.rawurlencode($layout),'cmd=in_iframe');
		echo '<iframe src="'.$url.'" id="gp_layout_iframe" name="gp_layout_iframe"></iframe>';

		echo '</div>';



		echo '</div>'; //#theme_editor

	}



	/**
	 * Display all the layouts available in a <select>
	 *
	 */
	public function LayoutSelect($curr_layout=false,$curr_info=false){
		global $gpLayouts, $langmessage, $config;

		$display = $langmessage['available_layouts'];
		if( $curr_layout ){
			$display = '<span class="layout_color_id" style="background-color:'.$curr_info['color'].';"></span> &nbsp; '
					. $curr_info['label'];
		}

		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$display.'</a>';

		echo '<div class="dd_list"><ul>';
		foreach($gpLayouts as $layout => $info){
			$attr = '';
			if( $layout == $curr_layout){
				$attr = ' class="selected"';
			}
			echo '<li'.$attr.'>';

			$display = '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span> &nbsp; '. $info['label'];
			if( $config['gpLayout'] == $layout ){
				$display .= ' <span class="layout_default"> ('.$langmessage['default'].')</span>';
			}
			echo \common::Link('Admin_Theme_Content/Edit/'.rawurlencode($layout),$display);
			echo '</li>';
		}
		echo '</ul></div>';
		echo '</div></div>';
	}



	/**
	 * Save edits to the layout css
	 *
	 */
	public function SaveCSS(){
		global $langmessage, $dataDir, $gpLayouts, $page;

		$layout_info = \common::LayoutInfo($this->curr_layout,false);
		$color = $layout_info['theme_color'];
		$theme_colors = $this->GetThemeColors($layout_info['dir']);
		$path = $dataDir.'/data/_layouts/'.$this->curr_layout.'/custom.css';
		$css =& $_POST['css'];

		//check theme color
		if( array_key_exists('color',$_REQUEST) ){

			if( !isset($theme_colors[$color]) ){
				message($langmessage['OOPS'].' (Invalid Color)');
				return false;
			}
			$color = $_REQUEST['color'];
		}

		$old_info = $new_info = $gpLayouts[$this->curr_layout];
		$theme_name = dirname($new_info['theme']);
		$new_info['theme'] = $theme_name.'/'.$color;
		$gpLayouts[$this->curr_layout] = $new_info;


		//delete css file if empty
		if( empty($css) ){
			unset($gpLayouts[$this->curr_layout]['css']);
			$this->RemoveCSS($this->curr_layout);

		//save if not empty
		}elseif( !\gpFiles::Save($path,$css) ){
			message($langmessage['OOPS'].' (CSS not saved)');
			return false;
		}


		$gpLayouts[$this->curr_layout]['css'] = true;
		if( !\admin_tools::SavePagesPHP() ){
			$gpLayouts[$this->curr_layout] = $old_info;
			message($langmessage['OOPS'].' (Data not saved)');
			return false;
		}

		message($langmessage['SAVED']);
		$page->SetTheme($this->curr_layout);

	}


	/**
	 * Preview changes to the custom css/less
	 *
	 */
	public function PreviewCSS(){
		global $page, $langmessage;

		$layout_info = \common::LayoutInfo($this->curr_layout,false);
		$theme_colors = $this->GetThemeColors($layout_info['dir']);
		$color = $layout_info['theme_color'];

		// which color option
		if( array_key_exists('color',$_REQUEST) ){

			if( !isset($theme_colors[$color]) ){
				message($langmessage['OOPS'].' (Invalid Color)');
				return false;
			}
			$color = $_REQUEST['color'];
		}

		$page->theme_color = $color;
		$page->theme_rel = dirname($page->theme_rel).'/'.$color;
		$page->theme_path = dirname($page->theme_path).'/'.$color;



		// which css files
		$less = array();
		if( file_exists($page->theme_dir . '/' . $page->theme_color . '/style.css') ){
			$page->css_user[] = rawurldecode($page->theme_path).'/style.css';
		}else{
			$less[] = $page->theme_dir . '/' . $page->theme_color . '/style.less';
		}

		// variables.less
		$var_file = $page->theme_dir . '/' . $page->theme_color . '/variables.less';
		if( file_exists($var_file) ){
			$less[] = $var_file;
		}


		$temp = trim($_REQUEST['css']);
		if( !empty($temp) ){
			$less[] = $_REQUEST['css']. "\n"; //make sure this is seen as code and not a filename
		}


		if( count($less) ){
			$compiled = \gpOutput::ParseLess( $less );
			if( !$compiled ){
				message($langmessage['OOPS'].' (Invalid LESS)');
				return false;
			}

			$page->head .= '<style>'.$compiled.'</style>';
		}

		$page->get_theme_css = false;
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


	/**
	 *
	 *
	 */
	public function SaveHeaderImage(){
		global $page, $dataDir, $dirPrefix, $langmessage;
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
		$src_img = \gp\tool\Image::getSrcImg($source_file_full);
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

		if( !\gp\tool\Image::createImg($src_img, $dest_img_full, $posx, $posy, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h, $width, $height) ){
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
			echo \common::Link($this->layout_slug,'<span class="folder"></span>'.$info['name'],'cmd=theme_images&theme='.rawurlencode($theme_id),' data-cmd="gpajax" class="gp_gallery_folder" ');
		}
		echo '</div>';
		echo '</div>';

		$gp_option_area = ob_get_clean();


		//images in theme
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
					echo \common::Link($this->layout_slug,$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,array('data-cmd'=>'creq'));
					echo '</td></tr>';
				}


				//new extra area
				echo '<tr><td>';
				echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
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
				echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
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
							echo \common::Link($this->layout_slug,$langmessage['add'],$addQuery.'&insert='.$gadget,array('data-cmd'=>'creq'));
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


				echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
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
				echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
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


	/**
	 * Display popup dialog for editing layout menus
	 *
	 */
	public function LayoutMenu(){
		global $langmessage, $gpLayouts;

		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['00PS']);
			return;
		}


		$showCustom			= false;
		$current_function	= false;
		$menu_args			= $this->MenuArgs($curr_info);

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}else{
			$current_function = $curr_info['key'];
		}



		echo '<div class="inline_box" style="width:30em">';

		echo '<div class="layout_links">';
		if( $showCustom ){
			echo ' <a href="#layout_menus" data-cmd="tabs">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" data-cmd="tabs" class="selected">'. $langmessage['Custom Menu'] .'</a>';
		}else{
			echo ' <a href="#layout_menus" data-cmd="tabs" class="selected">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" data-cmd="tabs">'. $langmessage['Custom Menu'] .'</a>';
		}
		echo '</div>';

		echo '<br/>';
		echo '<div id="area_lists">';

		//preset menus
			$style = '';
			if( $showCustom ){
				$style = ' class="nodisplay"';
			}
			echo '<div id="layout_menus" '.$style.'>';
			echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';

			echo '<table class="bordered">';
			$this->PresetMenuForm($menu_args);

			echo '<tr><td class="add" colspan="2">';
			echo '<button type="submit" name="cmd" value="LayoutMenuSave" class="gpsubmit">'.$langmessage['save'].'</button>';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

		//custom menus
			$style = ' class="nodisplay"';
			if( $showCustom ){
				$style = '';
			}
			echo '<div id="layout_custom" '.$style.'>';
			echo '<form action="'.\common::GetUrl($this->layout_slug).'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';

			$this->CustomMenuForm($curr_info['arg'],$menu_args);

			echo '<tr><td class="add" colspan="2">';
			echo '<button type="submit" name="cmd" value="LayoutMenuSave" class="gpsubmit">'.$langmessage['save'].'</button>';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

			echo '<p class="admin_note">';
			echo $langmessage['see_also'];
			echo ' ';
			echo \common::Link('Admin_Menu',$langmessage['file_manager']);
			echo ', ';
			echo \common::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
			echo '</p>';

		echo '</div>';
		echo '</div>';

	}

	/**
	 * Save the posted layout menu settings
	 *
	 */
	public function LayoutMenuSave(){
		global $config, $langmessage, $gpLayouts;

		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}



		if( isset($_POST['new_handle']) ){
			$new_gpOutCmd = $this->NewPresetMenu();
		}else{
			$new_gpOutCmd = $this->NewCustomMenu();
		}

		if( !$new_gpOutCmd ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}


		//prep
		$handlers = $this->GetAllHandlers($this->curr_layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers,$container,$curr_info['gpOutCmd']);


		if( !$this->AddToContainer($handlers[$container],$curr_info['gpOutCmd'],$new_gpOutCmd,true) ){
			return;
		}

		$this->SaveHandlersNew($handlers,$this->curr_layout);


		//message('not forwarding');
		$this->ReturnHeader();
	}


	public function ParseHandlerInfo($str,&$info){
		global $config,$gpOutConf;

		if( substr_count($str,'|') !== 1 ){
			return false;
		}


		list($container,$fullKey) = explode('|',$str);

		$arg = '';
		$pos = strpos($fullKey,':');
		$key = $fullKey;
		if( $pos > 0 ){
			$arg = substr($fullKey,$pos+1);
			$key = substr($fullKey,0,$pos);
		}

		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}

		$info = array();
		$info['gpOutCmd'] = trim($fullKey,':');
		$info['container'] = $container;
		$info['key'] = $key;
		$info['arg'] = $arg;

		return true;

	}


	public function GetValues($a,&$container,&$gpOutCmd){
		if( substr_count($a,'|') !== 1 ){
			return false;
		}

		list($container,$gpOutCmd) = explode('|',$a);
		return true;
	}


	public function AddToContainer(&$container,$to_gpOutCmd,$new_gpOutCmd,$replace=true,$offset=0){
		global $langmessage;

		//unchanged?
		if( $replace && ($to_gpOutCmd == $new_gpOutCmd) ){
			return true;
		}


		//add to to_container in front of $to_gpOutCmd
		if( !isset($container) || !is_array($container) ){
			message($langmessage['OOPS'].' (a1)');
			return false;
		}

		//can't have two identical outputs in the same container
		$check = array_search($new_gpOutCmd,$container);
		if( ($check !== null) && ($check !== false) ){
			message($langmessage['OOPS']. ' (Area already in container)');
			return false;
		}

		//if empty, just add
		if( count($container) === 0 ){
			$container[] = $new_gpOutCmd;
			return true;
		}

		$length = 1;
		if( $replace === false ){
			$length = 0;
		}

		//insert
		$where = array_search($to_gpOutCmd,$container);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. ' (Destination Container Not Found)');
			return false;
		}
		$where += $offset;

		array_splice($container,$where,$length,$new_gpOutCmd);

		return true;
	}


	public function NewCustomMenu(){

		$upper_bound =& $_POST['upper_bound'];
		$lower_bound =& $_POST['lower_bound'];
		$expand_bound =& $_POST['expand_bound'];
		$expand_all =& $_POST['expand_all'];
		$source_menu =& $_POST['source_menu'];

		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);

		$arg = $upper_bound.','.$lower_bound.','.$expand_bound.','.$expand_all.','.$source_menu;
		return 'CustomMenu:'.$arg;
	}


	public function NewPresetMenu(){
		global $gpOutConf;

		$new_gpOutCmd =& $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutCmd]) || !isset($gpOutConf[$new_gpOutCmd]['link']) ){
			return false;
		}

		return rtrim($new_gpOutCmd.':'.$this->CleanMenu($_POST['source_menu']),':');
	}


	public function PresetMenuForm($args = array()){
		global $gpOutConf,$langmessage;

		$current_function =& $args['current_function'];
		$current_menu =& $args['source_menu'];

		$this->MenuSelect($current_menu);


		echo '<tr><th colspan="2">';
			echo $langmessage['Menu Output'];
		echo '</th></tr>';


		$i = 0;
		foreach($gpOutConf as $outKey => $info){

			if( !isset($info['link']) ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<label for="new_handle_'.$i.'">';
			if( isset($langmessage[$info['link']]) ){
				echo str_replace(' ','&nbsp;',$langmessage[$info['link']]);
			}else{
				echo str_replace(' ','&nbsp;',$info['link']);
			}
			echo '</label>';
			echo '</td>';
			echo '<td class="add">';

			if( $current_function == $outKey ){
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" checked="checked"/>';
			}else{
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" />';
			}
			echo '</td>';
			echo '</tr>';
			$i++;
		}
	}


	public function MenuArgs($curr_info){

		$menu_args = array();

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;

			$args = explode(',',$curr_info['arg']);
			$args += array( 0=>0, 1=>-1, 2=>-1, 3=>0, 4=>'' ); //defaults
			list($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu) = $args;

			$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);


			$menu_args['upper_bound'] = $upper_bound;
			$menu_args['lower_bound'] = $lower_bound;
			$menu_args['expand_bound'] = $expand_bound;
			$menu_args['expand_all'] = $expand_all;
			$menu_args['source_menu'] = $source_menu;


		}else{

			$menu_args['current_function'] = $curr_info['key'];
			$menu_args['source_menu'] = $this->CleanMenu($curr_info['arg']);
		}


		return $menu_args;

	}



	/**
	 * Output form elements for setting custom menu settings
	 *
	 * @param string $arg
	 * @param array $menu_args
	 */
	public function CustomMenuForm($arg = '',$menu_args = array()){
		global $langmessage;


		$upper_bound	=& $menu_args['upper_bound'];
		$lower_bound	=& $menu_args['lower_bound'];
		$expand_bound	=& $menu_args['expand_bound'];
		$expand_all		=& $menu_args['expand_all'];
		$source_menu	=& $menu_args['source_menu'];


		echo '<table class="bordered">';

		$this->MenuSelect($source_menu);

		echo '<tr><th colspan="2">';
		echo $langmessage['Show Titles...'];
		echo '</th></tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'upper_bound', $upper_bound);
		$this->CustomMenuSection($langmessage['... At And Above Level'], 'lower_bound', $lower_bound);


		echo '<tr><th colspan="2">';
		echo $langmessage['Expand Menu...'];
		echo '</th></tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'expand_bound', $expand_bound);


		echo '<tr><td>';
		echo $langmessage['... Expand All'];
		echo '</td><td class="add">';
		$attr = $expand_all ? 'checked' : '';
		echo '<input type="checkbox" name="expand_all" '.$attr.'>';
		echo '</td></tr>';

	}




	public function CleanBounds(&$upper_bound,&$lower_bound,&$expand_bound,&$expand_all,&$source_menu){

		$upper_bound = (int)$upper_bound;
		$upper_bound = max(0,$upper_bound);
		$upper_bound = min(4,$upper_bound);

		$lower_bound = (int)$lower_bound;
		$lower_bound = max(-1,$lower_bound);
		$lower_bound = min(4,$lower_bound);

		$expand_bound = (int)$expand_bound;
		$expand_bound = max(-1,$expand_bound);
		$expand_bound = min(4,$expand_bound);

		if( $expand_all ){
			$expand_all = 1;
		}else{
			$expand_all = 0;
		}

		$source_menu = $this->CleanMenu($source_menu);
	}

	public function CleanMenu($menu){
		global $config;

		if( empty($menu) ){
			return '';
		}
		if( !isset($config['menus'][$menu]) ){
			return '';
		}
		return $menu;
	}


	/**
	 * Output section for custom menu form
	 *
	 * @param string $label
	 * @param string $name
	 * @param int $value
	 */
	public function CustomMenuSection($label, $name, $value){
		echo '<tr><td>';
		echo $label;
		echo '</td><td class="add">';
		echo '<select name="'.$name.'" class="gpselect">';
		for($i=0;$i<=4;$i++){

			$label		= ($i === 0) ? '' : $i;
			$selected	= ($i === $value) ? 'selected' : '';

			echo '<option value="'.$i.'" '.$selected.'>'.$label.'</option>';
		}

		echo '</select>';
		echo '</td></tr>';
	}


	public function MenuSelect($source_menu){
		global $config, $langmessage;

		echo '<tr><th colspan="2">';
			echo $langmessage['Source Menu'];
		echo '</th>';
		echo '</tr>';
		echo '<tr><td>';
		echo $langmessage['Menu'];
		echo '</td><td class="add">';
		echo '<select name="source_menu" class="gpselect">';
		echo '<option value="">'.$langmessage['Main Menu'].'</option>';
		if( isset($config['menus']) && count($config['menus']) > 0 ){
			foreach($config['menus'] as $id => $menu ){
				$attr = '';
				if( $source_menu == $id ){
					$attr = ' selected="selected"';
				}
				echo '<option value="'.htmlspecialchars($id).'" '.$attr.'>'.htmlspecialchars($menu).'</option>';
			}
		}
		echo '</select>';
		echo '</td></tr>';
	}

}