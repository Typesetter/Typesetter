<?php
namespace gp\install;


class Installer{

	/**
	 * Whether or not installation checks have passed
	 * 		2	= All checks have passed
	 * 		1	= One or more checks passed with partial availability
	 * 		0	= One or more checks failed but have common solutions
	 * 		-1	= One or more checks failed
	 *
	 * @var int
	 */
	public $can_install			= 2;

	public $can_write_data		= true;
	public $ftp_root			= false;
	public $root_mode;
	private $lang				= 'en';
	public $statuses			= [];

	private $ftp_connection = false;



	public function __construct(){
		global $languages;

		//language preferences
		if( isset($_GET['lang']) && isset($languages[$_GET['lang']]) ){
			$this->lang = $_GET['lang'];
			setcookie('lang',$this->lang);

		}elseif( isset($_COOKIE['lang']) && isset($languages[$_COOKIE['lang']]) ){
			$this->lang = $_COOKIE['lang'];
		}

		\gp\tool::GetLangFile('main.inc',$this->lang);

		// installation checks
		$this->CheckDataFolder();
		$this->CheckPHPVersion();
		$this->CheckEnv();
		$this->CheckMemory();
		$this->CheckImages();
		$this->CheckArchives();
		$this->CheckPath();
		$this->CheckIndexHtml();
	}


	public function Run(){

		$installed	= false;
		$cmd		= \gp\tool::GetCommand();

		switch($cmd){

			case 'Continue':
				$this->FTP_Prepare();
			break;

			case 'Install':
				$installed = $this->Install_Normal();
			break;
		}

		if( !$installed ){
			$this->LanguageForm();
			$this->DisplayStatus();
		}else{
			$this->Installed();
		}

	}


	/**
	 * Display check statuses
	 *
	 */
	public function DisplayStatus(){
		global $langmessage;

		echo '<h2>'.$langmessage['Checking_server'].'...</h2>';
		echo '<table class="styledtable fullwidth">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th>'.$langmessage['Current_Value'].'</th>';
		echo '<th>'.$langmessage['Expected_Value'].'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach($this->statuses as $row){
			echo '<tr><td>';
			echo $row['checking'];
			echo '</td>';

			if( $row['can_install'] === 2 ){
				$class = 'passed';
				$label = 'Passed';

			}elseif( $row['can_install'] === 1 ){
				$class = 'passed_orange';
				$label = 'Passed';

			}elseif( $row['can_install'] === 0 ){
				$class = 'passed_orange';
				$label = 'Failed';

			}else{
				$class = 'passed_orange';
				$label = 'Failed';
			}

			if( !empty($row['label']) ){
				$label = $row['label'];
			}

			echo '<td class="'.$class.'">'.$label.'</td>';
			echo '<td class="'.$class.'">'.$row['curr_value'].'</td>';
			echo '<td>'.$row['expected'].'</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

		echo '<p>';
		echo \gp\tool::Link('',$langmessage['Refresh']);
		echo '</p>';
		echo '<br/>';

		if( $this->can_install > 0 ){
			$this->Form_Entry();
			return;
		}

		if( !$this->can_write_data ){
			$this->Form_Permissions();
		}else{
			echo '<h3>'.$langmessage['Notes'].'</h3>';
			echo '<div>';
			echo $langmessage['Install_Conflict'];
			echo '</div>';
			echo '<p>';
			echo sprintf($langmessage['Install_Fix'],'');
			echo '</p>';
		}

	}



	function SetStatus($checking, $can_install, $curr_value, $expected = '', $status_label = ''){

		if( $can_install < $this->can_install ){
			$this->can_install = $can_install;
		}

		$this->statuses[] = [
									'checking'			=> $checking,
									'can_install'		=> $can_install,
									'curr_value'		=> $curr_value,
									'expected'			=> $expected,
									'label'				=> $status_label
								];

	}


