<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');

includeFile('tool/Image.php');

class Configuration extends \gp\special\Base{

	protected $variables;

	public function __construct($args){
		global $langmessage;

		parent::__construct($args);

		$this->page->ajaxReplace = array();


		//add examples to smtp_hosts
		$langmessage['about_config']['smtp_hosts']		.= 'ssl://smtp.gmail.com:465 ; tls://smtp.live.com:587';
		$langmessage['about_config']['showgplink']		= 'Showing the "powered by" link on your site is a great way to support '.CMS_NAME.' CMS.';
		$langmessage['about_config']['history_limit']	= 'Max: '.gp_backup_limit;
		$langmessage['about_config']['maxthumbsize']	.= ' '.\gp\tool::Link('Admin/Configuration',$langmessage['recreate_all_thumbnails'],'cmd=recreate_thumbs','class="" data-cmd="creq"');

		$this->variables = array(

						// these values aren't used
						//'timeoffset'=>'',
						//'dateformat'=>'',

						/* General Settings */
						'general_settings'			=> false,
						'title'						=> '',
						'keywords'					=> '',
						'desc'						=> 'textarea',

						'Interface'					=> false,
						'colorbox_style'			=> array('example1'=>'Example 1', 'example2'=>'Example 2', 'example3'=>'Example 3', 'example4'=>'Example 4', 'example5'=>'Example 5' ),
						'gallery_legacy_style'		=> 'boolean',
						'language'					=> '',
						'langeditor'				=> '',
						'showsitemap'				=> 'boolean',
						'showlogin'					=> 'boolean',
						'showgplink'				=> 'boolean',

						'Images'					=> false,
						'maximgarea'				=> 'integer',
						'resize_images'				=> 'boolean',
						'preserve_icc_profiles' 	=> 'boolean',
						'preserve_image_metadata' 	=> 'boolean',
						'maxthumbsize'				=> 'integer',
						'maxthumbheight'			=> 'integer',
						'thumbskeepaspect'			=> 'boolean',

						'Performance'				=> false,
						'auto_redir'				=> 'integer',
						'history_limit'				=> 'integer',
						'HTML_Tidy'					=> '',
						'Report_Errors'				=> 'boolean',
						'combinejs'					=> 'boolean',
						'combinecss'				=> 'boolean',
						'etag_headers'				=> 'boolean',
						'space_char'				=> array('_'=>'Undersorce "_"','-'=>'Dash "-"'),


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

	}

	public function RunScript(){

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
			case 'recreate_thumbs':
				$this->RecreateThumbs();
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

		$config_before	= $config;
		$possible		= $this->getPossible();

		foreach($possible as $key => $curr_possible){

			if( $curr_possible == 'boolean' ){
				if( isset($_POST[$key]) && ($_POST[$key] == 'true') ){
					$config[$key] = true;
				}else{
					$config[$key] = false;
				}

			}elseif( $curr_possible == 'integer' ){
				if( isset($_POST[$key]) && ( is_numeric($_POST[$key]) || $_POST[$key] == '' ) ){ // also allow empty values
					$config[$key] = $_POST[$key];
				}

			}elseif( isset($_POST[$key]) ){
				$config[$key] = $_POST[$key];
			}
		}


		$config['history_limit'] = min($config['history_limit'],gp_backup_limit);

		if( !\gp\admin\Tools::SaveConfig(true) ){
			return false;
		}


		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		}else{
			message($langmessage['SAVED']);
		}

		//resize thumbnails
		if( 
			$config_before['preserve_icc_profiles'] !== $config['preserve_icc_profiles'] 
			|| $config_before['preserve_image_metadata'] !== $config['preserve_image_metadata'] 
			|| $config_before['maxthumbsize'] !== $config['maxthumbsize'] 
			|| $config_before['maxthumbheight'] !== $config['maxthumbheight'] 
			|| $config_before['thumbskeepaspect'] !== $config['thumbskeepaspect'] 
		){
			msg(\gp\tool::Link('Admin/Configuration',$langmessage['recreate_all_thumbnails'],'cmd=recreate_thumbs','class="" data-cmd="creq"'));
		}


	}


