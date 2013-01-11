<?php
defined('is_running') or die('Not an entry point...');


class SimpleSlideshow{

	/*
	 * @static
	 */
	static function SectionTypes($section_types){

		$section_types['simple_slide'] = array();
		$section_types['simple_slide']['label'] = 'Simple Slideshow';

		return $section_types;
	}

	/*
	 * @static
	 */
	static function SectionToContent($section_data){

		if( $section_data['type'] != 'simple_slide' ){
			return $section_data;
		}


		SimpleSlideshow::AddComponents();


		//get the first image
		$first_image = '';
		$first_caption = '';

		//we could show the first caption
		//	- we'd have to make sure the html is valid first
		//	- convert &gt; into > etc
		$pattern = '#<a.*?title=[\'"]([^\'"]+)[\'"]#';
		if( preg_match($pattern,$section_data['content'],$caption_match) ){
			$first_caption = $caption_match[1];
		}

		$pattern = '#<a.*?href=[\'"]([^\'"]+)[\'"]#';
		if( preg_match($pattern,$section_data['content'],$image_match) ){

			$first_image = '<a href="#" name="gp_slideshow_next" class="slideshow_slide first_image" title="'.$first_caption.'">'
						.'<img src="'.$image_match[1].'" alt="'.$first_caption.'" />'
						.'</a>';
		}



		$controls = '<div class="gp_slide_cntrls"><span>'
					.'<a href="#" name="gp_slideshow_prev" class="gp_slide_prev" title="Previous"></a>'
					.'<a href="#" name="gp_slideshow_play" class="gp_slide_play_pause" title="Play / Pause"></a>'
					.'<a href="#" name="gp_slideshow_next" class="gp_slide_next" title="Next"></a>'
					.'</span></div>';

		$section_data['content'] = '<div class="slideshow_area">'
									.'<div class="gp_nosave">'
									.'<div class="slideshow-container">'
									.$controls
									.'<div class="loader"></div>'
									.$first_image
									.'</div>'
									.'<div class="caption-container"></div>'
									.'</div>'
									.$section_data['content']
									.'</div>';


		return $section_data;
	}




	/**
	 * @static
	 */
	static function DefaultContent($default_content,$type){
		global $langmessage;
		if( $type == 'simple_slide' ){
			return '<div class="gp_slide_thumbs"><ul class="gp_slideshow"><li class="gp_to_remove"><a></a></li></ul></div>';
		}
		return $default_content;
	}

	/**
	 * @static
	 */
	static function SaveSection($return,$section,$type){
		global $page;
		if( $type == 'simple_slide' ){
			$page->SaveSection_Text($section);
			return true;
		}
		return $return;
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
		SimpleSlideshow::AddComponents();

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
		if( $type !== 'simple_slide' ){
			return $scripts;
		}

		if( defined('gpversion') && version_compare(gpversion,'3.5','>=') ){
			$scripts[] = $addonRelativeCode.'/gallery_options.js';
			$scripts[] = '/include/js/inline_edit/inline_editing.js';
			$scripts[] = '/include/js/inline_edit/image_common.js';

			//$scripts[] = '/include/thirdparty/jquery_ui/jquery-ui.custom.min.js';

			$scripts[] = '/include/js/inline_edit/gallery_edit_202.js';
			$scripts[] = '/include/js/jquery.auto_upload.js';

		}else{

			$scripts[] = $addonRelativeCode.'/gallery_options.js';
			$scripts[] = '/include/js/inline_edit/inline_editing.js';
			$scripts[] = '/include/thirdparty/jquery_ui/jquery-ui.custom.min.js';
			$scripts[] = '/include/js/inline_edit/gallery_edit_202.js';
			$scripts[] = '/include/js/jquery.auto_upload.js';
		}

		return $scripts;
	}

}








