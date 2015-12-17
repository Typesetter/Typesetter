<?php
defined('is_running') or die('Not an entry point...');

includeFile('special/special_galleries.php');

class admin_galleries extends special_galleries{

	function __construct(){
		$this->galleries = special_galleries::GetData();


		$cmd = common::GetCommand();
		switch($cmd){

			case 'newdrag':
				$this->NewDrag();
			return;
		}

		$this->EditGalleries();
	}

	function EditGalleries(){
		global $page, $langmessage;

		$page->head_js[] = '/include/js/special_galleries.js';
		$page->css_admin[] = '/include/css/edit_gallery.css';



		echo '<h2>';
		echo common::Link('Special_Galleries',gpOutput::ReturnText('galleries'));
		echo ' &#187; '.$langmessage['administration'];
		echo '</h2>';

		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC2'];
		echo '</p>';

		$this->EditableArea();

	}



	function EditableArea(){
		global $gp_titles, $gp_index, $langmessage;


		$not_visible = array();

		echo '<table id="gp_galleries">';

		echo '<tr><td>';
		echo '<h3>'.$langmessage['visible_galleries'].'</h3>';
		echo '</td><td>';
		echo '<h3>'.$langmessage['hidden_galleries'].'</h3>';
		echo '</td></tr>';

		echo '<tr><td class="drag_gal_td">';
		echo '<div class="drag_galleries active_galleries cf">';

		foreach($this->galleries as $title => $info ){

			if( !$this->GalleryVisible($title,$info) ){
				continue;
			}

			$this->GalleryEditBox( $title, $info );
		}


		echo '</div>';
		echo '</td><td class="drag_gal_td">';


		echo '<div class="drag_galleries inactive_galleries cf">';
		if( count($this->not_visible) > 0 ){
			foreach($this->not_visible as $title => $info){
				$this->GalleryEditBox( $title, $info );
			}
		}


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


	function NewDrag(){
		global $page, $langmessage, $gp_index, $gp_titles;
		$page->ajaxReplace = array();

		//get the title of the gallery that was moved
		$dragging = $_POST['title'];
		if( !isset($this->galleries[$dragging]) ){
			message($langmessage['OOPS'].' (Title not in gallery list)');
			return false;
		}

		$index	= $gp_index[$dragging];
		$info	= $this->galleries[$dragging];
		unset($this->galleries[$dragging]);


		//set visibility
		if( isset($_POST['active']) ){
			$info['visibility'] = 'show';
			unset($gp_titles[$index]['vis']);
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

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (Not Saved)');
			return false;
		}



	}



}
