<?php
defined('is_running') or die('Not an entry point...');

global $langmessage, $install_ftp_connection;
$install_ftp_connection = false;
ob_start();


?>

<!DOCTYPE html>
<html>
<head>
<title>Typesetter Installation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="noindex,nofollow"/>
<script type="text/javascript">

function toggleOptions(){
	var options = document.getElementById('config_options').style;
	if( options.display == '' ){
		options.display = 'none';
	}else{
		options.display = '';
	}
}

</script>

<style type="text/css">

body{
	margin:1em 5em;
	font-family:"Segoe UI","San Francisco","DejaVu Sans","Helvetica Neue",Arial,sans-serif;
	background:#f1f1f1;
	font-size:13px;
}

td,th{
	font-size:12px;
	}

a{
	color:#4466aa;
	text-decoration:none;
	border-bottom:1px dotted #869ece;
	}

h1{
	margin-top:0;
	padding-top:0;
	font-size:30px;
	font-weight:normal;
	}
h2{
	font-weight:normal;
	font-size:20px;
}
h3,h4{
	font-weight:normal;
}


.wrapper{
	position:relative;
	width:800px;
	background:#fff;
	margin:0 auto;
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	-o-border-radius: 10px;
	border-radius: 10px;
	padding: 23px;
	border:1px solid #fff;
	border:1px solid #bbb;
}

.fullwidth{
	width:100%;
	}
.styledtable{
	border-collapse:collapse;
	border-bottom: 1px solid #ccc;
	border-spacing:0;
	}
.styledtable td, .styledtable th {
	border-top: 1px solid #eee;
	padding: 5px 20px;
	text-align:left;
	vertical-align:top;
	}
.styledtable th{
	border-top:1px solid #ccc;
	border-bottom:1px solid #ccc;
	background-color:#eee;
	font-weight:normal;
	white-space:nowrap;
	}
.styledtable tr{
	background: #fff;
	}
.styledtable tbody tr:nth-child(odd){
	background: #fbfbfb;
	}

.styledtable table td{
	padding:1px;
	border:0 none;
	}
.padded_table{
	border-collapse:collapse;
}
.padded_table > tbody > tr > td{
	padding:5px 8px;
}


.lang_select{
	position:absolute;
	top:23px;
	right:23px;
}

.lang_select select,
.install_button{
	font-size:130%;
	padding:7px 9px;
}
.lang_select option{
}

.sm{
	font-size:smaller;
}
input.text{
	font-size:12px;
	padding:4px 6px;
	width:20em;
	border:1px solid #aaa;
	margin:3px;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	-o-border-radius: 3px;
	border-radius: 3px;
}

input.text:focus{
	border-color:#333;
	}
.failed{
	color:#FF0000;
}


.passed{
	color:#009900;
}
.passed_orange{
	color:orange;
}

.code{
	margin:4px 0;
	padding:5px 7px;
	white-space:nowrap;
	background-color:#f5f5f5;
	display:block;
	}
.nowrap{
	white-space:nowrap;
}

ul.install_status{
	list-style:none;
	margin:0;
	padding:0;
}
ul.install_status li{
	margin:4px 0;
	padding:4px 7px;
}

ul.install_status .failed{
	border: 1px solid #e6da93;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	-o-border-radius:5px;
	border-radius:5px;
	background:#fff2a3;
	color:#4d4931;


}


</style>

</head>
<body>
<div class="wrapper">

<?php

new gp_install();
echo \gp\tool::ErrorBuffer(false);
?>

</div>
</body></html>

<?php


//Install Class
class gp_install{

	public $can_write_data		= true;
	public $ftp_root			= false;
	public $root_mode;
	private $passed				= true;



