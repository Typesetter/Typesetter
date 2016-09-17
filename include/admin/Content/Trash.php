<?php

namespace gp\admin\Content;

defined('is_running') or die('Not an entry point...');

class Trash extends \gp\special\Base{

	public $trash_files = array();
	public $section_types = array();

	public function __construct($args){
		global $langmessage;

		parent::__construct($args);

		$this->page->head_js[]	= '/include/js/admin/trash.js';
		$this->trash_files		= self::TrashFiles();

		$cmd = \gp\tool::GetCommand();
		switch($cmd){

			case 'RestoreDeleted':
				$this->RestoreDeleted();
			break;

			case 'DeleteFromTrash':
				$this->DeleteFromTrash();
			break;
		}

		//view a trash file
		$parts = explode('/',$this->page->requested);
		if( count($parts) > 2 ){
			$title = $parts[2];
			if( isset($this->trash_files[$title]) ){
				$this->ViewTrashFile($title);
				return;
			}
		}

		//view all trash files
		$this->Trash();

	}


	/**
	 * Add/Remove titles from the trash.php index file
	 * Delete files in $remove_from_trash
	 *
	 */
	public static function ModTrashData($add_to_trash,$remove_from_trash){

		$trash_titles = self::TrashFiles();

		foreach((array)$remove_from_trash as $title_index => $info){
			unset($trash_titles[$title_index]);

			//only delete the files if they're in the trash directory
			if( strpos($info['rm_path'],'/data/_trash') !== false ){
				\gp\tool\Files::RmAll($info['rm_path']);
			}
		}

		return self::SaveTrashTitles($trash_titles);
	}



	/**
	 * Return a sorted array of files in the trash
	 *
	 */
	public static function TrashFiles(){
		global $dataDir, $gp_index, $config;

		$trash_titles 	= array();

		// pre 4.6, deleted page info was stored
		$trash_file = $dataDir.'/data/_site/trash.php';
		if( \gp\tool\Files::Exists($trash_file) ){
			$trash_titles = \gp\tool\Files::Get($trash_file,'trash_titles');
		}


		// get files associated existing titles
		$pages_dir		= $dataDir.'/data/_pages/';
		$pages_dir_len	= strlen($pages_dir);
		$existing		= array();
		foreach($gp_index as $title => $index){

			if( \gp\tool::SpecialOrAdmin($title) !== false ){
				continue;
			}


			$file = \gp\tool\Files::PageFile($title);
			$file = substr($file,$pages_dir_len);

			if( strpos($file,'/') ){
				$existing[] = dirname($file);
			}else{
				$existing[] = $file;
			}
		}


		// post 4.6, deleted pages are left in the data/_pages folder
		$files			= scandir($pages_dir);
		$files			= array_diff($files, array('.','..','index.html'));


		// add the new files to the list of $trash_titles
		$new_trash_files	= array_diff($files,$existing);
		$page_prefix		= substr($config['gpuniq'],0,7).'_';
		foreach($new_trash_files as $file){

			$info						= array();
			$info_file					= $dataDir.'/data/_pages/'.$file.'/deleted.php';
			if( \gp\tool\Files::Exists($info_file) ){
				$info					= \gp\tool\Files::Get($info_file,'deleted');
				$info['page_file']		= $dataDir.'/data/_pages/'.$file.'/page.php';
			}else{
				$info['page_file']		= $dataDir.'/data/_pages/'.$file;
				$info['time']			= filemtime($info['page_file']);
				$info['orphaned']		= true;
			}

			//get index
			if( strpos($file,$page_prefix) === 0 ){
				$info['index'] 			= substr($file,8); // remove page_prefix
			}

			$info['rm_path']			= $dataDir.'/data/_pages/'.$file;
			$trash_titles[$file]		= $info;
		}

		//make sure we have a title
		foreach($trash_titles as $trash_index => &$info){
			if( !isset($info['title']) ){
				$info['title'] = str_replace('_',' ',$trash_index);
			}
		}

		uasort($trash_titles,array('self','TitleSort'));

		return $trash_titles;
	}


	public static function TitleSort($a,$b){
		return strnatcasecmp($a['title'],$b['title']);
	}


	/*
	 * Save $trash_titles to the trash.php index file
	 *
	 */
	public static function SaveTrashTitles($trash_titles){
		global $dataDir;
		$index_file = $dataDir.'/data/_site/trash.php';
		return \gp\tool\Files::SaveData($index_file,'trash_titles',$trash_titles);
	}




