<?php
defined('is_running') or die('Not an entry point...');


class TwitterCarousel{

	/**
	 * Generate a pseudo-random key for the content key to prevent duplicates
	 *
	 */
	static function ContentKey(){
		global $addonFolderName;
		return $addonFolderName;
	}


	/**
	 * @static
	 *
	 */
	static function SectionTypes($section_types, $generated_key = false ){

		$section_types[self::ContentKey()] = array('label' => 'Bootstrap Carousel Gallery');

		return $section_types;
	}

	/**
	 * @static
	 *
	 */
	static function SectionToContent($section_data){
		global $dataDir;

		if( $section_data['type'] != self::ContentKey() ){
			return $section_data;
		}

		self::AddComponents();

		return $section_data;
	}

	static function GenerateContent($section_data){
		global $dataDir;
		$section_data += array('images'=>array(),'height'=>'400');

		$id = 'carousel_'.time();

		$images = '';
		$indicators = '';
		$j = 0;
		$max_w = $max_h = 99999;
		foreach($section_data['images'] as $i => $img){
			if( empty($img) ){
				continue;
			}
			$caption =& trim($section_data['captions'][$i]);

			$class = '';
			if( $j == 0 ){
				$class = 'active';
			}

			//size
			$full_path = $dataDir.rawurldecode($img);
			$size_a = getimagesize($full_path);
			if( $size_a){
				$max_w = min($size_a[0],$max_w);
				$max_h = min($size_a[1],$max_h);
			}

			//images
			$caption_class = '';
			if( empty($caption) ){
				$caption_class = 'no_caption';
			}
			$images .= '<div class="item '.$class.'">'
						.'<img src="'.$img.'" alt="">'
						.'<div class="caption carousel-caption '.$caption_class.'">'.$caption.'</div>'
						.'</div>';

			//indicators
			$thumb_path = common::ThumbnailPath($img);
			$indicators .= '<li data-target="#'.$id.'" data-slide-to="'.$j.'" class="'.$class.'">'
							.'<a href="'.$img.'">'
							.'<img src="'.$thumb_path.'" alt="">'
							.'</a>'
							.'</li>';
			$j++;
		}

		ob_start();


		$class = 'gp_twitter_carousel carousel slide';
		if( !$section_data['auto_start'] ){
			$class .= ' start_paused';
		}
		$attr = ' data-speed="1000"';
		if( isset($section_data['interval_speed']) && is_numeric($section_data['interval_speed']) ){
			$attr = ' data-speed="500"';
		}
		$attr = ' style="padding-bottom:'.$section_data['height'].'"';
		echo '<div id="'.$id.'" class="'.$class.'"'.$attr.'>';

		// Indicators
		echo '<ol class="carousel-indicators">';
		echo $indicators;
		echo '</ol>';

		// Carousel items
		echo '<div class="carousel-inner">';
		echo $images;
		echo '</div>';

		// Carousel nav
		echo '<a class="carousel-control left" data-target="#'.$id.'" data-slide="prev">&lsaquo;</a>';
		echo '<a class="carousel-control right" data-target="#'.$id.'" data-slide="next">&rsaquo;</a>';
		echo '</div>';


		$str = ob_get_clean();
		return str_replace('<',"\n<",$str);

	}



	/**
	 * @static
	 */
	static function DefaultContent($default_content,$type){
		if( $type != self::ContentKey() ){
			return $default_content;
		}

		$section = array();


		ob_start();
		$id = 'carousel_'.time();

		echo '<div id="'.$id.'" class="gp_twitter_carousel carousel slide">';
		echo '<ol class="carousel-indicators">';
		echo '<li class="active gp_to_remove"></li>';
		echo '</ol>';

		//<!-- Carousel items -->
		echo '<div class="carousel-inner">';
		echo '<div class="item active gp_to_remove"><img/></div>';
		echo '</div>';

		//<!-- Carousel nav -->
		echo '<a class="carousel-control left" data-target="#'.$id.'" data-slide="prev">&lsaquo;</a>';
		echo '<a class="carousel-control right" data-target="#'.$id.'" data-slide="next">&rsaquo;</a>';
		echo '</div>';

		$section['content'] = ob_get_clean();
		$section['height'] = '30%';
		$section['auto_start'] = false;
		$section['interval_speed'] = 5000;
		return $section;
	}


	/**
	 * @static
	 *
	 */
	static function SaveSection($return,$section,$type){
		global $page;
		if( $type != self::ContentKey() ){
			return $return;
		}

		$_POST += array('auto_start'=>'');

		$page->file_sections[$section]['auto_start'] = ($_POST['auto_start'] == 'true');
		$page->file_sections[$section]['images'] = $_POST['images'];
		$page->file_sections[$section]['captions'] = $_POST['captions'];

		$page->file_sections[$section]['height'] = $_POST['height']; //need to

		/*if( !empty($_POST['height']) ){
			$page->file_sections[$section]['height'] = $_POST['height'];
		}else{
			unset($page->file_sections[$section]['height']);
		}
		*/

		if( isset($_POST['interval_speed']) && is_numeric($_POST['interval_speed']) ){
			$page->file_sections[$section]['interval_speed'] = $_POST['interval_speed'];
		}

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
		//$page->head_script .= "\nvar SLIDESHOW_BASE = '".$addonRelativeCode."';\n";
		self::AddComponents();
	}


	static function AddComponents(){
		global $addonFolderName, $page, $addonRelativeCode;
		static $done = false;

		if( $done ) return;

		//common::LoadComponents( 'bootstrap-all' );
		common::LoadComponents( 'bootstrap-carousel' );

		//$page->admin_js = true; //loads main.js
		$page->head_js[] = '/data/_addoncode/'.$addonFolderName.'/carousel.js';
		$page->css_user[] = '/data/_addoncode/'.$addonFolderName.'/carousel.css';

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








