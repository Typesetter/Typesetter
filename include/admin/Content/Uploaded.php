<?php

namespace gp\admin\Content{

	defined('is_running') or die('Not an entry point...');

	includeFile('image.php');

	class Uploaded extends \gp\special\Base{

		public $baseDir;
		public $subdir			= '';
		public $thumbFolder;
		public $isThumbDir		= false;
		public $imgTypes;
		public $errorMessages	= array();
		public $finder_opts		= array();
		public $currentDir;
		public $currentDir_Thumb;


		public function RunScript(){
			$file_cmd = \gp\tool::GetCommand('file_cmd');
			if( !empty($file_cmd) || (isset($_REQUEST['show']) && $_REQUEST['show'] == 'inline') ){
				$this->do_admin_uploaded($file_cmd);
			}else{
				$this->Finder();
			}
		}

		public function Finder(){
			global $config, $dataDir;

			$this->page->head .= "\n".'<link rel="stylesheet" type="text/css" media="screen" href="'.\gp\tool::GetDir('/include/thirdparty/finder/css/finder.css').'">';
			$this->page->head .= "\n".'<link rel="stylesheet" type="text/css" media="screen" href="'.\gp\tool::GetDir('/include/thirdparty/finder/style.css').'">';

			$this->page->head .= "\n".'<script type="text/javascript" src="'.\gp\tool::GetDir('/include/thirdparty/finder/js/finder.js').'"></script>';
			$this->page->head .= "\n".'<script type="text/javascript" src="'.\gp\tool::GetDir('/include/thirdparty/finder/config.js').'"></script>';


			echo '<div id="finder"></div>';

			\gp\tool::LoadComponents('selectable,draggable,droppable,resizable,dialog,slider,button');



			//get the finder language
			$language = $config['langeditor'];
			if( $language == 'inherit' ){
				$language = $config['language'];
			}
			$lang_file = '/include/thirdparty/finder/js/i18n/'.$language.'.js';
			$lang_full = $dataDir.$lang_file;
			if( file_exists($lang_full) ){
				$this->page->head .= "\n".'<script type="text/javascript" src="'.\gp\tool::GetDir($lang_file).'"></script>';
			}else{
				$language = 'en';
			}
			$this->finder_opts['lang'] = $language;
			$this->finder_opts['customData']['verified'] = \gp\tool::new_nonce('post',true);


			$this->finder_opts['uiOptions'] = array(

				// toolbar configuration
				'toolbar' => array(
					array('back', 'forward','up','reload'),
					array('home','netmount'),
					array('mkdir', 'upload'), //'mkfile',
					array('open', 'download', 'getfile'),
					array('info'),
					array('quicklook'),
					array('copy', 'cut', 'paste'),
					array('rm'),
					array('duplicate', 'rename', 'edit', 'resize'),
					array('extract', 'archive'),
					array('search'),
					array('view','sort'),
					array('help')
				),

				// directories tree options
				'tree' => array(
					// expand current root on init
					'openRootOnLoad' => true,
					// auto load current dir parents
					'syncTree' => true,
				),

				// navbar options
				'navbar' => array(
					'minWidth' => 150,
					'maxWidth' => 500
				),

				// current working directory options
				'cwd' => array(
					// display parent directory in listing as ".."
					'oldSchool' => false
				)
			);


			$this->FinderPrep();

			$this->finder_opts = \gp\tool\Plugins::Filter('FinderOptionsClient',array($this->finder_opts));
			gpSettingsOverride('finder_options_client',$this->finder_opts);

			$this->page->head_script .= "\n".'var finder_opts = '.json_encode($this->finder_opts).';';
		}

		public function FinderPrep(){
			$this->finder_opts['url']			= \gp\tool::GetUrl('Admin_Finder');
			$this->finder_opts['height']		= '100%';
			$this->finder_opts['resizable'] 	= false;
		}