	/**
	 * Get the $info array for $title for use with $gp_titles
	 *
	 */
	public static function GetInfo($trash_index){
		global $dataDir;
		static $trash_titles = false;

		if( $trash_titles === false ){
			$trash_titles = self::TrashFiles();
		}

		if( !array_key_exists($trash_index,$trash_titles) ){
			return false;
		}

		$title_info			= $trash_titles[$trash_index];


		//make sure we have a file or dir
		if( empty($title_info['rm_path']) ){

			if( empty($title_info['file']) ){
				$title_info['file']			= $trash_index.'.php';
			}
			$title_info['rm_path']			= $dataDir.'/data/_trash/'.$title_info['file'];
			$title_info['page_file']		= $dataDir.'/data/_trash/'.$title_info['file'];
		}



		//make sure we have a label
		if( empty($title_info['label']) ){
			$title_info['label']	= \gp\admin\Tools::LabelToSlug($trash_index);
		}

		//make sure we have a file_type
		if( empty($title_info['type']) ){
			$title_info['type']		= self::GetTypes($title_info['page_file']);
		}


		return $title_info;
	}



	/**
	 * Copy the php file in _pages to _trash for $title
	 *
	 */
	public static function MoveToTrash_File($title, $index, &$trash_data){
		global $dataDir, $gp_titles, $config;


		//get the file data
		$source_file			= \gp\tool\Files::PageFile($title);
		$source_dir				= dirname($source_file);
		$trash_file				= $source_dir.'/deleted.php';
		$file_sections			= \gp\tool\Files::Get($source_file,'file_sections');


		//create trash info file
		$trash_info				= $gp_titles[$index];
		$trash_info['title']	= $title;
		$trash_info['time']		= time();

		if( !\gp\tool\Files::SaveData($trash_file,'deleted',$trash_info) ){
			return false;
		}


		//update image information
		if( count($file_sections) ){
			includeFile('image.php');
			\gp_resized::SetIndex();
			foreach($file_sections as $section_data){
				if( isset($section_data['resized_imgs']) ){
					\gp\tool\Editing::ResizedImageUse($section_data['resized_imgs'],array());
				}
			}
		}

		return true;
	}


	/**
	 * Remove files from the trash by restoring them to $gp_titles and $gp_index
	 *
	 */
	public function RestoreDeleted(){
		global $langmessage,$gp_titles,$gp_index;

		if( empty($_POST['titles']) || !is_array($_POST['titles']) ){
			message($langmessage['OOPS'].' (No Titles)');
			return;
		}

		$titles = $_POST['titles'];
		self::RestoreTitles($titles);

		if( !$titles ){
			message($langmessage['OOPS'].' (R1)');
			return false;
		}

		if( !\gp\admin\Tools::SavePagesPHP(true) ){
			return false;
		}

		self::ModTrashData(null,$titles);

		$show_titles = array();
		foreach($titles as $trash_index => $info){
			$show_titles[] = \gp\tool::Link($info['title'],$info['title']);
			unset($this->trash_files[$trash_index]);
		}
		$title_string = implode(', ',$show_titles);

		$link		= \gp\tool::GetUrl('Admin/Menu');
		$message	= sprintf($langmessage['file_restored'],$title_string,$link);

		message($message);
	}



	/**
	 * Restore $titles and return array with menu information
	 * @param array $titles An array of titles to be restored. After completion, it will contain only the titles that were prepared successfully
	 * @return array A list of restored titles that can be used for menu insertion
	 *
	 */
	public static function RestoreTitles(&$titles){
		global $dataDir, $gp_index, $gp_titles, $config;

		$new_menu		= array();
		$restored		= array();
		foreach($titles as $trash_index){

			//get trash info about file
			$title_info = self::GetInfo($trash_index);
			if( $title_info === false ){
				continue;
			}

			$new_title = \gp\admin\Tools::CheckPostedNewPage($title_info['title'],$message);
			if( empty($new_title) ){
				continue;
			}


			//make sure the page_file exists
			if( !\gp\tool\Files::Exists($title_info['page_file']) ){
				continue;
			}


			//add to $gp_index before PageFile()
			if( isset($title_info['index']) ){
				$index					= $title_info['index'];
				$gp_index[$new_title]	= $index;
			}else{
				$index					= \gp\tool::NewFileIndex();
				$gp_index[$new_title]	= $index;
			}


			// move the trash file to the /_pages directory if needed
			$new_file = \gp\tool\Files::PageFile($new_title);
			if( !\gp\tool\Files::Exists($new_file) ){
				if( !\gp\tool\Files::Rename($title_info['page_file'],$new_file) ){
					unset($gp_index[$new_title]);
					continue;
				}
			}


			//add to $gp_titles
			$gp_titles[$index]				= array();
			$gp_titles[$index]['label']		= $title_info['label'];
			$gp_titles[$index]['type']		= $title_info['type'];

			$new_menu[$index]				= array();
			$restored[$trash_index]			= $title_info;

			self::RestoreFile($new_title, $new_file, $title_info);
		}

		$titles = $restored;

		return $new_menu;
	}

