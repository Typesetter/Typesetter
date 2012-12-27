<?php
defined('is_running') or die('Not an entry point...');

class admin_extra{

	var $folder;
	var $areas = array();

	function admin_extra(){
		global $langmessage, $dataDir;

		$this->folder = $dataDir.'/data/_extra';
		$this->areas = gpFiles::ReadDir($this->folder);
		asort($this->areas);

		$cmd = common::GetCommand();

		$show = true;
		switch($cmd){

			case 'delete';
				$this->DeleteArea();
			break;

			case 'save':
				if( $this->SaveExtra() ){
					break;
				}
			case 'edit':
				if( $this->EditExtra() ){
					$show = false;
				}
			break;

			case 'rawcontent':
				$this->RawContent();
			break;

			case 'inlineedit':
				$this->InlineEdit();
			die();

		}

		if( $show ){
			$this->ShowExtras();
		}
	}


	function InlineEdit(){

		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			echo 'false';
			return false;
		}

		$data = array();
		$data['type'] = 'text';
		$data['content'] = '';

		$file = $this->folder.'/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$data['content'] = ob_get_clean();
		}

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($data);

	}

	/**
	 * Send the content of the extra area to the client in a json response
	 *
	 */
	function RawContent(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();


		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$file = $this->folder.'/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$content = ob_get_clean();
		}

		$page->ajaxReplace[] = array('rawcontent','',$content);
	}

	/**
	 * Delete an extra content area
	 *
	 */
	function DeleteArea(){
		global $langmessage;

		$title =& $_POST['file'];
		$file = $this->ExtraExists($title);
		if( !$file ){
			message($langmessage['OOPS']);
			return;
		}

		if( unlink($file) ){
			message($langmessage['SAVED']);
			unset($this->areas[$title]);
		}else{
			message($langmessage['OOPS']);
		}
	}

	/**
	 * Check to see if the extra area exists
	 *
	 */
	function ExtraExists($file){
		global $dataDir;

		if( !isset($this->areas[$file]) ){
			return false;
		}

		return $this->folder.'/'.$file.'.php';
	}


	function ShowExtras(){
		global $langmessage;

		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered full_width">';
		echo '<tr>';
			echo '<th>';
			echo 'Area';
			echo '</th>';
			echo '<th>';
			echo '&nbsp;';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';

		foreach($this->areas as $file){
			$extraName = $file;
			echo '<tr>';
				echo '<td style="white-space:nowrap">';
				echo str_replace('_',' ',$extraName);
				echo '</td>';
				echo '<td>"<span class="admin_note">';
				$full_path = $this->folder.'/'.$file.'.php';
				$contents = file_get_contents($full_path);
				$contents = strip_tags($contents);
				echo substr($contents,0,50);
				echo '</span>..."</td>';
				echo '<td style="white-space:nowrap">';
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo ' &nbsp; ';

				$title = sprintf($langmessage['generic_delete_confirm'],htmlspecialchars($file));
				echo common::Link('Admin_Extra',$langmessage['delete'],'cmd=delete&file='.$file,array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));
				echo '</td>';
				echo '</tr>';
		}

		echo '</table>';

		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Extra').'" method="post">';
		echo '<input type="hidden" name="cmd" value="edit" />';
		echo '<input type="text" name="file" value="" size="15" class="gpinput"/> ';
		echo '<input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gpsubmit"/>';
		echo '</form>';
		echo '</p>';


	}


	function EditExtra(){
		global $langmessage;

		$title = gp_edit::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$file = $this->folder.'/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$content = ob_get_clean();
		}

		echo '<form action="'.common::GetUrl('Admin_Extra','file='.$title).'" method="post">';
		echo '<h2>';
		echo common::Link('Admin_Extra',$langmessage['theme_content']);
		echo ' &gt; '.str_replace('_',' ',$title).'</h2>';
		echo '<input type="hidden" name="cmd" value="save" />';

		gp_edit::UseCK($content);

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</form>';
		return true;
	}

	/**
	 * Save the posted content for an extra content area
	 *
	 */
	function SaveExtra(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();


		if( empty($_REQUEST['file']) ){
			message($langmessage['OOPS']);
			return false;
		}

		$title = gp_edit::CleanTitle($_REQUEST['file']);
		$file = $this->folder.'/'.$title.'.php';
		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);


		if( !gpFiles::SaveFile($file,$text) ){
			message($langmessage['OOPS']);
			$this->EditExtra();
			return false;
		}

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		$this->areas[$title] = $title;
		return true;
	}
}