	/**
	 * Check the data folder to see if it's writable
	 *
	 */
	public function CheckDataFolder(){
		global $dataDir,$langmessage;

		$folder = $dataDir.'/data';
		if( strlen($folder) > 33 ){
			$show = '...'.substr($folder,-30);
		}else{
			$show = $folder;
		}


		$status			= null;
		$class			= 'passed';
		$can_install	= 2;


		if( !is_dir($folder)){
			if( !@mkdir($folder) ){
				$class					= 'passed_orange';
				$status					= $langmessage['See_Below'].' (0)';
				$this->can_write_data	= false;
			}
		}elseif( !gp_is_writable($folder) ){
			$class						= 'passed_orange';
			$status						= $langmessage['See_Below'].' (1)';
			$this->can_write_data		= false;
		}

		if( $this->can_write_data ){
			$current				= $langmessage['Writable'];
		}else{
			$current				= $langmessage['Not Writable'];
			$can_install			= 0;
		}

		$this->SetStatus( $show, $can_install, $current, $langmessage['Writable'], $status);
	}


	/**
	 * Check the php version
	 *
	 */
	private function CheckPHPVersion(){
		global $langmessage;

		$version		= phpversion();
		$can_install	= 2;

		if( version_compare($version,'5.4','<') ){
			$can_install		= -1;
		}

		$this->SetStatus($langmessage['PHP_Version'], $can_install, $version, '5.4+');
	}


	/**
	 * Check the env for server variables
	 *
	 */
	private function CheckEnv(){
		global $langmessage;

		//make sure $_SERVER['SCRIPT_NAME'] is set
		$checking		= '<a href="http://www.php.net/manual/reserved.variables.server.php" target="_blank">SCRIPT_NAME or PHP_SELF</a>';
		$can_install	= 2;
		$expected		= $langmessage['Set'];
		$curr			= $langmessage['Set'];

		if( !\gp\tool::GetEnv('SCRIPT_NAME','index.php') && !\gp\tool::GetEnv('PHP_SELF','index.php') ){
			$curr			= $langmessage['Not_Set'];
			$can_install	= -1;
		}

		$this->SetStatus($checking, $can_install, $curr, $expected);
	}


	/**
	 * Check php's memory limit
	 * LESS compilation uses a fair amount of memory
	 */
	private function CheckMemory(){

		$checkValue 	= ini_get('memory_limit');
		$expected		= '16M+ or Adjustable';
		$checking		= '<a href="http://php.net/manual/ini.core.php#ini.memory-limit" target="_blank">Memory Limit</a>';
		$curr			= $checkValue;


		// adjustable
		if( @ini_set('memory_limit','96M') !== false ){
			$this->SetStatus( $checking, 2, $checkValue .' and adjustable', $expected);
			return;
		}

		// cant check memory
		if( !$checkValue ){
			$this->SetStatus( $checking, 1, '???', $expected);
			return;
		}


		$byte_value = \gp\tool::getByteValue($checkValue);
		$mb_16		= \gp\tool::getByteValue('16M');


		if( $byte_value > 100663296 ){
			$this->SetStatus( $checking, 2, $checkValue, $expected);

		}elseif( $byte_value >= $mb_16 ){
			$this->SetStatus( $checking, 1, $checkValue, $expected);

		}else{
			$this->SetStatus( $checking, 0, $checkValue, $expected);
		}

	}

	/**
	 * Very unlikely, ".php" cannot be in the directory name. see SetGlobalPaths()
	 *
	 */
	public function CheckPath(){
		global $langmessage;

		$dir		= dirname(__FILE__);
		$checking	= 'Install Directory';
		$curr		= str_replace('.php','<b>.php</b>',$dir);

		if( strpos($dir,'.php') === false ){
			$this->SetStatus( $checking, 2, $curr );
			return;
		}


		$this->SetStatus( $checking, 0, $curr , 'Rename your file structure so that directories do not use ".php".');
	}


