<?php
defined('is_running') or die('Not an entry point...');

class admin_trash{

	function admin_trash(){
		global $langmessage;

		$cmd = common::GetCommand();
		switch($cmd){

			case $langmessage['restore']:
			case 'restore_one':
				$this->RestoreNew();
			break;

			case $langmessage['delete']:
			case 'delete_one':
				$this->DeleteFromTrash();
			break;

		}

		$this->Trash();

	}

	/**
	 * Make Sure the trash folder exists
	 * admin_trash::PrepFolder();
	 */
	static function PrepFolder(){
		global $dataDir;
		$trash_dir = $dataDir.'/data/_trash';
		gpFiles::CheckDir($trash_dir);

	}


	/**
	 * Add/Remove titles from the trash.php index file
	 * Delete files in $remove_from_trash
	 *
	 * @static
	 */
	static function ModTrashData($add_to_trash,$remove_from_trash){
		global $dataDir;

		$trash_titles = admin_trash::TrashFiles();

		//remove_from_trash
		foreach((array)$remove_from_trash as $title => $null){
			if( empty($trash_titles[$title]['file']) ){
				$trash_file = $dataDir.'/data/_trash/'.$title.'.php';
			}else{
				$trash_file = $dataDir.'/data/_trash/'.$trash_titles[$title]['file'];
			}
			if( file_exists($trash_file) ){
				unlink($trash_file);
			}

			if( isset($trash_titles[$title]) ){
				unset($trash_titles[$title]);
			}
		}

		//add_to_trash
		foreach((array)$add_to_trash as $title => $info){
			$trash_titles[$title]['label'] = $info['label'];
			$trash_titles[$title]['type'] = $info['type'];
			$trash_titles[$title]['file'] = $info['file'];
			$trash_titles[$title]['time'] = time();

		}

		return admin_trash::SaveTrashTitles($trash_titles);
	}



	/*
	 * Return a sorted array of files in the trash
	 * @static
	 */
	static function TrashFiles(){
		global $dataDir;
		$trash_dir = $dataDir.'/data/_site/trash.php';

		if( !file_exists($trash_dir) ){
			return admin_trash::GenerateTrashIndex();
		}

		$trash_titles = array();
		include($trash_dir);
		return $trash_titles;
	}


	/*
	 * Create the trash.php index file based on the /_trash folder contents
	 * @static
	 */
	static function GenerateTrashIndex(){
		global $dataDir;

		$trash_dir = $dataDir.'/data/_trash';

		$trash_files = gpFiles::ReadDir($trash_dir);
		natcasesort($trash_files);

		$trash_titles = array();
		foreach($trash_files as $file){

			$trash_titles[$file] = array();
			$trash_titles[$file]['label'] = admin_tools::LabelToSlug($file);
			$trash_titles[$file]['time'] = time();
		}

		admin_trash::SaveTrashTitles($trash_titles);

		return $trash_titles;
	}

	/*
	 * Save $trash_titles to the trash.php index file
	 * @static
	 */
	static function SaveTrashTitles($trash_titles){
		global $dataDir;
		$index_file = $dataDir.'/data/_site/trash.php';
		uksort($trash_titles,'strnatcasecmp');
		return gpFiles::SaveArray($index_file,'trash_titles',$trash_titles);
	}




	/**
	 * Get the $info array for $title for use with $gp_titles
	 * @static
	 */
	static function GetInfo($title){
		global $dataDir;
		static $trash_titles = false;
		if( $trash_titles === false ){
			$trash_titles = admin_trash::TrashFiles();
		}

		if( !isset($trash_titles[$title]) ){
			return false;
		}

		$title_info = $trash_titles[$title];

		//make sure we have a label
		if( empty($title_info['label']) ){
			$title_info['label'] = admin_tools::LabelToSlug($title);
		}

		//make sure we have a file_type
		if( empty($title_info['type']) ){
			$trash_file = $dataDir.'/data/_trash/'.$title.'.php';
			$title_info['type'] = admin_trash::GetTypes($trash_file);
		}

		//make sure we have a file name
		if( empty($title_info['file']) ){
			$title_info['file'] = $dataDir.'/data/_trash/'.$title.'.php';
		}

		return $title_info;
	}



