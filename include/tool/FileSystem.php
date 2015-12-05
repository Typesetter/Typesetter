<?php
defined('is_running') or die('Not an entry point...');


define('gp_filesystem_direct',1);
define('gp_filesystem_ftp',2);
gp_defined('FS_CHMOD_DIR',0755);
gp_defined('FS_CHMOD_FILE',0644); // 0666 causes errors on some systems


class gp_filesystem_base{

	public $conn_id			= false;
	public $connect_vars	= array();
	public $temp_file		= null;
	public $method			= 'gp_filesystem_base';



	/**
	 * Determine which class is needed to write to $context
	 *
	 * @param array|string $context
	 */
	public static function init($context){

		if( is_array($context) ){
			$method = gp_filesystem_base::get_filesystem_method_list($context);
		}else{
			$method = gp_filesystem_base::get_filesystem_method($context);
		}
		return gp_filesystem_base::set_method($method);
	}


	/**
	 * Return a filesystem object based on the $method
	 * @param string $method
	 */
	public static function set_method($method){

		switch($method){
			case 'gp_filesystem_direct':
			return new gp_filesystem_direct();


			case 'gp_filesystem_ftp':
			return new gp_filesystem_ftp();
		}

	}



	/**
	 * Determine which class is needed to write to $context
	 *
	 * @param string $context
	 */
	public static function get_filesystem_method($context){

		while( !file_exists($context) ){
			$context = common::DirName($context);
		}


		//direct
		if( gp_is_writable($context) ){

			if( !is_dir($context) ){
				return 'gp_filesystem_direct';
			}

			if( function_exists('posix_getuid') && function_exists('fileowner') ){
				$direct = false;

				//check more for directories
				$temp_file_name = $context . '/temp-write-test-' . time();

				$temp_handle = @fopen($temp_file_name, 'w');

				if( $temp_handle ){
					if( posix_getuid() == @fileowner($temp_file_name) ){
						$direct = true;
					}
					@fclose($temp_handle);
					@unlink($temp_file_name);
				}

				if( $direct ){
					return 'gp_filesystem_direct';
				}

			}

		}

		//ftp
		if( function_exists('ftp_connect') ){
			return 'gp_filesystem_ftp';
		}

		return false;
	}


	/**
	 * check files and folders in $context according to instructions
	 *
	 * @since 2.0b1
	 *
	 * @param array $context array of paths (as keys) and instructions (as values), possible values are (file,dir)
	 */
	public static function get_filesystem_method_list($context = array()){
		$result = 1;

		foreach($context as $file => $instruction){
			switch($instruction){
				case 'dir':
					$temp_result = gp_filesystem_base::get_filesystem_method_dir($file);
				break;
				case 'file':
					$temp_result = gp_filesystem_base::get_filesystem_method_file($file);
				break;
				default:
					$temp_result = false;
				break;
			}
			if( $temp_result === false ){
				return false;
			}
			$result = max($temp_result,$result);
		}

		switch($result){
			case 1:
			return 'gp_filesystem_direct';
			case 2:
			return 'gp_filesystem_ftp';
			default:
			return false;
		}
	}


	public static function get_filesystem_method_dir($dir){
		$result = gp_filesystem_base::get_filesystem_method_file($dir);
		if( $result === false ){
			return false;
		}

		$dh = @opendir($dir);
		if( !$dh ){
			return $result;
		}

		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			$fullPath = $dir.'/'.$file;
			if( is_link($fullPath) ){
				$temp_result = gp_filesystem_base::get_filesystem_method_link($fullPath);
			}elseif( is_dir($fullPath) ){
				$temp_result = gp_filesystem_base::get_filesystem_method_dir($fullPath);
			}else{
				$temp_result = gp_filesystem_base::get_filesystem_method_file($fullPath);
			}


			if( $temp_result === false ){
				return false;
			}
			$result = max($temp_result,$result);

		}

