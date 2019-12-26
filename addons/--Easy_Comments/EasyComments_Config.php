<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Config extends EasyComments{

	public function __construct(){
		parent::__construct();

		$cmd = \gp\tool::GetCommand();

		switch($cmd){

			case 'save_config':
				$this->SaveConfig();
			default:
				$this->ShowConfig();
			break;

		}
	}


	/**
	 * Save posted configuration options
	 *
	 */
	public function SaveConfig(){
		global $langmessage;

		$format = htmlspecialchars($_POST['date_format']);
		if( @date($format) ){
			$this->config['date_format'] = $format;
		}

		$this->config['commenter_website'] = (string)$_POST['commenter_website'];

		if( isset($_POST['comment_captcha']) ){
			$this->config['comment_captcha'] = true;
		}else{
			$this->config['comment_captcha'] = false;
		}


		if( !\gp\tool\Files::SaveData($this->config_file, 'config', $this->config) ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Show EasyComments configuration options
	 *
	 */
	public function ShowConfig(){
		global $langmessage;

		$defaults = $this->Defaults();

		$array = $_POST + $this->config;

		echo '<h2>Easy Comments Configuration</h2>';

		echo '<form class="renameform" action="'.\gp\tool::GetUrl('Admin_Comments_Config').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr><th>';
			echo 'Option';
			echo '</th><th>';
			echo 'Value';
			echo '</th><th>';
			echo 'Default';
			echo '</th></tr>';

		echo '<tr><td>';
			echo 'Date Format';
			echo ' (<a href="http://php.net/manual/en/function.date.php" target="_blank">About</a>)';
			echo '</td><td>';
			echo '<input type="text" name="date_format" size="30" value="'.htmlspecialchars($array['date_format']).'" />';
			echo '</td><td>';
			echo $defaults['date_format'];
			echo '</td></tr>';

		echo '<tr><td>';
			echo 'Commenter Website';
			echo '</td><td>';
			echo '<select name="commenter_website">';
				if( $array['commenter_website'] == 'nofollow' ){
					echo '<option value="">Hide</option>';
					echo '<option value="nofollow" selected="selected">Nofollow Link</option>';
					echo '<option value="link">Follow Link</option>';
				}elseif( $array['commenter_website'] == 'link' ){
					echo '<option value="">Hide</option>';
					echo '<option value="nofollow" selected="selected">Nofollow Link</option>';
					echo '<option value="link" selected="selected">Follow Link</option>';
				}else{
					echo '<option value="">Hide</option>';
					echo '<option value="nofollow">Nofollow Link</option>';
					echo '<option value="link">Follow Link</option>';
				}
			echo '</select>';
			echo '</td><td>';
			echo 'Hide';
			echo '</td></tr>';

		echo '<tr><td>';
			echo 'reCaptcha';
			echo '</td><td>';

			if( !\gp\tool\Recaptcha::isActive() ){
				$disabled = ' disabled="disabled" ';
			}else{
				$disabled = '';
			}

			if( $array['comment_captcha'] ){
				echo '<input type="checkbox" name="comment_captcha" value="allow" checked="checked" '.$disabled.'/>';
			}else{
				echo '<input type="checkbox" name="comment_captcha" value="allow" '.$disabled.'/>';
			}
			echo '</td><td>';
			echo '';
			echo '</td></tr>';

		echo '<tr><td>';
			echo '</td><td>';
			echo '<input type="hidden" name="cmd" value="save_config" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" /> ';
			echo '</td><td>';
			echo '</td></tr>';

		echo '</table>';
	}


}
