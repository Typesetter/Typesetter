<?php
defined('is_running') or die('Not an entry point...');


class SlideshowB{

	/**
	 * Generate a pseudo-random key for the content key to prevent duplicates
	 *
	 */
	static function ContentKey(){
		global $addonFolderName;

		static $key = false;
		if( $key !== false ){
			return $key;
		}

		$key = 'slideshow_b_'.$addonFolderName;
		return $key;
	}


	/**
	 * @static
	 *
	 */
	static function SectionTypes($section_types, $generated_key = false ){

		$section_types[self::ContentKey()] = array('label' => 'Slideshow B');

		return $section_types;
	}

	/**
	 * @static
	 */
	static function SectionToContent($section_data){
		global $dataDir;

		if( $section_data['type'] != self::ContentKey() ){
			return $section_data;
		}


		SlideshowB::AddComponents();

		return $section_data;
	}

	static function GenerateContent($section_data){
		$section_data += array('images'=>array());

		$icons = $first_image = $first_caption = '';
		foreach($section_data['images'] as $i => $img){
			$caption =& $section_data['captions'][$i];

			$hash = $size_a = false;
			$attr = '';

			if( empty($first_image) ){
				$hash = 1;
				$first_caption = $caption;
				$first_image = '<a class="slideshowb_img_'.$hash.'" data-cmd="slideshowb_next" href="'.$img.'">';
				$first_image .= '<img src="'.$img.'" alt="">';
				$first_image .= '</a>';
			}

			if( $hash ){
				$attr = ' data-hash="'.$hash.'" class="slideshowb_icon_'.$hash.'"';
			}

			$thumb_path = common::ThumbnailPath($img);
			$icons .= '<li>';
			$icons .= '<a data-cmd="slideshowb_img" href="'.$img.'"'.$attr.' title="'.htmlspecialchars($caption).'">';
			$icons .= '<img alt="" src="'.$thumb_path.'">';
			$icons .= '</a>';
			$icons .= '<div class="caption">'.$caption.'</div>';
			$icons .= '</li>';
		}

		ob_start();


		echo '<div class="slideshowb_wrap">';
		echo '<div class="slideshowb_images">';
		echo $first_image;
		echo '</div>';
		echo '<div class="slideshowb_caption prov_caption">';
		echo $first_caption.'&nbsp;';
		echo '</div>';
		echo '<div class="slideshowb_icons prov_icons"><span></span><ul>';
		echo $icons;
		echo '</ul></div>';
		echo '</div>';

		return ob_get_clean();
	}




	/**
	 * @static
	 */
	static function DefaultContent($default_content,$type){
		if( $type == self::ContentKey() ){
			ob_start();
			echo '<div class="slideshowb_wrap">';
			echo '<div class="slideshowb_images">';
			//echo $images;
			echo '</div>';
			echo '<div class="slideshowb_caption prov_caption">';
			//echo $caption.'&nbsp;';
			echo '</div>';
			echo '<div class="slideshowb_icons prov_icons"><span></span><ul>';
			//echo $icons;
			echo '</ul></div>';
			echo '</div>';
			return ob_get_clean();
		}
		return $default_content;
	}

	/**
	 * @static
	 */
	static function SaveSection($return,$section,$type){
		global $page;
		if( $type != self::ContentKey() ){
			return $return;
		}

		$page->file_sections[$section]['images'] = $_REQUEST['images'];
		$page->file_sections[$section]['captions'] = $_REQUEST['captions'];
		$page->file_sections[$section]['content'] = self::GenerateContent($page->file_sections[$section]);

		return true;
	}


	/**
	 * Make sure the .js and .css is available to admins
	 *
	 * @static
	 *
	 */
	static function GenerateContent_Admin(){
		global $addonFolderName, $page, $addonRelativeCode;
		$page->head_script .= "\nvar SLIDESHOW_BASE = '".$addonRelativeCode."';\n";
		SlideshowB::AddComponents();
	}


	static function AddComponents(){
		global $addonFolderName, $page, $addonRelativeCode;
		static $done = false;

		if( $done ) return;

		$page->admin_js = true; //loads main.js
		$page->head_js[] = '/data/_addoncode/'.$addonFolderName.'/slideshow.js';
		$page->css_user[] = '/data/_addoncode/'.$addonFolderName.'/slideshow.css';

		$done = true;
	}


	/**
	 *
	 *
	 */
	static function InlineEdit_Scripts($scripts,$type){
		global $addonRelativeCode;
		if( $type !== self::ContentKey() ){
			return $scripts;
		}

		$scripts[] = '/include/js/inline_edit/inline_editing.js';
		$scripts[] = '/include/js/inline_edit/image_common.js';
		$scripts[] = '/include/js/inline_edit/gallery_edit_202.js';
		$scripts[] = $addonRelativeCode.'/gallery_options.js';
		$scripts[] = '/include/js/jquery.auto_upload.js';


		return $scripts;
	}
}








