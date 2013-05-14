<?php
defined('is_running') or die('Not an entry point...');

gp_defined('gp_lock_time',900); // = 15 minutes
includeFile('admin/admin_tools.php');
includeFile('tool/editing.php');

class gpsession{


	static function Init(){
		global $config;

		gp_defined('gp_session_cookie',self::SessionCookie($config['gpuniq']));

		$cmd = common::GetCommand();

		switch( $cmd ){
			case 'logout':
				gpsession::LogOut();
			return;
			case 'login':
				gpsession::LogIn();
			return;
			case 'enable_component':
				gpsession::EnableComponent();
			break;

		}

		if( isset($_COOKIE[gp_session_cookie]) ){
			gpsession::CheckPosts();
			gpsession::start($_COOKIE[gp_session_cookie]);
		}

	}


	static function LogIn(){
		global $dataDir, $langmessage, $config, $gp_index;

		// check nonce
		// expire the nonce after 10 minutes
		$nonce = $_POST['login_nonce'];
		if( !common::verify_nonce( 'login_nonce', $nonce, true, 300 ) ){
			message($langmessage['OOPS'].' (Expired Nonce)');
			return;
		}

		if( !isset($_COOKIE['g']) && !isset($_COOKIE[gp_session_cookie]) ){
			message($langmessage['COOKIES_REQUIRED']);
			return false;
		}

		//delete the entry in $sessions if we're going to create another one with login
		if( isset($_COOKIE[gp_session_cookie]) ){
			gpsession::CleanSession($_COOKIE[gp_session_cookie]);
		}


		include($dataDir.'/data/_site/users.php');
		$username = gpsession::GetLoginUser( $users, $nonce );
		if( $username === false ){
			gpsession::IncorrectLogin('1');
			return false;
		}
		$users[$username] += array('attempts'=> 0,'granted'=>''); // 'editing' will be set EditingValue()
		$userinfo = $users[$username];

		//Check Attempts
		if( $userinfo['attempts'] >= 5 ){
			$timeDiff = (time() - $userinfo['lastattempt'])/60; //minutes
			if( $timeDiff < 10 ){
				message($langmessage['LOGIN_BLOCK'],ceil(10-$timeDiff));
				return false;
			}
		}


		//check against password sent to a user's email address from the forgot_password form
		$passed = false;
		$pass_hash = $config['passhash'];
		if( isset($userinfo['passhash']) ){
			$pass_hash = $userinfo['passhash'];
		}

		if( !empty($userinfo['newpass']) && gpsession::CheckPassword($userinfo['newpass'],$nonce,$pass_hash) ){
			$userinfo['password'] = $userinfo['newpass'];
			$passed = true;

		//check password
		}elseif( gpsession::CheckPassword($userinfo['password'],$nonce,$pass_hash) ){
			$passed = true;
		}


		//if passwords don't match
		if( $passed !== true ){
			gpsession::IncorrectLogin('2');
			gpsession::UpdateAttempts($users,$username);
			return false;
		}

		//will be saved in UpdateAttempts
		if( isset($userinfo['newpass']) ){
			unset($userinfo['newpass']);
		}

		$session_id = gpsession::create($userinfo,$username);
		if( !$session_id ){
			message($langmessage['OOPS'].' (Data Not Saved)');
			gpsession::UpdateAttempts($users,$username,true);
			return false;
		}

		$logged_in = gpsession::start($session_id);

		if( $logged_in === true ){
			message($langmessage['logged_in']);
		}

		//need to save the user info regardless of success or not
		//also saves file_name in users.php
		$users[$username] = $userinfo;
		gpsession::UpdateAttempts($users,$username,true);

		//redirect to prevent resubmission
		$redirect = 'Admin';
		if( isset($_REQUEST['file']) && isset($gp_index[$_REQUEST['file']]) ){
			$redirect = $_REQUEST['file'];
		}
		$url = common::GetUrl($redirect,'',false);
		common::Redirect($url);

	}

