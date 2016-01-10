<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');

includeFile('tool/email_mailer.php');


class Configuration{

	protected $variables;

	public function __construct(){
		global $langmessage,$page;

		$page->ajaxReplace = array();


		//add examples to smtp_hosts
		$langmessage['about_config']['smtp_hosts']		.= 'ssl://smtp.gmail.com:465 ; tls://smtp.live.com:587';
		$langmessage['about_config']['showgplink']		= 'Showing the "powered by" link on your site is a great way to support gpEasy CMS.';
		$langmessage['about_config']['history_limit']	= 'Max: '.gp_backup_limit;


		$this->variables = array(

						// these values aren't used
						//'timeoffset'=>'',
						//'dateformat'=>'',

						/* General Settings */
						'general_settings'		=> false,
						'title'					=> '',
						'keywords'				=> '',
						'desc'					=> 'textarea',

						'Interface'				=> false,
						'colorbox_style'		=> array('example1'=>'Example 1', 'example2'=>'Example 2', 'example3'=>'Example 3', 'example4'=>'Example 4', 'example5'=>'Example 5' ),
						'language'				=> '',
						'langeditor'			=> '',
						'showsitemap'			=> 'boolean',
						'showlogin'				=> 'boolean',
						'showgplink'			=> 'boolean',

						'Performance'			=> false,
						'maximgarea'			=> 'integer',
						'maxthumbsize'			=> 'integer',
						'auto_redir'			=> 'integer',
						'history_limit'			=> 'integer',
						'HTML_Tidy'				=> '',
						'Report_Errors'			=> 'boolean',
						'combinejs'				=> 'boolean',
						'combinecss'			=> 'boolean',
						'etag_headers'			=> 'boolean',
						'resize_images'			=> 'boolean',
						'space_char'			=> array('_'=>'Undersorce "_"','-'=>'Dash "-"'),


						/* Contact Configuration */
						'contact_config'		=> false,
						'toemail'				=> '',
						'toname'				=> '',
						'from_address'			=> '',
						'from_name'				=> '',
						'from_use_user'			=> 'boolean',
						'require_email'			=> '',
						'contact_advanced'		=> false,
						'mail_method'			=> '',
						'sendmail_path'			=> '',
						'smtp_hosts'			=> '',
						'smtp_user'				=> '',
						'smtp_pass'				=> 'password',
						//'fromemail'			=> '',

						'reCaptcha'				=> false,
						'recaptcha_public'		=> '',
						'recaptcha_private'		=> '',
						'recaptcha_language'	=> '',
						);

		$cmd = \common::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
		}