	private function getValues(){
		global $config;

		$mailer = new \gp\tool\Emailer();

		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			$show = $config;
		}else{
			$show = $_POST;
		}
		if( empty($show['recaptcha_language']) ){
			$show['recaptcha_language'] = 'inherit';
		}

		if( empty($show['from_address']) ){
			$show['from_address'] = $mailer->From_Address();
		}
		if( empty($show['from_name']) ){
			$show['from_name'] = $mailer->From_Name();
		}
		if( empty($show['mail_method']) ){
			$show['mail_method'] = $mailer->Mail_Method();
		}

		//suhosin will stop the script if a POST value contains a real path like /usr/sbin/sendmail
		//if( empty($show['sendmail_path']) ){
		//	$show['sendmail_path'] = $mailer->Sendmail_Path();
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

		$possible['langeditor'] = \gp\tool\Files::readDir($langDir,'js');
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
		$possible['require_email'] = array(	'none'		=> 'None',
											''			=> 'Subject &amp; Message',
											'email'		=> 'Subject, Message &amp; Email' );


		//see xoopsmultimailer.php
		$possible['mail_method'] = array(	'mail'		=> 'PHP mail()',
											'sendmail'	=> 'sendmail',
											'smtp'		=> 'smtp',
											'smtpauth'	=> 'SMTPAuth' );

		//CDN
		foreach(\gp\tool\Output\Combine::$scripts as $key => $script_info){
			if( !isset($script_info['cdn']) ){
				continue;
			}

			$config_key					= 'cdn_'.$key;

			if( !array_key_exists($config_key, $possible) ){
				continue;
			}

			$opts						= array_keys($script_info['cdn']);
			$possible[$config_key]		= array_combine($opts, $opts);
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
		global $langmessage;

		$possible_values	= $this->getPossible();
		$array				= $this->getValues();

		echo '<form action="'.\gp\tool::GetUrl($this->page->requested).'" method="post">';



		//order by the possible values
		$opened = false;
		foreach($possible_values as $key => $possible_value){

			if( $possible_value === false ){
				if( $opened ){
					echo '</table>';
					$this->SaveAllButton(false);
					echo '<br/>';
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


		$this->SaveAllButton(true);
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
		echo \gp\tool::Link('Admin/Preferences',$langmessage['Preferences'],'','data-cmd="gpabox"');
		echo '</p>';

	}


	/**
	 * Display Save All buttons
	 * @param boolean $is_last If true include admin notice and hidden cmd input
	 */
	protected function SaveAllButton($is_last=true){
		global $langmessage;

		echo '<div style="margin:1em 0">';

		if( $is_last ){
			echo '<input type="hidden" name="cmd" value="save_config" />';
		}

		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			echo '<input value="' . $langmessage['save'] . ' (' . $langmessage['All'] . ')" type="submit" name="aaa" accesskey="s" class="gppost gpsubmit" />';
		}else{
			echo '<input value="' . $langmessage['save'] . ' (' . $langmessage['All'] . ')" type="submit" name="aaa" accesskey="s" class="gpsubmit"/>';
		}

 		echo '</div>';

		if( $is_last ){
			echo '<p class="admin_note">';
			echo '<b>';
			echo $langmessage['see_also'];
			echo '</b> ';
			echo \gp\tool::Link('Admin/Preferences',$langmessage['Preferences'],'','data-cmd="gpabox"');
			echo '</p>';
		}

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
				self::formOptions($value,$current_value);
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

	/**
	 * Recreate all of the thumbnails according to the size in the configuration
	 *
	 */
	function RecreateThumbs($dir_rel = ''){
		global $dataDir;

		$dir_full	= $dataDir.'/data/_uploaded'.$dir_rel;
		$files		= scandir($dir_full);

		foreach($files as $file){

			if( $file == '.' || $file == '..' || $file == 'thumbnails' ){
				continue;
			}

			$file_full	= $dir_full.'/'.$file;
			$file_rel	= $dir_rel.'/'.$file;

			if( is_dir($file_full) ){
				$this->RecreateThumbs($file_rel);
				continue;
			}

			if( \gp\admin\Content\Uploaded::IsImg($file_full) ){
				\gp\admin\Content\Uploaded::CreateThumbnail($file_full);
			}
		}

	}

}
