<?php
defined('is_running') or die('Not an entry point...');

class admin_trash{

	function __construct(){
		global $langmessage;

		$cmd = common::GetCommand();
		switch($cmd){

			case 'RestoreDeleted':
				$this->RestoreDeleted();
			break;

			case 'DeleteFromTrash':
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
		foreach((array)$remove_from_trash as $title_index => $info){

			gpFiles::RmAll($info['rm_path']);

			if( isset($trash_titles[$title_index]) ){
				unset($trash_titles[$title_index]);
			}
		}

		//add_to_trash
		foreach((array)$add_to_trash as $title => $info){
			$trash_titles[$title]['label']	= $info['label'];
			$trash_titles[$title]['type']	= $info['type'];
			//$trash_titles[$title]['file']	= $info['file'];
			$trash_titles[$title]['title']	= $info['title'];
			$trash_titles[$title]['time']	= time();

		}

		return admin_trash::SaveTrashTitles($trash_titles);
	}



	/**
	 * Return a sorted array of files in the trash
	 * @static
	 *
	 */
	static function TrashFiles(){
		global $dataDir;

		$trash_file = $dataDir.'/data/_site/trash.php';

		if( !gpFiles::Exists($trash_file) ){
			return admin_trash::GenerateTrashIndex();
		}

		return gpFiles::Get($trash_file,'trash_titles');
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
		return gpFiles::SaveData($index_file,'trash_titles',$trash_titles);
	}




	/**
	 * Get the $info array for $title for use with $gp_titles
	 * @static
	 */
	static function GetInfo($trash_index){
		global $dataDir;
		static $trash_titles = false;

		if( $trash_titles === false ){
			$trash_titles = admin_trash::TrashFiles();
		}

		if( !array_key_exists($trash_index,$trash_titles) ){
			return false;
		}

		$title_info			= $trash_titles[$trash_index];
		$trash_dir			= $dataDir.'/data/_trash/'.$trash_index;

		//make sure we have a label
		if( empty($title_info['label']) ){
			$title_info['label']	= admin_tools::LabelToSlug($trash_index);
		}

		//make sure we have a file_type
		if( empty($title_info['type']) ){
			$trash_file				= $dataDir.'/data/_trash/'.$trash_index.'.php';
			$title_info['type']		= admin_trash::GetTypes($trash_file);
		}

		//make sure we have a file or dir
		if( gpFiles::Exists($trash_dir) ){
			$title_info['rm_path']		= $trash_dir;

		}else{

			if( empty($title_info['file']) ){
				$title_info['file']		= $trash_index.'.php';
			}
			$title_info['rm_path']		= $dataDir.'/data/_trash/'.$title_info['file'];
		}


		//make sure we have a title
		if( !isset($title_info['title']) ){
			$title_info['title'] = $trash_index;
		}


		return $title_info;
	}



	/**
	 * Copy the php file in _pages to _trash for $title
	 *
	 */
	static function MoveToTrash_File($title, $index, &$trash_data){
		global $dataDir, $gp_titles;


		//get a unique index
		$trash_index		= sha1($title);
		$num_index			= preg_replace('#[^0-9]#','',$trash_index);
		do{
			$trash_index	= sha1($num_index);
			$trash_dir		= $dataDir.'/data/_trash/'.$trash_index;
			$old_file		= $dataDir.'/data/_trash/'.$trash_index.'.php';
			$num_index++;

		}while( is_numeric($trash_index) || gpFiles::Exists($trash_dir) || gpFiles::Exists($old_file) );


		$trash_file								= $trash_dir.'/page.php';
		$trash_data[$trash_index]				= $gp_titles[$index];
		$trash_data[$trash_index]['title']		= $title;


		//get the file data
		$source_file		= gpFiles::PageFile($title);
		$file_sections		= gpFiles::Get($source_file,'file_sections');

		if( !$file_sections ){
			return false;
		}

		if( !gpFiles::CheckDir($trash_dir) ){
			return false;
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
	function RestoreDeleted(){
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
		foreach($titles as $trash_index => $info){
			$show_titles[] = common::Link($info['title'],$info['title']);
		}
		$title_string = implode(', ',$show_titles);

		$link		= common::GetUrl('Admin_Menu');
		$message	= sprintf($langmessage['file_restored'],$title_string,$link);

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

		$new_menu		= array();
		$restored		= array();
		foreach($titles as $trash_index => $empty){

			//get trash info about file
			$title_info = admin_trash::GetInfo($trash_index);
			if( $title_info === false ){
				continue;
			}

			$new_title = admin_tools::CheckPostedNewPage($title_info['title'],$message);
			if( !$new_title ){
				continue;
			}


			//add to $gp_index first for PageFile()
			$index					= common::NewFileIndex();
			$gp_index[$new_title]	= $index;


			//make sure the trash file exists
			if( isset($title_info['file']) ){
				$trash_file = $dataDir.'/data/_trash/'.$title_info['file'];
			}else{
				$trash_file = $dataDir.'/data/_trash/'.$trash_index.'/page.php';
			}


			if( !gpFiles::Exists($trash_file) ){
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
			$gp_titles[$index]				= array();
			$gp_titles[$index]['label']		= $title_info['label'];
			$gp_titles[$index]['type']		= $title_info['type'];

			$new_menu[$index]				= array();
			$restored[$trash_index]			= $title_info;

			admin_trash::RestoreFile($new_title, $trash_file, $title_info);
		}

		$titles = $restored;

		return $new_menu;
	}

	/**
	 * Get the content of the file in the trash so we can restore file information
	 *  - resized images
	 *  - special_galleries::UpdateGalleryInfo($title,$content)
	 *
	 */
	static function RestoreFile($title,$file,$title_info){

		$file_sections = gpFiles::Get($file,'file_sections');

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

		$types			= array();
		$file_sections	= gpFiles::Get($file,'file_sections');

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
		echo '<table class="bordered striped">';

		ob_start();
		echo '<tr><th colspan="2">';
		echo '<input type="checkbox" name="" class="check_all"/>';
		echo '</th><th>';
		echo '<button type="submit" name="cmd" value="RestoreDeleted" class="gppost gpsubmit">'.$langmessage['restore'].'</button>';
		echo ' &nbsp; ';
		echo '<button type="submit" name="cmd" value="DeleteFromTrash" class="gppost gpsubmit">'.$langmessage['delete'].'</button>';
		echo '</th></tr>';
		$heading = ob_get_clean();

		echo $heading;

		$i = 0;
		foreach($trashtitles as $trash_index => $info){

			if( isset($info['title']) ){
				$title = $info['title'];
			}else{
				$title = $info['title'];
			}

			echo '<tr><td>';
			echo '<label style="display:block;">';
			echo '<input type="checkbox" name="title['.htmlspecialchars($trash_index).']" value="1" />';
			echo ' &nbsp; ';
			echo htmlspecialchars(str_replace('_',' ',$title));
			echo '</label>';
			echo '</td><td>';

			echo admin_tools::Elapsed(time() - $info['time']).' ago';

			echo '</td><td>';

			echo common::Link('Admin_Trash',$langmessage['restore'],'cmd=RestoreDeleted&title['.rawurlencode($trash_index).']=1',array('data-cmd'=>'postlink'));
			echo ' &nbsp; ';
			echo common::Link('Admin_Trash',$langmessage['delete'],'cmd=DeleteFromTrash&title['.rawurlencode($trash_index).']=1',array('data-cmd'=>'postlink'));

			echo '</td></tr>';
		}

		echo $heading;

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

		$titles			= array();
		$not_deleted	= array();
		$incomplete		= false;

		foreach($_POST['title'] as $trash_index => $null){
			$title_info = admin_trash::GetInfo($trash_index);

			if( $title_info === false ){
				$incomplete = true;
				continue;
			}
			$titles[$trash_index] = $title_info;
		}


		if( !admin_trash::ModTrashData(null,$titles) ){
			return false;
		}

		//remove the data
		foreach($titles as $trash_index => $info){
			gpFiles::RmAll($info['rm_path']);
		}


		if( $incomplete ){
			message($langmessage['delete_incomplete']);
		}
	}

}