	public function __construct(){
		global $languages,$install_language,$langmessage;

		//language preferences
			$install_language = 'en';

			if( isset($_GET['lang']) && isset($languages[$_GET['lang']]) ){
				$install_language = $_GET['lang'];

			}elseif( isset($_COOKIE['lang']) && isset($languages[$_COOKIE['lang']]) ){
				$install_language = $_COOKIE['lang'];
			}
			setcookie('lang',$install_language);

			\gp\tool::GetLangFile('main.inc',$install_language);


		echo '<h1>';
		echo $langmessage['Installation'];
		echo ' - v'.gpversion;
		echo '</h1>';

		$installed = false;
		$cmd = \gp\tool::GetCommand();
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
			$this->CheckFolders();
		}else{
			$this->Installed();
		}

	}

	/**
	 * Installation checks
	 *
	 */
	public function CheckFolders(){
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

		$this->CheckDataFolder();
		$this->CheckPHPVersion();
		$this->CheckEnv();
		$this->CheckSafeMode();
		$this->CheckGlobals();
		$this->CheckMagic();
		$this->CheckMemory();




		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th colspan="2">'.$langmessage['Notes'].'</th>';
		echo '</tr>';
		echo '</tbody>';
		echo '<tbody>';
		$this->CheckIndexHtml();
		$this->CheckImages();
		$this->CheckPath();
		echo '</tbody>';


		echo '</table>';
		echo '<p>';

		echo \gp\tool::Link('',$langmessage['Refresh']);
		echo '</p>';
		echo '<br/>';

		if( $this->passed ){
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

	/**
	 * Output a status row
	 *
	 */
	public function StatusRow($value_ok, $label_true, $label_false){
		global $langmessage;

		if( $value_ok ){
			$this->StatusRowFormat('passed',$label_true, $label_true);
		}else{
			$this->StatusRowFormat('failed',$label_false, $label_true);
			$this->passed = false;
		}

	}

	private function StatusRowFormat($class, $curr, $expected, $status = null){
		global $langmessage;

		if( is_null($status) ){
			switch($class){
				case 'failed':
				$status = $langmessage['Failed'];
				break;

				case 'passed_orange':
				case 'passed':
				$status = $langmessage['Passed'];
				break;
			}
		}

		echo '<td class="'.$class.'">'.$status.'</td>';
		echo '<td class="'.$class.'">'.$curr.'</td>';
		echo '<td>'.$expected.'</td>';
		echo '</tr>';
	}


	/**
	 * Check the data folder to see if it's writable
	 *
	 */
	public function CheckDataFolder(){
		global $dataDir,$langmessage;

		echo '<tr><td class="nowrap">';
		$folder = $dataDir.'/data';
		if( strlen($folder) > 33 ){
			$show = '...'.substr($folder,-30);
		}else{
			$show = $folder;
		}
		echo $show;
		echo ' &nbsp; </td>';

		$status = null;
		$class = 'passed';


		if( !is_dir($folder)){
			if( !@mkdir($folder, 0777) ){
				$class	= 'passed_orange';
				$status	= $langmessage['See_Below'].' (0)';
				$this->can_write_data = false;
				$this->passed = false;
			}
		}elseif( !gp_is_writable($folder) ){
			$class		= 'passed_orange';
			$status		= $langmessage['See_Below'].' (1)';
			$this->can_write_data = false;
			$this->passed = false;
		}

		if( $this->can_write_data ){
			$current = $langmessage['Writable'];
		}else{
			$current = $langmessage['Not Writable'];
		}


		$this->StatusRowFormat($class,$current,$langmessage['Writable'], $status);
	}


	/**
	 * Check the php version
	 *
	 */
	private function CheckPHPVersion(){
		global $langmessage;

		$version = phpversion();
		$class = 'passed';

		echo '<tr><td>';
		echo $langmessage['PHP_Version'];
		echo '</td>';

		if( version_compare($version,'5.3','<') ){
			$class = 'failed';
			$this->passed = false;
		}

		$this->StatusRowFormat($class,$version,'5.3+');
	}


	/**
	 * Check the env for server variables
	 *
	 */
	private function CheckEnv(){
		global $langmessage;

		//make sure $_SERVER['SCRIPT_NAME'] is set
		echo '<tr><td>';
		echo '<a href="http://www.php.net/manual/reserved.variables.server.php" target="_blank">';
		echo 'SCRIPT_NAME or PHP_SELF';
		echo '</a>';
		echo '</td>';
		$checkValue = \gp\tool::GetEnv('SCRIPT_NAME','index.php') || \gp\tool::GetEnv('PHP_SELF','index.php');
		$this->StatusRow($checkValue,$langmessage['Set'],$langmessage['Not_Set']);
	}


	/**
	 * Check php's safe mode setting
	 *
	 */
	private function CheckSafeMode(){
		global $langmessage;

		$checkValue = !\gp\tool::IniGet('safe_mode');
		echo '<tr><td>';
		echo '<a href="http://php.net/manual/features.safe-mode.php" target="_blank">';
		echo 'Safe Mode';
		echo '</a>';
		echo '</td>';

		$this->StatusRow($checkValue, $langmessage['Off'], $langmessage['On']);
	}


	/**
	 * Check the register globals setting
	 *
	 */
	private function CheckGlobals(){
		global $langmessage;

		$checkValue = \gp\tool::IniGet('register_globals');
		echo '<tr><td>';
		echo '<a href="http://php.net/manual/security.globals.php" target="_blank">';
		echo 'Register Globals';
		echo '</a>';
		echo '</td>';
		if( $checkValue ){
			$this->StatusRowFormat('passed_orange',$langmessage['On'],$langmessage['Off']);
		}else{
			$this->StatusRowFormat('passed',$langmessage['Off'],$langmessage['Off']);
		}
	}


	/**
	 * Check magic_quotes_sybase and magic_quotes_runtime
	 *
	 */
	private function CheckMagic(){
		global $langmessage;

		// magic_quotes_sybase
		$checkValue = !\gp\tool::IniGet('magic_quotes_sybase');
		echo '<tr><td>';
		echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
		echo 'Magic Quotes Sybase';
		echo '</a>';
		echo '</td>';
		$this->StatusRow($checkValue,$langmessage['Off'],$langmessage['On']);

		//magic_quotes_runtime
		$checkValue = !\gp\tool::IniGet('magic_quotes_runtime');
		echo '<tr><td>';
		echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
		echo 'Magic Quotes Runtime';
		echo '</a>';
		echo '</td>';
		$this->StatusRow($checkValue,$langmessage['Off'],$langmessage['On']);
	}

	/**
	 * Check php's memory limit
	 * LESS compilation uses a fair amount of memory
	 */
	private function CheckMemory(){

		$checkValue = ini_get('memory_limit');
		$expected	= '16M+ or Adjustable';
		echo '<tr><td>';
		echo '<a href="http://php.net/manual/ini.core.php#ini.memory-limit" target="_blank">';
		echo 'Memory Limit';
		echo '</a>';
		echo '</td>';

		// adjustable
		if( @ini_set('memory_limit','96M') !== false ){
			$this->StatusRow('passed','Adjustable',$expected);
			return;
		}

		// cant check memory
		if( !$checkValue ){
			$this->StatusRow('passed_orange','???',$expected);
			return;
		}


		$byte_value = \gp\tool::getByteValue($checkValue);
		$mb_16		= \gp\tool::getByteValue('16M');


		if( $byte_value > 100663296 ){
			$this->StatusRow('passed',$checkValue,$expected);

		}elseif( $byte_value >= $mb_16 ){
			$this->StatusRow('passed_orange',$checkValue,$expected);

		}else{
			$this->StatusRow('failed',$checkValue,$expected);
			$this->passed = false;
		}

	}

	/**
	 * Very unlikely, ".php" cannot be in the directory name. see SetGlobalPaths()
	 *
	 */
	public function CheckPath(){
		global $langmessage;

		$dir	= dirname(__FILE__);

		if( strpos($dir,'.php') === false ){
			return;
		}

		echo '<tr><td class="nowrap">';
		if( strlen($dir) > 30 ){
			echo '...'.substr($dir,-27);
		}else{
			echo $dir;
		}
		echo '</td>';
		echo '<td class="failed">'.$langmessage['Failed'].'</td>';
		echo '<td class="failed" colspan="2">';
		echo str_replace('.php','<b>.php</b>',$dir);
		echo '<br/>';
		echo 'Your installation directory contains the string ".php".';
		echo ' To Continue, rename your file structure so that directories do not use ".php".';
		echo '</td></tr>';


		$this->passed = false;
	}


	/**
	 * Warn user if there's an index.html file
	 *
	 */
	public function CheckIndexHtml(){
		global $langmessage, $dataDir;

		$index = $dataDir.'/index.html';


		echo '<tr><td>';

		if( strlen($index) > 30 ){
			echo '...'.substr($index,-27);
		}else{
			echo $index;
		}
		echo '</td>';

		if( !file_exists($index) ){
			$this->StatusRowFormat('passed','','');
		}else{
			$this->StatusRowFormat('passed_orange',$langmessage['index.html exists'],'');
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
			if( $supported_types & IMG_PNG) {
				$supported[] = 'png';
			}
			if( $supported_types & IMG_WBMP) {
				$supported[] = 'bmp';
			}
			if( $supported_types & IMG_GIF) {
				$supported[] = 'gif';
			}
		}



		echo '<tr><td>';
		echo '<a href="http://www.php.net/manual/en/book.image.php" target="_blank">';
		echo $langmessage['image_functions'];
		echo '</a>';
		echo '</td>';
		if( count($supported) > 0 ){

			if( count($supported) == 4 ){
				$this->StatusRowFormat('passed',implode(', ',$supported),'');
			}else{
				$this->StatusRowFormat('passed_orange',implode(', ',$supported),'',$langmessage['partially_available'] );
			}

		}else{
			$this->StatusRowFormat('passed_orange',$langmessage['unavailable'],'');
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
		global $langmessage, $install_ftp_connection, $dataDir;

		//Change Mode of /data
		$ftpData = $this->ftp_root.'/data';
		$modDir = ftp_site($install_ftp_connection, 'CHMOD 0777 '. $ftpData );
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
		global $dataDir, $install_ftp_connection, $langmessage;

		$this->root_mode = fileperms($dataDir);
		if( !$this->root_mode ){
			return false;
		}


		// (1)
		$modDir = ftp_site($install_ftp_connection, 'CHMOD 0777 '. $this->ftp_root );
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

			$changed	= ftp_rename($install_ftp_connection, $ftp_dir , $ftp_del );
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
		global $install_ftp_connection, $langmessage;

		if( !$this->root_mode || !$install_ftp_connection ){
			return;
		}


		$mode		= $this->root_mode & 0777;
		$mode		= '0'.decoct($mode);
		$ftp_cmd	= 'CHMOD '.$mode.' '.$this->ftp_root;

		if( !ftp_site($install_ftp_connection, $ftp_cmd ) ){
			echo '<li><span class="failed">';
			echo sprintf($langmessage['Could_Not_'],'<em>Restore mode for '. $this->ftp_root.': '.$ftp_cmd.'</em>');
			echo '</span></li>';
			return;
		}
	}


	/**
	 * Establish an FTP connection to be used by the installer
	 * todo: remove $install_ftp_connection globabl
	 */
	public function FTPConnection(){
		global $dataDir, $langmessage, $install_ftp_connection;


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
		$install_ftp_connection = @ftp_connect($_POST['ftp_server'],21,6);
		if( !$install_ftp_connection ){
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
		$login_result = @ftp_login($install_ftp_connection, $_POST['ftp_user'], $_POST['ftp_pass']);
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
			$this->ftp_root = \gp\tool\FileSystemFtp::GetFTPRoot($install_ftp_connection,$dataDir);
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
		global $languages, $install_language;

		echo '<div class="lang_select">';
		echo '<form action="'.\gp\tool::GetUrl('').'" method="get">';
		echo '<select name="lang" onchange="this.form.submit()">';
		foreach($languages as $lang => $label){
			if( $lang === $install_language ){
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
		global $langmessage,$install_language;

		echo '<h2>'.$langmessage['Installing'].'</h2>';
		echo '<ul class="install_status">';

		$config = array();
		$config['language'] = $install_language;

		$success = false;
		if( \gp\install\Tools::gpInstall_Check() ){
			$success = \gp\install\Tools::Install_DataFiles_New(false, $config);
		}
		echo '</ul>';

		return $success;
	}


}//end class












