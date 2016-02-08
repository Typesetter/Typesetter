<?php
namespace gp\tool;

defined('is_running') or die('Not an entry point...');


gp_defined('FS_CHMOD_DIR',0755);
gp_defined('FS_CHMOD_FILE',0644); // 0666 causes errors on some systems


class FileSystem{

	public $conn_id			= false;
	public $connect_vars	= array();
	public $temp_file		= null;
	public $method			= 'gp_filesystem_direct';
	public $connect_msg		= '';



	/**
	 * Determine which class is needed to write to $context
	 *
	 * @param array|string $context
	 */
	public static function init($context){

		if( is_array($context) ){
			$method = self::get_filesystem_method_list($context);
		}else{
			$method = self::get_filesystem_method($context);
		}
		return self::set_method($method);
	}


	/**
	 * Return a filesystem object based on the $method
	 * @param string $method
	 */
	public static function set_method($method){

		switch($method){
			case 'gp_filesystem_direct':
			return new FileSystem();


			case 'gp_filesystem_ftp':
			return new FileSystemFtp();
		}

	}


	/**
	 * Determine which class is needed to write to $context
	 *
	 * @param string $context
	 */
	public static function get_filesystem_method($context){

		while( !file_exists($context) ){
			$context = \gp\tool::DirName($context);
		}

		if( gp_is_writable($context) ){
			return 'gp_filesystem_direct';
		}

		if( function_exists('ftp_connect') ){
			return 'gp_filesystem_ftp';
		}

	}


	/**
	 * check files and folders in $context according to instructions
	 *
	 * @since 2.0b1
	 *
	 * @param array $context array of paths (as keys) and instructions (as values), possible values are (file,dir)
	 */
	public static function get_filesystem_method_list($context = array()){

		foreach($context as $file => $instruction){
			switch($instruction){
				case 'dir':
					$writable = self::writable($file);
				break;
				case 'file':
					$writable = gp_is_writable($file);
				break;
			}

			if( $writable ){
				continue;
			}

			if( function_exists('ftp_connect') ){
				return 'gp_filesystem_ftp';
			}

			return;
		}

		return 'gp_filesystem_direct';
	}


	/**
	 * Determine if the file/directory/link is writable
	 *
	 */
	public static function writable($file){

		if( is_link($file) ){
			return self::writable_link($file);
		}

		if( is_dir($file) ){
			return self::writable_dir($file);
		}

		return gp_is_writable($file);
	}


	/**
	 * Determine if the directory and all it's contents are writable
	 *
	 */
	public static function writable_dir($dir){

		if( !gp_is_writable($dir) ){
			return false;
		}

		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}


		$dir = str_replace('\\','/',$dir);
		$dir = rtrim($dir,'/');

		while( ($file = readdir($dh)) !== false){

			if( $file === '.' || $file === '..' ){
				continue;
			}

			$full_path = $dir.'/'.$file;

			if( !self::writable($full_path) ){
				msg('not writable: '.$full_path);
				return false;
			}

		}

