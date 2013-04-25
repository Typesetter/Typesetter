<?php
defined('is_running') or die('Not an entry point...');

class special_galleries{
	var $galleries = array();
	var $title_removed = false;
	var $not_visible = array();

	function special_galleries(){
		$this->galleries = special_galleries::GetData();

		if( common::LoggedIn() ){
			$cmd = common::GetCommand();
			switch($cmd){
				case 'edit':
					$this->EditGalleries();
				return;

				case 'newdrag':
					$this->NewDrag();
				return;
			}
		}

		$this->GenerateOutput();
	}

	function NewDrag(){
		global $page, $langmessage;
		$page->ajaxReplace = array();

		//get the title of the gallery that was moved
		$dragging = $_POST['title'];
		if( !isset($this->galleries[$dragging]) ){
			message($langmessage['OOPS'].' (Title not in gallery list)');
			return false;
		}

		$info = $this->galleries[$dragging];
		unset($this->galleries[$dragging]);

		//set visibility
		if( isset($_POST['active']) ){
			$info['visibility'] = 'show';
		}else{
			$info['visibility'] = 'hide';
		}


		//place before the element represented by $_POST['next'] if it's set
		if( isset($_POST['next']) ){
			$next = $_POST['next'];
			if( !isset($this->galleries[$next]) ){
				message($langmessage['OOPS'].' (Next not found)');
				return false;
			}

			if( !gpFiles::ArrayInsert($next,$dragging,$info,$this->galleries) ){
				message($langmessage['OOPS'].' (Insert Failed)');
				return false;
			}

		//place at the end
		}else{
			$this->galleries[$dragging] = $info;
		}

		//save it
		if( !special_galleries::SaveIndex($this->galleries) ){
			message($langmessage['OOPS'].' (Not Saved)');
			return false;
		}

	}


	function EditGalleries(){
		global $page, $langmessage;

		$page->head_js[] = '/include/js/special_galleries.js';
		$page->css_admin[] = '/include/css/edit_gallery.css';


		echo admin_tools::AdminContainer();
		echo '<div id="admincontent">';
		admin_tools::AdminContentPanel();
		echo '<div id="admincontent_inner">';


		echo '<h2>';
		echo gpOutput::ReturnText('galleries');
		echo '</h2>';

		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC2'];
		echo ' &nbsp; ';
		echo common::Link('Special_Galleries',$langmessage['back']);
		echo '</p>';

		$this->EditableArea();

		echo '</div>';
		echo '</div>';
		echo '</div>';

	}

	function EditableArea(){
		global $gp_titles, $gp_index, $langmessage;


		$not_visible = array();

		echo '<table id="gp_galleries"><tr><td>';

		echo '<h3>'.$langmessage['visible_galleries'].'</h2>';

		echo '<div class="drag_galleries active_galleries">';

		foreach($this->galleries as $title => $info ){

			if( !$this->GalleryVisible($title,$info) ){
				continue;
			}

			$this->GalleryEditBox( $title, $info );
		}

		echo '<br class="gpclear"/>';
		echo '</div>';
		echo '</td><td>';

		echo '<h3>'.$langmessage['hidden_galleries'].'</h2>';

		echo '<div class="drag_galleries inactive_galleries">';
		if( count($this->not_visible) > 0 ){
			foreach($this->not_visible as $title => $info){
				$this->GalleryEditBox( $title, $info );
			}
		}

		echo '<br class="gpclear"/>';
		echo '</div>';

		echo '</td></tr></table>';

	}

	function GalleryEditBox( $title, $info ){
		if( is_array($info) ){
			$icon = $info['icon'];
		}else{
			$icon = $info;
		}

		if( empty($icon) ){
			$thumbPath = common::GetDir('/include/imgs/blank.gif');
		}elseif( strpos($icon,'/thumbnails/') === false ){
			$thumbPath = common::GetDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
		}else{
			$thumbPath = common::GetDir('/data/_uploaded'.$icon);
		}
		echo '<div class="draggable">';
		echo common::Link('Special_Galleries',htmlspecialchars($title),'cmd=drag&to=%s&title='.urlencode($title),'data-cmd="gpajax" class="dragdroplink nodisplay" ');
		echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" class="title" />';

		echo ' <img src="'.$thumbPath.'" alt="" class="icon"/>';
		echo '<div class="caption">';
		echo str_replace('_',' ',$title);
		echo '</div>';
		echo '</div>';
	}


	//page is hidden or deleted
	function GalleryVisible( $title, $info ){
		global $gp_index, $gp_menu;

		if( !isset($gp_index[$title]) ){
			unset($this->galleries[$title]);
			$this->title_removed = true;
			return false;
		}

		$visibility =& $info['visibility'];

		if( $visibility == 'show' ){
			return true;
		}
		$this->not_visible[$title] = $info;

		return false;
	}