	/**
	 * Get the content of the file in the trash so we can restore file information
	 *  - resized images
	 *  - \gp\special\Galleries::UpdateGalleryInfo($title,$content)
	 *
	 */
	public static function RestoreFile($title,$file,$title_info){

		$file_sections = \gp\tool\Files::Get($file,'file_sections');

		// Restore resized images
		if( count($file_sections) ){
			includeFile('image.php');
			\gp_resized::SetIndex();
			foreach($file_sections as $section => $section_data){

				if( !isset($section_data['resized_imgs']) ){
					continue;
				}

				foreach($section_data['resized_imgs'] as $image_index => $sizes){
					if( !isset(\gp_resized::$index[$image_index]) ){
						continue;
					}
					$img = \gp_resized::$index[$image_index];
					foreach($sizes as $size){
						list($width,$height) = explode('x',$size);
						\gp\tool\Editing::CreateImage($img,$width,$height);
					}
				}
				\gp\tool\Editing::ResizedImageUse(array(),$section_data['resized_imgs']);
			}
			\gp_resized::SaveIndex();
		}


		// Restore Galleries
		if( strpos($title_info['type'],'gallery') !== false ){
			\gp\special\Galleries::UpdateGalleryInfo($title,$file_sections);
		}
	}



	/**
	 * Get the section types so we can set the $gp_titles and $meta_data variables correctly
	 * In future versions, fetching the $meta_data['file_type'] value will suffice
	 *
	 */
	public static function GetTypes($file){

		$types			= array();
		$file_sections	= \gp\tool\Files::Get($file,'file_sections');

		foreach($file_sections as $section){
			$types[] = $section['type'];
		}
		$types = array_unique($types);
		return implode(',',$types);
	}


	/**
	 * View all files in the trash
	 *
	 */
	public function Trash(){
		global $dataDir,$langmessage;

		$this->section_types			= \gp\tool\Output\Sections::GetTypes();

		echo '<h2>'.$langmessage['trash'].'</h2>';


		if( count($this->trash_files) == 0 ){
			echo '<ul><li>'.$langmessage['TRASH_IS_EMPTY'].'</li></ul>';
			return false;
		}

		echo '<form action="'.\gp\tool::GetUrl('Admin/Trash').'" method="post">';
		echo '<table class="bordered striped full_width">';

		ob_start();
		echo '<tr><th colspan="3">';
		echo '<input type="checkbox" name="" class="check_all"/>';
		echo '</th><th>';
		echo '<button type="submit" name="cmd" value="RestoreDeleted" class="gppost gpsubmit">'.$langmessage['restore'].'</button> ';
		echo '<button type="submit" name="cmd" value="DeleteFromTrash" class="gppost gpsubmit">'.$langmessage['delete'].'</button>';
		echo '</th></tr>';
		$heading = ob_get_clean();

		echo $heading;

		// non-orphaned
		$orphaned = array();
		foreach($this->trash_files as $trash_index => $info){
			if( isset($info['orphaned']) ){
				$orphaned[$trash_index] = $info;
			}else{
				$this->TrashRow($trash_index, $info);
			}
		}

		// orphaned files
		if( $orphaned ){
			echo '<tr><td colspan="3">';
			echo '<input type="checkbox" style="visibility:hidden"> &nbsp; ';
			echo count($orphaned).' Orphaned Files Found';
			echo '</td><td>';
			echo '<a class="nowrap" data-cmd="ViewOrphaned">'.$langmessage['View'].'</a> &nbsp; ';
			$q = array();
			$q['titles'] = array_keys($orphaned);
			$q = 'cmd=DeleteFromTrash&'.http_build_query($q);
			echo \gp\tool::Link('Admin/Trash',$langmessage['delete'],$q,array('data-cmd'=>'postlink'));
			echo '</td></tr>';

			foreach($orphaned as $trash_index => $info){
				$this->TrashRow($trash_index, $info);
			}
		}



		echo $heading;

		echo '</table>';
		echo '</form>';
	}