	/**
	 * Warn user if there's an index.html file
	 *
	 */
	public function CheckIndexHtml(){
		global $langmessage, $dataDir;

		$show	= 'Existing index.html';
		$index	= $dataDir.'/index.html';

		if( file_exists($index) ){
			$this->SetStatus( $show, 1, $index, $langmessage['index.html exists']);
		}else{
			$this->SetStatus( $show, 2, '', '');
		}
	}


	/**
	 * Check for image manipulation functions
	 *
	 */
	public function CheckImages(){
		global $langmessage;

		$supported = array();
		if( function_exists('imagetypes') ){

			$supported_types = imagetypes();
			if( $supported_types & IMG_JPG ){
				$supported[] = 'jpg';
			}
			if( $supported_types & IMG_PNG){
				$supported[] = 'png';
			}
			if( $supported_types & IMG_WBMP){
				$supported[] = 'bmp';
			}
			if( $supported_types & IMG_GIF){
				$supported[] = 'gif';
			}
			if( $supported_types & IMG_WEBP ){
				$supported[] = 'webp';
			}
		}



		$checking				= '<a href="http://www.php.net/manual/en/book.image.php" target="_blank">'.$langmessage['image_functions'].'</a>';
		$supported_string		= implode(', ',$supported);

		if( count($supported) >= 4 ){
			$this->SetStatus( $checking, 2, $supported_string);

		}elseif( count($supported) > 0 ){

			$this->SetStatus( $checking, 1, $supported_string,'',$langmessage['partially_available']);

		}else{
			$this->SetStatus( $checking, 1, $supported_string,'',$langmessage['unavailable']);
		}
	}


	/**
	 * Check for archive processing capabilities
	 *
	 */
	public function CheckArchives(){
		global $langmessage;

		$supported = array();

		if( class_exists('\ZipArchive') ){
			$supported['zip'] = 'zip';
		}

		if( class_exists('\PharData') ){
			if( !defined('HHVM_VERSION') || !ini_get('phar.readonly') ){
				if( function_exists('gzopen') ){
					$supported['tgz'] = 'gzip';
				}
				if( function_exists('bzopen') ){
					$supported['tbz'] = 'bzip';
				}
				$supported['tar'] = 'tar';
			}
		}

		$checking				= '<a href="https://www.php.net/manual/en/refs.compression.php" target="_blank">Archive Extensions</a>';
		$supported_string		= implode(', ',$supported);

		if( count($supported) == 4 ){
			$this->SetStatus( $checking, 2, $supported_string);

		}elseif( count($supported) > 0 ){
			$this->SetStatus( $checking, 1, $supported_string, '', $langmessage['partially_available'] );

		}else{
			$this->SetStatus( $checking, 1, $supported_string, '', $langmessage['unavailable'] );
		}

	}



	/**
	 * Change the permissions of the data directory
	 *
	 */
	public function FTP_Prepare(){
		global $langmessage;

		echo '<h2>'.$langmessage['Using_FTP'].'...</h2>';
		echo '<ul>';
		$this->_FTP_Prepare();
		$this->FTP_RestoreMode();
		echo '</ul>';
	}


	public function _FTP_Prepare(){

		if( !$this->FTPConnection() ){
			return;
		}

		if( $this->FTP_DataFolder() ){
			return;
		}

		$this->FTP_DataMode();
	}

	/**
	 * If recreating the data folder with php doesn't work
	 * Attempt to change the mode of the folder directly
	 *
	 */
	public function FTP_DataMode(){
		global $langmessage, $dataDir;

		//Change Mode of /data
		$ftpData = $this->ftp_root.'/data';
		$modDir = ftp_site($this->ftp_connection, 'CHMOD 0777 '. $ftpData );
		if( !$modDir ){
			echo '<li><span class="failed">';
			echo sprintf($langmessage['Could_Not_'],'<em>CHMOD 0777 '. $ftpData.'</em>');
			echo '</span></li>';
			return false;
		}

		echo '<li><span class="passed">';
		echo sprintf($langmessage['FTP_PERMISSIONS_CHANGED'],'<em>'.$ftpData.'</em>');
		echo '</span></li>';



		//passed
		echo '<li><span class="passed"><b>';
		echo $langmessage['Success_continue_below'];
		echo '</b></span></li>';
	}


