<?php
namespace gp\tool;


class FileSystemFtp extends FileSystem{

	public $connect_vars	= array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'','port'=>'21');
	public $ftp_root		= null;
	public $method			= 'gp_filesystem_ftp';

	public function get_base_dir(){
		global $dataDir;

		if( is_null($this->ftp_root) ){
			$this->ftp_root = self::GetFTPRoot($this->conn_id,$dataDir);
			$this->ftp_root = rtrim($this->ftp_root,'/');
		}
		return $this->ftp_root;
	}



	/**
	 * Connect to ftp using the supplied values
	 * @return bool
	 */
	public function connect_handler($args){
		global $langmessage;

		$args += array('ftp_server'=>'','port'=>'','ftp_user'=>'','ftp_pass'=>'');

		if( empty($args['ftp_server']) ){
			$this->connect_msg = $langmessage['couldnt_connect'].' (Missing Arguments)';
			return false;
		}
		if( empty($args['port']) ){
			$args['port'] = 21;
		}

		$this->conn_id = @ftp_connect($args['ftp_server'],$args['port'],6);

		if( !$this->conn_id ){
			$this->connect_msg = $langmessage['couldnt_connect'].' (Server Connection Failed)';
			return false;
		}

		//use ob_ to keep error message from displaying
		ob_start();
		$connected = @ftp_login($this->conn_id,$args['ftp_user'], $args['ftp_pass']);
		ob_end_clean();

		if( !$connected ){
			$this->connect_msg = $langmessage['couldnt_connect'].' (Server Connection Failed)';
			return false;
		}

		@ftp_pasv($this->conn_id, true );

		return true;
	}


	/**
	 * Connect to ftp server using either Post or saved values
	 * Connection values will not be kept in $config in case they're being used for a system revert which will replace the config.php file
	 * Also handle moving ftp connection values from $config to a sep
	 *
	 * @return bool
	 */
	public function connect(){
		global $config, $dataDir, $langmessage;


		$save_values						= false;
		$connect_args						= \gp\tool\Files::Get('_updates/connect','connect_args');

		if( !$connect_args || !isset($connect_args['ftp_user']) ){
			if( isset($config['ftp_user']) ){
				$connect_args['ftp_user']		= $config['ftp_user'];
				$connect_args['ftp_server']		= $config['ftp_server'];
				$connect_args['ftp_pass']		= $config['ftp_pass'];
				$connect_args['ftp_root']		= $config['ftp_root'];
				$save_values = true;
			}
		}

		if( isset($_POST['ftp_pass']) ){
			$connect_args					= $_POST;
			$save_values					= true;
		}

		$connect_args						= $this->get_connect_vars($connect_args);
		$connected							= $this->connect_handler($connect_args);

		if( !is_null($connected) ){
			return false;
		}

		//get the ftp_root
		if( empty($connect_args['ftp_root']) || $save_values ){

			$this->ftp_root = $this->get_base_dir();
			if( $this->ftp_root === false ){
				return $langmessage['couldnt_connect'].' (Couldn\'t find root)';
			}
			$connect_args['ftp_root'] = $this->ftp_root;
			$save_values = true;
		}else{
			$this->ftp_root = $connect_args['ftp_root'];
		}


		//save ftp info
		if( !$save_values ){
			return true;
		}

		$connection_file	= $dataDir.'/data/_updates/connect.php';
		if( !\gp\tool\Files::SaveData($connection_file,'connect_args',$connect_args) ){
			return true;
		}

		/*
		 * Remove from $config if it's not a safe mode installation
		 */
		if( !isset($config['useftp']) && isset($config['ftp_user']) ){
			unset($config['ftp_user']);
			unset($config['ftp_server']);
			unset($config['ftp_pass']);
			unset($config['ftp_root']);
			\gp\admin\Tools::SaveConfig();
		}

		return true;
	}

	public function ConnectOrPrompt($action=''){

		$connected = $this->connect();

		if( $connected === true ){
			return true;
		}

		if( isset($_POST['connect_values_submitted']) ){
			msg($this->connect_msg);
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
			echo '<form method="post" action="'.\gp\tool::GetUrl($action).'">';
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
			$args['ftp_server'] = self::GetFTPServer();
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

		if( !$this->CheckDir($file) ){
			return false;
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
	 * Make sure the directory exists for a file
	 *
	 */
	public function CheckDir($file){

		$dir = \gp\tool::DirName($file);
		if( $this->file_exists($dir) ){
			return true;
		}

		if( !$this->CheckDir($dir) ){
			return false;
		}

		return $this->mkdir($dir);
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


	/**
	 * Attempt to get the ftp_server address
	 *
	 */
	static function GetFTPServer(){

		$server = \gp\tool::ServerName(true);
		if( $server === false ){
			return '';
		}

		$conn_id = @ftp_connect($server,21,6);

		if( $conn_id ){

			@ftp_quit($conn_id);
			return $server;
		}
		return '';
	}


	/**
	 * Attempt to locate the $testDir on the ftp server
	 *
	 */
	static function GetFTPRoot($conn_id,$testDir){
		$ftp_root = false;

		//attempt to find the ftp_root
		$testDir = $testDir.'/';
		$array = ftp_nlist( $conn_id, '.');
		if( !$array ){
			return false;
		}
		$possible = array();
		foreach($array as $file){
			if( $file{0} == '.' ){
				continue;
			}

			//is the $file within the $testDir.. not the best test..
			$pos = strpos($testDir,'/'.$file.'/');
			if( $pos === false ){
				continue;
			}


			$possible[] = substr($testDir,$pos);
		}
		$possible[] = '/'; //test this too


		foreach($possible as $file){

			if( self::TestFTPDir($conn_id,$file,$testDir) ){
				$ftp_root = $file;
				break;
			}

		}
		return $ftp_root;
	}


	/**
	 * Test the $file by adding a directory and seeing if it exists in relation to the $testDir
	 * uses output buffering to prevent warnings from showing when we try a directory that doesn't exist
	 *
	 */
	static function TestFTPDir($conn_id,$file,$testDir){
		$success = false;

		ftp_chdir( $conn_id, '/');

		$random_name = 'gpeasy_random_'.rand(1000,9999);
		$random_full = rtrim($file,'/').'/'.$random_name;
		$test_full = rtrim($testDir,'/').'/'.$random_name;

		ob_start();
		if( !@ftp_mkdir($conn_id,$random_full) ){
			ob_end_clean();
			return false;
		}
		ob_end_clean();

		if( file_exists($test_full) ){
			$success = true;
		}

		ftp_rmdir($conn_id,$random_full);

		return $success;
	}

}
