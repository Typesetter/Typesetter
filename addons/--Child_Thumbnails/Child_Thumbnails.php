<?php

defined('is_running') or die('Not an entry point...');

includeFile('tool/SectionContent.php');

class Child_Thumbnails{

	function __construct(){
		global $page, $gp_index, $gp_titles, $gp_menu;

		if( !isset($gp_menu[$page->gp_index]) ){
			return;
		}

		$titles = common::Descendants($page->gp_index, $gp_menu);
		$level = $gp_menu[$page->gp_index]['level'];

		echo '<ul class="child_thumbnails">';
		foreach( $titles as $index ){

			//only show children
			$child_level = $gp_menu[$index]['level'];
			if( $child_level != $level+1 ){
				continue;
			}

			$title = array_search($index, $gp_index);

			//don't show if external link
			if( !$title ){
				continue;
			}

			if( !isset($gp_titles[$index]['vis']) ){
				$this->Child($title);
			}

		}
		echo '</ul>';

	}


	/**
	 * Get The Image
	 *
	 */
	public function Child($title){
		global $dirPrefix, $addonRelativeCode, $addonPathCode;

		$content = $this->TitleContent($title);

		$img_pos = strpos($content,'<img');
		if( $img_pos !== false ){
			$src_pos = strpos($content, 'src=', $img_pos);
			if( $src_pos !== false ){
				$src = substr($content, $src_pos+4);
				$quote = $src[0];
				if( $quote == '"' || $quote == "'" ){
					$src_pos = strpos($src, $quote,1);
					$src = substr($src, 1, $src_pos-1);
					// check for resized image, get original source if img is resized
					if( strpos($src,'image.php') !== false && strpos($src,'img=') !== false ){
						$src = $dirPrefix . '/data/_uploaded/' . urldecode(substr($src,strpos($src,'img=')+4));
					}
					$thumb_path = common::ThumbnailPath($src);
				}
			}
		}else{
			// uncomment tne next line if you don't want pages w/o images listed using the default thumbnail.
			// return;
			$thumb_path = $dirPrefix . '/include/imgs/default_thumb.jpg';
		}

		$url = common::GetURL($title);
		$label = common::GetLabel($title);

		echo '<li>';
		echo 	'<a href="' . $url . '">';
		echo 		'<img alt="' . $label .'" src="' . $thumb_path . '"/>';
		echo 		'<span>' . $label . '</span>';
		echo 	'</a>';
		echo '</li>';
	}


	/**
	 * Return the formatted content of the title
	 *
	 */
	public function TitleContent($title){
		$file = gpFiles::PageFile($title);

		$file_sections = $file_stats = array();

		ob_start();
		require($file);
		ob_get_clean();

		if( !is_array($file_sections) ){
			return '';
		}

		//prevent infinite loops
		foreach($file_sections as $key=>$val){
			if( $val['type'] == 'include' ){
				//dummy section instead of include
				$file_sections[$key] = array (
					'type' => 'text',
					'content' => '<div><p>Lorem ipsum </p></div>',
					'attributes' => array (), 
				);
			}
		}

		if( !$file_sections ){
			return '';
		}

		$file_sections = array_values($file_sections);

		return section_content::Render($file_sections,$title,$file_stats);
	}

}
