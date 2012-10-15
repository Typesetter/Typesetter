<?php
defined('is_running') or die('Not an entry point...');


includeFile('tool/Images.php');
includeFile('image.php');

class admin_uploaded{

	var $baseDir;
	var $subdir = false;
	var $thumbFolder;
	var $isThumbDir = false;
	var	$imgTypes;
	var $errorMessages = array();


	function elFinder(){
		global $page, $GP_INLINE_VARS;

		$GP_INLINE_VARS['admin_resizable'] = false;

		$page->head .= "\n".'<link rel="stylesheet" type="text/css" media="screen" href="'.common::GetDir('/include/thirdparty/elfinder/css/elfinder.min.css').'">';
		$page->head .= "\n".'<link rel="stylesheet" type="text/css" media="screen" href="'.common::GetDir('/include/thirdparty/elfinder/style.css').'">';

		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/thirdparty/elfinder/js/elfinder.full.js').'"></script>';
		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/thirdparty/elfinder/config.js').'"></script>';

		echo '<div id="elfinder"></div>';

		common::LoadComponents('selectable,draggable,droppable,resizable,dialog,slider,button');

		$this->elFinderPrep();
	}

	function elFinderPrep(){
		global $page, $gpAdmin;

		//$page->head .= '<script type="text/javascript" src="js/i18n/elfinder.ru.js"></script>';

		$el_opts['url'] = common::GetUrl('Admin_Finder');
		$el_opts['lang'] = 'en';
		$el_opts['width'] = $gpAdmin['gpui_pw'];
		if( $gpAdmin['gpui_ph'] > 0 ){
			$el_opts['height'] = $gpAdmin['gpui_ph'];
		}

		$page->head_script .= "\n".'var elfinder_opts = '.json_encode($el_opts).';';
	}


	function admin_uploaded(){
		$file_cmd = common::GetCommand('file_cmd');
		if( !empty($file_cmd) || (isset($_REQUEST['show']) && $_REQUEST['show'] == 'inline') ){
			$this->do_admin_uploaded($file_cmd);
		}else{
			$this->elFinder();
		}
	}

	function do_admin_uploaded($file_cmd){
		global $page;


		$this->Init();
		$page->ajaxReplace = array();

		switch($file_cmd){
			case 'delete':
				$this->DeleteConfirmed();
			return;

			case 'inline_upload':
				$this->InlineUpload();
			//dies
		}
	}

	function Init(){
		global $langmessage, $dataDir,$page, $upload_extensions_allow, $upload_extensions_deny;

		$this->baseDir = $dataDir.'/data/_uploaded';
		$this->thumbFolder = $dataDir.'/data/_uploaded/image/thumbnails';
		$this->currentDir = $this->baseDir;
		$page->label = $langmessage['uploaded_files'];


		$this->AllowedExtensions = array('7z', 'aiff', 'asf', 'avi', 'bmp', 'bz', 'csv', 'doc', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip');
		if( is_array($upload_extensions_allow) ){
			$this->AllowedExtensions = array_merge($this->AllowedExtensions,$upload_extensions_allow);
		}
		if( is_array($upload_extensions_deny) ){
			$this->AllowedExtensions = array_diff($this->AllowedExtensions,$upload_extensions_deny);
		}
		$this->imgTypes = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1);


		//get the current path
		$parts = str_replace( array('\\','//'),array('/','/'),$page->title);
		$parts = trim($parts,'/');
		$parts = explode('/',$parts);
		array_shift($parts);
		if( count($parts) > 0 ){
			$this->subdir = '/'.implode('/',$parts);
			$this->subdir = gp_edit::CleanArg($this->subdir);
		}
		if( !empty($_REQUEST['dir']) ){
			$this->subdir .= gp_edit::CleanArg($_REQUEST['dir']);
		}
		$this->subdir = str_replace( array('\\','//'),array('/','/'),$this->subdir);

		if( $this->subdir == '/' ){
			$this->subdir = false;
		}else{
			$this->currentDir .= $this->subdir;
		}

		//prompt to create the requested subdirectory
		if( !file_exists($this->currentDir) ){
			gpFiles::CheckDir($this->currentDir);
		}


		//is in thumbnail directory?
		if( strpos($this->currentDir,$this->thumbFolder) !== false ){
			$this->isThumbDir = true;
		}
		$this->currentDir_Thumb = $this->thumbFolder.$this->subdir;

	}


