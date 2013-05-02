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

}
div,p,td,th{
	font-size:12px;
	}

a{
	color:#4466aa;
	text-decoration:none;
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
.padded_table td{
	padding:5px;
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

	var $can_write_data = true;


	function gp_install(){
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
				FTP_Prepare();
			break;

			case 'Install':
				$installed = Install_Normal();
			break;
		}

		if( !$installed ){
			LanguageForm();
			$this->CheckFolders();
		}else{
			Installed();
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

			}elseif( version_compare(phpversion(),'5.2','>=') ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.phpversion().'</td>';

			}else{
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.phpversion().'</td>';
				$ok = false;

			}
			echo '<td>5.2+</td>';
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
			Form_Entry();
			return;
		}

		if( !$this->can_write_data ){
			Form_Permissions();
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
			echo '<td class="failed">'.$langmessage['Failed'].': '.$langmessage['See_Below'].'</td>';
			echo '<td class="failed">'.$label_true.'</td>';
		}
		echo '<td>'.$label_true.'</td>';
	}


	function CheckDataFolder(){
		global $ok,$dataDir,$langmessage;

		echo '<tr>';

		echo '<td class="nowrap">';
		$folder = $dataDir.'/data';
		if( strlen($folder) > 23 ){
			$show = '...'.substr($folder,-20);
		}else{
			$show = $folder;
		}
		echo sprintf($langmessage['Permissions_for'],$show);
		echo ' &nbsp; ';
		echo '</td>';


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

		//show current info
		$expected = '777';
		if( file_exists($folder) && $current = @substr(decoct(fileperms($folder)), -3) ){
			$expected = FileSystem::getExpectedPerms($folder);
			if( FileSystem::perm_compare($expected,$current) ){
				echo '<td class="passed">';
				echo $current;
			}else{
				echo '<td class="passed_orange">';
				echo $current;
			}
		}else{
			echo '<td class="passed_orange">';
			echo '???';
		}
		echo '</td>';
		echo '<td>';
		echo $expected;
		echo '</td>';
		echo '</tr>';
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




}//end class