		return $result;
	}

	/**
	 * Get the minimum filesystem_method for $file
	 *
	 * @param string $file
	 */
	public static function get_filesystem_method_file($file){

		if( gp_is_writable($file) ){
			return gp_filesystem_direct;
		}

		if( function_exists('ftp_connect') ){
			return gp_filesystem_ftp;
		}

		return false;
	}


	/**
	 * Get the minimum filesystem_method for $link
	 * if the target of the symbolic link doesnt exist then is_writable($file) will return false
	 *
	 * @param string $link
	 */
	public static function get_filesystem_method_link($link){

		$temp = gp_filesystem_base::TempFile( $link );
		if( @rename( $link, $temp ) && @rename( $temp, $link ) ){
			return gp_filesystem_direct;
		}

		if( function_exists('ftp_connect') ){
			return gp_filesystem_ftp;
		}

		return false;
	}

	public function connectForm(){
		return true;
	}
	public function CompleteForm(){
		return true;
	}

	public function ConnectOrPrompt(){
		return true;
	}
	public function connect(){
		return true;
	}

	/**
	 * @return mixed
	 */
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

	public function put_contents($file, $contents, $type = '' ){
		if( !gpFiles::Save($file,$contents) ){
			return false;
		}
		//the gpEasy core does not need to be world writable
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
	public function TempFile( $relative_from ){
		global $dataDir;
		static $rand_index;

		clearstatcache();

		if( is_null($rand_index) ){
			$rand_index = rand(1000,9000);
		}

		$i = 0;
		do{
			$new_relative	= $relative_from.'-'.$rand_index;
			$full_path		= $dataDir.$new_relative;
			$rand_index++;
			$i++;

		}while( file_exists($full_path) && $i < 100 );

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
			$trash_rel		= $this->TempFile( $to_rel.'-old' );
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
	 * @access private
	 *
	 * @param string $text String to test against
	 * @return bool true if string is binary, false otherwise
	 */
	public function is_binary( $text ) {
		return (bool) preg_match('|[^\x20-\x7E]|', $text);
	}


}


class gp_filesystem_ftp extends gp_filesystem_base{

	public $connect_vars	= array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'','port'=>'21');
	public $ftp_root		= null;
	public $method			= 'gp_filesystem_ftp';

	public function __construct(){
		includeFile('tool/ftp.php');
	}

	public function get_base_dir(){
		global $dataDir;

		if( is_null($this->ftp_root) ){
			$this->ftp_root = gpftp::GetFTPRoot($this->conn_id,$dataDir);
			$this->ftp_root = rtrim($this->ftp_root,'/');
		}
		return $this->ftp_root;
	}



	/**
	 * Connect to ftp using the supplied values
	 * @return mixed true on success, Error string on failure
	 */
	public function connect_handler($args){
		global $langmessage;

		if( empty($args['ftp_server']) ){
			return $langmessage['couldnt_connect'].' (Missing Arguments)';
		}
		if( empty($args['port']) ){
			$args['port'] = 21;
		}

		$this->conn_id = @ftp_connect($args['ftp_server'],$args['port'],6);

		if( !$this->conn_id ){
			return $langmessage['couldnt_connect'].' (Server Connection Failed)';
		}

		//use ob_ to keep error message from displaying
		ob_start();
		$connected = @ftp_login($this->conn_id,$args['ftp_user'], $args['ftp_pass']);
		ob_end_clean();

		if( !$connected ){
			return $langmessage['couldnt_connect'].' (Authentication Failed)';
		}

		@ftp_pasv($this->conn_id, true );

		return true;
	}

	/**
	 * Connect to ftp server using either Post or saved values
	 * Connection values will not be kept in $config in case they're being used for a system revert which will replace the config.php file
	 * Also handle moving ftp connection values from $config to a sep
	 *
	 * @return bool true if connected, error message otherwise
	 */
	public function connect(){
		global $config, $dataDir, $langmessage;


		$save_values						= false;
		$connect_args						= gpFiles::Get('_updates/connect','connect_args');

		if( !$connect_args || (!isset($connect_args['ftp_user']) && isset($config['ftp_user'])) ){
			$connect_args['ftp_user']		= $config['ftp_user'];
			$connect_args['ftp_server']		= $config['ftp_server'];
			$connect_args['ftp_pass']		= $config['ftp_pass'];
			$connect_args['ftp_root']		= $config['ftp_root'];
			$save_values = true;
		}

		if( isset($_POST['ftp_pass']) ){
			$connect_args					= $_POST;
			$save_values					= true;
		}

		$connect_args						= $this->get_connect_vars($connect_args);
		$connected							= $this->connect_handler($connect_args);

		if( $connected !== true ){
			return $connected;
		}

		//get the ftp_root
		if( empty($connect_args['ftp_root']) || $save_values ){

			$this->ftp_root = $this->get_base_dir();
			if( !$this->ftp_root ){
				return $langmessage['couldnt_connect'].' (Couldn\'t find root)';
			}
			$connect_args['ftp_root'] = $this->ftp_root;
			$save_values = true;
		}else{
			$this->ftp_root = $connect_args['ftp_root'];
		}


		//save ftp info
		if( !$save_values ){
			return $connected;
		}

		$connection_file	= $dataDir.'/data/_updates/connect.php';
		if( !gpFiles::SaveData($connection_file,'connect_args',$connect_args) ){
			return $connected;
		}

		/*
		 * Remove from $config if it's not a safe mode installation
		 */
		if( !isset($config['useftp']) && isset($config['ftp_user']) ){
			unset($config['ftp_user']);
			unset($config['ftp_server']);
			unset($config['ftp_pass']);
			unset($config['ftp_root']);
			admin_tools::SaveConfig();
		}

		return $connected;

	}

	public function ConnectOrPrompt($action=''){

		$connected = $this->connect();

		if( $connected === true ){
			return true;
		}elseif( isset($_POST['connect_values_submitted']) ){
			msg($connected);
		}
		$this->CompleteForm($_POST, $action);

		return false;
	}



	public function CompleteForm($args = false, $action=''){
		global $langmessage;

		echo '<p>';
		echo $langmessage['supply_ftp_values'];
		echo '</p>';

		if( $action === false ){
			echo '<form method="post" action="">';
		}else{
			echo '<form method="post" action="'.common::GetUrl($action).'">';
		}

		//include the current request's query so that we continue the same action after the login form is submitted
		$this->ArrayToForm($_REQUEST);

		echo '<table>';
		$this->connectForm($args);
		echo '</table>';
		echo '<input type="submit" name="" value="'.$langmessage['continue'].'..." class="gpsubmit"/>';


		echo '</form>';
	}

	public function connectForm($args = false){

		if( !is_array($args) ){
			$args = $_POST;
		}

		$args += $this->connect_vars;
		if( empty($args['ftp_server']) ){
			$args['ftp_server'] = gpftp::GetFTPServer();
		}

		echo '<tr><td>';
		echo 'FTP Hostname';
		echo '</td><td>';
		echo '<input type="hidden" name="filesystem_method" value="'.htmlspecialchars($this->method).'" />';
		echo '<input type="hidden" name="connect_values_submitted" value="true" />';
		echo '<input type="text" name="ftp_server" value="'.htmlspecialchars($args['ftp_server']).'" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'FTP Username';
		echo '</td><td>';
		echo '<input type="text" name="ftp_user" value="'.htmlspecialchars($args['ftp_user']).'" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'FTP Password';
		echo '</td><td>';
		echo '<input type="password" name="ftp_pass" value="" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'FTP Port';
		echo '</td><td>';
		echo '<input type="text" name="port" value="'.htmlspecialchars($args['port']).'" class="gpinput"/>';
		echo '</td></tr>';
	}

	public function mkdir($path){

		if( !@ftp_mkdir($this->conn_id, $path) ){
			return false;
		}

		return true;
	}

	public function unlink($path){
		return ftp_delete($this->conn_id, $path);
	}


	/**
	 * Remove a file, symlink or directory
	 * @param string $path
	 */
	public function rmdir_all($path){

		if( empty($path) ) return false;


		$pwd = @ftp_pwd($this->conn_id);

		if( !$this->is_dir($path,$pwd) ){
			return $this->unlink($path);
		}

		$this->rmdir_dir($path);

		@ftp_chdir($this->conn_id, $pwd);

		return @ftp_rmdir($this->conn_id, $path);
	}


	/**
	 * Get a list of files and folders in $dir
	 *
	 * @param string $dir
	 * @param bool $show_hidden
	 */
	public function dirlist( $dir, $show_hidden=true ){
		$pwd = @ftp_pwd($this->conn_id);

		// Cant change to folder = folder doesnt exist
		if( !@ftp_chdir($this->conn_id, $dir) ){
			return false;
		}

		@ftp_pasv($this->conn_id, true );
		$ftp_list = @ftp_nlist($this->conn_id, '.');//no arguments like "-a"!
		@ftp_chdir($this->conn_id, $pwd);

		// Empty array = non-existent folder (real folder will show . at least)
		if( empty($ftp_list) ){
			return false;
		}

		$list = array();
		foreach($ftp_list as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			if( !$show_hidden && $file{0} == '.' ){
				continue;
			}
			$list[$file] = $file;
		}

		return $list;
	}

	public function is_dir($path,$pwd = false){

		if( $pwd === false ){
			$pwd = @ftp_pwd($this->conn_id);
		}

		ob_start(); //prevent error messages
		$changed_dir = @ftp_chdir($this->conn_id, $path );
		ob_end_clean();

		if( $changed_dir ){
			$new_pwd = @ftp_pwd($this->conn_id);
			if( $path == $new_pwd || $pwd != $new_pwd ){
				return true;
			}
		}
		return false;
	}


	public function rename($old_name,$new_name){
		return ftp_rename( $this->conn_id , $old_name , $new_name );
	}

	public function put_contents($file, $contents, $type = '' ){
		if( empty($type) ){
			$type = $this->is_binary($contents) ? FTP_BINARY : FTP_ASCII;
		}

		$temp = $this->put_contents_file();
		$handle = fopen($temp,'w+');
		if( !$handle ){
			trigger_error('Could not open temporary file');
			return false;
		}

		if( fwrite($handle, $contents) ===  false ){
			fclose($handle);
			return false;
		}

		fseek($handle, 0); //Skip back to the start of the file being written to

		$ret = @ftp_fput($this->conn_id, $file, $handle, $type);

		fclose($handle);
		return $ret;
	}


	/**
	 * Get a temporary file that will be used with ftp_fput()
	 *
	 */
	private function put_contents_file(){
		global $dataDir;

		if( !is_null($this->temp_file) ){
			return $this->temp_file;
		}

		do{
			$this->temp_file = $dataDir.'/data/_updates/temp_'.md5(microtime(true));
		}while( file_exists($this->temp_file) );

		return $this->temp_file;
	}


	/**
	 * Check to see if $file exists, assumes the parent directory exists
	 * Checking for file existence with php's file_exist doesn't always work correctly for files created/deleted with ftp functions
	 *
	 */
	public function file_exists($file){

		$size = ftp_size($this->conn_id, $file);
		if( $size >= 0 ){
			return true;
		}

		return $this->is_dir($file);
	}


}

class gp_filesystem_direct extends gp_filesystem_base{
	public $method = 'gp_filesystem_direct';

}