	/**
	 * Return the username for the login request
	 *
	 */
	static function GetLoginUser( $users, $nonce ){

		$_POST += array('user_sha'=>'','username'=>'','login_nonce'=>'');

		if( gp_require_encrypt && empty($_POST['user_sha']) ){
			return false;
		}

		foreach($users as $username => $info){
			$sha_user = sha1($nonce.$username);

			if( !gp_require_encrypt
				&& !empty($_POST['username'])
				&& $_POST['username'] == $username
				){
					return $username;
			}

			if( $sha_user === $_POST['user_sha'] ){
				return $username;
			}
		}

		return false;
	}


	/**
	 * check password, choose between plaintext, md5 encrypted or sha-1 encrypted
	 * @param string $user_pass
	 * @param string $nonce
	 * @param string $pass_hash Password hashing algorithm
	 *
	 */
	static function CheckPassword( $user_pass, $nonce, $pash_hash ){
		global $config;

		//without encryption
		if( !gp_require_encrypt && !empty($_POST['password']) ){
			$pass = common::hash($_POST['password'],$pash_hash);
			if( $user_pass === $pass ){
				return true;
			}
			return false;
		}

		$posted_pass = false;
		switch($pash_hash){

			case 'md5':
				$posted_pass = $_POST['pass_md5'];
				$nonced_pass = sha1($nonce.$user_pass);
			break;

			case 'sha1':
				$posted_pass = $_POST['pass_sha'];
				$nonced_pass = sha1($nonce.$user_pass);
			break;

			case 'sha512':
				//javascript only loops through sha512 50 times
				$posted_pass = common::hash($_POST['pass_sha512'],'sha512',950);
				$nonced_pass = $user_pass;
			break;
		}

		if( $posted_pass && $posted_pass === $nonced_pass ){
			return true;
		}

		return false;
	}


	static function IncorrectLogin($i){
		global $langmessage;
		message($langmessage['incorrect_login'].' ('.$i.')');
		$url = common::GetUrl('Admin','cmd=forgotten');
		message($langmessage['forgotten_password'],$url);
	}


	//get/set the value of $userinfo['file_name']
	static function SetSessionFileName($userinfo,$username){

		if( !isset($userinfo['file_name']) ){

			if( isset($userinfo['cookie_id']) ){
				$old_file_name = 'gpsess_'.$userinfo['cookie_id'];
				unset($userinfo['cookie_id']);
			}else{
				//$old_file_name = 'gpsess_'.md5($username.$pass);
				$old_file_name = 'gpsess_'.md5($username.$userinfo['password']);

			}
			$userinfo['file_name'] = gpsession::UpdateFileName($old_file_name);
		}
		return $userinfo;
	}

	static function UpdateFileName($old_file_name){
		global $dataDir;

		//get a new unique name
		do{
			$new_file_name = 'gpsess_'.common::RandomString(40).'.php';
			$new_file = $dataDir.'/data/_sessions/'.$new_file_name;
		}while( file_exists($new_file) );


		$old_file = $dataDir.'/data/_sessions/'.$old_file_name;
		if( !file_exists($old_file) ){
			return $new_file_name;
		}

		if( rename($old_file,$new_file) ){
			return $new_file_name;
		}
		return $old_file_name;
	}

	static function LogOut(){
		global $langmessage;

		if( !isset($_COOKIE[gp_session_cookie]) ){
			return false;
		}

		$session_id = $_COOKIE[gp_session_cookie];

		gpFiles::Unlock('admin',sha1(sha1($session_id)));
		gpsession::cookie(gp_session_cookie,'',time()-42000);
		gpsession::CleanSession($session_id);
		message($langmessage['LOGGED_OUT']);
	}

	static function CleanSession($session_id){
		//remove the session_id from session_ids.php
		$sessions = gpsession::GetSessionIds();
		unset($sessions[$session_id]);
		gpsession::SaveSessionIds($sessions);
	}

	/**
	 * Set a session cookie
	 * Attempt to use httponly if available
	 *
	 */
	static function cookie($name,$value,$expires = false){
		global $dirPrefix;

		$cookiePath = '/';
		if( !empty($dirPrefix) ){
			$cookiePath = $dirPrefix;
		}
		$cookiePath = common::HrefEncode($cookiePath,false);


		if( $expires === false ){
			$expires = time()+2592000;
		}elseif( $expires === true ){
			$expires = 0; //expire at end of session
		}

		setcookie($name, $value, $expires, $cookiePath, '', '', true);
	}