	function ReadableMax(){
		$value = ini_get('upload_max_filesize');

		if( empty($value) ){
			return '2 Megabytes';//php default
		}
		return $value;
	}


	function Max_File_Size(){
		$max = admin_uploaded::getByteValue();
		if( $max !== false ){
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max.'" />';
		}
	}

	function getByteValue($value=false){

		if( $value === false ){
			$value = ini_get('upload_max_filesize');
		}

		if( empty($value) ){
			return false;
			//$value = '2M';
		}

		if( is_numeric($value) ){
			return (int)$value;
		}


		$lastChar = $value{strlen($value)-1};
		$num = (int)substr($value,0,-1);

		switch(strtolower($lastChar)){

			case 'g':
				$num *= 1024;
			case 'm':
				$num *= 1024;
			case 'k':
				$num *= 1024;
			break;
		}
		return $num;

	}

	/**
	 * Upload one image
	 *
	 */
	function InlineUpload(){

		if( count($_FILES['userfiles']['name']) != 1 ){
			$this->InlineResponse('failed','Empty Array');
		}

		$name = $_FILES['userfiles']['name'][0];
		if( empty($name) ){
			$this->InlineResponse('failed','Empty Name');
		}

		$uploaded = $this->UploadFile(0);
		$this->CleanTemporary();
		if( $uploaded === false ){
			reset($this->errorMessages);
			$this->InlineResponse('failed',current($this->errorMessages));
		}
		gpPlugin::Action('FileUploaded',$uploaded);

		$output =& $_POST['output'];
		switch($output){
			case 'gallery';
				$return_content = admin_uploaded::ShowFile_Gallery($this->subdir,$uploaded,$this->isThumbDir);
			break;

			default:
				$this->InlineResponse('deprecated','deprecated');
			break;
		}



		if( $return_content === false ){
			$this->InlineResponse('notimage','');
		}else{
			$this->InlineResponse('success',$return_content);
		}

	}

