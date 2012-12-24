<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/recaptcha.php');

global $contact_message_sent, $message_send_attempt;
$contact_message_sent = false;
$message_send_attempt = false;

class special_contact extends special_contact_gadget{
	var $sent = false;

	function special_contact(){
		$this->special_contact_gadget();
	}

	function ShowForm(){
		global $page,$langmessage,$config;

		echo gpOutput::GetExtra('Contact');
		parent::ShowForm();
	}

}

class special_contact_gadget{
	var $sent = false;

	function special_contact_gadget(){
		global $page,$langmessage,$config,$contact_message_sent,$message_send_attempt;

		$this->sent = $contact_message_sent;

		if( empty($config['toemail']) ){

			if( common::LoggedIn() ){
				$url = common::GetUrl('Admin_Configuration');
				message($langmessage['enable_contact'],$url);
			}

			echo $langmessage['not_enabled'];
			return;
		}

		$cmd = common::GetCommand();
		switch($cmd){
			case 'gp_send_message':
				if( !$message_send_attempt  ){
					$message_send_attempt  = true;
					if( !$this->sent && $this->SendMessage() ){
						$this->sent = $contact_message_sent = true;
						break;
					}
				}
			default:
			break;
		}

		$this->ShowForm();
	}


	function SendMessage(){
		global $langmessage, $config, $gp_mailer;

		includeFile('tool/email_mailer.php');


		$headers = array();
		$_POST += array('subject'=>'','contact_nonce'=>'');


		//check nonce
		if( !common::verify_nonce('contact_post',$_POST['contact_nonce'],true) ){
			message($langmessage['OOPS'].'(Invalid Nonce)');
			return;
		}
		if( !empty($_POST['contact_void']) ){
			message($langmessage['OOPS'].'(Robot Detected)');
			return;
		}


		//captcha
		if( !gp_recaptcha::Check() ){
			return;
		}

		if( !gpPlugin::Filter('contact_form_check',array(true)) ){
			return;
		}

		//subject
		$_POST['subject'] = strip_tags($_POST['subject']);

		//message
		$tags = '<p><div><span><font><b><i><tt><em><i><a><strong><blockquote>';
		$message = nl2br(strip_tags($_POST['message'],$tags));


		//reply name
		if( !empty($_POST['email']) ){

			//check format
			if( !$this->ValidEmail($_POST['email']) ){
				message($langmessage['invalid_email']);
				return false;
			}

			$replyName = str_replace(array("\r","\n"),array(' '),$_POST['name']);
			$replyName = strip_tags($replyName);
			$replyName = htmlspecialchars($replyName);

			$gp_mailer->AddReplyTo($_POST['email'],$replyName);

			if( common::ConfigValue('from_use_user',false) ){
				$gp_mailer->SetFrom($_POST['email'],$replyName);
			}
		}


		//check for required values
		$require_email =& $config['require_email'];
		if( strpos($require_email,'email') !== false ){
			if( empty($_POST['email']) ){
				$field = gpOutput::SelectText('your_email');
				message($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
		}
		if( strpos($require_email,'none') === false ){

			if( empty($_POST['subject']) ){
				$field = gpOutput::SelectText('subject');
				message($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
			if( empty($message) ){
				$field = gpOutput::SelectText('message');
				message($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
		}



		if( $gp_mailer->SendEmail($config['toemail'], $_POST['subject'], $message) ){
			message($langmessage['message_sent']);
			return true;
		}

		message($langmessage['OOPS'].' (Send Failed)');
		return false;
	}

	function ValidEmail($email){
		return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
	}

	function ShowForm(){
		global $page,$langmessage,$config;

		$attr = '';
		if( $this->sent ){
			$attr = ' readonly="readonly" ';
		}

		$_GET += array('name'=>'','email'=>'','subject'=>'','message'=>'');
		$_POST += array('name'=>$_GET['name'],'email'=>$_GET['email'],'subject'=>$_GET['subject'],'message'=>$_GET['message']);

		$require_email =& $config['require_email'];

		echo '<form class="contactform" action="'.common::GetUrl($page->title).'" method="post">';

		//nonce fields
		echo '<div style="display:none !important">';
		echo '<input type="hidden" name="contact_nonce" value="'.htmlspecialchars(common::new_nonce('contact_post',true)).'" />';
		echo '<input type="text" name="contact_void" value="" />';
		echo '</div>';



			echo '<label for="contact_name"><span class="title">';
			echo gpOutput::ReturnText('your_name');
			echo '</span><input id="contact_name" class="input text" type="text" name="name" value="'.htmlspecialchars($_POST['name']).'" '.$attr.' />';
			echo '</label>';

			echo '<label for="contact_email"><span class="title">';
			echo gpOutput::ReturnText('your_email');
			if( strpos($require_email,'email') !== false ){
				echo '*';
			}
			echo '</span><input id="contact_email" class="input text" type="text" name="email" value="'.htmlspecialchars($_POST['email']).'" '.$attr.'/>';
			echo '</label>';

			echo '<label for="contact_subject"><span class="title">';
			echo gpOutput::ReturnText('subject');
			if( strpos($require_email,'none') === false ){
				echo '*';
			}
			echo '</span><input id="contact_subject" class="input text" type="text" name="subject" value="'.htmlspecialchars($_POST['subject']).'" '.$attr.'/>';
			echo '</label>';

			echo '<label for="contact_message">';
			echo gpOutput::ReturnText('message');
			if( strpos($require_email,'none') === false ){
				echo '*';
			}
			echo '</label>';
			echo '<textarea id="contact_message" name="message" '.$attr.' rows="10" cols="10">';
			echo htmlspecialchars($_POST['message']);
			echo '</textarea>';

		gpPlugin::Action('contact_form_pre_captcha');

		if( !$this->sent && gp_recaptcha::isActive() ){
			echo '<div class="captchaForm">';
			echo gpOutput::ReturnText('captcha');
			gp_recaptcha::Form();
			echo '</div>';
		}

			if( $this->sent ){
				echo gpOutput::ReturnText('message_sent');
			}else{
				echo '<input type="hidden" name="cmd" value="gp_send_message" />';

				$key = 'send_message';
				$text = gpOutput::SelectText($key);

				if( gpOutput::ShowEditLink('Admin_Theme_Content') ){
					$query = 'cmd=edittext&key='.urlencode($key);
					echo gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],$query,' title="'.$key.'" data-cmd="gpabox" ');
					echo '<input type="submit" class="submit editable_area" id="ExtraEditArea'.$edit_index.'" name="aaa" value="'.$text.'" />';
				}else{
					echo '<input type="submit" class="submit" name="aaa" value="'.$text.'" />';
				}

			}




		echo '</form>';
	}
}