//Install Functions


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
		echo '<ul>';
		echo '<li>';
		echo common::Link('',$langmessage['View_your_web_site']);
		echo '</li>';
		echo '<li>';
		echo common::Link('Admin',$langmessage['Log_in_and_start_editing']);
		echo '</li>';
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


	function Form_Permissions(){
		global $langmessage,$dataDir;

		echo '<div>';
		echo '<h3>'.$langmessage['Changing_File_Permissions'].'</h3>';
		echo '<p>';
		echo $langmessage['REFRESH_AFTER_CHANGE'];
		echo '</p>';

		echo '<table class="styledtable fullwidth">';

		echo '<tr>';
		echo '<th>';
		echo $langmessage['manual_method'];
		echo '</th>';
		echo '</tr><tr>';
		echo '<td>';
		echo '<p>';
		echo $langmessage['LINUX_CHMOD'];
		echo '</p>';
		echo '<div class="code"><tt>';
		echo 'chmod 777 "'.$dataDir.'/data"';
		//echo 'chmod 777 "/'.$langmessage['your_install_directory'].'/data"';
		echo '</tt></div>';
		echo '<p>';
		echo '<a href="">'.$langmessage['Refresh'].'</a>';
		echo '</p>';
		echo '</td></tr>';

		echo '<tr><th>FTP</th>';
		echo '</tr>';
		echo '<tr><td>';
		echo '<p>';
		echo $langmessage['MOST_FTP_CLIENTS'];
		echo '</p>';
		echo '</td>';
		echo '</tr>';

		if( function_exists('ftp_connect') ){
			echo '<tr><th>';
			echo $langmessage['Installer'];
			echo '</th>';
			echo '</tr>';
			echo '<tr><td>';
			echo '<p>';
			echo $langmessage['FTP_CHMOD'];
			echo '</p>';
			echo '<form action="'.common::GetUrl('').'" method="post">';
			echo '<table class="padded_table">';
			Form_FTPDetails();
			echo '<tr>';
				echo '<td align="left">&nbsp;</td><td>';
				echo '<input type="hidden" name="cmd" value="Continue" />';
				echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
				echo '</td>';
				echo '</tr>';
			echo '</table>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
		}



		echo '</table>';
		echo '</div>';


	}

	function Form_FTPDetails($required=false){
		global $langmessage;
		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'');

		if( $required ){
			$required = '*';
		}
		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Server'].$required.' </td><td>';
			echo '<input type="text" class="text" size="20" name="ftp_server" value="'. htmlspecialchars($_POST['ftp_server']) .'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Username'].$required.' </td><td>';
			echo '<input type="text" class="text" size="20" name="ftp_user" value="'. htmlspecialchars($_POST['ftp_user']) .'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Password'].$required.' </td><td>';
			echo '<input type="password" class="text" size="20" name="ftp_pass" value="" />';
			echo '</td>';
			echo '</tr>';
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


	function FTP_Prepare(){
		global $langmessage,$install_ftp_connection;

		echo '<h2>'.$langmessage['Using_FTP'].'...</h2>';
		echo '<ul>';

		$ftp_root = false;
		if( Install_FTPConnection($ftp_root) === false ){
			return;
		}

		//Change Mode of /data
		echo '<li>';
			$ftpData = $ftp_root.'/data';
			$modDir = ftp_site($install_ftp_connection, 'CHMOD 0777 '. $ftpData );
			if( !$modDir ){
				echo '<span class="failed">';
				echo sprintf($langmessage['Could_Not_'],'<em>CHMOD 0777 '. $ftpData.'</em>');
				//echo 'Could not <em>CHMOD 0777 '. $ftpData.'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['FTP_PERMISSIONS_CHANGED'],'<em>'.$ftpData.'</em>');
				//echo 'File permissions for <em>'.$ftpData.'</em> changed.';
				echo '</span>';
			}
			echo '</li>';

		echo '<li>';
				echo '<span class="passed">';
				echo '<b>'.$langmessage['Success_continue_below'].'</b>';
				echo '</span>';
				echo '</li>';

		echo '</ul>';



	}

	function Install_FTPConnection(&$ftp_root){
		global $dataDir,$langmessage,$install_ftp_connection;

		//test for functions
		echo '<li>';
			if( !function_exists('ftp_connect') ){
				echo '<span class="failed">';
				echo $langmessage['FTP_UNAVAILABLE'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo $langmessage['FTP_AVAILABLE'];
				echo '</span>';
			}
			echo '</li>';

		//Try to connect
		echo '<li>';
			$install_ftp_connection = @ftp_connect($_POST['ftp_server'],21,6);
			if( !$install_ftp_connection ){
				echo '<span class="failed">';
				echo sprintf($langmessage['FAILED_TO_CONNECT'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['CONNECTED_TO'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
				//echo 'Connected to <em>'.$_POST['ftp_server'].'</em>';
				echo '</span>';
			}
			echo '</li>';

		//Log in
		echo '<li>';
			$login_result = @ftp_login($install_ftp_connection, $_POST['ftp_user'], $_POST['ftp_pass']);
			if( !$login_result ){
				echo '<span class="failed">';
				echo sprintf($langmessage['NOT_LOOGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
				//echo 'Could not log in user  <em>'.$_POST['ftp_user'].'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['LOGGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
				//echo 'User <em>'.$_POST['ftp_user'].'</em> logged in.';
				echo '</span>';
			}
			echo '</li>';

		//Get FTP Root

		echo '<li>';
			$ftp_root = false;
			if( $login_result ){
				$ftp_root = gpftp::GetFTPRoot($install_ftp_connection,$dataDir);
			}
			if( !$ftp_root ){
			//if( !$login_result ){
				echo '<span class="failed">';
				echo $langmessage['ROOT_DIRECTORY_NOT_FOUND'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['FTP_ROOT'],'<em>'.$ftp_root.'</em>');
				//echo 'FTP Root found: <em>'.$ftp_root.'</em>';
				echo '</span>';
			}
			echo '</li>';

		return true;
	}





