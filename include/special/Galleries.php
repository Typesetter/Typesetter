<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');

class Galleries extends \gp\special\Base{

	public $galleries = array();
	public $title_removed = false;
	public $not_visible = array();

	public function __construct($args){

		parent::__construct($args);

		$this->galleries = self::GetData();
		$this->GenerateOutput();
	}


	/**
	 * Determine if the gallery page is hidden or deleted
	 *
	 */
	public function GalleryVisible( $title, $info ){
		global $gp_index, $gp_menu, $gp_titles;

		if( !isset($gp_index[$title]) ){
			unset($this->galleries[$title]);
			$this->title_removed = true;
			return false;
		}

		$index			= $gp_index[$title];
		$title_info		= $gp_titles[$index];

		if( (isset($info['visibility']) && $info['visibility'] == 'hide') || isset($title_info['vis']) ){
			$this->not_visible[$title] = $info;
			return false;
		}

		return true;
	}

	// save the galleries index file
	public function PostSave(){
		if( !$this->title_removed ){
			return;
		}
		self::SaveIndex($this->galleries);
	}



	/**
	 * Get Gallery Index
	 *
	 * @static
	 */
	public static function GetData(){

		$galleries = \gp\tool\Files::Get('_site/galleries');
		if( !$galleries ){
			return array();
		}

		if( version_compare(\gp\tool\Files::$last_version,'2.2','<=') ){
			self::UpdateData($galleries);
		}

		return $galleries;
	}

	/**
	 * Add visibility settings according to old method for handling gallery visibility
	 * @static
	 */
	public static function UpdateData(&$galleries){
		global $gp_index, $gp_menu;

		foreach($galleries as $title => $info){

			if( isset($info['visibility']) ){
				continue;
			}

			$id = $gp_index[$title];

			if( !isset($gp_menu[$id]['level']) ){
				$galleries[$title]['visibility'] = 'hide';
			}else{
				$galleries[$title]['visibility'] = 'show';
			}
		}
	}


	public function GenerateOutput(){
		global $langmessage;

		\gp\tool::ShowingGallery();

		echo '<div class="GPAREA filetype-special_galleries">';
		echo '<h2>';
		echo \gp\tool\Output::ReturnText('galleries');
		echo '</h2>';

		$wrap = \gp\admin\Tools::CanEdit($this->page->gp_index);
		if( $wrap ){
			echo \gp\tool\Output::EditAreaLink($edit_index,'Admin/Galleries',$langmessage['edit']);
			echo '<div class="editable_area cf" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}


		$image_text = \gp\tool\Output::ReturnText('image');
		$images_text = \gp\tool\Output::ReturnText('images');

		$list = '';
		$shown = 0;
		foreach($this->galleries as $title => $info ){


			//page is hidden
			if( !$this->GalleryVisible($title,$info) ){
				continue;
			}

			$count = '';
			if( is_array($info) ){
				$icon = $info['icon'];
				if( $info['count'] == 1 ){
					$count = $info['count'].' '.\gp\tool\Output::ReturnText('image');
				}elseif( $info['count'] > 1 ){
					$count = $info['count'].' '.\gp\tool\Output::ReturnText('images');
				}
			}else{
				$icon = $info;
			}

			if( empty($icon) ){
				continue;
			}


			$icon = rawurldecode($icon); //prevent double encoding
			if( strpos($icon,'/thumbnails/') === false ){
				$thumbPath = \gp\tool::GetDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
			}else{
				$thumbPath = \gp\tool::GetDir('/data/_uploaded'.$icon);
			}

			$label = \gp\tool::GetLabel($title);
			$title_attr = ' title="'.\gp\tool::GetBrowserTitle($title).'"';
			$label_img = ' <img src="'.$thumbPath.'" alt=""/>';

			$list .= '<li>'
					. \gp\tool::Link($title,$label_img,'',$title_attr)
					. '<div>'
					. \gp\tool::Link($title, $label,'',$title_attr)
					. '<p>'
					.$count
					.'</p>'
					.'</div>'
					.'</li>';
		}

		if( !empty($list) ){
			echo '<ul class="gp_gallery gp_galleries">';
			echo $list;
			echo '</ul>';
		}

		if( $wrap ){
			echo '</div>';
		}
		$this->PostSave();

    echo '</div>';
	}

	/*

	Updating Functions

		The galleries.php file needs to  be updated when changes are made to pages with galleries
		When a page is...
			... renamed:				RenamedGallery()
			... edited:					UpdateGalleryInfo()
			... added:					do nothing, there won't be any images yet, wait till edited
			... deleted:				RemovedGallery()
			... restored from trash:	UpdateGalleryInfo() via RestoreFile() in gp\admin\Content\Trash
	*/



	/**
	 * Extract information about the gallery from it's html: img_count, icon_src
	 *
	 * @static
	 */
	public static function UpdateGalleryInfo($title,$file_sections){

		$content = '';
		$has_gallery = false;
		foreach($file_sections as $section_data){
			if( $section_data['type'] == 'gallery' ){
				$content .= $section_data['content'];
				$has_gallery = true;
			}
		}

		if( !$has_gallery ){
			self::RemovedGallery($title);
			return;
		}

		$new_count = preg_match_all('#(rel|class)="gallery_gallery"#',$content,$matches);

		//first image
		$new_icon = '';
		$first_img = preg_match('#<img[^>]*src="([^>"]*)"[^>]*>#',$content,$match); //uploaded file's names are stripped of " and >
		if( $first_img === 1 ){
			$new_icon = $match[1];

			$pos = strpos($new_icon,'/data/_uploaded');
			if( $pos !== false ){
				$new_icon = substr($new_icon,$pos+15);
			}
		}


		$galleries = self::GetData();

		$orig_icon = $orig_count = false;
		$orig_info = array();
		if( isset($galleries[$title]) && is_array($galleries[$title]) ){
			$orig_info = $galleries[$title];
			$orig_icon = $orig_info['icon'];
			$orig_count = $orig_info['count'];
		}

		if( ($orig_icon == $new_icon ) && ($orig_count == $new_count) ){
			return;
		}

		$orig_info['icon'] = $new_icon;
		$orig_info['count'] = $new_count;
		$galleries[$title] = $orig_info;
		self::SaveIndex($galleries);
	}


	/**
	 * Handle the removal of a gallery page for \gp\admin\Menu\Tools.php
	 *
	 */
	public static function RemovedGallery($title){

		$galleries = self::GetData();
		if( !isset($galleries[$title]) ){
			return;
		}

		unset($galleries[$title]);
		self::SaveIndex($galleries);
	}


	/**
	 * Handle the renaming of galleries for \gp\admin\Menu\Tools.php
	 *
	 * @static
	 *
	 */
	public static function RenamedGallery($old_title,$new_title){

		$galleries = self::GetData();
		if( !isset($galleries[$old_title]) ){
			return;
		}

		if( \gp\tool\Files::ArrayInsert($old_title,$new_title,$galleries[$old_title],$galleries,0,1) ){
			self::SaveIndex($galleries);
		}
	}

	public static function SaveIndex($galleries){
		global $dataDir;

		$file = $dataDir.'/data/_site/galleries.php';
		return \gp\tool\Files::SaveData($file,'galleries',$galleries);
	}


}