		public function do_admin_uploaded($file_cmd){

			$this->Init();
			$this->page->ajaxReplace = array();

			switch($file_cmd){
				case 'delete':
					$this->DeleteConfirmed();
				return;

				case 'inline_upload':
					$this->InlineUpload();
				//dies
			}
		}

		public function Init(){
			global $langmessage, $dataDir;

			$this->baseDir		= $dataDir.'/data/_uploaded';
			$this->thumbFolder	= $dataDir.'/data/_uploaded/image/thumbnails';
			$this->currentDir	= $this->baseDir;
			$this->page->label	= $langmessage['uploaded_files'];

			$this->imgTypes		= array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1,'svg'=>1,'svgz'=>1);

			$this->SetDirectory();

			//prompt to create the requested subdirectory
			if( !file_exists($this->currentDir) ){
				\gp\tool\Files::CheckDir($this->currentDir);
			}


			//is in thumbnail directory?
			if( strpos($this->currentDir,$this->thumbFolder) !== false ){
				$this->isThumbDir = true;
			}
			$this->currentDir_Thumb = $this->thumbFolder.$this->subdir;

		}

		/**
		 * Set the upload directory
		 *
		 */
		public function SetDirectory(){

			$subdir		= '';
			$path		= \gp\tool::WhichPage(); // get the current path, not using $page->requested since space characters will have been changed to underscores
			$path		= str_replace('\\','/',$path);

			//@since 5.0
			if( strpos($path,'Admin/Uploaded') === 0 ){
				$path	= substr($path,14);
				$path	= trim($path,'/');
				$parts	= explode('/',$path);


			//backwards compat
			}else{
				$path = trim($path,'/');
				$parts = explode('/',$path);
				array_shift($parts);
			}

			if( count($parts) > 0 ){
				$subdir = '/'.implode('/',$parts);
				$subdir = \gp\tool\Editing::CleanArg($subdir);
			}


			if( !empty($_REQUEST['dir']) ){
				$subdir .= \gp\tool\Editing::CleanArg($_REQUEST['dir']);
			}

			$subdir				= \gp\tool\Files::Canonicalize($subdir);
			$subdir				= rtrim($subdir,'/');
			$current_dir		= $this->currentDir . $subdir;

			if( !\gp\tool\Files::CheckPath( $current_dir, $this->currentDir ) ){
				return;
			}

			$this->subdir		= $subdir;
			$this->currentDir	= $current_dir;
		}


		public function ReadableMax(){
			$value = ini_get('upload_max_filesize');

			if( empty($value) ){
				return '2 Megabytes';//php default
			}
			return $value;
		}


		public static function Max_File_Size(){
			$value = ini_get('upload_max_filesize');
			if( empty($value) ){
				return;
			}
			$max = \gp\tool::getByteValue($value);
			if( $max !== false ){
				echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max.'" />';
			}
		}


		/**
		 * Upload one image
		 *
		 */
		public function InlineUpload(){

			if( count($_FILES['userfiles']['name']) != 1 ){
				$this->InlineResponse('failed','Empty Array');
			}

			$name = $_FILES['userfiles']['name'][0];
			if( empty($name) ){
				$this->InlineResponse('failed','Empty Name');
			}

			$uploaded = $this->UploadFile(0);
			if( $uploaded === false ){
				reset($this->errorMessages);
				$this->InlineResponse('failed',current($this->errorMessages));
			}
			\gp\tool\Plugins::Action('FileUploaded',$uploaded);

			$return_content = self::ShowFile_Gallery($this->subdir,$uploaded);

			if( is_string($return_content) ){
				$this->InlineResponse('success',$return_content);
			}else{
				$this->InlineResponse('notimage','');
			}

		}