	public function TrashRow($trash_index, $info, $show_orphaned = false ){
		global $langmessage;

		$class = '';
		if( isset($info['orphaned']) ){
			$class = 'orphaned';
		}

		//title
		echo '<tr class="'.$class.'"><td>';
		echo '<label style="display:block;">';
		echo '<input type="checkbox" name="titles[]" value="'.htmlspecialchars($trash_index).'" />';
		echo ' &nbsp; ';

		if( isset($info['orphaned']) ){
			echo '(Orphaned) &nbsp; ';
		}

		echo \gp\tool::Link('Admin/Trash/'.$trash_index,str_replace('_',' ',$info['title']));
		echo '</label>';

		//time
		echo '</td><td>';

		if( !empty($info['time']) ){
			$elapsed = \gp\admin\Tools::Elapsed(time() - $info['time']);
			echo sprintf($langmessage['_ago'],$elapsed);
		}

		echo '</td><td>';
		if( isset($info['type']) ){
			$this->TitleTypes($info['type']);
		}

		echo '</td><td>';

		if( \gp\admin\Tools::CheckPostedNewPage($info['title'], $msg) ){
			echo \gp\tool::Link('Admin/Trash',$langmessage['restore'],'cmd=RestoreDeleted&titles[]='.rawurlencode($trash_index),array('data-cmd'=>'postlink'));
		}else{
			echo '<span>'.$langmessage['restore'].'</span>';
		}
		echo ' &nbsp; ';
		echo \gp\tool::Link('Admin/Trash',$langmessage['delete'],'cmd=DeleteFromTrash&titles[]='.rawurlencode($trash_index),array('data-cmd'=>'postlink'));

		echo '</td></tr>';
	}

	/**
	 * List section types
	 *
	 */
	public function TitleTypes($types){
		global $gp_titles;

		$types		= explode(',',$types);
		$types		= array_filter($types);
		$types		= array_unique($types);

		foreach($types as $i => $type){
			if( isset($this->section_types[$type]) && isset($this->section_types[$type]['label']) ){
				$types[$i] = $this->section_types[$type]['label'];
			}
		}

		echo implode(', ',$types);
	}

	/**
	 * Check and remove the requested files from the trash
	 *
	 */
	public function DeleteFromTrash(){
		global $dataDir,$langmessage;

		if( empty($_POST['titles']) || !is_array($_POST['titles']) ){
			message($langmessage['OOPS'].' (No Titles)');
			return;
		}

		$titles			= array();
		$incomplete		= false;

		foreach($_POST['titles'] as $trash_index){
			$title_info = self::GetInfo($trash_index);

			if( $title_info === false ){
				$incomplete = true;
				continue;
			}
			$titles[$trash_index] = $title_info;
		}

		if( !self::ModTrashData(null,$titles) ){
			return false;
		}


		//remove the data
		foreach($titles as $trash_index => $info){
			\gp\tool\Files::RmAll($info['rm_path']);
			unset($this->trash_files[$trash_index]);
		}


		if( $incomplete ){
			message($langmessage['delete_incomplete']);
		}
	}


	/**
	 * View the contents of a trash file
	 *
	 */
	public function ViewTrashFile($trash_index){
		global $dataDir, $langmessage, $trash_file;

		$title_info = self::GetInfo($trash_index);


		//delete / restore links
		echo '<div class="pull-right">';
		echo \gp\tool::Link('Admin/Trash',$langmessage['restore'],'cmd=RestoreDeleted&titles[]='.rawurlencode($trash_index),array('data-cmd'=>'cnreq','class'=>'gpsubmit'));
		echo ' &nbsp; ';
		echo \gp\tool::Link('Admin/Trash',$langmessage['delete'],'cmd=DeleteFromTrash&titles[]='.rawurlencode($trash_index),array('data-cmd'=>'cnreq','class'=>'gpsubmit'));
		echo '</div>';


		echo '<h2 class="hmargin">';
		echo \gp\tool::Link('Admin/Trash',$langmessage['trash']);
		echo ' &#187; ';
		echo htmlspecialchars($title_info['title']);
		echo '</h2>';
		echo '<hr>';


		//get file sections
		$file_sections		= \gp\tool\Files::Get($title_info['page_file'],'file_sections');

		if( $file_sections ){
			echo \gp\tool\Output\Sections::Render($file_sections,$title_info['title']);
		}else{
			echo '<p>This page no longer has any content</p>';
		}
	}

}

