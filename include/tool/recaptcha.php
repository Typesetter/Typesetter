<?php

class gp_recaptcha{

	/**
	 * Checks to see if any anti-spam functionality is enabled
	 * @static
	 * @return bool True if plugin-hooks or recaptcha is enabled
	 */
	static function isActive(){

		if( gpPlugin::HasHook('AntiSpam_Form') && gpPlugin::HasHook('AntiSpam_Check') ){
			return true;
		}
		return gp_recaptcha::hasRecaptcha();
	}

	/**
	 * Checks to see if recaptcha configuration has been set up
	 * @static
	 * @return bool True if recaptcha_public and recaptcha_private are set
	 */
	static function hasRecaptcha(){
		global $config;

		if( !empty($config['recaptcha_public']) && !empty($config['recaptcha_private']) ){
			return true;
		}

		return false;
	}


	/**
	 * Return the html of a recaptcha area for use in a  <form>
	 * @static
	 * @return string
	 */
	static function GetForm($theme='dark'){
		global $config;

		$html = '';
		if( gp_recaptcha::hasRecaptcha() ){
			includeFile('thirdparty/recaptcha/autoload.php');
			$html = '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    		$html .= '<div class="g-recaptcha" data-theme="'.$theme.'" data-sitekey="'.$config['recaptcha_public'].'"></div>'; //data-size="compact"
		}

		return gpPlugin::Filter('AntiSpam_Form',array($html));
	}

	/**
	 * Ouptut the html of a recaptcha area for use in a  <form>
	 * @static
	 *
	 */
	static function Form($theme='dark'){
		echo gp_recaptcha::GetForm($theme);
	}

	/**
	 * Verify the user submitted form by checking anti-spam hooks and/or recaptcha if they exist
	 * @static
	 *
	 */
	static function Check(){
		global $page,$langmessage,$config,$dataDir;

		// if hooks return false, stop
		if( !gpPlugin::Filter('AntiSpam_Check',array(true)) ) return false;

		// if recaptcha inactive, stop
		if( !gp_recaptcha::hasRecaptcha() ) return true;

		require_once($dataDir.'/include/thirdparty/recaptcha/autoload.php');

		if (!ini_get('allow_url_fopen')) {
			// allow_url_fopen = Off
			$recaptcha = new \ReCaptcha\ReCaptcha($config['recaptcha_private'], new \ReCaptcha\RequestMethod\SocketPost());
		}
		else {
			// allow_url_fopen = On
			$recaptcha = new \ReCaptcha\ReCaptcha($config['recaptcha_private']);
		}

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $ip);
		if (!$resp->isSuccess()) {
			//$error_codes = $resp->getErrorCodes();
			//error_log();
			message($langmessage['INCORRECT_CAPTCHA']);
			return false;
		}

		return true;
	}
}

class gp_antispam extends gp_recaptcha{}