	/**
	 * Create the data folder with appropriate permissions
	 *
	 * 1) make parent directory writable
	 * 2) delete existing /data folder
	 * 3) create /data folder with php's mkdir() (so it has the correct owner)
	 * 4) restore parent directory
	 *
	 */
	public function FTP_DataFolder(){
		global $dataDir, $langmessage;

		$this->root_mode = fileperms($dataDir);
		if( !$this->root_mode ){
			return false;
		}


		// (1)
		$modDir = ftp_site($this->ftp_connection, 'CHMOD 0777 '. $this->ftp_root );
		if( !$modDir ){
			echo '<li><span class="failed">';
			echo sprintf($langmessage['Could_Not_'],'<em>CHMOD 0777 '. $this->ftp_root.'</em>');
			echo '</span></li>';
			return false;
		}

		// (2) use rename instead of trying to delete recursively
		$php_dir	= $dataDir.'/data';
		$php_del	= false;

		if( file_exists($php_dir) ){

			$ftp_dir	= rtrim($this->ftp_root,'/').'/data';
			$del_name	= '/data-delete-'.rand(0,10000);
			$ftp_del	= rtrim($this->ftp_root,'/').$del_name;
			$php_del	= $dataDir.$del_name;

			$changed	= ftp_rename($this->ftp_connection, $ftp_dir , $ftp_del );
			if( !$changed ){
				echo '<li><span class="failed">';
				echo sprintf($langmessage['Could_Not_'],'<em>Remove '. $this->ftp_root.'/data</em>');
				echo '</span></li>';
				return false;
			}
		}


		// (3) use rename instead of trying to delete recursively
		$mode = 0755;
		if( defined(gp_chmod_dir) ){
			$mode = gp_chmod_dir;
		}
		if( !mkdir($php_dir,$mode) ){
			echo '<li><span class="failed">';
			echo sprintf($langmessage['Could_Not_'],'<em>mkdir('.$php_dir.')</em>');
			echo '</span></li>';
			return false;
		}


		// (4) will be done afterwards


		// make sure it's writable ?
		clearstatcache();
		if( !gp_is_writable($php_dir) ){
			return false;
		}

		echo '<li><span class="passed"><b>';
		echo $langmessage['Success_continue_below'];
		echo '</b></span></li>';

		if( $php_del ){
			$this->CopyData($php_del, $php_dir);
		}

		return true;
	}

	/**
	 * Copy files from the "deleted" data folder to the new data folder
	 *
	 */
	public function CopyData($from_dir, $to_dir){

		$files = scandir($from_dir);
		foreach($files as $file){

			if( $file === '..' || $file === '.' ){
				continue;
			}

			$from = $from_dir.'/'.$file;

			//no directories
			if( is_dir($from) ){
				continue;
			}

			$to = $to_dir.'/'.$file;
			copy($from,$to);
		}
	}


	/**
	 * Restore the mode of the root directory to it's original mode
	 *
	 */
	public function FTP_RestoreMode(){
		global $langmessage;

		if( !$this->root_mode || !$this->ftp_connection ){
			return;
		}


		$mode		= $this->root_mode & 0777;
		$mode		= '0'.decoct($mode);
		$ftp_cmd	= 'CHMOD '.$mode.' '.$this->ftp_root;

		if( !ftp_site($this->ftp_connection, $ftp_cmd ) ){
			echo '<li><span class="failed">';
			echo sprintf($langmessage['Could_Not_'],'<em>Restore mode for '. $this->ftp_root.': '.$ftp_cmd.'</em>');
			echo '</span></li>';
			return;
		}
	}


