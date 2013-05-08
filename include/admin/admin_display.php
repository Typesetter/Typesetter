<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_tools.php');

class admin_display extends display{
	var $pagetype = 'admin_display';
	var $requested = false;

	var $editable_content = false;
	var $editable_details = false;

	var $show_admin_content = true;
	var $non_admin_content = '';
	var $admin_html = '';

	function admin_display($title){
		global $langmessage;

		$this->requested = str_replace(' ','_',$title);
		$this->title = $title;

		$scripts = admin_tools::AdminScripts();

		$pos = strpos($title,'/');
		if( $pos > 0 ){
			$title = substr($title,0,$pos);
		}
		if( isset($scripts[$title]) && isset($scripts[$title]['label']) ){
			$this->label = $scripts[$title]['label'];
		}else{
			//$this->label = str_replace('_',' ',$title);
			$this->label = $langmessage['administration'];
		}

		$this->head .= "\n".'<meta name="robots" content="noindex,nofollow" />';
	}

	function RunScript(){
		global $page;

		$this->SetTheme();

		ob_start();
		if( !common::LoggedIn() ){
			$this->AnonUser();
		}else{
			$this->RunAdminScript();
		}
		$this->contentBuffer = ob_get_clean();
	}


	//called by templates
	function GetContent(){

		$this->GetGpxContent();

		if( !empty($this->non_admin_content) ){
			echo '<div class="filetype-text cf">';
			//echo '<div id="gpx_content" class="filetype-text">'; //id="gpx_content" conflicts with admin content
			echo $this->non_admin_content;
			echo '</div>';
		}

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent($ajax = false){
		global $gp_admin_html;

		if( empty($this->show_admin_content) ){
			return;
		}

		$request_type = common::RequestType();
		if( $request_type == 'body' ){
			echo $this->contentBuffer;
			return;
		}

		ob_start();
		echo '<div id="gpx_content"><div id="admincontent">';
		admin_tools::AdminContentPanel();
		echo '<div id="admincontent_inner">';
		echo $this->contentBuffer;
		echo '</div></div></div>';
		$admin_content = ob_get_clean();

		if( !$ajax && common::LoggedIn() ){
			$gp_admin_html .= admin_tools::AdminContainer().$admin_content.'</div>';
			return;
		}
		echo $admin_content;
	}

	function AnonUser(){
		$cmd = common::GetCommand();
		switch($cmd){
			case 'send_password';
				if( $this->SendPassword() ){
					$this->LoginForm();
				}else{
					$this->FogottenPassword();
				}
			break;

			case 'forgotten':
				$this->FogottenPassword();
			break;
			default:
				$this->LoginForm();
			break;
		}
	}

	/**
	 * Find the requested admin script and execute it if the user has permissions to view it
	 *
	 */
	function RunAdminScript(){
		global $dataDir,$langmessage;

		//resolve request for /Admin_Theme_Content if the request is for /Admin_Theme_Conent/1234
		$parts = explode('/',$this->requested);
		do{

			$request_string = implode('/',$parts);
			$scriptinfo = false;
			$scripts = admin_tools::AdminScripts();
			if( isset($scripts[$request_string]) ){
				$scriptinfo = $scripts[$request_string];
				if( admin_tools::HasPermission($request_string) ){

					admin_display::OrganizeFrequentScripts($request_string);
					gpOutput::ExecInfo($scriptinfo);

					return;
				}else{
					message($langmessage['not_permitted']);
					$parts = array();
				}
			}elseif( count($scripts) > 0 ){

				//check case
				$case_check = array_keys($scripts);
				$case_check = array_combine($case_check, $case_check);
				$case_check = array_change_key_case( $case_check, CASE_LOWER );

				$lower = strtolower($request_string);
				if( isset($case_check[$lower]) ){
					$location = common::GetUrl($case_check[$lower],http_build_query($_GET),false);
					common::Redirect($location);
				}
			}

			//these are here because they should be available to everyone
			switch($request_string){
				case 'Admin_Browser':
					includeFile('admin/admin_browser.php');
					new admin_browser();
				return;

				case 'Admin_Preferences':
					includeFile('admin/admin_preferences.php');
					new admin_preferences();
				return;

				case 'Admin_About':
					includeFile('admin/admin_about.php');
					new admin_about();
				return;

				case 'Admin_Finder':
					includeFile('thirdparty/finder/connector.php');
				return;

			}
			array_pop($parts);
		}while( count($parts) );

		$this->AdminPanel();
	}


	/**
	 * Show the default admin page
	 *
	 */
	function AdminPanel(){
		global $langmessage, $page;

		$cmd = common::GetCommand();
		switch($cmd){
			case 'embededcheck':
				$this->EmbededCheck();
			return;
		}

		$page->head_js[] = '/include/js/auto_width.js';

		echo '<h2>'.$langmessage['administration'].'</h2>';

		echo '<div id="adminlinks2" class="cf">';
		admin_tools::AdminPanelLinks(false);

		//resources
		echo '<div class="panelgroup" id="panelgroup_resources">';
		echo '<span class="icon_page_gear"><span>'.$langmessage['resources'].' (gpEasy.com)</span></span>';
		echo '<div class="panelgroup2">';
		echo '<ul>';

		if( admin_tools::HasPermission('Admin_Addons') ){
			echo '<li>'.common::Link('Admin_Addons/Remote',$langmessage['Download Plugins']).'</li>';
		}
		if( admin_tools::HasPermission('Admin_Theme_Content') ){
			echo '<li>'.common::Link('Admin_Theme_Content/Remote',$langmessage['Download Themes']).'</li>';
		}
		echo '<li><a href="http://gpeasy.com">Support Forum</a></li>';
		echo '<li><a href="http://gpeasy.com/Services">Service Providers</a></li>';
		echo '<li><a href="http://gpeasy.com">Official gpEasy Site</a></li>';
		echo '<li><a href="https://github.com/oyejorge/gpEasy-CMS/issues">Report A Bug</a></li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';


		echo '</div>';


		echo '<div id="adminfooter">';
		echo '<ul>';
		echo '<li>WYSIWYG editor by  <a href="http://ckeditor.com/">CKEditor.net</a></li>';
		echo '<li>Galleries made possible by <a href="http://colorpowered.com/colorbox/">ColorBox</a></li>';
		echo '<li>Icons by <a href="http://www.famfamfam.com/">famfamfam.com</a></li>';
		echo '</ul>';
		echo '</div>';
	}


	function EmbededCheck(){
		includeFile('tool/update.php');
		new update_class('embededcheck');
	}


	function SendPassword(){
		global $langmessage, $dataDir, $gp_mailer, $config;

		includeFile('tool/email_mailer.php');
		include($dataDir.'/data/_site/users.php');

		$username = $_POST['username'];

		if( !isset($users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$userinfo = $users[$username];



		if( empty($userinfo['email']) ){
			message($langmessage['no_email_provided']);
			return false;
		}

		$passwordChars = str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',3);
		$newpass = str_shuffle($passwordChars);
		$newpass = substr($newpass,0,8);


		$pass_hash = $config['passhash'];
		if( isset($users[$username]['passhash']) ){
			$pass_hash = $users[$username]['passhash'];
		}

		$users[$username]['newpass'] = common::hash($newpass,$pass_hash);
		if( !gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$link = common::AbsoluteLink('Admin',$langmessage['login']);
		$message = sprintf($langmessage['passwordremindertext'],$server,$link,$username,$newpass);

		if( $gp_mailer->SendEmail($userinfo['email'], $langmessage['new_password'], $message) ){
			list($namepart,$sitepart) = explode('@',$userinfo['email']);
			$showemail = substr($namepart,0,3).'...@'.$sitepart;
			message(sprintf($langmessage['password_sent'],$username,$showemail));
			return true;
		}


		message($langmessage['OOPS'].' (Email not sent)');

		return false;
	}


	function FogottenPassword(){
		global $langmessage, $page;
		$_POST += array('username'=>'');
		$page->css_admin[] = '/include/css/login.css';


		echo '<div id="loginform">';
		echo '<form class="loginform" action="'.common::GetUrl('Admin').'" method="post">';
		echo '<p><b>'.$langmessage['send_password'].'</b></p>';
		//echo '<b>'.sprintf($langmessage['forgotten_password'],'').'</b>';

		echo '<label>';
		echo $langmessage['username'];
		echo '<input type="text" name="username" value="'.htmlspecialchars($_POST['username']).'" class="login_text" />';
		echo '</label>';

		echo '<input type="hidden" name="cmd" value="send_password" />';
		echo '<input type="submit" name="aa" value="'.$langmessage['send_password'].'" class="login_submit" />';
		echo ' &nbsp; <label>'. common::Link('Admin',$langmessage['back']).'</label>';

		echo '</form>';
		echo '</div>';

	}

	function LoginForm(){
		global $langmessage,$page;


		$page->head .= "\n<script type=\"text/javascript\">var IE_LT_8 = false;</script><!--[if lt IE 8]>\n<script type=\"text/javascript\">IE_LT_8=true;</script>\n<![endif]-->";
		$page->head_js[] = '/include/js/login.js';
		$page->head_js[] = '/include/js/md5_sha.js';
		$page->head_js[] = '/include/thirdparty/js/jsSHA.js';

		$page->css_admin[] = '/include/css/login.css';


		$_POST += array('username'=>'');
		$_REQUEST += array('file'=>'');
		$page->admin_js = true;
		includeFile('tool/sessions.php');
		gpsession::cookie('g',2);

		echo '<div class="req_script nodisplay" id="login_container">';

		echo '<div id="browser_warning" class="nodisplay">';
		echo '<div><b>'.$langmessage['Browser Warning'].'</b></div>';
		echo '<p>';
		echo $langmessage['Browser !Supported'];
		echo '</p>';
		echo '<p>';
		echo '<a href="http://www.mozilla.com/">Firefox</a>';
		echo '<a href="http://www.google.com/chrome">Chrome</a>';
		echo '<a href="http://www.apple.com/safari">Safari</a>';
		echo '<a href="http://www.microsoft.com/windows/internet-explorer/default.aspx">Explorer</a>';

		echo '</p>';
		echo'</div>';

		echo '<div id="loginform">';
		echo '<p><b>'.$langmessage['LOGIN_REQUIRED'].'</b></p>';
			echo '<div id="login_timeout" class="nodisplay">Log in Timeout: '.common::Link('Admin','Reload to continue...').'</div>';

			echo '<form action="'.common::GetUrl('Admin').'" method="post" id="login_form">';
			echo '<input type="hidden" name="file" value="'.htmlspecialchars($_REQUEST['file']).'">';

			echo '<div>';
			echo '<input type="hidden" name="cmd" value="login" />';
			echo '<input type="hidden" name="login_nonce" value="'.htmlspecialchars(common::new_nonce('login_nonce',true,300)).'" />';
			echo '</div>';

			echo '<label>';
			echo $langmessage['username'];
			echo '<input type="text" class="login_text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '<input type="hidden" name="user_sha" value="" />';
			echo '</label>';

			echo '<label>';
			echo $langmessage['password'];
			echo '<input type="password" class="login_text password" name="password" value="" />';
			echo '<input type="hidden" name="pass_md5" value="" />';
			echo '<input type="hidden" name="pass_sha" value="" />';
			echo '<input type="hidden" name="pass_sha512" value="" />';
			echo '</label>';

			echo '<input type="submit" class="login_submit" name="aa" value="'.$langmessage['login'].'" />';

			echo '<p>';
			echo '<label>';
			echo '<input type="checkbox" name="remember" '.$this->checked('remember').'/> ';
			echo '<span>'.$langmessage['remember_me'].'</span>';
			echo '</label> ';

			echo '<label>';
			echo '<input type="checkbox" name="encrypted" '.$this->checked('encrypted').'/> ';
			echo '<span>'.$langmessage['send_encrypted'].'</span>';
			echo '</label>';
			echo '</p>';

			echo '<p>';
			echo '<label>';
			$url = common::GetUrl('Admin','cmd=forgotten');
			echo sprintf($langmessage['forgotten_password'],$url);
			echo '</label>';
			echo '</p>';


			echo '</form>';
		echo '</div>';

		echo '</div>';

		echo '<div class="without_script" id="javascript_warning">';
		echo '<p><b>'.$langmessage['JAVASCRIPT_REQ'].'</b></p>';
		echo '<p>';
		echo $langmessage['INCOMPAT_BROWSER'];
		echo ' ';
		echo $langmessage['MODERN_BROWSER'];
		echo '</p>';
		echo '</div>';





	}

	function Checked($name){

		if( strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' )
			return ' checked="checked" ';

		if( !isset($_POST[$name]) )
			return '';

		return ' checked="checked" ';
	}






	static function OrganizeFrequentScripts($page){
		global $gpAdmin;

		if( !isset($gpAdmin['freq_scripts']) ){
			$gpAdmin['freq_scripts'] = array();
		}
		if( !isset($gpAdmin['freq_scripts'][$page]) ){
			$gpAdmin['freq_scripts'][$page] = 0;
		}else{
			$gpAdmin['freq_scripts'][$page]++;
			if( $gpAdmin['freq_scripts'][$page] >= 10 ){
				admin_display::CleanFrequentScripts();
			}
		}

		arsort($gpAdmin['freq_scripts']);
	}

	static function CleanFrequentScripts(){
		global $gpAdmin;

		//reduce to length of 5;
		$count = count($gpAdmin['freq_scripts']);
		if( $count > 3 ){
			for($i=0;$i < ($count - 5);$i++){
				array_pop($gpAdmin['freq_scripts']);
			}
		}

		//reduce the hit count on each of the top five
		$min_value = end($gpAdmin['freq_scripts']);
		foreach($gpAdmin['freq_scripts'] as $page => $hits){
			$gpAdmin['freq_scripts'][$page] = $hits - $min_value;
		}
	}


}
