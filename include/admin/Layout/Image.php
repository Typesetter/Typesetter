<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Image extends \gp\admin\Layout\Edit{

	function __construct($args){

		parent::__construct($args);

		// inline editing images
		$this->cmds['InlineEdit']		= '';	//added to in js
		$this->cmds['gallery_folder']	= 'GalleryImages';
		$this->cmds['gallery_images']	= 'GalleryImages';
		$this->cmds['save_inline']		= 'SaveHeaderImage';
		$this->cmds['image_editor']		= '\\gp\\tool\\Editing::ImageEditor';
	}


	/**
	 * Load the inline editor for a theme image
	 *
	 */
	public function InlineEdit(){

		$section = array();
		$section['type'] = 'image';
		\gp\tool\Output\Ajax::InlineEdit($section);
		die();
	}

	public function GalleryImages(){
		$_GET += array('dir'=>'/headers');
		\gp\admin\Content\Uploaded::InlineList($_GET['dir']);
	}



	/**
	 * Save a theme image
	 * Resize image if necessary
	 *
	 */
	public function SaveHeaderImage(){
		global $gpLayouts,$langmessage;

		$this->page->ajaxReplace = array();


		$section = array();
		if( !\gp\tool\Editing::SectionFromPost_Image($section, '/data/_uploaded/headers/') ){
			return false;
		}


		$save_info = array();
		$save_info['img_rel']	= $section['attributes']['src'];
		$save_info['width']		= $section['attributes']['width'];
		$save_info['height']	= $section['attributes']['height'];

		$container = $_REQUEST['container'];
		$gpLayouts[$this->curr_layout]['images'][$container] = array(); //prevents shuffle
		$gpLayouts[$this->curr_layout]['images'][$container][] = $save_info;


		if( !$this->SaveLayouts() ){
			return false;
		}
		$this->page->ajaxReplace[] = array('ck_saved','','');
		return true;
	}


	/**
	 * Show images available in themes
	 *
	 */
	public function ShowThemeImages(){
		global $langmessage;

		$this->page->ajaxReplace = array();
		$current_theme = false;

		//which theme folder
		if( isset($_REQUEST['theme']) && isset($this->avail_addons[$_REQUEST['theme']]) ){
			$current_theme = $_REQUEST['theme'];
			$current_info = $this->avail_addons[$current_theme];
			$current_label = $current_info['name'];
			$current_dir = $current_info['full_dir'];
			$current_url = \gp\tool::GetDir($current_info['rel']);

		//current layout
		}else{
			$layout_info = \gp\tool::LayoutInfo($this->curr_layout,false);
			$current_label = $layout_info['theme_name'];
			$current_dir = $layout_info['dir'];
			$current_url = \gp\tool::GetDir(dirname($layout_info['path']));
		}


		//list of themes
		ob_start();
		echo '<div class="gp_edit_select">';
		echo '<a class="gp_selected_folder"><span class="folder"></span>';
		echo $current_label;
		echo '</a>';

		echo '<div class="gp_edit_select_options">';

		foreach($this->avail_addons as $theme_id => $info){
			$slug = 'Admin_Theme_Content/Image/'.rawurlencode($this->curr_layout);
			echo \gp\tool::Link($slug,'<span class="folder"></span>'.$info['name'],'cmd=ShowThemeImages&theme='.rawurlencode($theme_id),' data-cmd="gpajax" class="gp_gallery_folder" ');
		}
		echo '</div>';
		echo '</div>';

		$gp_option_area = ob_get_clean();


		//images in theme
		$images = array();
		self::GetAvailThemeImages( $current_dir, $current_url, $images );
		ob_start();
		foreach($images as $image ){
			echo '<div class="expand_child">'
				. '<a href="'.$image['url'].'" data-cmd="gp_gallery_add" data-width="'.$image['width'].'" data-height="'.$image['height'].'">'
				. '<img src="'.$image['url'].'" alt=""/>'
				. '</a></div>';
		}
		$gp_gallery_avail_imgs = ob_get_clean();


		if( $current_theme ){
			$this->page->ajaxReplace[] = array('inner','#gp_option_area',$gp_option_area);
			$this->page->ajaxReplace[] = array('inner','#gp_gallery_avail_imgs',$gp_gallery_avail_imgs);
		}else{
			$content = '<div id="gp_option_area">'.$gp_option_area.'</div>'
						.'<div id="gp_gallery_avail_imgs">'.$gp_gallery_avail_imgs.'</div>';
			$this->page->ajaxReplace[] = array('inner','#gp_image_area',$content);
		}

		$this->page->ajaxReplace[] = array('inner','#gp_folder_options',''); //remove upload button
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


}