	/**
	 * Output a list a images in a director for use in inline editing
	 * @static
	 */
	function InlineList($dir_piece,$add_all_images = true){
		global $page,$langmessage,$dataDir;
		$page->ajaxReplace = array();


		$dir_piece = common::WinPath($dir_piece);
		$dir = $dataDir.'/data/_uploaded'.$dir_piece;

		$prev_piece = false;

		while( ($dir_piece != '/') && !file_exists($dir) ){
			$prev_piece = $dir_piece;
			$dir = dirname($dir);
			$dir_piece = dirname($dir_piece);
		}

		//new directory?
		if( $prev_piece ){
			$prev_piece = gp_edit::CleanArg($prev_piece);
			$dir_piece = $prev_piece;
			$dir = $dataDir.'/data/_uploaded'.$prev_piece;

			if( !gpFiles::CheckDir($dir) ){
				message($langmessage['OOPS']);
				$dir = dirname($dir);
				$dir_piece = dirname($prev_piece);
			}
		}


		ob_start();
		$isThumbDir = false;
		$thumbFolder = $dataDir.'/data/_uploaded/image/thumbnails';

		if( strpos($dir,$thumbFolder) !== false ){
			$isThumbDir = true;
		}


		$folders = $files = array();
		$allFiles = gpFiles::ReadFolderAndFiles($dir);
		list($folders,$files) = $allFiles;


		//available images
		$avail_imgs = '<div id="gp_gallery_avail_imgs">';
		$image_count = 0;
		foreach($files as $file){
			$img = admin_uploaded::ShowFile_Gallery($dir_piece,$file,$isThumbDir);
			if( $img ){
				$avail_imgs .= $img;
				$image_count++;
			}
		}
		$avail_imgs .= '</div>';


		//folder select
		echo '<div class="option_area">';
		echo '<div class="gp_edit_select">';
		echo '<a class="gp_gallery_folder ckeditor_control" name="gp_show_select"><span class="folder"></span>';
		if( strlen($dir_piece) > 23 ){
			echo '...'.substr($dir_piece,-20);
		}else{
			echo $dir_piece;
		}
		echo '</a>';

		echo '<div class="gp_edit_select_options">';
		if( $dir_piece != '/' ){
			$temp = dirname($dir_piece);
			echo '<a href="?cmd=new_dir&dir='.rawurlencode($dir_piece).'" class="gp_gallery_folder" name="gpabox"><span class="add"></span>'.$langmessage['create_dir'].'</a>';
			echo '<a class="gp_gallery_folder" name="gp_gallery_folder" rel="'.htmlspecialchars($temp).'"><span class="folder"></span>../</a>';
		}

		foreach($folders as $folder){
			if( $dir_piece == '/' ){
				$new_dir = '/'.$folder;
			}else{
				$new_dir = $dir_piece.'/'.$folder;
			}
			echo '<a class="gp_gallery_folder" name="gp_gallery_folder" rel="'.htmlspecialchars($new_dir).'"><span class="folder"></span>'.$folder.'</a>';
		}
		echo '</div>';
		echo '</div>';


		//add all images
		if( $add_all_images && $image_count > 0 ){
			echo '<a name="gp_gallery_add_all" class="ckeditor_control half_width">'.$langmessage['Add All Images'].'</a>';
		}

		if( $dir_piece != '/' ){

			echo '<form action="'.common::GetUrl('Admin_Uploaded').'" method="post"  enctype="multipart/form-data" class="gp_upload_form" id="gp_upload_form">';
			admin_uploaded::Max_File_Size();
			echo '<a class="ckeditor_control half_width">'.$langmessage['upload_files'].'</a>';
			echo '<div class="gp_object_wrapper">';
			echo '<input type="file" name="userfiles[]" class="file" />';

			echo '<input type="hidden" name="file_cmd" value="inline_upload" />';
			echo '<input type="hidden" name="output" value="gallery" />';
			echo '<input type="hidden" name="dir" value="'.$dir_piece.'" />';
			echo '</div>';
			echo '</form>';
		}

		echo '</div>';

		echo $avail_imgs;

		$content = ob_get_clean();

		$page->ajaxReplace[] = array('inner','#gp_image_area',$content);
		$page->ajaxReplace[] = array('gp_gallery_images','',''); //tell the script the images have been loaded
	}

	function InlineImageList($list){


	}

	/**
	 * @static
	 */
	function ShowFile_Gallery($dir_piece,$file,$isThumbDir){
		global $langmessage, $dataDir;

		if( !admin_uploaded::IsImg($file) ){
			return false;
		}

		//for gallery editing
		$rel_path = '/data/_uploaded'.$dir_piece.'/'.$file;
		$id = self::ImageId($rel_path);
		$file_url = common::GetDir($rel_path);
		$full_path = $dataDir.$rel_path;

		//thumbnail
		if( $isThumbDir ){
			$thumb = ' <img src="'.$file_url.'" alt="" />';
		}else{
			$thumb = ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$dir_piece.'/'.$file.'.jpg').'" alt="" />';
		}

		//get size
		$src_img = thumbnail::getSrcImg($full_path);
		$size = '';
		if( $src_img ){
			$size = ' data-width="'.imagesx($src_img).'" data-height="'.imagesy($src_img).'"';
		}

		$query_string = 'file_cmd=delete&show=inline&file='.urlencode($file);

