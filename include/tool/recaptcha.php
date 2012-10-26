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
	static function GetForm(){
		global $config,$dataDir;

		$html = '';
		if( gp_recaptcha::hasRecaptcha() ){

			includeFile('thirdparty/recaptchalib.php');
			$themes = array('red','white','blackglass','clean');
			$theme = 'clean';


			$lang = $config['recaptcha_language'];
			if( $lang == 'inherit' ){
				$lang = $config['language'];
			}

			$recaptchaLangs['en'] = true;
			$recaptchaLangs['nl'] = true;
			$recaptchaLangs['fr'] = true;
			$recaptchaLangs['de'] = true;
			$recaptchaLangs['pt'] = true;
			$recaptchaLangs['ru'] = true;
			$recaptchaLangs['es'] = true;
			$recaptchaLangs['tr'] = true;
			if( isset($recaptchaLangs[$lang]) ){
				$html .= '<script type="text/javascript">var RecaptchaOptions = { lang : "'.$lang.'", theme:"'.$theme.'" };</script>';
			}

			$html .= recaptcha_get_html($config['recaptcha_public']);
		}

		return gpPlugin::Filter('AntiSpam_Form',array($html));
	}

	/**
	 * Ouptut the html of a recaptcha area for use in a  <form>
	 * @static
	 *
	 */
	static function Form(){
		echo gp_recaptcha::GetForm();
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


		//prevent undefined index warnings if there is a bot
		$_POST += array('recaptcha_challenge_field'=>'','recaptcha_response_field'=>'');

		//includeFile('thirdparty/recaptchalib.php');
		require_once($dataDir.'/include/thirdparty/recaptchalib.php');
		$resp = recaptcha_check_answer($config['recaptcha_private'],
										$_SERVER['REMOTE_ADDR'],
										$_POST['recaptcha_challenge_field'],
										$_POST['recaptcha_response_field']);



		if (!$resp->is_valid) {
			message($langmessage['INCORRECT_CAPTCHA']);
			//if( common::LoggedIn() ){
			//	message($langmessage['recaptcha_said'],$resp->error);
			//}
			return false;
		}

		return true;
	}
}

class gp_antispam extends gp_recaptcha{}