		/**
		 * Output a list a images in a director for use in inline editing
		 * @static
		 */
		public static function InlineList($dir_piece){
			global $langmessage, $dataDir, $page;

			$page->ajaxReplace = array();


			$dir_piece = \gp\tool::WinPath($dir_piece);
			$dir = $dataDir.'/data/_uploaded'.$dir_piece;

			$prev_piece = false;

			while( ($dir_piece != '/') && !file_exists($dir) ){
				$prev_piece = $dir_piece;
				$dir = \gp\tool::DirName($dir);
				$dir_piece = \gp\tool::DirName($dir_piece);
			}

			//new directory?
			if( $prev_piece ){
				$prev_piece = \gp\tool\Editing::CleanArg($prev_piece);
				$dir_piece = $prev_piece;
				$dir = $dataDir.'/data/_uploaded'.$prev_piece;

				if( !\gp\tool\Files::CheckDir($dir) ){
					message($langmessage['OOPS']);
					$dir = \gp\tool::DirName($dir);
					$dir_piece = \gp\tool::DirName($prev_piece);
				}
			}


			//folder information
			$folders = $files = array();
			$allFiles = \gp\tool\Files::ReadFolderAndFiles($dir);
			list($folders,$files) = $allFiles;

			//available images
			ob_start();
			$image_count = 0;
			foreach($files as $file){
				$img = self::ShowFile_Gallery($dir_piece,$file);
				if( is_string($img) ){
					echo $img;
					$image_count++;
				}
			}
			$gp_gallery_avail_imgs = ob_get_clean();


			$gp_option_area	= self::InlineList_Options($dir_piece, $folders);
			$folder_options = self::InlineList_Folder($image_count, $dir_piece);



			//send content according to request
			$cmd = \gp\tool::GetCommand();
			switch($cmd){
				case 'gallery_folder':
					$page->ajaxReplace[] = array('inner','#gp_option_area',$gp_option_area);
					$page->ajaxReplace[] = array('inner','#gp_gallery_avail_imgs',$gp_gallery_avail_imgs);
				break;
				default:
					$content = '<div id="gp_option_area">'.$gp_option_area.'</div>'
								.'<div id="gp_gallery_avail_imgs">'.$gp_gallery_avail_imgs.'</div>';
					$page->ajaxReplace[] = array('inner','#gp_image_area',$content);
				break;
			}



			$page->ajaxReplace[] = array('inner','#gp_folder_options',$folder_options);
			$page->ajaxReplace[] = array('gp_gallery_images','',''); //tell the script the images have been loaded
		}


		/**
		 * Return folder options for the InlineList
		 *
		 */
		public static function InlineList_Options($dir_piece, $folders){
			global $langmessage, $dataDir;

			$return		= '';
			$return		.= '<div class="gp_edit_select">';
			$return		.= '<a class="gp_selected_folder"><i class="fa fa-folder-o"></i> ';
			if( strlen($dir_piece) > 23 ){
				$return		.= '...'.substr($dir_piece,-20);
			}else{
				$return		.= $dir_piece;
			}
			$return		.= '</a>';

			$return		.= '<div class="gp_edit_select_options">';
			if( $dir_piece != '/' ){
				$temp = \gp\tool::DirName($dir_piece);
				$return		.= '<a href="?cmd=new_dir&dir='.rawurlencode($dir_piece).'" class="gp_gallery_folder" data-cmd="gpabox"><i class="fa fa-plus"></i> '.$langmessage['create_dir'].'</a>';
				$return		.= '<a class="gp_gallery_folder" data-cmd="gp_gallery_folder" data-arg="'.htmlspecialchars($temp).'"><i class="fa fa-folder-o"></i> .../</a>';
			}

			foreach($folders as $folder){
				if( $dir_piece == '/' ){
					$sub_dir = '/'.$folder;
				}else{
					$sub_dir = $dir_piece.'/'.$folder;
				}
				$full_dir = $dataDir.'/data/_uploaded'.$sub_dir;
				$sub_files = scandir($full_dir);
				$count = 0;
				foreach($sub_files as $file){
					if( self::IsImg($file) ){
						$count++;
					}
				}
				$return		.= '<a class="gp_gallery_folder" data-cmd="gp_gallery_folder" data-arg="'.htmlspecialchars($sub_dir).'"><i class="fa fa-folder-o"></i> <span class="gp_count">'.$count.'</span>'.$folder.'</a>';
			}
			$return		.= '</div></div>';

			return $return;
		}