		$this->showForm();
	}


	/**
	 * Save the posted configuration
	 *
	 */
	protected function SaveConfig(){
		global $config, $langmessage;


		$possible = $this->getPossible();

		foreach($possible as $key => $curr_possible){

			if( $curr_possible == 'boolean' ){
				if( isset($_POST[$key]) && ($_POST[$key] == 'true') ){
					$config[$key] = true;
				}else{
					$config[$key] = false;
				}

			}elseif( $curr_possible == 'integer' ){
				if( isset($_POST[$key]) && is_numeric($_POST[$key]) ){
					$config[$key] = $_POST[$key];
				}

			}elseif( isset($_POST[$key]) ){
				$config[$key] = $_POST[$key];
			}
		}


		$config['history_limit'] = min($config['history_limit'],gp_backup_limit);

		if( !\gp\admin\Tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}

		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		}else{
			message($langmessage['SAVED']);
		}

	}


	private function getValues(){
		global $config, $gp_mailer;

		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			$show = $config;
		}else{
			$show = $_POST;
		}
		if( empty($show['recaptcha_language']) ){
			$show['recaptcha_language'] = 'inherit';
		}

		if( empty($show['from_address']) ){
			$show['from_address'] = $gp_mailer->From_Address();
		}
		if( empty($show['from_name']) ){
			$show['from_name'] = $gp_mailer->From_Name();
		}
		if( empty($show['mail_method']) ){
			$show['mail_method'] = $gp_mailer->Mail_Method();
		}

		//suhosin will stop the script if a POST value contains a real path like /usr/sbin/sendmail
		//if( empty($show['sendmail_path']) ){
		//	$show['sendmail_path'] = $gp_mailer->Sendmail_Path();
		//}

		return $show;
	}


	/**
	 * Get possible configuration values
	 *
	 */
	protected function getPossible(){
		global $dataDir,$langmessage;

		$possible = $this->variables;

		$langDir = $dataDir.'/include/thirdparty/ckeditor_34/lang'; //ckeditor

		$possible['langeditor'] = \gpFiles::readDir($langDir,'js');
		unset($possible['langeditor']['_languages']);
		$possible['langeditor']['inherit'] = ' '.$langmessage['default']; //want it to be the first in the list
		asort($possible['langeditor']);


		//recaptcha language
		$possible['recaptcha_language'] = array();
		$possible['recaptcha_language']['inherit'] = $langmessage['default'];
		$possible['recaptcha_language']['en'] = 'en';
		$possible['recaptcha_language']['nl'] = 'nl';
		$possible['recaptcha_language']['fr'] = 'fr';
		$possible['recaptcha_language']['de'] = 'de';
		$possible['recaptcha_language']['pt'] = 'pt';
		$possible['recaptcha_language']['ru'] = 'ru';
		$possible['recaptcha_language']['es'] = 'es';
		$possible['recaptcha_language']['tr'] = 'tr';



		//website language
		$possible['language'] = $this->GetPossibleLanguages();


		//tidy
		if( function_exists('tidy_parse_string') ){
			$possible['HTML_Tidy'] = array('off'=>$langmessage['Off'],''=>$langmessage['On']);
		}else{
			$possible['HTML_Tidy'] = array(''=>'Unavailable');
		}



		//required email fields
		$possible['require_email'] = array(	'none'      => 'None',
											''          => 'Subject &amp; Message',
											'email'     => 'Subject, Message &amp; Email' );


		//see xoopsmultimailer.php
		$possible['mail_method'] = array(	'mail'      => 'PHP mail()',
											'sendmail'  => 'sendmail',
											'smtp'      => 'smtp',
											'smtpauth'  => 'SMTPAuth' );

		//CDN
		foreach(\gp\tool\Output\Combine::$scripts as $key => $script_info){
			if( !isset($script_info['cdn']) ){
				continue;
			}

			$config_key              = 'cdn_'.$key;

			if( !array_key_exists($config_key, $possible) ){
				continue;
			}

			$opts                     = array_keys($script_info['cdn']);
			$possible[$config_key]    = array_combine($opts, $opts);
			array_unshift($possible[$config_key],$langmessage['None']);
		}


		gpSettingsOverride('configuration',$possible);


		return $possible;
	}


	/**
	 * Return a list of possible languages
	 * Based on the files in /include/languages
	 *
	 */
	private function GetPossibleLanguages(){
		global $dataDir;
		$lang_dir = $dataDir.'/include/languages';

		$files		= scandir($lang_dir);
		$languages	= array();
		foreach($files as $file){
			if( $file == '.' || $file == '..' || strpos($file,'main.inc') === false ){
				continue;
			}

			$languages[] = str_replace('.main.inc','',$file);
		}

		return array_combine($languages, $languages);
	}

	/**
	 * Display configuration settings
	 *
	 */
	protected function showForm(){
		global $langmessage, $page;

		$possible_values	= $this->getPossible();
		$array				= $this->getValues();

		echo '<form action="'.\common::GetUrl($page->requested).'" method="post">';



		//order by the possible values
		$opened = false;
		foreach($possible_values as $key => $possible_value){

			if( $possible_value === false ){
				if( $opened ){
					echo '</table><br/>';
				}
				echo '<h2>';
				if( isset($langmessage[$key]) ){
					echo $langmessage[$key];
				}else{
					echo str_replace('_',' ',$key);
				}
				echo '</h2>';
				echo '<table class="bordered configuration">';
				$opened = true;
				continue;
			}

			if( isset($array[$key]) ){
				$value = $array[$key];
			}else{
				$value = '';
			}

			echo "\n\n";


			echo '<tr><td style="white-space:nowrap">';
			if( isset($langmessage[$key]) ){
				echo $langmessage[$key];
			}else{
				echo str_replace('_',' ',$key);
			}
			echo '</td><td>';


			if( is_array($possible_value) ){
				self::formSelect($key,$possible_value,$value);
			}else{
				switch($possible_value){
					case 'boolean':
						$this->formCheckbox($key,$value);
					break;
					case 'textarea':
						$this->formTextarea($key,$value);
					break;
					default:
						$this->formInput($key,$value,$possible_value);
					break;
				}
			}

			if( isset($langmessage['about_config'][$key]) ){
				echo $langmessage['about_config'][$key];
			}
			echo '</td></tr>';

		}

		echo '</table>';


		$this->SaveButtons();
		echo '</form>';
	}


	/**
	 * Display Save buttons
	 *
	 */
	protected function SaveButtons(){
		global $langmessage;


		echo '<div style="margin:1em 0">';
		echo '<input type="hidden" name="cmd" value="save_config" />';

		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			echo '<input value="'.$langmessage['save'].'" type="submit" name="aaa" accesskey="s" class="gppost gpsubmit" />';
		}else{
			echo '<input value="'.$langmessage['save'].'" type="submit" name="aaa" accesskey="s" class="gpsubmit"/>';
		}

 		echo '</div>';

		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo \common::Link('Admin/Preferences',$langmessage['Preferences'],'','data-cmd="gpabox"');
		echo '</p>';

	}


	/**
	 *	Form Functions
	 *
	 */
	public function formCheckbox($key,$value){
		$checked = '';
		if( $value && $value !== 'false' ){
			$checked = ' checked="checked"';
		}
		echo '<input type="hidden" name="'.$key.'" value="false" '.$checked.'/> &nbsp;';
		echo '<input type="checkbox" name="'.$key.'" value="true" '.$checked.'/> &nbsp;';
	}

	public function formInput($name,$value,$type='text'){
		echo "\n<div>";
		echo '<input id="'.$name.'" name="'.$name.'" size="50" value="'.htmlspecialchars($value).'" type="'.$type.'" class="gpinput"/>';
		echo '</div>';
	}

	public function formTextarea($name,$value){
		global $langmessage;
		$count_label = sprintf($langmessage['_characters'],'<span>'.strlen($value).'</span>');
		echo '<span class="show_character_count gptextarea">';
		echo '<textarea id="'.$name.'" name="'.$name.'" cols="50" rows="2">'.htmlspecialchars($value).'</textarea>';
		echo '<span class="character_count">'.$count_label.'</span>';
		echo '</span>';
	}

	public static function formSelect($name,$possible,$value=null){

		echo '<div>';
		echo "\n".'<select name="'.$name.'" class="gpselect">';
		if( !isset($possible[$value]) ){
			echo '<option value="" selected="selected"></option>';
		}

		self::formOptions($possible,$value);
		echo '</select>';
		echo '</div>';
	}

	public static function formOptions($array,$current_value){
		global $languages;

		foreach($array as $key => $value){
			if( is_array($value) ){
				echo '<optgroup label="'.$value.'">';
				$this->formOptions($value,$current_value);
				echo '</optgroup>';
				continue;
			}

			if($key == $current_value){
				$focus = ' selected="selected" ';
			}else{
				$focus = '';
			}
			if( isset($languages[$value]) ){
				$value = $languages[$value];
			}

			echo '<option value="'.htmlspecialchars($key).'" '.$focus.'>'.$value.'</option>';

		}

	}

}
