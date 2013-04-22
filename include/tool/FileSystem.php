<?php
defined('is_running') or die('Not an entry point...');

global $gp_filesystem;
$gp_filesystem = false;


define('gp_filesystem_direct',1);
define('gp_filesystem_ftp',2);
defined('FS_CHMOD_DIR') or define('FS_CHMOD_DIR', 0755 );
defined('FS_CHMOD_FILE') or define('FS_CHMOD_FILE', 0644 ); // 0666 causes errors on some systems


class gp_filesystem_base{
	var $conn_id = false;
	var $connect_vars = array();
	var $temp_file = false;
	var $method = 'gp_filesystem_base';


	static function init($context = false, $get_method = 'basic'){
		global $gp_filesystem;

		if( $gp_filesystem !== false ){
			return true;
		}

		if( $get_method == 'list' ){
			$method = gp_filesystem_base::get_filesystem_method_list($context);
		}else{
			$method = gp_filesystem_base::get_filesystem_method($context);
		}
		return gp_filesystem_base::set_method($method);
	}

	static function set_method($method){
		global $gp_filesystem;
		switch($method){
			case 'gp_filesystem_direct':
				$gp_filesystem = new gp_filesystem_direct();
			return true;
			case 'gp_filesystem_ftp':
				$gp_filesystem = new gp_filesystem_ftp();
			return true;
		}
		return false;
	}