		/**
		 * Return folder options for the InlineList
		 *
		 */
		public static function InlineList_Folder($image_count, $dir_piece){
			global $langmessage;

			ob_start();

			if( $image_count > 0 ){
				echo '<a data-cmd="gp_gallery_add_all" class="ckeditor_control half_width add_all_images">'.$langmessage['Add All Images'].'</a>';
			}

			if( $dir_piece != '/' ){

				echo '<form action="'.\gp\tool::GetUrl('Admin/Uploaded').'" method="post"  enctype="multipart/form-data" class="gp_upload_form" id="gp_upload_form">';
				self::Max_File_Size();
				echo '<a class="ckeditor_control half_width">'.$langmessage['upload_files'].'</a>';
				echo '<div class="gp_object_wrapper">';
				echo '<input type="file" name="userfiles[]" class="file" />';

				echo '<input type="hidden" name="file_cmd" value="inline_upload" />';
				echo '<input type="hidden" name="output" value="gallery" />';
				echo '<input type="hidden" name="dir" value="'.$dir_piece.'" />';
				echo '</div>';
				echo '</form>';
			}

			return ob_get_clean();
		}


		/**
		 * @static
		 */
		public static function ShowFile_Gallery($dir_piece,$file){
			global $langmessage, $dataDir;

			if( !self::IsImg($file) ){
				return;
			}

			//for gallery editing
			$rel_path = '/data/_uploaded'.$dir_piece.'/'.$file;
			$id = self::ImageId($rel_path);
			$file_url = \gp\tool::GetDir($rel_path);
			$full_path = $dataDir.$rel_path;

			//thumbnail
			$thumb_url = \gp\tool::ThumbnailPath($file_url);

			// alternate text from file name
			$img_alt = str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME) );

			$thumb = ' <img src="'.$thumb_url.'" alt="'.$img_alt.'" />';

			//get size
			$size = '';
			$size_a = getimagesize($full_path);
			if( is_array($size_a) ){
				$size = ' data-width="'.$size_a[0].'" data-height="'.$size_a[1].'"';
			}

			$query_string = 'file_cmd=delete&show=inline&file='.urlencode($file);

			return '<div class="expand_child" id="'.$id.'">'
					. '<a href="'.$file_url.'" title="'.$file.'" data-cmd="gp_gallery_add" '.$size.'>'
					. $thumb
					. '</a>'
					. '<span>'
					. \gp\tool::Link(
						'Admin/Uploaded'.$dir_piece,
						'',
						$query_string,
						array(
							'class'=>'delete fa fa-trash gpconfirm',
							'data-cmd'=>'gpajax',
							'title'=>$langmessage['delete_confirm']
						),
						'delete'
					)
					. '</span>'
					. '</div>';
		}

		public static function ImageId($path){
			$encoded = base64_encode($path);
			$encoded = rtrim($encoded, '=');
			return 'gp_image_'.strtr($encoded, '+/=', '-_.');
		}


		public function InlineResponse($status,$message){
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

		public function UploadFile($key){
			global $langmessage,$config;

			$fName = $_FILES['userfiles']['name'][$key];

			$code = (int)$_FILES['userfiles']['error'][$key];
			switch( $code ){

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

				case UPLOAD_ERR_CANT_WRITE:
				case UPLOAD_ERR_NO_TMP_DIR:
				case UPLOAD_ERR_EXTENSION:
					$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' ('.$code.')', $fName);
				return false;
			}


			$upload_moved = false;
			$fName = $this->SanitizeName($fName);
			$from = $_FILES['userfiles']['tmp_name'][$key];

			if( !self::AllowedExtension($fName) ){
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
			$file_type = self::GetFileType($fName);
			if( isset($this->imgTypes[$file_type]) && function_exists('imagetypes') ){

				//check the image size if image is not svg/z
				if( $config['maximgarea'] > 0 && strpos('svgz', $file_type) !== 0 ){
					\gp\tool\Image::CheckArea($to,$config['maximgarea']);
				}

				self::CreateThumbnail($to);
			}


			return $fName;
		}

		/**
		 * Create a thumbnail for the image at the path given by $original
		 *
		 */
		public static function CreateThumbnail($original){
			global $config, $dataDir;

			$prefix = $dataDir.'/data/_uploaded';
			$thumb_prefix = $dataDir.'/data/_uploaded/image/thumbnails';
			if( strpos($original,$thumb_prefix) !== false ){
				return;
			}
			if( strpos($original,$prefix) !== 0 ){
				return;
			}

			$thumb_path	= \gp\tool::ThumbnailPath($original);
			$thumb_dir	= \gp\tool::DirName($thumb_path);

			\gp\tool\Files::CheckDir($thumb_dir);

			if( !empty($config['maxthumbheight']) && $config['maxthumbheight'] !== $config['maxthumbsize'] ){
				// NEW TS 5.1: proportional thumbnails
				return \gp\tool\Image::CreateRect($original, $thumb_path, $config['maxthumbsize'], $config['maxthumbheight'], $config['thumbskeepaspect']);
			}else{
				// return \gp\tool\Image::createSquare($original,$thumb_path,$config['maxthumbsize']);
				return \gp\tool\Image::CreateRect($original, $thumb_path, $config['maxthumbsize'], $config['maxthumbsize'], $config['thumbskeepaspect']);
			}
		}


		public function FixRepeatNames(&$name){

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
		public function WindowsName($name){

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
		 * Check the file extension agains $allowed_types
		 *
		 */
		public static function AllowedExtension( &$file , $fix = true ){
			global $upload_extensions_allow, $upload_extensions_deny;
			static $allowed_types = false;

			$file = \gp\tool\Files::NoNull($file);

			if( !gp_restrict_uploads ){
				return true;
			}


			$parts = explode('.',$file);
			if( count($parts) < 2 ){
				return true;
			}


			//build list of allowed extensions once
			if( !$allowed_types ){

				if( is_string($upload_extensions_deny) && strtolower($upload_extensions_deny) === 'all' ){
					$allowed_types = array();
				}else{
					$allowed_types = array(
						/** Images **/		'bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'tif', 'tiff', 'svg', 'svgz',
						/** Media **/		'aiff', 'asf', 'avi', 'fla', 'flac', 'flv', 'm4v', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ogg', 'oga', 'ogv', 'opus', 'qt', 'ram', 'rm', 'rmi', 'rmvb', 'swf', 'wav', 'wma', 'webm', 'wmv', 
						/** Archives **/	'7z', 'bz', 'gz', 'gzip', 'rar', 'tar', 'tgz', 'zip',
						/** Text/Docs **/	'css', 'csv', 'doc', 'docx', 'htm', 'html', 'js', 'json', 'less', 'md', 'ods', 'odt', 'pages', 'pdf', 'ppt', 'pptx', 'rtf', 'txt', 'scss', 'sxc', 'sxw', 'vsd', 'xls', 'xlsx', 'xml', 'xsl', 
					);


				}

				if( is_array($upload_extensions_allow) ){
					$upload_extensions_allow	= array_map('trim',$upload_extensions_allow);
					$upload_extensions_allow	= array_map('strtolower',$upload_extensions_allow);
					$allowed_types				= array_merge($allowed_types,$upload_extensions_allow);
				}
				if( is_array($upload_extensions_deny) ){
					$upload_extensions_allow	= array_map('trim',$upload_extensions_allow);
					$upload_extensions_allow	= array_map('strtolower',$upload_extensions_allow);
					$allowed_types				= array_diff($allowed_types,$upload_extensions_deny);
				}
			}

			$allowed_types = \gp\tool\Plugins::Filter('AllowedTypes',array($allowed_types));


			//make sure the extension is allowed
			$file_type = array_pop($parts);
			if( !in_array( strtolower($file_type), $allowed_types ) ){
				return false;
			}

			if( $fix ){
				return implode('_',$parts).'.'.$file_type;
			}else{
				return implode('.',$parts).'.'.$file_type;
			}
		}


		/**
		 * Clean a filename by removing unwanted characters
		 *
		 */
		public function SanitizeName( $sname ){
			global $config;

			$sname = stripslashes( $sname ) ;

			// Replace dots in the name with underscores (only one dot can be there... security issue).
			$sname = preg_replace( '/\\.(?![^.]*$)/', '_', $sname );

			// Remove \ / | : ? * " < >
			return preg_replace( '/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/u', '_', $sname ) ;
		}


		/**
		 * Delete a single file or folder
		 *
		 */
		public function DeleteConfirmed(){
			global $langmessage;

			if( $this->isThumbDir ){
				return false;
			}

			if( \gp\tool::verify_nonce('delete') === false ){
				message($langmessage['OOPS'].' (Invalid Nonce)');
				return;
			}

			$file = $this->CheckFile();
			if( !$file ){
				return;
			}
			$full_path = $this->currentDir.'/'.$file;
			$rel_path = '/data/_uploaded'.$this->subdir.'/'.$file;

			if( !\gp\tool\Files::RmAll($full_path) ){
				message($langmessage['OOPS']);
				return;
			}

			$this->page->ajaxReplace[] = array('img_deleted','',$rel_path);
			$this->page->ajaxReplace[] = array('img_deleted_id','',self::ImageId($rel_path));
		}

		/**
		 * Verify a file is editable or deleteable
		 *
		 */
		public function CheckFile($warn = true){
			global $langmessage;

			if( empty($_REQUEST['file']) ){
				if( $warn ) message($langmessage['OOPS'].'(2)');
				return false;
			}

			return $this->CheckFileName($_REQUEST['file'],$warn);
		}

		public function CheckFileName($file,$warn){
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
		public static function GetFileType($file){
			$name_parts = explode('.',$file);
			$file_type = array_pop($name_parts);
			return strtolower($file_type);
		}

		/**
		 * Determines if the $file is an image based on the file extension
		 * @static
		 * @return bool
		 */
		public static function IsImg($file){
			$img_types = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1,'svg'=>1, 'svgz'=>1);

			$type = self::GetFileType($file);

			return isset($img_types[$type]);
		}




		/**
		 *  Performs actions after changes are made to files in finder
		 *
		 */
		public static function FinderChange($cmd, $result, $args, $finder){
			global $dataDir,$config;

			includeFile('image.php');
			\gp_resized::SetIndex();
			$base_dir = $dataDir.'/data/_uploaded';
			$thumb_dir = $dataDir.'/data/_uploaded/image/thumbnails';
			self::SetRealPath($result,$finder);


			switch($cmd){

				case 'rename':
				self::RenameResized($result['removed'][0],$result['added'][0]);
				break;

				case 'rm':
				self::RemoveResized($result['removed']);
				break;

				case 'paste':
				self::MoveResized($result['removed'],$result['added']);
				break;

				//check the image size
				case 'upload':
				self::MaxSize($result['added']);
				break;
			}


			//removed files first
			//	- Remove associated thumbnail
			if( isset($result['removed']) && count($result['removed']) > 0 ){
				foreach($result['removed'] as $removed){
					$removed_path = $removed['realpath'];
					\gp\tool\Plugins::Action('FileDeleted',$removed_path);

					$thumb_path = str_replace($base_dir,$thumb_dir,$removed_path);
					if( file_exists($thumb_path) ){
						if( is_dir($thumb_path) ){
							\gp\tool\Files::RmAll($thumb_path);
						}else{
							unlink($thumb_path);
						}
						continue;
					}

					// svg or not svg
					$nameParts = explode('.',$removed_path);
					$type = array_pop($nameParts);
					$type = strtolower($type);
					if( strpos('svgz',$type) !== 0 ){
						$type = 'jpg';
					}

					$thumb_path = str_replace($base_dir,$thumb_dir,$removed_path).'.'.$type;
					if( file_exists($thumb_path) ){
						unlink($thumb_path);
					}
				}
			}


			//added files
			self::FinderActions($result, 'added', 'FileUploaded');

			//changed files (resized)
			self::FinderActions($result, 'changed', 'FileChanged');

			\gp_resized::SaveIndex();

			//debug
			/*
			$log_file = $dataDir.'/data/_temp/finder_log-'.time().'.txt';
			$data = get_defined_vars();
			$content = print_r($data,true).'<hr/>';
			$fp = fopen($log_file,'a');
			fwrite($fp,$content);
			*/
		}

		/**
		 * Call Actions on the finder result
		 *
		 */
		protected static function FinderActions($result, $key, $action){

			if( isset($result[$key]) && count($result[$key]) > 0 ){
				foreach($result[$key] as $changed){
					\gp\tool\Plugins::Action($action,$changed['realpath']);
					self::CreateThumbnail($changed['realpath']);
				}
			}
		}

		/**
		 * Make sure newly uploaded images are within the site's max-size setting
		 *
		 */
		public function MaxSize($added){
			global $config;

			if( $config['maximgarea'] > 0 ){
				foreach($added as $file){
					\gp\tool\Image::CheckArea($file['realpath'],$config['maximgarea']);
				}
			}
		}

		/**
		 * Move
		 *
		 */
		public function MoveResized($removed,$added){
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
			self::RemoveResized($new_removed);


			//rename files that were moved
			foreach($added as $akey => $ainfo){
				$rinfo = $moved[$akey];
				self::RenameResized($rinfo,$ainfo);
			}
		}

		/**
		 * Remove all of the resized images for an image that is deleted
		 *
		 */
		public function RemoveResized($removed){
			global $dataDir;

			foreach($removed as $key => $info){
				$img = self::TrimBaseDir($info['realpath']);
				$index = array_search($img,\gp_resized::$index);
				if( !$index ){
					continue;
				}
				unset(\gp_resized::$index[$index]);
				$folder = $dataDir.'/data/_resized/'.$index;
				if( file_exists($folder) ){
					\gp\tool\Files::RmAll($folder);
				}
			}
		}



		/**
		 * Update the name of an image in the index when renamed
		 *
		 */
		public function RenameResized($removed,$added){
			$added_img = self::TrimBaseDir($added['realpath']);
			$removed_img = self::TrimBaseDir($removed['realpath']);
			$index = array_search($removed_img,\gp_resized::$index);
			if( !$index ){
				return false;
			}
			\gp_resized::$index[$index] = $added_img;
		}


		/**
		 * Make sure the realpath value is set for finder arrays
		 *
		 */
		public function SetRealPath(&$array,$finder){
			foreach($array as $type => $list){
				if( !is_array($list) ){
					continue;
				}
				foreach($list as $key => $info){
					if( isset($info['hash']) && !isset($info['realpath']) ){
						$array[$type][$key]['realpath'] = $finder->realpath($info['hash']);
					}
				}
			}
		}


		/**
		 * Get a relative file path by stripping the base dir off of a full path
		 *
		 */
		public function TrimBaseDir($full_path){
			global $dataDir;

			$base_dir = $dataDir.'/data/_uploaded';
			$len = strlen($base_dir);
			if( strpos($full_path,$base_dir) === 0 ){
				return substr($full_path,$len);
			}
			return $full_path;
		}

	}
}

namespace{
	class admin_uploaded extends \gp\admin\Content\Uploaded{}
}