	// save the galleries index file
	function PostSave(){
		if( !$this->title_removed ){
			return;
		}
		special_galleries::SaveIndex($this->galleries);
	}



	/**
	 * Get Gallery Index
	 *
	 * @static
	 */
	static function GetData(){
		global $dataDir;

		$galleries = array();
		$fileVersion = '0';
		$file = $dataDir.'/data/_site/galleries.php';
		if( file_exists($file) ){
			require($file);
			if( version_compare($fileVersion,'2.2','<=') ){
				special_galleries::UpdateData($galleries);
			}
		}

		return $galleries;
	}

	/**
	 * Add visibility settings according to old method for handling gallery visibility
	 * @static
	 */
	static function UpdateData(&$galleries){
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


	function GenerateOutput(){
		global $langmessage,$page;

		common::ShowingGallery();

		echo '<h2>';
		echo gpOutput::ReturnText('galleries');
		echo '</h2>';

		includeFile('admin/admin_tools.php');
		$wrap = admin_tools::CanEdit($page->gp_index);
		if( $wrap ){
			echo gpOutput::EditAreaLink($edit_index,'Special_Galleries',$langmessage['edit'],'cmd=edit');
			echo '<div class="editable_area cf" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}


		$image_text = gpOutput::ReturnText('image');
		$images_text = gpOutput::ReturnText('images');

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
					$count = $info['count'].' '.gpOutput::ReturnText('image');
				}elseif( $info['count'] > 1 ){
					$count = $info['count'].' '.gpOutput::ReturnText('images');
				}
			}else{
				$icon = $info;
			}

			if( empty($icon) ){
				continue;
			}


			$icon = rawurldecode($icon); //prevent double encoding
			if( strpos($icon,'/thumbnails/') === false ){
				$thumbPath = common::GetDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
			}else{
				$thumbPath = common::GetDir('/data/_uploaded'.$icon);
			}

			$label = common::GetLabel($title);
			$title_attr = ' title="'.common::GetBrowserTitle($title).'"';
			$label_img = ' <img src="'.$thumbPath.'" alt=""/>';

			$list .= '<li>'
					. common::Link($title,$label_img,'',$title_attr)
					. '<div>'
					. common::Link($title, $label,'',$title_attr)
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
	}

	/*

	Updating Functions

		The galleries.php file needs to  be updated when changes are made to pages with galleries
		When a page is...
			... renamed:				RenamedGallery()
			... edited:					UpdateGalleryInfo()
			... added:					do nothing, there won't be any images yet, wait till edited
			... deleted:				RemovedGallery()
			... restored from trash:	UpdateGalleryInfo() via RestoreFile() in admin_trash.php
	*/



	/**
	 * Extract information about the gallery from it's html: img_count, icon_src
	 *
	 * @static
	 */

	///data/_uploaded/image/thumbnails/image/gpeasy/admin_pages/01login.png.jpg
	///data/_uploaded/image/gpeasy/xamppsetup/01.png
	static function UpdateGalleryInfo($title,$file_sections){

		$content = '';
		$has_gallery = false;
		foreach($file_sections as $section_data){
			if( $section_data['type'] == 'gallery' ){
				$content .= $section_data['content'];
				$has_gallery = true;
			}
		}

		if( !$has_gallery ){
			special_galleries::RemovedGallery($title);
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


		$galleries = special_galleries::GetData();

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
		special_galleries::SaveIndex($galleries);
	}


	/**
	 * Handle the removal of a gallery page for admin_menu_tools.php
	 *
	 */
	static function RemovedGallery($title){

		$galleries = special_galleries::GetData();
		if( !isset($galleries[$title]) ){
			return;
		}

		unset($galleries[$title]);
		special_galleries::SaveIndex($galleries);
	}


	/**
	 * Handle the renaming of galleries for admin_menu_tools.php
	 *
	 * @static
	 *
	 */
	static function RenamedGallery($old_title,$new_title){

		$galleries = special_galleries::GetData();
		if( !isset($galleries[$old_title]) ){
			return;
		}

		if( gpFiles::ArrayInsert($old_title,$new_title,$galleries[$old_title],$galleries,0,1) ){
			special_galleries::SaveIndex($galleries);
		}
	}

	static function SaveIndex($galleries){
		global $dataDir;

		includeFile('admin/admin_tools.php');

		$file = $dataDir.'/data/_site/galleries.php';
		return gpFiles::SaveArray($file,'galleries',$galleries);
	}


}