	/**
	 * Copy the php file in _pages to _trash for $title
	 *
	 */
	static function MoveToTrash_File($title, $index, &$trash_data){
		global $dataDir, $gp_titles;

		$source_file = gpFiles::PageFile($title);
		$trash_file_name = sha1($title).'.php';
		$trash_file = $dataDir.'/data/_trash/'.$trash_file_name;

		$trash_data[$title] = $gp_titles[$index];
		$trash_data[$title]['file'] = $trash_file_name;

		if( !file_exists($source_file) ){
			return false;
		}

		//get the file data
		$file_sections = array();
		ob_start();
		include($source_file);
		ob_get_clean();


		if( file_exists($trash_file) ){
			if( !unlink($trash_file) ){
				return false;
			}
		}

		if( !copy($source_file,$trash_file) ){
			return false;
		}

		//update image information
		if( count($file_sections) ){
			includeFile('image.php');
			gp_resized::SetIndex();
			foreach($file_sections as $section_data){
				if( isset($section_data['resized_imgs']) ){
					gp_edit::ResizedImageUse($section_data['resized_imgs'],array());
				}
			}
		}


		return true;
	}


	/**
	 * Remove files from the trash by restoring them to $gp_titles and $gp_index
	 *
	 */
	function RestoreNew(){
		global $langmessage,$gp_titles,$gp_index;

		if( empty($_POST['title']) || !is_array($_POST['title']) ){
			message($langmessage['OOPS'].' (No Titles)');
			return;
		}

		$titles = $_POST['title'];
		admin_trash::RestoreTitles($titles);

		if( count($titles) == 0 ){
			message($langmessage['OOPS'].' (R1)');
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R4)');
			return false;
		}

		admin_trash::ModTrashData(null,$titles);

		$show_titles = array();
		foreach($titles as $title => $null){
			$show_titles[] = htmlspecialchars($title);
		}
		$title_string = implode(', ',$show_titles);

