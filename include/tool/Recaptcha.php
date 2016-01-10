<?php

namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	class Recaptcha{

		/**
		 * Checks to see if any anti-spam functionality is enabled
		 * @static
		 * @return bool True if plugin-hooks or recaptcha is enabled
		 */
		public static function isActive(){

			if( \gp\tool\Plugins::HasHook('AntiSpam_Form') && \gp\tool\Plugins::HasHook('AntiSpam_Check') ){
				return true;
			}
			return self::hasRecaptcha();
		}

		/**
		 * Checks to see if recaptcha configuration has been set up
		 * @static
		 * @return bool True if recaptcha_public and recaptcha_private are set
		 */
		public static function hasRecaptcha(){
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
		public static function GetForm($theme='light'){
			global $config;

			$html = '';
			if( self::hasRecaptcha() ){
				includeFile('thirdparty/recaptcha/autoload.php');
				$html = '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
				$html .= '<div class="g-recaptcha" data-theme="'.$theme.'" data-sitekey="'.$config['recaptcha_public'].'"></div>'; //data-size="compact"
			}

			return \gp\tool\Plugins::Filter('AntiSpam_Form',array($html));
		}

		/**
		 * Ouptut the html of a recaptcha area for use in a  <form>
		 * @static
		 *
		 */
		public static function Form($theme='light'){
			echo self::GetForm($theme);
		}

		/**
		 * Verify the user submitted form by checking anti-spam hooks and/or recaptcha if they exist
		 * @static
		 *
		 */
		public static function Check(){
			global $page,$langmessage,$config,$dataDir;

			// if hooks return false, stop
			if( !\gp\tool\Plugins::Filter('AntiSpam_Check',array(true)) ) return false;

			// if recaptcha inactive, stop
			if( !self::hasRecaptcha() ) return true;

			if( empty($_POST['g-recaptcha-response']) ){
				return false;
			}

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
				msg($langmessage['INCORRECT_CAPTCHA']);
				return false;
			}

			return true;
		}
	}
}

namespace{
	class gp_recaptcha extends \gp\tool\Recaptcha{}
}