	//needed for writing to the /include, .htaccess and possibly /themes files
	/* static */
	static function get_filesystem_method($context = false){
		global $dataDir;

		if( $context === false ){
			$context = $dataDir . '/include';
		}

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
	static function get_filesystem_method_list($context = array()){
		$result = 1;

		if( is_string($context) ){
			$context = array($context);
		}

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


	static function get_filesystem_method_dir($dir){
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
	 * @static
	 */
	static function get_filesystem_method_file($file){


		if( gp_is_writable($file) ){
			return gp_filesystem_direct;
		}elseif( function_exists('ftp_connect') ){
			return gp_filesystem_ftp;
		}else{
			return false;
		}
	}

	/**
	 * Get the minimum filesystem_method for $link
	 * if the target of the symbolic link doesnt exist then is_writable($file) will return false
	 * @static
	 */
	static function get_filesystem_method_link($link){

		$temp = gp_filesystem_base::TempFile( $link );
		if( @rename( $link, $temp ) ){
			@rename( $temp, $link );
			return gp_filesystem_direct;
		}

		if( function_exists('ftp_connect') ){
			return gp_filesystem_ftp;
		}else{
			return false;
		}
	}



	/*
	returns true if connectForm() is needed
	*/
	function requiresForm($context){
		return false;
	}

	function connectForm(){
		return true;
	}
	function CompleteForm(){
		return true;
	}

	function ConnectOrPrompt(){
		return true;
	}
	function connect(){
		return true;
	}
	function connect_handler($args){
		return true;
	}
	function get_base_dir(){
		global $dataDir;
		return $dataDir;
	}

	function mkdir($dir){
		return mkdir($dir,FS_CHMOD_DIR);
	}

	function unlink($path){
		return unlink($path);
	}

	function is_dir($path){
		return is_dir($path);
	}


	function rmdir_all($dir){

		if( empty($dir) ) return false;

		if( is_link($dir) ){
			return $this->unlink($dir);
		}

		if( !$this->is_dir($dir) ){
			return $this->unlink($dir);
		}

		$success = true;
		$list = $this->dirlist($dir);
		if( is_array($list) ){
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
		}

		if( !$success ){
			return false;
		}
		return rmdir($dir);
	}

	function dirlist($dir, $show_hidden=true){

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

	function rename($old_name,$new_name){
		return rename($old_name,$new_name);
	}

	function put_contents($file, $contents, $type = '' ){
		if( !gpFiles::Save($file,$contents) ){
			return false;
		}
		//the gpEasy core does not need to be world writable
		@chmod($file,FS_CHMOD_FILE);
		return true;
	}

	function get_connect_vars($args){
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

	function destruct(){
		if( $this->temp_file == false ){
			return;
		}
		if( file_exists($this->temp_file) ){
			unlink($this->temp_file);
		}
	}


	function ArrayToForm($array,$name=false){

		foreach($array as $key => $value){

			if( $name ){
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
	 * @param string $replace_from The relative path of a file
	 * @return string The path of a non-existant file in the same directory as $relative_from
	 *
	 */
	function TempFile( $relative_from ){
		static $rand_index, $dataDir;
		if( is_null($rand_index) ){
			$rand_index = rand(1000,9000);
		}

		$new_relative = $relative_from.'-'.$rand_index;
		$full_path = $dataDir.$new_relative;

		while( file_exists($full_path) ){
			$new_name = $relative_from.'-'.$rand_index;
			$full_path = $dataDir.$new_relative;
			$rand_index++;
		}

		return $new_relative;
	}


	/**
	 * Replace the directories in $replace_dirs
	 *
	 * @param array $replace_dirs The relative paths of directories to be replaced
	 * @return mixed true if successful, error string otherwise
	 *
	 */
	 function ReplaceDirs( $replace_dirs, &$clean_dirs ){
		global $langmessage, $dataDir;

		$fs_root = $this->get_base_dir();
		$trash_dirs = array();
		$completed = true;
		$message;
		foreach( $replace_dirs as $to_rel => $from_rel ){

			$to_rel = trim($to_rel,'/');
			$from_rel = trim($from_rel,'/');


			$completed = false;
			$to_full = $fs_root.'/'.$to_rel;
			$from_full = $fs_root.'/'.$from_rel;
			$trash_rel = $this->TempFile( $to_rel.'-old' );
			$trash_full = $fs_root.'/'.$trash_rel;

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

			$to_full = $fs_root.'/'.$to_rel;
			$from_full = $fs_root.'/'.$replace_dirs[$to_rel];
			$trash_full = $fs_root.'/'.$trash_rel;

			$this->rename( $to_full, $from_full );
			$this->rename( $trash_full, $to_full );
		}

		$clean_dirs = $replace_dirs;

		return $message;
	}

	function file_exists($file){
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
	function is_binary( $text ) {
		return (bool) preg_match('|[^\x20-\x7E]|', $text); //chr(32)..chr(127)
	}


}


class gp_filesystem_ftp extends gp_filesystem_base{

	var $connect_vars = array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'','port'=>'21');
	var $ftp_root = false;
	var $method = 'gp_filesystem_ftp';

	function gp_filesystem_ftp(){
		includeFile('tool/ftp.php');
	}

	function get_base_dir(){
		global $dataDir;

		if( $this->ftp_root === false ){
			$this->ftp_root = gpftp::GetFTPRoot($this->conn_id,$dataDir);
			$this->ftp_root = rtrim($this->ftp_root,'/');
		}
		return $this->ftp_root;
	}



	/**
	 * Connect to ftp using the supplied values
	 * @return mixed true on success, Error string on failure
	 */
	function connect_handler($args){
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
	function connect(){
		global $config, $dataDir, $langmessage;

		$save_values = false;
		$args = false;

		//get connection values
		$connect_args = array();
		$connection_file = $dataDir.'/data/_updates/connect.php';
		if( file_exists($connection_file) ){
			include($connection_file);
		}
		if( !isset($connection_file['ftp_user']) && isset($config['ftp_user']) ){
			$connect_args['ftp_user'] = $config['ftp_user'];
			$connect_args['ftp_server'] = $config['ftp_server'];
			$connect_args['ftp_pass'] = $config['ftp_pass'];
			$connect_args['ftp_root'] = $config['ftp_root'];
			$save_values = true;
		}
		if( isset($_POST['ftp_pass']) ){
			$connect_args = $_POST;
			$save_values = true;
		}
		$connect_args = $this->get_connect_vars($connect_args);

		$connected = $this->connect_handler($connect_args);

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
		if( !gpFiles::SaveArray($connection_file,'connect_args',$connect_args) ){
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

	function ConnectOrPrompt($action=false){

		$connected = $this->connect();

		if( $connected === true ){
			return true;
		}elseif( isset($_POST['connect_values_submitted']) ){
			message($connected);
		}
		$this->CompleteForm($_POST, $action);

		return false;
	}



	function CompleteForm($args = false, $action=false){
		global $langmessage;

		echo '<p>';
		echo $langmessage['supply_ftp_values_to_continue'];
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

	function connectForm($args = false){

		if( !is_array($args) ){
			$args = $_POST;
		}

		$args += $this->connect_vars;
		if( empty($args['ftp_server']) ){
			$args['ftp_server'] = gpftp::GetFTPServer();
		}

		echo '<input type="hidden" name="filesystem_method" value="'.htmlspecialchars($this->method).'" />';
		echo '<input type="hidden" name="connect_values_submitted" value="true" />';
		echo '<tr><td>';
			echo 'FTP Hostname';
			echo '</td><td>';
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

	function mkdir($path){

		if( !@ftp_mkdir($this->conn_id, $path) ){
			return false;
		}

		//@ftp_site($this->conn_id, sprintf('CHMOD %o %s', FS_CHMOD_DIR, $path));
		return true;
	}

	function unlink($path){
		return ftp_delete($this->conn_id, $path);
	}

	function rmdir_all($dir){

		if( empty($dir) ) return false;

		$success = true;
		$pwd = @ftp_pwd($this->conn_id);

		if( !$this->is_dir($dir,$pwd) ){
			return $this->unlink($dir);
		}

		$list = $this->dirlist($dir);

		if( is_array($list) ){

			foreach($list as $file){
				$full_path = $dir.'/'.$file;

				if( $this->is_dir($full_path,$pwd) ){
					if( !$this->rmdir_all($full_path) ){
						$success = false;
					}
				}elseif( !@ftp_delete($this->conn_id, $full_path) ){
					$success = false;
				}
			}
		}


		@ftp_chdir($this->conn_id, $pwd);

		return @ftp_rmdir($this->conn_id, $dir);
	}

	function dirlist( $dir, $show_hidden=true ){
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

	function is_dir($path,$pwd = false){

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


	function rename($old_name,$new_name){
		return ftp_rename( $this->conn_id , $old_name , $new_name );
	}

	function put_contents($file, $contents, $type = '' ){
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

	function put_contents_file(){
		global $dataDir;

		if( $this->temp_file === false ){
			do{
				$this->temp_file = $dataDir.'/data/_updates/temp_'.time();
			}while( file_exists($this->temp_file) );
		}
		return $this->temp_file;
	}


	/**
	 * Check to see if $file exists, assumes the parent directory exists
	 * Checking for file existence with php's file_exist doesn't always work correctly for files created/deleted with ftp functions
	 *
	 */
	function file_exists($file){

		$size = ftp_size($this->conn_id, $file);
		if( $size >= 0 ){
			return true;
		}

		return $this->is_dir($file);
	}


}

class gp_filesystem_direct extends gp_filesystem_base{
	var $method = 'gp_filesystem_direct';

}
