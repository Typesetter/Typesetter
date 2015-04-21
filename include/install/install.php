<?php
defined('is_running') or die('Not an entry point...');

global $langmessage, $install_ftp_connection;
$install_ftp_connection = false;
ob_start();

includeFile('tool/install.php');
includeFile('admin/admin_tools.php');
includeFile('tool/ftp.php');


?>

<!DOCTYPE html>
<html>
<head>
<title>gpEasy Installation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
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
	font-family: "Lucida Grande",Verdana,"Bitstream Vera Sans",Arial,sans-serif;
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
echo common::ErrorBuffer(false);
echo '</div>';
echo '</body></html>';


//Install Class
class gp_install{

	var $can_write_data		= true;
	var $ftp_root			= false;
	var $root_mode;



	function __construct(){
		global $languages,$install_language,$langmessage;

		//language preferences
			$install_language = 'en';

			if( isset($_GET['lang']) && isset($languages[$_GET['lang']]) ){
				$install_language = $_GET['lang'];

			}elseif( isset($_COOKIE['lang']) && isset($languages[$_COOKIE['lang']]) ){
				$install_language = $_COOKIE['lang'];
			}
			setcookie('lang',$install_language);

			common::GetLangFile('main.inc',$install_language);


		echo '<h1>'.$langmessage['Installation'].'</h1>';

		$installed = false;
		$cmd = common::GetCommand();
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

	function CheckFolders(){
		global $ok,$langmessage;

		$ok = true;

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

		//Check PHP Version
		echo '<tr>';
			echo '<td>';
			echo $langmessage['PHP_Version'];
			echo '</td>';
			if( !function_exists('version_compare') ){
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">???</td>';
				$ok = false;

			}elseif( version_compare(phpversion(),'5.3','>=') ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.phpversion().'</td>';

			}else{
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.phpversion().'</td>';
				$ok = false;

			}
			echo '<td>5.3+</td>';
			echo '</tr>';


		//make sure $_SERVER['SCRIPT_NAME'] is set
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://www.php.net/manual/reserved.variables.server.php" target="_blank">';
			echo 'SCRIPT_NAME or PHP_SELF';
			echo '</a>';
			echo '</td>';
			$checkValue = common::GetEnv('SCRIPT_NAME','index.php') || common::GetEnv('PHP_SELF','index.php');
			$ok = $ok && $checkValue;
			$this->StatusRow($checkValue,$langmessage['Set'],$langmessage['Not_Set']);
			echo '</tr>';

		//Check Safe Mode
		$checkValue = common::IniGet('safe_mode');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/features.safe-mode.php" target="_blank">';
			echo 'Safe Mode';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="failed">'.$langmessage['Failed'].': '.$langmessage['See_Below'].'</td>';
				echo '<td class="failed">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';

		//Check register_globals
		$checkValue = common::IniGet('register_globals');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.globals.php" target="_blank">';
			echo 'Register Globals';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange">'.$langmessage['On'].'</td>';
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';

		//Check common::IniGet( 'magic_quotes_sybase' )
		$checkValue = !common::IniGet('magic_quotes_sybase');
		$ok = $ok && $checkValue;
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Sybase';
			echo '</a>';
			echo '</td>';
			$this->StatusRow($checkValue,$langmessage['Off'],$langmessage['On']);
			echo '</tr>';

		//magic_quotes_runtime
		$checkValue = !common::IniGet('magic_quotes_runtime');
		$ok = $ok && $checkValue;
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Runtime';
			echo '</a>';
			echo '</td>';
			$this->StatusRow($checkValue,$langmessage['Off'],$langmessage['On']);
			echo '</tr>';


		// memory_limit
		// LESS compiling uses a fair amount of memory
		$checkValue = ini_get('memory_limit');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/ini.core.php#ini.memory-limit" target="_blank">';
			echo 'Memory Limit';
			echo '</a>';
			echo '</td>';

			//can't get memory_limit value
			if( @ini_set('memory_limit','96M') !== false ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">Adjustable</td>';

			}elseif( !$checkValue ){
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange">';
				echo '???';
				echo '</td>';

			}else{
				$byte_value = common::getByteValue($checkValue);
				if( $byte_value > 100663296 ){
					echo '<td class="passed">'.$langmessage['Passed'].'</td>';
					echo '<td class="passed">';
					echo $checkValue;
					echo '</td>';

				}elseif( $byte_value > 67108864 ){
					echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
					echo '<td class="passed_orange">';
					echo $checkValue;
					echo '</td>';

				}else{
					echo '<td class="failed">'.$langmessage['Failed'].'</td>';
					echo '<td class="failed">'.$checkValue.'</td>';
					$ok = false;
				}
			}
			echo '<td> 96M+ or Adjustable</td>';
			echo '</tr>';


		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th colspan="2">'.$langmessage['Notes'].'</th>';
		echo '</tr>';
		echo '</tbody>';
		echo '<tbody>';
		$this->CheckIndexHtml();
		$this->CheckImages();
		$ok = $ok && $this->CheckPath();
		echo '</tbody>';


		echo '</table>';
		echo '<p>';

		echo common::Link('',$langmessage['Refresh']);
		echo '</p>';
		echo '<br/>';

		if( $ok ){
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

	function StatusRow($value_ok,$label_true,$label_false){
		global $langmessage;

		if( $value_ok ){
			echo '<td class="passed">'.$langmessage['Passed'].'</td>';
			echo '<td class="passed">'.$label_true.'</td>';
		}else{
			echo '<td class="failed">'.$langmessage['Failed'].'</td>';
			echo '<td class="failed">'.$label_true.'</td>';
		}
		echo '<td>'.$label_true.'</td>';
	}


	function CheckDataFolder(){
		global $ok,$dataDir,$langmessage;

		echo '<tr><td class="nowrap">';
		$folder = $dataDir.'/data';
		if( strlen($folder) > 33 ){
			$show = '...'.substr($folder,-30);
		}else{
			$show = $folder;
		}
		echo $show;
		echo ' &nbsp; </td>';


		if( !is_dir($folder)){
			if(!@mkdir($folder, 0777)) {
				echo '<td class="passed_orange">'.$langmessage['See_Below'].' (0)</td>';
				$this->can_write_data = $ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
			}
		}elseif( gp_is_writable($folder) ){
			echo '<td class="passed">'.$langmessage['Passed'].'</td>';
		}else{
			echo '<td class="passed_orange">'.$langmessage['See_Below'].' (1)</td>';
			$this->can_write_data = $ok = false;
		}

		if( $this->can_write_data ){
			echo '<td class="passed">';
			echo $langmessage['Writable'];
		}else{
			echo '<td class="passed_orange">';
			echo $langmessage['Not Writable'];
		}

		echo '</td><td>';
		echo $langmessage['Writable'];
		echo '</td></tr>';
	}



	/*
	 *
	 * Check Functions
	 *
	 */


	//very unlikely, cannot have two ".php/" in path: see SetGlobalPaths()
	function CheckPath(){
		global $langmessage;

		$path = __FILE__;

		$test = $path;
		$pos = strpos($test,'.php');
		if( $pos === false ){
			return true;
		}
		$test = substr($test,$pos+4);
		$pos = strpos($test,'.php');
		if( $pos === false ){
			return true;
		}

		echo '<tr>';
			echo '<td class="nowrap">';
			if( strlen($path) > 30 ){
				echo '...'.substr($path,-27);
			}else{
				echo $path;
			}
			echo '</td>';
			echo '<td class="failed">'.$langmessage['Failed'].': '.$langmessage['See_Below'].'</td>';
			echo '<td class="failed" colspan="2">';
			echo str_replace('.php','<b>.php</b>',$path);
			echo '<br/>';
			echo 'The file structure contains multiple cases of ".php".';
			echo ' To Continue, rename your file structure so that directories do not use ".php".';
			echo '</td>';
			echo '</tr>';


		return false;
	}

	function CheckIndexHtml(){
		global $langmessage,$dataDir;

		$index = $dataDir.'/index.html';


		echo '<tr>';
			echo '<td>';
			if( strlen($index) > 30 ){
				echo '...'.substr($index,-27);
			}else{
				echo $index;
			}
			echo '</td>';

			if( !file_exists($index) ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed" colspan="2"></td>';
			}else{
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange" colspan="2">'.$langmessage['index.html exists'].'</td>';
			}
			echo '</tr>';

	}


	function CheckImages(){
		global $langmessage;

		$passed = false;
		$supported = array();
		if( function_exists('imagetypes') ){
			$passed = true;
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



		echo '<tr>';
			echo '<td>';
			echo '<a href="http://www.php.net/manual/en/book.image.php" target="_blank">';
			echo $langmessage['image_functions'];
			echo '</a>';
			echo '</td>';
			if( $passed ){

				if( count($supported) == 4 ){
					echo '<td class="passed">'.$langmessage['Passed'].'</td>';
					echo '<td class="passed" colspan="2">'.implode(', ',$supported).'</td>';
				}else{
					echo '<td class="passed_orange">'.$langmessage['partially_available'].'</td>';
					echo '<td class="passed_orange" colspan="2">'.implode(', ',$supported).'</td>';
				}

			}else{
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange" colspan="2">'.$langmessage['unavailable'].'</td>';
			}
			echo '</tr>';


	}



	/**
	 * Change the permissions of the data directory
	 *
	 */
	function FTP_Prepare(){
		global $langmessage;

		echo '<h2>'.$langmessage['Using_FTP'].'...</h2>';
		echo '<ul>';
		$this->_FTP_Prepare();
		$this->FTP_RestoreMode();
		echo '</ul>';
	}


	function _FTP_Prepare(){

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
	function FTP_DataMode(){
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
	function FTP_DataFolder(){
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
	function CopyData($from_dir, $to_dir){

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
	function FTP_RestoreMode(){
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
	function FTPConnection(){
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
			$this->ftp_root = gpftp::GetFTPRoot($install_ftp_connection,$dataDir);
		}
		if( !$this->ftp_root ){
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


	function Form_Permissions(){
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
		echo '<li>Run gpEasy Installer by refreshing this page</li>';
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
	function GetPHPOwner(){
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


	function Form_FTPDetails(){
		global $langmessage;

		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'');

		echo '<form action="'.common::GetUrl('').'" method="post">';
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



	function LanguageForm(){
		global $languages, $install_language;

		echo '<div class="lang_select">';
		echo '<form action="'.common::GetUrl('').'" method="get">';
		echo '<select name="lang" onchange="this.form.submit()">';
		foreach($languages as $lang => $label){
			if( $lang === $install_language ){
				echo '<option value="'.$lang.'" selected="selected">';
			}else{
				echo '<option value="'.$lang.'">';
			}
			//echo $lang.' - '.$label;
			echo '&nbsp; '.$label.' &nbsp; ('.$lang.')';
			echo '</option>';
		}

		echo '</select>';
		echo '<div class="sm">';
		echo '<a href="http://ptrans.wikyblog.com/pt/gpEasy" target="_blank">Help translate gpEasy</a>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}


	function Installed(){
		global $langmessage;
		echo '<h4>'.$langmessage['Installation_Was_Successfull'].'</h4>';

		echo '<h2>';
		echo common::Link('',$langmessage['View_your_web_site']);
		echo '</h2>';

		echo '</ul>';

		echo '<p>';
		echo 'For added security, you may delete the /include/install/install.php file from your server.';
		echo '</p>';
	}


	function Form_Entry(){
		global $langmessage;

		//echo '<h3>'.$langmessage['configuration'].'</h3>';
		//echo '<h3>'.$langmessage['User Details'].'</h3>';
		echo '<form action="'.common::GetUrl('').'" method="post">';
		echo '<table class="styledtable">';
		Install_Tools::Form_UserDetails();
		Install_Tools::Form_Configuration();
		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="Install" />';
		echo '<input type="submit" class="submit install_button" name="aaa" value="'.$langmessage['Install'].'" />';
		echo '</p>';
		echo '</form>';
	}


	function Install_Normal(){
		global $langmessage,$install_language;

		echo '<h2>'.$langmessage['Installing'].'</h2>';
		echo '<ul class="install_status">';

		$config = array();
		$config['language'] = $install_language;

		$success = false;
		if( Install_Tools::gpInstall_Check() ){
			$success = Install_Tools::Install_DataFiles_New(false, $config);
		}
		echo '</ul>';

		return $success;
	}


}//end class