		return '<span class="expand_child" id="'.$id.'">'
				. '<a href="'.$file_url.'" name="gp_gallery_add" rel="'.$file_url.'" '.$size.'>'
				. $thumb
				. '</a>'
				. common::Link('Admin_Uploaded'.$dir_piece,'',$query_string,' class="delete gpconfirm" name="gpajax" title="'.$langmessage['delete_confirm'].'"','delete')
				. '</span>';
	}

	static function ImageId($path){
		$encoded = base64_encode($path);
		$encoded = rtrim($encoded, '=');
		return 'gp_image_'.strtr($encoded, '+/=', '-_.');
	}




	function InlineResponse($status,$message){
		echo '<div>';
		echo '<textarea class="status">';
		echo htmlspecialchars($status);
		echo '</textarea>';
		echo '<textarea class="message">';
		echo htmlspecialchars($message);
		echo '</textarea>';
		echo '</div>';
		die();
	}

	function UploadFile($key){
		global $langmessage,$config;

		$fName = $_FILES['userfiles']['name'][$key];

		switch( (int)$_FILES['userfiles']['error'][$key]){

			case UPLOAD_ERR_OK:
			break;

			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_INI_SIZE:
				$this->errorMessages[] = sprintf($langmessage['upload_error_size'],$this->ReadableMax() );
			return false;

			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_PARTIAL:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR_PARTIAL'], $fName);
			return false;

			case UPLOAD_ERR_NO_TMP_DIR:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (1)', $fName);
				//trigger_error('Missing a temporary folder for file uploads.');
			return false;

			case UPLOAD_ERR_CANT_WRITE:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (2)', $fName);
				//trigger_error('PHP couldn\'t write to the temporary directory: '.$fName);
			return false;

			case UPLOAD_ERR_EXTENSION:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (3)', $fName);
				//trigger_error('File upload stopped by extension: '.$fName);
			return false;
		}


		$upload_moved = false;
		$fName = $this->SanitizeName($fName);
		$from = $_FILES['userfiles']['tmp_name'][$key];

		if( !$this->UploadCompressed( $from, $fName, $upload_moved ) ){
			return false;
		}

		$fName = $this->WindowsName($fName);
		$to = $this->FixRepeatNames($fName);

		if( $upload_moved ){
			if( !rename($from,$to) ){
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (Rename Failed from '.$to.')', $fName);
				return false;
			}
		}elseif( !move_uploaded_file($from,$to) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (Move Upload Failed)', $fName);
			return false;
		}

		@chmod( $to, 0666 );

		//for images
		$file_type = admin_uploaded::GetFileType($fName);
		if( isset($this->imgTypes[$file_type]) && function_exists('imagetypes') ){

			//check the image size
			thumbnail::CheckArea($to,$config['maximgarea']);

			self::CreateThumbnail($to);
		}


		return $fName;
	}

	/**
	 * Create a thumbnail for the image at the path given by $original
	 *
	 */
	static function CreateThumbnail($original){
		global $config, $dataDir;

		$prefix = $dataDir.'/data/_uploaded';
		$thumb_prefix = $dataDir.'/data/_uploaded/image/thumbnails';
		if( strpos($original,$thumb_prefix) !== false ){
			return;
		}
		if( strpos($original,$prefix) !== 0 ){
			return;
		}

		$len = strlen($prefix);
		$thumb_path = substr($original,$len);
		$thumb_path = $thumb_prefix.$thumb_path;

		$thumb_dir = dirname($thumb_path);
		$thumb_path = $thumb_dir.'/'.basename($thumb_path).'.jpg';
		gpFiles::CheckDir($thumb_dir);
		thumbnail::createSquare($original,$thumb_path,$config['maxthumbsize']);
	}


	function FixRepeatNames(&$name){

		$name_parts = explode('.',$name);
		$file_type = array_pop($name_parts);
		$temp_name = implode('.',$name_parts);

		$num = 0;
		$name = $temp_name.'.'.$file_type;
		$to = $this->currentDir.'/'.$name;
		while( file_exists($to) ){
			$name = $temp_name.'_'.$num.'.'.$file_type;
			$to = $this->currentDir.'/'.$name;
			$num++;
		}

		return $to;
	}


	/**
	 * Try to fix file uploads for Windows
	 * Windows systems don't like long names: MAX_PATH of 260 http://msdn.microsoft.com/en-us/library/aa365247.aspx
	 */
	function WindowsName($name){

		$name_parts = explode('.',$name);
		$file_type = array_pop($name_parts);
		$temp_name = implode('.',$name_parts);

		$server_software =& $_SERVER['SERVER_SOFTWARE'];
		$server_software = strtolower($server_software);
		if( strpos($server_software,'win') === false ){
			return $name;
		}

		if( isset($this->imgTypes[$file_type]) && function_exists('imagetypes') ){
			$max_len = 260 - strlen($this->currentDir_Thumb);
		}else{
			$max_len = 260 - strlen($this->currentDir);
		}

		// adjust a minimum of 8 for _#.jpg postfix, / and . characters
		$max_len -= (strlen($file_type) + 20);

		if( strlen($temp_name) > $max_len ){
			$temp_name = substr($temp_name,0,$max_len);
		}

		return $temp_name.'.'.$file_type;
	}




	/**
	 * Save a compressed copy of the uploaded file
	 *
	 */
	function UploadCompressed( &$from, &$fName, &$upload_moved ){
		global $config, $dataDir, $langmessage;


		//check file type
		$file_type = admin_uploaded::GetFileType($fName);

		if( isset($config['check_uploads']) && $config['check_uploads'] === false ){
			return true;
		}

		if( in_array( $file_type, $this->AllowedExtensions ) ){
			return true;
		}

		$upload_moved = true;
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/ArchiveTar/Tar.php');


		//first move the file to a temporary folder
		//some installations don't like working with files in the default tmp folder
		do{
			$this->temp_folder = $dataDir.'/data/_temp/'.rand(1000,9000);
		}while( file_exists($this->temp_folder) );

		gpFiles::CheckDir($this->temp_folder,false);
		$temp_file = $this->temp_folder.'/'.$fName;
		$this->temp_files[] = $temp_file;

		if( !move_uploaded_file($from,$temp_file) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (UC1)', $fName);
			return false;
		}

		//prepare file names that may be used
		//replace . with underscore for security
		$fName = str_replace('.','_',$fName);
		$tar_name = $fName.'.tar';
		$tgz_name = $fName.'.tgz';
		$tbz_name = $fName.'.tar.bz';

		//create a .tar archive of the file in the same folder
		$tar_path = $temp_file.'.tar';

		$this->temp_files[] = $tar_path;
		$tar_object = new Archive_Tar($tar_path);
		$files = array($temp_file);

		if( !$tar_object->createModify($files, '', $this->temp_folder) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (CM1)', $fName);
			return false;
		}

		$fName = $tar_name;
		$from = $tar_path;

		//compress if available, try gz first
		if( function_exists('gzopen') ){

			$compress_path = $temp_file.'.tgz';
			$this->temp_files[] = $compress_path;

			//gz compress the tar
			$gz_handle = @gzopen($compress_path, 'wb9');
			if( $gz_handle ){
				if( @gzwrite( $gz_handle, file_get_contents($tar_path)) ){
					@gzclose($gz_handle);
					$fName = $tgz_name;
					$from = $compress_path;
					//return true;
				}
			}
		}

		//if gz isn't available or doesn't work, try bz
		if( function_exists('bzopen') ){

			$compress_path = $temp_file.'.tbz';
			$this->temp_files[] = $compress_path;

			//gz compress the tar
			$bz_handle = @bzopen($compress_path, 'w');
			if( $bz_handle ){
				if( @bzwrite( $bz_handle, file_get_contents($tar_path)) ){
					@bzclose($bz_handle);
					$fName = $tbz_name;
					$from = $compress_path;
					return true;
				}
			}
		}

		return true;
	}

	/**
	 * Clean up temporary file and folder if they exist
	 * Should be called after every instance of UploadFile()
	 */
	function CleanTemporary(){

		if( empty($this->temp_folder) || !file_exists($this->temp_folder) ){
			return;
		}

		if( count($this->temp_files) > 0 ){
			foreach($this->temp_files as $file){
				if( file_exists($file) ){
					unlink($file);
				}
			}
		}
		rmdir($this->temp_folder);
	}



	/**
	 * Clean a filename by removing unwanted characters
	 *
	 */
	function SanitizeName( $sname ){
		global $config;

		$sname = stripslashes( $sname ) ;

		// Replace dots in the name with underscores (only one dot can be there... security issue).
		if( $config['check_uploads'] ){
			$sname = preg_replace( '/\\.(?![^.]*$)/', '_', $sname );
		}

		// Remove \ / | : ? * " < >
		return preg_replace( '/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/u', '_', $sname ) ;
	}


	/**
	 * Delete a single file or folder
	 *
	 */
	function DeleteConfirmed(){
		global $langmessage,$page;

		if( $this->isThumbDir ){
			return false;
		}

		if( !common::verify_nonce('delete') ){
			message($langmessage['OOPS'].' (Invalid Nonce)');
			return;
		}

		$file = $this->CheckFile();
		if( !$file ){
			return;
		}
		$full_path = $this->currentDir.'/'.$file;
		$rel_path = '/data/_uploaded'.$this->subdir.'/'.$file;

		if( !gpFiles::RmAll($full_path) ){
			message($langmessage['OOPS']);
			return;
		}

		$page->ajaxReplace[] = array('img_deleted','',$rel_path);
		$page->ajaxReplace[] = array('img_deleted_id','',self::ImageId($rel_path));
	}

	/**
	 * Verify a file is editable or deleteable
	 *
	 */
	function CheckFile($warn = true){
		global $langmessage;

		if( empty($_REQUEST['file']) ){
			if( $warn ) message($langmessage['OOPS'].'(2)');
			return false;
		}

		return $this->CheckFileName($_REQUEST['file'],$warn);
	}

	function CheckFileName($file,$warn){
		global $langmessage;
		if( (strpos($file,'/') !== false ) || (strpos($file,'\\') !== false) ){
			if( $warn ) message($langmessage['OOPS'].'(3)');
			return false;
		}
		$fullPath = $this->currentDir.'/'.$file;
		if( !file_exists($fullPath) ){
			if( $warn ) message($langmessage['OOPS'].'(4)');
			return false;
		}

		if( strpos($fullPath,$this->baseDir) === false ){
			if( $warn ) message($langmessage['OOPS'].' (5)');
			return false;
		}
		return $file;
	}






	/**
	 * Get the file extension for $file
	 * @static
	 * @param string $file The $file name or path
	 * @return string The extenstion of $file
	 */
	function GetFileType($file){
		$name_parts = explode('.',$file);
		$file_type = array_pop($name_parts);
		return strtolower($file_type);
	}

	/**
	 * Determines if the $file is an image based on the file extension
	 * @return bool
	 */
	function IsImg($file){
		$img_types = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1);

		$type = admin_uploaded::GetFileType($file);

		return isset($img_types[$type]);
	}




	/**
	 *  Performs actions after changes are made to files in elFinder
	 *
	 */
	static function FinderChange($cmd, $result, $args, $elfinder){
		global $dataDir,$config;

		includeFile('image.php');
		gp_resized::SetIndex();
		$base_dir = $dataDir.'/data/_uploaded';
		$thumb_dir = $dataDir.'/data/_uploaded/image/thumbnails';
		admin_uploaded::SetRealPath($result,$elfinder);

		switch($cmd){

			case 'rename':
			admin_uploaded::RenameResized($result['removed'][0],$result['added'][0]);
			break;

			case 'rm':
			admin_uploaded::RemoveResized($result['removed']);
			break;

			case 'paste':
			admin_uploaded::MoveResized($result['removed'],$result['added']);
			break;

			//check the image size
			case 'upload':
			admin_uploaded::MaxSize($result['added']);
			break;
		}


		//removed files first
		//	- Remove associated thumbnail
		if( isset($result['removed']) && count($result['removed']) > 0 ){
			foreach($result['removed'] as $removed){
				$removed_path = $removed['realpath'];
				$thumb_path = str_replace($base_dir,$thumb_dir,$removed_path).'.jpg';
				if( file_exists($thumb_path) ){
					unlink($thumb_path);
				}
			}
		}


		//addded files
		if( isset($result['added']) && count($result['added']) > 0 ){
			foreach($result['added'] as $added){
				$added_path = $added['realpath'];
				$thumb_path = str_replace($base_dir,$thumb_dir,$added_path).'.jpg';
				gpFiles::CheckDir($thumb_dir);
				thumbnail::createSquare($added_path,$thumb_path,$config['maxthumbsize']);
				gpPlugin::Action('FileUploaded',$added_path);
			}
		}

		//changed files (resized)
		if( isset($result['changed']) && count($result['changed']) > 0 ){
			foreach($result['changed'] as $changed){
				$changed_path = $changed['realpath'];
				$thumb_path = str_replace($base_dir,$thumb_dir,$changed_path).'.jpg';
				gpFiles::CheckDir($thumb_dir);
				thumbnail::createSquare($changed_path,$thumb_path,$config['maxthumbsize']);
			}
		}

		gp_resized::SaveIndex();

		//debug
		/*
		$log_file = $dataDir.'/data/_temp/finder_log-all_vars.txt';
		$data = get_defined_vars();
		$content = print_r($data,true);
		gpFiles::Save($log_file,$content);
		*/
	}

	/**
	 * Make sure newly uploaded images are within the site's max-size setting
	 *
	 */
	function MaxSize($added){
		global $config;
		foreach($added as $file){
			thumbnail::CheckArea($file['realpath'],$config['maximgarea']);
		}
	}

	/**
	 * Move
	 *
	 */
	function MoveResized($removed,$added){
		global $dataDir;


		//separate removed and moved entries
		$moved = array();
		$new_removed = array();
		foreach($added as $akey => $ainfo){
			$source = $ainfo['source'];
			foreach($removed as $rkey => $rinfo){
				if( $source == $rinfo['realpath'] ){
					$moved[$akey] = $rinfo;
				}else{
					$new_removed[$rkey] = $rinfo;
				}
			}
		}

		//remove files that weren't moved
		admin_uploaded::RemoveResized($new_removed);


		//rename files that were moved
		foreach($added as $akey => $ainfo){
			$rinfo = $moved[$akey];
			admin_uploaded::RenameResized($rinfo,$ainfo);
		}
	}

	/**
	 * Remove all of the resized images for an image that is deleted
	 *
	 */
	function RemoveResized($removed){
		global $dataDir;

		foreach($removed as $key => $info){
			$img = admin_uploaded::TrimBaseDir($info['realpath']);
			$index = array_search($img,gp_resized::$index);
			if( !$index ){
				continue;
			}
			unset(gp_resized::$index[$index]);
			$folder = $dataDir.'/data/_resized/'.$index;
			if( file_exists($folder) ){
				gpFiles::RmAll($folder);
			}
		}
	}



	/**
	 * Update the name of an image in the index when renamed
	 *
	 */
	function RenameResized($removed,$added){
		$added_img = admin_uploaded::TrimBaseDir($added['realpath']);
		$removed_img = admin_uploaded::TrimBaseDir($removed['realpath']);
		$index = array_search($removed_img,gp_resized::$index);
		if( !$index ){
			return false;
		}
		gp_resized::$index[$index] = $added_img;
	}


	/**
	 * Make sure the realpath value is set for elfinder arrays
	 *
	 */
	function SetRealPath(&$array,$elfinder){
		foreach($array as $type => $list){
			if( !is_array($list) ){
				continue;
			}
			foreach($list as $key => $info){
				if( !isset($info['realpath']) ){
					$array[$type][$key]['realpath'] = $elfinder->realpath($info['hash']);
				}
			}
		}
	}

	/**
	 * Get a relative file path by stripping the base dir off of a full path
	 *
	 */
	function TrimBaseDir($full_path){
		global $dataDir;

		$base_dir = $dataDir.'/data/_uploaded';
		$len = strlen($base_dir);
		if( strpos($full_path,$base_dir) === 0 ){
			return substr($full_path,$len);
		}
		return $full_path;
	}

}