		$link = common::GetUrl('Admin_Menu');
		$message = sprintf($langmessage['file_restored'],$title_string,$link);
		message($message);
	}



	/**
	 * Restore $titles and return array with menu information
	 * @param array $titles An array of titles to be restored. After completion, it will contain only the titles that were prepared successfully
	 * @return array A list of restored titles that can be used for menu insertion
	 *
	 */
	static function RestoreTitles(&$titles){
		global $dataDir, $gp_index, $gp_titles;

		$new_menu = array();
		$new_titles = array();
		foreach($titles as $title => $empty){

			$new_title = admin_tools::CheckPostedNewPage($title,$message);
			if( !$new_title ){
				//message($message);
				continue;
			}

			//add to $gp_index first for PageFile()
			$index = common::NewFileIndex();
			$gp_index[$new_title] = $index;

			//get trash info about file
			$title_info = admin_trash::GetInfo($title);
			if( $title_info === false ){
				unset($gp_index[$new_title]);
				continue;
			}

			//make sure the trash file exists
			$trash_file = $dataDir.'/data/_trash/'.$title_info['file'];
			if( !file_exists($trash_file) ){
				unset($gp_index[$new_title]);
				continue;
			}

			//copy the trash file to the /_pages directory
			$new_file = gpFiles::PageFile($new_title);
			if( !copy($trash_file,$new_file) ){
				unset($gp_index[$new_title]);
				continue;
			}

			//add to $gp_titles
			$gp_titles[$index] = array();
			$gp_titles[$index]['label'] = $title_info['label'];
			$gp_titles[$index]['type'] = $title_info['type'];

			$new_menu[$index] = array();
			$new_titles[$new_title] = true;

			admin_trash::RestoreFile($new_title, $trash_file, $title_info);
		}

		$titles = $new_titles;

		return $new_menu;
	}

	/**
	 * Get the content of the file in the trash so we can restore file information
	 *  - resized images
	 *  - special_galleries::UpdateGalleryInfo($title,$content)
	 *
	 */
	static function RestoreFile($title,$file,$title_info){
		//get the file data
		$file_sections = array();
		ob_start();
		include($file);
		ob_get_clean();


		// Restore resized images
		if( count($file_sections) ){
			includeFile('image.php');
			gp_resized::SetIndex();
			foreach($file_sections as $section => $section_data){

				if( !isset($section_data['resized_imgs']) ){
					continue;
				}

				foreach($section_data['resized_imgs'] as $image_index => $sizes){
					if( !isset(gp_resized::$index[$image_index]) ){
						continue;
					}
					$img = gp_resized::$index[$image_index];
					foreach($sizes as $size){
						list($width,$height) = explode('x',$size);
						gp_edit::CreateImage($img,$width,$height);
					}
				}
				gp_edit::ResizedImageUse(array(),$section_data['resized_imgs']);
			}
			gp_resized::SaveIndex();
		}


		// Restore Galleries
		if( strpos($title_info['type'],'gallery') !== false ){
			includeFile('special/special_galleries.php');
			special_galleries::UpdateGalleryInfo($title,$file_sections);
		}
	}



	/**
	 * Get the section types so we can set the $gp_titles and $meta_data variables correctly
	 * In future versions, fetching the $meta_data['file_type'] value will suffice
	 *
	 * @static
	 */
	static function GetTypes($file){

		$types = array();
		$file_sections = array();
		require($file);
		foreach($file_sections as $section){
			$types[] = $section['type'];
		}
		$types = array_unique($types);
		return implode(',',$types);
	}


	function Trash(){
		global $dataDir,$langmessage;


		echo '<h2>'.$langmessage['trash'].'</h2>';

		$trashtitles = admin_trash::TrashFiles();

		if( count($trashtitles) == 0 ){
			echo '<ul><li>'.$langmessage['TRASH_IS_EMPTY'].'</li></ul>';
			return false;
		}

		echo '<form action="'.common::GetUrl('Admin_Trash').'" method="post">';
		echo '<table class="bordered">';

		echo '<tr>';
		echo '<th><input type="checkbox" name="" class="check_all"/></th>';
		echo '<th>';
		echo '<input type="submit" name="cmd" value="'.$langmessage['restore'].'" class="gppost gpsubmit" />';
		echo ' &nbsp; ';
		echo '<input type="submit" name="cmd" value="'.$langmessage['delete'].'" class="gppost gpsubmit" />';
		echo '</th>';
		echo '</tr>';

		$i = 0;
		foreach($trashtitles as $title => $info){
			echo '<tr class="'.($i++ % 2 ? 'even' : '').'">';
			echo '<td>';
			echo '<label style="display:block;">';
			echo '<input type="checkbox" name="title['.htmlspecialchars($title).']" value="1" />';
			echo ' &nbsp; ';
			echo htmlspecialchars(str_replace('_',' ',$title));
			echo '</label>';
			echo '</td>';
			echo '<td>';

			echo common::Link('Admin_Trash',$langmessage['restore'],'cmd=restore_one&title['.rawurlencode($title).']=1',array('data-cmd'=>'postlink'));
			echo ' &nbsp; ';
			echo common::Link('Admin_Trash',$langmessage['delete'],'cmd=delete_one&title['.rawurlencode($title).']=1',array('data-cmd'=>'postlink'));

			echo '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th><input type="checkbox" name="" class="check_all"/></th>';
		echo '<th>';
		echo '<input type="submit" name="cmd" value="'.$langmessage['restore'].'" class="gppost gpsubmit" />';
		echo ' &nbsp; ';
		echo '<input type="submit" name="cmd" value="'.$langmessage['delete'].'" class="gppost gpsubmit" />';
		echo '</th>';
		echo '</tr>';


		echo '</table>';
		echo '</form>';
	}


	/**
	 * Check and remove the requested files from the trash
	 *
	 */
	function DeleteFromTrash(){
		global $dataDir,$langmessage;

		if( empty($_POST['title']) || !is_array($_POST['title']) ){
			message($langmessage['OOPS'].' (No Titles)');
			return;
		}

		$titles = array();
		$not_deleted = array();
		foreach($_POST['title'] as $title => $null){
			$title_info = admin_trash::GetInfo($title);
			if( $title_info === false ){
				$not_deleted[] = $title;
				continue;
			}
			$titles[$title] = true;
		}

		admin_trash::ModTrashData(null,$titles);

		if( count($not_deleted) > 0 ){
			message($langmessage['delete_incomplete'].': '.implode(', ',$not_deleted));
		}
	}

}