	/**
	 * Establish an FTP connection to be used by the installer
	 *
	 */
	public function FTPConnection(){
		global $dataDir, $langmessage;


		//test for functions
		if( !function_exists('ftp_connect') ){
			echo '<li>';
			echo '<span class="failed">';
			echo $langmessage['FTP_UNAVAILABLE'];
			echo '</span>';
			echo '</li>';
			return false;
		}


		//Try to connect
		echo '<li>';
		$this->ftp_connection = @ftp_connect($_POST['ftp_server'],21,6);
		if( !$this->ftp_connection ){
			echo '<span class="failed">';
			echo sprintf($langmessage['FAILED_TO_CONNECT'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
			echo '</span>';
			echo '</li>';
			return false;
		}

		echo '<span class="passed">';
		echo sprintf($langmessage['CONNECTED_TO'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
		echo '</span></li>';


		//Log in
		echo '<li>';
		$login_result = @ftp_login($this->ftp_connection, $_POST['ftp_user'], $_POST['ftp_pass']);
		if( !$login_result ){
			echo '<span class="failed">';
			echo sprintf($langmessage['NOT_LOOGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
			echo '</span></li>';
			return false;
		}

		echo '<span class="passed">';
		echo sprintf($langmessage['LOGGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
		echo '</span></li>';


		//Get FTP Root
		echo '<li>';
		if( $login_result ){
			$this->ftp_root = \gp\tool\FileSystemFtp::GetFTPRoot($this->ftp_connection,$dataDir);
		}
		if( $this->ftp_root === false ){
			echo '<span class="failed">';
			echo $langmessage['ROOT_DIRECTORY_NOT_FOUND'];
			echo '</span>';
			echo '</li>';
			return false;
		}

		echo '<span class="passed">';
		echo sprintf($langmessage['FTP_ROOT'],'<em>'.$this->ftp_root.'</em>');
		echo '</span></li>';

		return true;
	}


	public function Form_Permissions(){
		global $langmessage,$dataDir;

		echo '<div>';
		echo '<h2>'.$langmessage['Changing_File_Permissions'].'</h2>';
		echo '<p>';
		echo $langmessage['REFRESH_AFTER_CHANGE'];
		echo '</p>';

		echo '<table class="styledtable fullwidth">';

		//manual method
		echo '<tr><th>';
		echo $langmessage['manual_method'];
		echo '</th></tr>';
		echo '<tr><td><p>';
		echo $langmessage['LINUX_CHOWN'];
		echo '</p>';

		$owner = $this->GetPHPOwner();
		if( $owner ){
			echo '<tt class="code">chown '.$owner.' "'.$dataDir.'/data"</tt>';
			echo '<small>Note: "'.$owner.'" appears to be the owner uid of PHP on your server</small>';
		}else{
			echo '<tt class="code">chown ?? "'.$dataDir.'/data"</tt>';
			echo '<small>Replace ?? with the owner uid of PHP on your server</small>';
		}

		echo '<p><a href="">'.$langmessage['Refresh'].'</a></p>';
		echo '</td></tr>';

		//ftp
		echo '<tr><th>FTP</th></tr>';
		echo '<tr><td><p>';
		echo $langmessage['MOST_FTP_CLIENTS'];
		echo '</p>';

		echo '<p>Using your FTP client, we recommend the following steps to make the data directory writable</p>';

		echo '<ol>';
		echo '<li>Make "'.$dataDir.'" writable</li>';
		echo '<li>Delete "'.$dataDir.'/data"</li>';
		echo '<li>Run '.CMS_NAME.' Installer by refreshing this page</li>';
		echo '<li>Restore the permissions of "'.$dataDir.'"</li>';
		echo '</ol>';

		echo '</td></tr>';


		//
		if( function_exists('ftp_connect') ){
			echo '<tr><th>';
			echo $langmessage['Installer'];
			echo '</th></tr>';
			echo '<tr><td>';
			echo '<p>';
			echo $langmessage['FTP_CHMOD'];
			echo '</p>';
			$this->Form_FTPDetails();
			echo '</td></tr>';
		}



		echo '</table>';
		echo '</div>';
	}

	/**
	 * Attempt to get the owner of php
	 *
	 */
	public function GetPHPOwner(){
		global $dataDir;

		if( !function_exists('fileowner') ){
			return;
		}


		$name = tempnam( sys_get_temp_dir(), 'gpinstall-' );
		if( !$name ){
			return;
		}

		return fileowner($name);
	}


	public function Form_FTPDetails(){
		global $langmessage;

		$_POST += array('ftp_server'=>\gp\tool\FileSystemFtp::GetFTPServer(),'ftp_user'=>'');

		echo '<form action="'.\gp\tool::GetUrl('').'" method="post">';
		echo '<table class="padded_table">';
		echo '<tr><td align="left">'.$langmessage['FTP_Server'].' </td><td>';
		echo '<input type="text" class="text" size="20" name="ftp_server" value="'. htmlspecialchars($_POST['ftp_server']) .'" required />';
		echo '</td></tr>';

		echo '<tr><td align="left">'.$langmessage['FTP_Username'].' </td><td>';
		echo '<input type="text" class="text" size="20" name="ftp_user" value="'. htmlspecialchars($_POST['ftp_user']) .'" />';
		echo '</td></tr>';

		echo '<tr><td align="left">'.$langmessage['FTP_Password'].' </td><td>';
		echo '<input type="password" class="text" size="20" name="ftp_pass" value="" />';
		echo '</td></tr>';

		echo '<tr><td align="left">&nbsp;</td><td>';
		echo '<input type="hidden" name="cmd" value="Continue" />';
		echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		echo '</td></tr>';
		echo '</table>';
		echo '</form>';

	}



	public function LanguageForm(){
		global $languages;

		echo '<div class="lang_select">';
		echo '<form action="'.\gp\tool::GetUrl('').'" method="get">';
		echo '<select name="lang" onchange="this.form.submit()">';
		foreach($languages as $lang => $label){
			if( $lang === $this->lang ){
				echo '<option value="'.$lang.'" selected="selected">';
			}else{
				echo '<option value="'.$lang.'">';
			}
			echo '&nbsp; '.$label.' &nbsp; ('.$lang.')';
			echo '</option>';
		}

		echo '</select>';
		echo '<div class="sm">';
		echo '<a href="https://github.com/Typesetter/Typesetter/tree/master/include/languages" target="_blank">Help translate '.CMS_NAME.'</a>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}


	public function Installed(){
		global $langmessage;
		echo '<h4>'.$langmessage['Installation_Was_Successfull'].'</h4>';

		echo '<h2>';
		echo \gp\tool::Link('',$langmessage['View_your_web_site']);
		echo '</h2>';

		echo '</ul>';

		echo '<p>';
		echo 'For added security, you may delete the /include/install/install.php file from your server.';
		echo '</p>';
	}


	public function Form_Entry(){
		global $langmessage;

		echo '<form action="'.\gp\tool::GetUrl('').'" method="post">';
		echo '<table class="styledtable">';
		\gp\install\Tools::Form_UserDetails();
		\gp\install\Tools::Form_Configuration();
		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="Install" />';
		echo '<input type="submit" class="submit install_button" name="aaa" value="'.$langmessage['Install'].'" />';
		echo '</p>';
		echo '</form>';
	}


	public function Install_Normal(){
		global $langmessage;

		echo '<h2>'.$langmessage['Installing'].'</h2>';
		echo '<ul class="install_status">';

		$config 				= [];
		$config['language'] 	= $this->lang;

		$success = false;
		if( \gp\install\Tools::gpInstall_Check() ){
			$success = \gp\install\Tools::Install_DataFiles_New(false, $config);
		}
		echo '</ul>';

		return $success;
	}


}