	/**
	 * Update the number of login attempts and the time of the last attempt for a $username
	 *
	 */
	static function UpdateAttempts($users,$username,$reset = false){
		global $dataDir;

		if( $reset ){
			$users[$username]['attempts'] = 0;
		}else{
			$users[$username]['attempts']++;
		}
		$users[$username]['lastattempt'] = time();
		gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users);
	}



	//called when a user logs in
	static function create(&$user_info,$username){
		global $dataDir, $langmessage;

		//update the session files to .php files
		//changes to $userinfo will be saved by UpdateAttempts() below
		$user_info = gpsession::SetSessionFileName($user_info,$username);
		$user_file_name = $user_info['file_name'];
		$user_file = $dataDir.'/data/_sessions/'.$user_file_name;


		//use an existing session_id if the new login matches an existing session (uid and file_name)
		$sessions = gpsession::GetSessionIds();
		$uid = gpsession::auth_browseruid();
		$session_id = false;
		foreach($sessions as $sess_temp_id => $sess_temp_info){
			if( isset($sess_temp_info['uid']) && $sess_temp_info['uid'] == $uid && $sess_temp_info['file_name'] == $user_file_name ){
				$session_id = $sess_temp_id;
			}
		}

		//create a unique session id if needed
		if( $session_id === false ){
			do{
				$session_id = common::RandomString(40);
			}while( isset($sessions[$session_id]) );
		}

		$expires = !isset($_POST['remember']);
		gpsession::cookie(gp_session_cookie,$session_id,$expires);

		//save session id
		$sessions[$session_id] = array();
		$sessions[$session_id]['file_name'] = $user_file_name;
		$sessions[$session_id]['uid'] = $uid;
		//$sessions[$session_id]['time'] = time(); //for session locking
		if( !gpsession::SaveSessionIds($sessions) ){
			return false;
		}

		//make sure the user's file exists
		$new_data = gpsession::SessionData($user_file,$checksum);
		$new_data['username'] = $username;
		$new_data['granted'] = $user_info['granted'];
		if( isset($user_info['editing']) ){
			$new_data['editing'] = $user_info['editing'];
		}
		admin_tools::EditingValue($new_data);
		gpFiles::SaveArray($user_file,'gpAdmin',$new_data);
		return $session_id;
	}



	/**
	 * Return the contents of the session_ids.php data file
	 * @return array array of all sessions
	 */
	static function GetSessionIds(){
		global $dataDir;
		$sessions = array();
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		if( file_exists($sessions_file) ){
			require($sessions_file);
		}

		return $sessions;
	}

	/**
	 * Save $sessions to the session_ids.php data file
	 * @param $sessions array array of all sessions
	 * @return bool
	 */
	static function SaveSessionIds($sessions){
		global $dataDir;

		while( $current = current($sessions) ){
			$key = key($sessions);

			//delete if older than
			if( isset($current['time']) && $current['time'] > 0 && ($current['time'] < (time() - 1209600)) ){
			//if( $current['time'] < time() - 2592000 ){ //one month
				unset($sessions[$key]);
				$continue = true;
			}else{
				next($sessions);
			}
		}

		//clean
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		return gpFiles::SaveArray($sessions_file,'sessions',$sessions);
	}

	/**
	 * Determine if $session_id represents a valid session and if so start the session
	 *
	 */
	static function start($session_id){
		global $langmessage, $dataDir,$GP_LANG_VALUES;

		//get the session file
		$sessions = gpsession::GetSessionIds();
		if( !isset($sessions[$session_id]) ){
			gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
			message($langmessage['Session Expired'].' (timeout)');
			return false;
		}
		$sess_info = $sessions[$session_id];

		//check ~ip, ~user agent ...
		if( gp_browser_auth && isset($sess_info['uid']) ){
			$auth_uid = gpsession::auth_browseruid();
			$auth_uid_legacy = gpsession::auth_browseruid(true);//legacy option added to prevent logging users out, added 2.0b2
			if( ($sess_info['uid'] != $auth_uid) && ($sess_info['uid'] != $auth_uid_legacy) ){
				gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
				message($langmessage['Session Expired'].' (browser auth)');
				return false;
			}
		}


		$session_file = $dataDir.'/data/_sessions/'.$sess_info['file_name'];
		if( ($session_file === false) || !file_exists($session_file) ){
			gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
			message($langmessage['Session Expired'].' (invalid)');
			return false;
		}

		//prevent browser caching when editing
		Header( 'Last-Modified: ' . gmdate( 'D, j M Y H:i:s' ) . ' GMT' );
		Header( 'Expires: ' . gmdate( 'D, j M Y H:i:s', time() ) . ' GMT' );
		Header( 'Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
		Header( 'Cache-Control: post-check=0, pre-check=0', false );
		Header( 'Pragma: no-cache' ); // HTTP/1.0


		$GLOBALS['gpAdmin'] = gpsession::SessionData($session_file,$checksum);


		//lock to prevent conflicting edits
		if( gp_lock_time > 0 && ( !empty($GLOBALS['gpAdmin']['editing']) || !empty($GLOBALS['gpAdmin']['granted']) ) ){
			$expires = gp_lock_time;
			if( !gpFiles::Lock('admin',sha1(sha1($session_id)),$expires) ){
				message( $langmessage['site_locked'].' '.sprintf($langmessage['lock_expires_in'],ceil($expires/60)) );
				$GLOBALS['gpAdmin']['locked'] = true;
			}else{
				unset($GLOBALS['gpAdmin']['locked']);
			}
		}


		register_shutdown_function(array('gpsession','close'),$session_file,$checksum);

		gpsession::SaveSetting();

		//make sure forms have admin nonce
		ob_start(array('gpsession','AdminBuffer'));

		$GP_LANG_VALUES += array('cancel'=>'ca','update'=>'up','caption'=>'cp','Width'=>'Width','Height'=>'Height');
		common::LoadComponents('sortable,autocomplete,gp-admin,gp-admin-css');

		return true;
	}

	/**
	 * Perform admin only changes to the content buffer
	 * This will happen before gpOutput::BufferOut()
	 *
	 */
	static function AdminBuffer($buffer){
		global $wbErrorBuffer, $gp_admin_html;


		//check for html document
		$html_doc = true;
		if( strpos($buffer,'<!-- get_head_placeholder '.gp_random.' -->') === false ){
			$html_doc = false;
		}

		// Add a generic admin nonce field to each post form
		// Admin nonces are also added with javascript if needed
		$count = preg_match_all('#<form[^<>]*method=[\'"]post[\'"][^<>]*>#i',$buffer,$matches);
		if( $count ){
			$nonce = common::new_nonce('post',true);
			$matches[0] = array_unique($matches[0]);
			foreach($matches[0] as $match){

				//make sure it's a local action
				if( preg_match('#action=[\'"]([^\'"]+)[\'"]#i',$match,$sub_matches) ){
					$action = $sub_matches[1];
					if( substr($action,0,2) === '//' ){
						continue;
					}elseif( strpos($action,'://') ){
						continue;
					}
				}
				$replacement = '<span class="nodisplay"><input type="hidden" name="verified" value="'.$nonce.'"/></span>';
				$pos = strpos($buffer,$match)+strlen($match);
				$buffer = substr_replace($buffer,$replacement,$pos,0);
			}
		}


		//add $gp_admin_html to the document
		$pos_body = strpos($buffer,'<body');
		if( $html_doc && $pos_body ){
			$pos = strpos($buffer,'>',$pos_body);
			$buffer = substr_replace($buffer,'<div id="gp_admin_html">'.$gp_admin_html.gpOutput::$editlinks.'</div>',$pos+1,0);
		}

		return $buffer;
	}

	/**
	 * Get the session data from a user session file
	 * @param string $session_file The full path to the user's session file
	 * @param string $checksum
	 * @return array The user's session data
	 */
	static function SessionData($session_file,&$checksum){

		$gpAdmin = array();
		if( file_exists($session_file) ){
			require($session_file);
		}
		$checksum = '';
		if( isset($gpAdmin['checksum']) ){
			$checksum = $gpAdmin['checksum'];
		}

		//$gpAdmin = gpsession::gpui_defaults() + $gpAdmin; //reset the defaults
		return $gpAdmin + gpsession::gpui_defaults();
	}

	static function gpui_defaults(){

		return array(	'gpui_cmpct'=>0,
						'gpui_tx'=>6,
						'gpui_ty'=>10,
						'gpui_ckx'=>20,
						'gpui_cky'=>240,
						'gpui_ckd'=>false,
						'gpui_pposx'=>0,
						'gpui_pposy'=>0,
						'gpui_pw'=>0,
						'gpui_ph'=>0,
						'gpui_vis'=>'cur',
						);
	}


	/**
	 * Prevent XSS attacks for logged in users by making sure the request contains a valid nonce
	 *
	 */
	static function CheckPosts(){

		if( count($_POST) == 0 ){
			return;
		}

		if( empty($_POST['verified']) ){
			gpsession::StripPost('XSS Verification Parameter Error');
			return;
		}
		if( !common::verify_nonce('post',$_POST['verified'],true) ){
			gpsession::StripPost('XSS Verification Parameter Mismatch');
			return;
		}
	}

	/**
	 * Unset all $_POST values
	 *
	 */
	static function StripPost($message){
		global $langmessage, $post_quarantine;
		message($langmessage['OOPS'].' ('.$message.')');
		$post_quarantine = $_POST;
		foreach($_POST as $key => $value){
			unset($_POST[$key]);
		}
	}


	/**
	 * Save any changes to the $gpAdmin array
	 * @param string $file Session file path
	 * @param string $checksum_read The original checksum of the $gpAdmin array
	 *
	 */
	static function close($file,$checksum_read){
		global $gpAdmin;

		self::FatalNotices();
		gpsession::Cron();
		self::LayoutInfo();

		unset($gpAdmin['checksum']);
		$checksum = common::ArrayHash($gpAdmin);

		//nothing changes
		if( $checksum === $checksum_read ){
			return;
		}
		if( !isset($gpAdmin['username']) ){
			trigger_error('username not set');
			die();
		}

		$gpAdmin['checksum'] = $checksum; //store the new checksum
		gpFiles::SaveArray($file,'gpAdmin',$gpAdmin);
	}

	/**
	 * Update layout information if needed
	 *
	 */
	static function LayoutInfo(){
		global $page, $gpLayouts, $get_all_gadgets_called;


		if( !gpOutput::$template_included ){
			return;
		}

		if( common::RequestType() != 'template' ){
			return;
		}

		$layout = $page->gpLayout;
		if( !isset($gpLayouts[$layout]) ){
			return;
		}

		$layout_info =& $gpLayouts[$layout];

		//template.php file not modified
		$template_file = realpath($page->theme_dir.'/template.php');
		$template_mod = filemtime($template_file);
		if( isset($layout_info['template_mod']) && $layout_info['template_mod'] >= $template_mod ){
			return;
		}

		$contents = ob_get_contents();

		//charset
		if( strpos($contents,'charset=') !== false ){
			return;
		}

		//get just the head of the buffer to see if we need to add charset
		$pos = strpos($contents,'</head');
		unset($layout_info['doctype']);
		if( $pos > 0 ){
			$head = substr($contents,0,$pos);
			$layout_info['doctype'] = self::DoctypeMeta($head);
		}
		$layout_info['all_gadgets'] = $get_all_gadgets_called;


		//save
		$layout_info['template_mod'] = $template_mod;
		admin_tools::SavePagesPHP();
	}


	/**
	 * Determine if gpEasy needs to add a <meta charset> tag
	 * Look at the beginning of the document to see what kind of doctype the current template is using
	 * See http://www.w3schools.com/tags/tag_doctype.asp for description of different doctypes
	 *
	 */
	static function DoctypeMeta($doc_start){

		//charset already set
		if( stripos($doc_start,'charset=') !== false ){
			return '';
		}

		// html5
		// spec states this should be "the first element child of the head element"
		if( stripos($doc_start,'<!doctype html>') !== false ){
			return '<meta charset="UTF-8" />';
		}
		return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	}

	/**
	 * Perform regular tasks
	 * Once an hour only when admin is logged in
	 *
	 */
	static function Cron(){
		global $dataDir;

		$file_stats = $cron_info = array();
		$time_file = $dataDir.'/data/_site/cron_info.php';
		if( file_exists($time_file) ){
			require($time_file);
		}
		$file_stats += array('modified' => 0);
		if( (time() - $file_stats['modified']) < 3600 ){
			return;
		}

		gpsession::CleanTemp();
		gpFiles::SaveArray($time_file,'cron_info',$cron_info);
	}

	/**
	 * Clean old files and folders from the temporary folder
	 * Delete after 36 hours (129600 seconds)
	 *
	 */
	static function CleanTemp(){
		global $dataDir;
		$temp_folder = $dataDir.'/data/_temp';
		$files = gpFiles::ReadDir($temp_folder,false);
		foreach($files as $file){
			if( $file == 'index.html') continue;
			$full_path = $temp_folder.'/'.$file;
			$mtime = (int)filemtime($full_path);
			$diff = time() - $mtime;
			if( $diff < 129600 ) continue;
			gpFiles::RmAll($full_path);
		}
	}


	/**
	 * Save user settings
	 *
	 */
	static function SaveSetting(){

		$cmd = common::GetCommand();
		if( empty($cmd) ){
			return;
		}

		switch($cmd){
			case 'savegpui':
				gpsession::SaveGPUI();
			//dies
		}
	}

	/**
	 * Save UI values for the current user
	 *
	 */
	static function SaveGPUI(){
		global $gpAdmin;

		gpsession::SetGPUI();
		includeFile('tool/ajax.php');

		//send response so an error is not thrown
		echo gpAjax::Callback($_REQUEST['jsoncallback']).'([]);';
		die();

		//for debugging
		die('debug: '.showArray($_POST).'result: '.showArray($gpAdmin));
	}


	/**
	 * Set UI values from posted data for the current user
	 *
	 */
	static function SetGPUI(){
		global $gpAdmin;

		$possible = array();

		//only change the panel position if it's the default layout
		if( isset($_POST['gpui_dlayout']) && $_POST['gpui_dlayout'] == 'true' ){
			$possible['gpui_pposx']	= 'integer';
			$possible['gpui_pposy']	= 'integer';
			$possible['gpui_pw']	= 'integer';
			$possible['gpui_ph']	= 'integer';
		}

		$possible['gpui_cmpct']	= 'integer';
		$possible['gpui_vis']	= array('con'=>'con','cur'=>'cur','app'=>'app','add'=>'add','set'=>'set','upd'=>'upd','use'=>'use','false'=>false);


		$possible['gpui_tx']	= 'integer';
		$possible['gpui_ty']	= 'integer';
		$possible['gpui_ckx']	= 'integer';
		$possible['gpui_cky']	= 'integer';
		$possible['gpui_ckd']	= 'boolean';


		foreach($possible as $key => $key_possible){

			if( !isset($_POST[$key]) ){
				continue;
			}
			$value = $_POST[$key];

			if( $key_possible == 'boolean' ){
				if( !$value || $value === 'false' ){
					$value = false;
				}else{
					$value = true;
				}
			}elseif( $key_possible == 'integer' ){
				$value = (int)$value;
			}elseif( is_array($key_possible) ){
				if( !isset($key_possible[$value]) ){
					continue;
				}
			}

			$gpAdmin[$key] = $value;
		}

		//remove gpui_ settings no longer in $possible
		unset($gpAdmin['gpui_con']);
		unset($gpAdmin['gpui_cur']);
		unset($gpAdmin['gpui_app']);
		unset($gpAdmin['gpui_add']);
		unset($gpAdmin['gpui_set']);
		unset($gpAdmin['gpui_upd']);
		unset($gpAdmin['gpui_use']);
		unset($gpAdmin['gpui_edb']);
		unset($gpAdmin['gpui_brdis']);//3.5
	}

	/**
	 * Output the UI variables as a Javascript Object
	 *
	 */
	static function GPUIVars(){
		global $gpAdmin,$page,$config;


		echo 'var gpui={';
		echo 'ph:'.$gpAdmin['gpui_ph'];
		//echo ',pposx:'.$gpAdmin['gpui_pposx'];
		//echo ',pposy:'.$gpAdmin['gpui_pposy'];
		//echo ',pw:'.$gpAdmin['gpui_pw'];
		echo ',cmpct:'.(int)$gpAdmin['gpui_cmpct'];

		//the following control which admin toolbar areas are expanded
		echo ',vis:"'.$gpAdmin['gpui_vis'].'"';

		//toolbar location
		echo ',tx:'. $gpAdmin['gpui_tx']; //20
		echo ',ty:'. $gpAdmin['gpui_ty']; //10

		//#ckeditor_area
		echo ',ckx:'. max(5,$gpAdmin['gpui_ckx']);
		echo ',cky:'. max(0,$gpAdmin['gpui_cky']);
		echo ',ckd:'.( !isset($gpAdmin['gpui_ckd']) || !$gpAdmin['gpui_ckd'] ? 'false' : 'true' ); //docked

		//default layout (admin layout)
		if( $page->gpLayout && $page->gpLayout == $config['gpLayout'] ){
			echo ',dlayout:true';
		}else{
			echo ',dlayout:false';
		}
		echo '};';
	}



	/**
	 * Code modified from dokuwiki
	 * /dokuwiki/inc/auth.php
	 *
	 * Builds a pseudo UID from browser and IP data
	 *
	 * This is neither unique nor unfakable - still it adds some
	 * security. Using the first part of the IP makes sure
	 * proxy farms like AOLs are stil okay.
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 *
	 * @return  string  a MD5 sum of various browser headers
	 */
	static function auth_browseruid($legacy = false){

		$uid = '';
		if( isset($_SERVER['HTTP_USER_AGENT']) ){
			$uid .= $_SERVER['HTTP_USER_AGENT'];
		}
		if( isset($_SERVER['HTTP_ACCEPT_ENCODING']) ){
			$uid .= $_SERVER['HTTP_ACCEPT_ENCODING'];
		}

		// IE does not report ACCEPT_LANGUAGE consistently
		//if( $legacy && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
		//	$uid .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		//}

		if( isset($_SERVER['HTTP_ACCEPT_CHARSET']) ){
			$uid .= $_SERVER['HTTP_ACCEPT_CHARSET'];
		}

		if( $legacy ){
			if( isset($_SERVER['REMOTE_ADDR']) ){
				$ip = $_SERVER['REMOTE_ADDR'];
				if( strpos($ip,'.') !== false ){
					$uid .= substr($ip,0,strpos($ip,'.'));
				}elseif( strpos($ip,':') !== false ){
					$uid .= substr($ip,0,strpos($ip,':'));
				}
			}
		}else{
			$ip = gpsession::clientIP(true);
			$uid .= substr($ip,0,strpos($ip,'.'));
		}

		//ie8 will report ACCEPT_LANGUAGE as en-us and en-US depending on the type of request (normal, ajax)
		$uid = strtolower($uid);

		return md5($uid);
	}

	/**
	 * Via Dokuwiki
	 * Return the IP of the client
	 *
	 * Honours X-Forwarded-For and X-Real-IP Proxy Headers
	 *
	 * It returns a comma separated list of IPs if the above mentioned
	 * headers are set. If the single parameter is set, it tries to return
	 * a routable public address, prefering the ones suplied in the X
	 * headers
	 *
	 * @param  boolean $single If set only a single IP is returned
	 * @author Andreas Gohr <andi@splitbrain.org>
	 *
	 */
	static function clientIP($single=false){
	    $ip = array();
	    $ip[] = $_SERVER['REMOTE_ADDR'];
	    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	        $ip = array_merge($ip,explode(',',str_replace(' ','',$_SERVER['HTTP_X_FORWARDED_FOR'])));
	    if(!empty($_SERVER['HTTP_X_REAL_IP']))
	        $ip = array_merge($ip,explode(',',str_replace(' ','',$_SERVER['HTTP_X_REAL_IP'])));

	    // some IPv4/v6 regexps borrowed from Feyd
	    // see: http://forums.devnetwork.net/viewtopic.php?f=38&t=53479
	    $dec_octet = '(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|[0-9])';
	    $hex_digit = '[A-Fa-f0-9]';
	    $h16 = "{$hex_digit}{1,4}";
	    $IPv4Address = "$dec_octet\\.$dec_octet\\.$dec_octet\\.$dec_octet";
	    $ls32 = "(?:$h16:$h16|$IPv4Address)";
	    $IPv6Address =
	        "(?:(?:{$IPv4Address})|(?:".
	        "(?:$h16:){6}$ls32" .
	        "|::(?:$h16:){5}$ls32" .
	        "|(?:$h16)?::(?:$h16:){4}$ls32" .
	        "|(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32" .
	        "|(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32" .
	        "|(?:(?:$h16:){0,3}$h16)?::(?:$h16:){1}$ls32" .
	        "|(?:(?:$h16:){0,4}$h16)?::$ls32" .
	        "|(?:(?:$h16:){0,5}$h16)?::$h16" .
	        "|(?:(?:$h16:){0,6}$h16)?::" .
	        ")(?:\\/(?:12[0-8]|1[0-1][0-9]|[1-9][0-9]|[0-9]))?)";

	    // remove any non-IP stuff
	    $cnt = count($ip);
	    $match = array();
	    for($i=0; $i<$cnt; $i++){
	        if(preg_match("/^$IPv4Address$/",$ip[$i],$match) || preg_match("/^$IPv6Address$/",$ip[$i],$match)) {
	            $ip[$i] = $match[0];
	        } else {
	            $ip[$i] = '';
	        }
	        if(empty($ip[$i])) unset($ip[$i]);
	    }
	    $ip = array_values(array_unique($ip));
	    if(!$ip[0]) $ip[0] = '0.0.0.0'; // for some strange reason we don't have a IP

	    if(!$single) return join(',',$ip);

	    // decide which IP to use, trying to avoid local addresses
	    $ip = array_reverse($ip);
	    foreach($ip as $i){
	        if(preg_match('/^(::1|[fF][eE]80:|127\.|10\.|192\.168\.|172\.((1[6-9])|(2[0-9])|(3[0-1]))\.)/',$i)){
	            continue;
	        }else{
	            return $i;
	        }
	    }
	    // still here? just use the first (last) address
	    return $ip[0];
	}

	/**
	 * Re-enable components that were disabled because of fatal errors
	 *
	 */
	function EnableComponent(){
		global $dataDir,$page;

		includeFile('admin/admin_errors.php');
		admin_errors::ClearError($_REQUEST['hash']);
		$title = common::WhichPage();
		common::Redirect(common::GetUrl($title,'',false));
	}


	/**
	 * Notify the admin if there have been any fatal errors
	 *
	 */
	static function FatalNotices(){
		global $dataDir, $page;

		if( !admin_tools::HasPermission('Admin_Errors') ){
			return;
		}

		if( is_object($page) && strpos($page->title,'Admin_Errors') !== false ){
			return;
		}

		$dir = $dataDir.'/data/_site';
		$files = scandir($dir);
		$has_fatal = false;
		foreach($files as $file){
			if( strpos($file,'fatal_') === false ){
				continue;
			}
			$full_path = $dir.'/'.$file;
			$info_hash = md5_file($full_path);
			if( in_array($info_hash,gpOutput::$fatal_notices) ){
				continue;
			}
			$has_fatal = true;
			break;
		}

		if( !$has_fatal ){
			return;
		}

		$msg = 'Warning: One or more components have caused fatal errors and have been disabled. '
				.common::Link('Admin_Errors','More Information');
		msg($msg);
	}

	static function SessionCookie($uniq){
		return 'gpEasy_'.substr(sha1($uniq),12,12);
	}


}
