<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Config extends EasyComments{


	function EasyComments_Config(){


		$this->Init();
		//$this->GetIndex();
		$cmd = common::GetCommand();

		switch($cmd){

			case 'save_config':
				$this->SaveConfig();
			default:
				$this->ShowConfig();
			break;

		}
	}


	function SaveConfig(){
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


		if( !gpFiles::SaveArray($this->config_file,'config',$this->config) ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);
		return true;
	}

	function ShowConfig(){
		global $langmessage;

		$defaults = $this->Defaults();

		$array = $_POST + $this->config;

		echo '<h2>Easy Comments Configuration</h2>';

		echo '<form class="renameform" action="'.common::GetUrl('Admin_Comments_Config').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Option';
			echo '</th>';
			echo '<th>';
			echo 'Value';
			echo '</th>';
			echo '<th>';
			echo 'Default';
			echo '</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Date Format';
			echo ' (<a href="http://php.net/manual/en/function.date.php" target="_blank">About</a>)';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="date_format" size="30" value="'.htmlspecialchars($array['date_format']).'" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['date_format'];
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Commenter Website';
			echo '</td>';
			echo '<td>';
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
			echo '</td>';
			echo '<td>';
			echo 'Hide';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'reCaptcha';
			echo '</td>';
			echo '<td>';

			if( !gp_recaptcha::isActive() ){
				$disabled = ' disabled="disabled" ';
			}else{
				$disabled = '';
			}

			if( $array['comment_captcha'] ){
				echo '<input type="checkbox" name="comment_captcha" value="allow" checked="checked" '.$disabled.'/>';
			}else{
				echo '<input type="checkbox" name="comment_captcha" value="allow" '.$disabled.'/>';
			}
			echo '</td>';
			echo '<td>';
			echo '';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="save_config" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" /> ';
			echo '</td>';
			echo '<td>';
			echo '</td>';
			echo '</tr>';

		echo '</table>';
	}


}