		return true;
	}


	/**
	 * Get the minimum filesystem_method for $link
	 * if the target of the symbolic link doesnt exist then is_writable($file) will return false
	 *
	 * @param string $link
	 */
	public static function writable_link($link){

		$temp = self::TempFile( $link );
		if( @rename( $link, $temp ) && @rename( $temp, $link ) ){
			return true;
		}

		return false;
	}

	public function connectForm(){
		return true;
	}
	public function CompleteForm(){
		return true;
	}

	public function ConnectOrPrompt($action=''){
		return true;
	}
	public function connect(){
		return true;
	}

	public function connect_handler($args){
		return true;
	}

	public function get_base_dir(){
		global $dataDir;
		return $dataDir;
	}

	public function mkdir($dir){
		if( !mkdir($dir,FS_CHMOD_DIR) ){
			return false;
		}
		chmod($dir,FS_CHMOD_DIR);
		return true;
	}

	public function unlink($path){
		return unlink($path);
	}

	public function is_dir($path){
		return is_dir($path);
	}


	/**
	 * Delete folders
	 *
	 */
	public function CleanUpFolders($folders, &$not_deleted){

		$filesystem_base	= $this->get_base_dir();
		$not_deleted		= array();

		foreach($folders as $old_folder){

			if( ( strpos($old_folder,'../') !== false ) || ( strpos($old_folder,'./') !== false ) ){
				continue;
			}

			$old_folder			= '/'.ltrim($old_folder,'/');
			$old_folder_full	= $filesystem_base.$old_folder;

			if( !$this->file_exists($old_folder_full) ){
				continue;
			}

			if( !$this->rmdir_all($old_folder_full) ){
				$not_deleted[] = htmlspecialchars($old_folder);
			}
		}

		return true;
	}


	/**
	 * Remove a file, symlink or directory
	 * @param string $path
	 */
	public function rmdir_all($path){

		if( empty($path) ) return false;

		if( is_link($path) ){
			return $this->unlink($path);
		}

		if( !$this->is_dir($path) ){
			return $this->unlink($path);
		}

		$success = $this->rmdir_dir($path);

		if( !$success ){
			return false;
		}
		return rmdir($path);
	}


	/**
	 * Remnove a directory and all it's contents
	 *
	 */
	public function rmdir_dir($dir){

		$success	= true;
		$list		= $this->dirlist($dir);

		if( !is_array($list) ){
			return true;
		}

		foreach($list as $file){
			$full_path = $dir.'/'.$file;
			if( $this->is_dir($full_path) ){
				if( !$this->rmdir_all($full_path) ){
					$success = false;
				}
			}elseif( !$this->unlink($full_path) ){
				$success = false;
			}
		}

		return $success;
	}


	/**
	 * Get a list of files and folders in $dir
	 *
	 * @param string $dir
	 * @param bool $show_hidden
	 */
	public function dirlist($dir, $show_hidden=true){

		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}

		$list = array();
		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			if( !$show_hidden && $file{0} == '.' ){
				continue;
			}
			$list[$file] = $file;
		}
		closedir($dh);
		return $list;
	}

	public function rename($old_name,$new_name){
		return rename($old_name,$new_name);
	}

	public function RelRename($old_rel, $new_rel){
		$fs_root		= $this->get_base_dir();
		$old			= $fs_root.$old_rel;
		$new			= $fs_root.$new_rel;
		return $this->rename($old,$new);
	}

	public function put_contents($file, $contents, $type = '' ){
		if( !\gp\tool\Files::Save($file,$contents) ){
			return false;
		}
		//the core does not need to be world writable
		@chmod($file,FS_CHMOD_FILE);
		return true;
	}

	public function get_connect_vars($args){
		$result = array();
		if( is_array($args) ){
			foreach($args as $key => $value ){
				if( array_key_exists($key,$this->connect_vars) ){
					$result[$key] = $value;
				}
			}
		}
		return $result;
	}

	public function destruct(){
		if( is_null($this->temp_file) ){
			return;
		}
		if( file_exists($this->temp_file) ){
			unlink($this->temp_file);
		}
	}

	/**
	 * Create <input> elements for all the values in $array
	 *
	 * @param array $array
	 * @param string $name
	 */
	public function ArrayToForm($array,$name=null){

		foreach($array as $key => $value){

			if( !is_null($name) ){
				$full_name = $name.'['.$key.']';
			}else{
				$full_name = $key;
			}

			if( is_array($value) ){
				$this->ArrayToForm($value,$full_name);
				continue;
			}
			echo '<input type="hidden" name="'.htmlspecialchars($full_name).'" value="'.htmlspecialchars($value).'" />';
		}

	}

	/**
	 * Append a random string to the end of $relative_from to get the path of a non-existant file
	 *
	 * @param string $relative_from The relative path of a file
	 * @return string The path of a non-existant file in the same directory as $relative_from
	 *
	 */
	public static function TempFile( $relative_from, $extension = '' ){
		global $dataDir;
		static $rand_index;

		if( !empty($extension) ){
			$extension = '.'.ltrim($extension,'.');
		}

		clearstatcache();

		if( is_null($rand_index) ){
			$rand_index = rand(1000,9000);
		}

		$i = 0;
		do{
			$new_relative	= $relative_from.'-'.$rand_index.$extension;
			$full_path		= $dataDir.$new_relative;
			$i++;

		}while( file_exists($full_path) && $i < 100 && $rand_index++ );

		return $new_relative;
	}


	/**
	 * Replace the directories in $replace_dirs
	 *
	 * @param array $replace_dirs The relative paths of directories to be replaced
	 * @return mixed true if successful, error string otherwise
	 *
	 */
	 public function ReplaceDirs( $replace_dirs, &$clean_dirs ){
		global $langmessage, $dataDir;


		$fs_root		= $this->get_base_dir();
		$trash_dirs		= array();
		$completed		= true;
		$message		= '';
		foreach( $replace_dirs as $to_rel => $from_rel ){


			$to_rel			= trim($to_rel,'/');
			$from_rel		= trim($from_rel,'/');


			$completed		= false;
			$to_full		= $fs_root.'/'.$to_rel;
			$from_full		= $fs_root.'/'.$from_rel;
			$trash_rel		= self::TempFile( $to_rel.'-old' );
			$trash_full		= $fs_root.'/'.$trash_rel;

			if( !$this->file_exists($from_full) ){
				$message = $langmessage['dir_replace_failed'].' (Exist Check Failed - '.$this->method.' - '.htmlspecialchars($from_full).')';
				break;
			}



			//rename the original to the trash if it exists
			if( $this->file_exists($to_full) ){
				if( !$this->rename( $to_full, $trash_full ) ){
					$message = $langmessage['dir_replace_failed'].' (Rename of existing directory failed - '.$this->method.')';
					break;
				}
				$trash_dirs[$to_rel] = $trash_rel;
			}


			//if we've gotten this far, it's very unlikely this rename would fail
			if( !$this->rename( $from_full, $to_full ) ){
				$message = $langmessage['dir_replace_failed'].' (Rename of new directory failed - '.$this->method.')';
				break;
			}

			//break;
			$completed = true;
		}


		if( $completed ){
			$clean_dirs = $trash_dirs;
			return true;
		}

		//if it's not all completed, undo the changes that were completed
		foreach( $trash_dirs as $to_rel => $trash_rel ){

			$to_full		= $fs_root.'/'.$to_rel;
			$from_full		= $fs_root.'/'.$replace_dirs[$to_rel];
			$trash_full		= $fs_root.'/'.$trash_rel;

			$this->rename( $to_full, $from_full );
			$this->rename( $trash_full, $to_full );
		}

		$clean_dirs = $replace_dirs;

		return $message;
	}

	public function file_exists($file){
		clearstatcache(true, $file);
		return file_exists($file);
	}


	/**
	 * Determines if the string provided contains binary characters.
	 *
	 * @since 2.7
	 *
	 * @param string $text String to test against
	 * @return bool true if string is binary, false otherwise
	 */
	public function is_binary( $text ) {
		return (bool) preg_match('|[^\x20-\x7E]|', $text);
	}

}


