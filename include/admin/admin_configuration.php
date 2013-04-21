<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/email_mailer.php');


class admin_configuration{

	var $variables;
	//var $defaultVals = array();

	function admin_configuration(){
		global $langmessage,$page;

		$page->ajaxReplace = array();
		//$page->head_js[] = '/include/js/x_forms.js';


		//add examples to smtp_hosts
		$langmessage['about_config']['smtp_hosts'] .= ' smtp.yourserver.com ; ssl://smtp.gmail.com:465';
		$langmessage['about_config']['showgplink'] = 'Showing the "powered by" link on your site is a great way to support gpEasy CMS.';
		$langmessage['jquery'] = 'Google CDN';

		$this->variables = array(

						// these values exist and are used, but not necessarily needed

						// these values aren't used
						//'author'=>'',
						//'timeoffset'=>'',
						//'fromname'=>'',
						//'fromemail'=>'',
						//'contact_message'=>'',
						//'dateformat'=>'',

						/* General Settings */
						'general_settings'=>false,
						'title'=>'',
						'keywords'=>'',
						'desc'=>'textarea',

						'Interface'=>false,
						'colorbox_style' => array('example1'=>'Example 1', 'example2'=>'Example 2', 'example3'=>'Example 3', 'example4'=>'Example 4', 'example5'=>'Example 5', 'example6'=>'Example 6'),
						'language'=>'',
						'langeditor'=>'',
						'showsitemap'=>'boolean',
						'showlogin'=>'boolean',
						'showgplink'=>'boolean',

						'Performance'=>false,
						'jquery'=>'',
						'maximgarea'=>'integer',
						'maxthumbsize'=>'integer',
						'auto_redir'=>'integer',
						'HTML_Tidy'=>'',
						'Report_Errors'=>'boolean',
						'combinejs'=>'boolean',
						'combinecss'=>'boolean',
						'etag_headers'=>'boolean',
						'resize_images'=>'boolean',


						/* Contact Configuration */
						'contact_config'=>false,
						'toemail'=>'',
						'toname'=>'',
						'from_address'=>'',
						'from_name'=>'',
						'from_use_user'=>'boolean',
						'require_email'=>'',
						'contact_advanced'=>false,
						'mail_method'=>'',
						'sendmail_path'=>'',
						'smtp_hosts'=>'',
						'smtp_user'=>'',
						'smtp_pass'=>'password',
						//'fromemail'=>'',

						'reCaptcha'=>false,
						'recaptcha_public'=>'',
						'recaptcha_private'=>'',
						'recaptcha_language'=>'',
						);

		$cmd = common::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
		}

		echo '<h2>'.$langmessage['configuration'].'</h2>';
		$this->showForm();
	}


	function SaveConfig(){
		global $config, $langmessage;

		$possible = $this->variables;

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

		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}

		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			message($langmessage['SAVED'].' '.$langmessage['REFRESH']);
		}else{
			message($langmessage['SAVED']);
		}

	}


	function getValues(){
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

	function getPossible(){
		global $dataDir,$langmessage;

		$possible = $this->variables;

		//$langDir = $dataDir.'/include/thirdparty/fckeditor/editor/lang'; //fckeditor
		$langDir = $dataDir.'/include/thirdparty/ckeditor_34/lang'; //ckeditor

		$possible['langeditor'] = gpFiles::readDir($langDir,'js');
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
		$langDir = $dataDir.'/include/languages';
		$possible['language'] = gpFiles::readDir($langDir,1);
		asort($possible['language']);

		//jQuery
		$possible['jquery'] = array('local'=>$langmessage['None'],'google'=>'jQuery','jquery_ui'=>'jQuery & jQuery UI');


		//tidy
		if( function_exists('tidy_parse_string') ){
			$possible['HTML_Tidy'] = array('off'=>$langmessage['Off'],''=>$langmessage['On']);
		}else{
			$possible['HTML_Tidy'] = array(''=>'Unavailable');
		}



		//
		$possible['require_email'] = array(	'none'=>'None',
											''=>'Subject &amp; Message',
											'email'=>'Subject, Message &amp; Email');


		//see xoopsmultimailer.php
		$possible['mail_method'] = array(	'mail'=>'PHP mail()',
											'sendmail'=>'sendmail',
											'smtp'=>'smtp',
											'smtpauth'=>'SMTPAuth');



		return $possible;
	}

	function showForm(){
		global $langmessage;
		$possible_values = $this->getPossible();


		$array = $this->getValues();

		echo '<form action="'.common::GetUrl('Admin_Configuration').'" method="post">';
		echo '<div class="collapsible">';


		//order by the possible values
		$openbody = false;
		foreach($possible_values as $key => $possible_value){

			if( $possible_value === false ){
				$class = $style = '';
				if( $openbody ){
					echo '</table>';
					echo '</div>';
					$class = ' gp_collapsed';
					$style = ' nodisplay';
				}
				echo '<h4 class="head'.$class.' one">';
				echo '<a data-cmd="collapsible">';
				if( isset($langmessage[$key]) ){
					echo $langmessage[$key];
				}else{
					echo str_replace('_',' ',$key);
				}
				echo '</a>';
				echo '</h4>';


				//start new
				echo '<div class="collapsearea'.$style.'">';
				echo '<table class="bordered configuration">';

				$openbody = true;
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
			echo '</td>';
			echo '<td>';


			if( is_array($possible_value) ){
				$this->formSelect($key,$possible_value,$value);
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
		echo '</div>';
		echo '</div>'; //end collapsible

		echo '<div style="margin:1em 0">';
		echo '<input type="hidden" name="cmd" value="save_config" />';

		if( isset($_GET['gpreq']) && $_GET['gpreq'] == 'json' ){
			echo '<input value="'.$langmessage['save'].'" type="submit" name="aaa" accesskey="s" class="gppost gpsubmit" />';
		}else{
			echo '<input value="'.$langmessage['save'].'" type="submit" name="aaa" accesskey="s" class="gpsubmit"/>';
		}
		echo ' <input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';

 		echo '</div>';

		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo common::Link('Admin_Preferences',$langmessage['Preferences'],'','data-cmd="gpabox"');
		echo '</p>';

		echo '</form>';
	}


	/**
	 *	Form Functions
	 *
	 */
	function formCheckbox($key,$value){
		$checked = '';
		if( $value && $value !== 'false' ){
			$checked = ' checked="checked"';
		}
		echo '<input type="hidden" name="'.$key.'" value="false" '.$checked.'/> &nbsp;';
		echo '<input type="checkbox" name="'.$key.'" value="true" '.$checked.'/> &nbsp;';
	}

	function formInput($name,$value,$type='text'){
		echo "\n<div>";
		echo '<input id="'.$name.'" name="'.$name.'" size="50" value="'.htmlspecialchars($value).'" type="'.$type.'" class="gpinput"/>';
		echo '</div>';
	}

	function formTextarea($name,$value){
		global $langmessage;
		$count_label = sprintf($langmessage['_characters'],'<span>'.strlen($value).'</span>');
		echo '<textarea id="'.$name.'" name="'.$name.'" cols="40" rows="2" class="gptextarea show_character_count">'.htmlspecialchars($value).'</textarea><span class="character_count">'.$count_label.'</span>';
	}

	function formSelect($name,$possible,$value=null){

		echo '<div>';
		echo "\n".'<select name="'.$name.'" class="gpselect">';
		if( !isset($possible[$value]) ){
			echo '<option value="" selected="selected"></option>';
		}

		$this->formOptions($possible,$value);
		echo '</select>';
		echo '</div>';
	}

	function formOptions($array,$current_value){
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
