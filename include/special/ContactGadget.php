<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');

class ContactGadget extends \gp\special\Base{

	public $sent = false;

	public function __construct($args){
		global $langmessage, $config, $contact_message_sent, $message_send_attempt;

		parent::__construct($args);
		$this->sent = $contact_message_sent;

		if( empty($config['toemail']) ){

			if( \gp\tool::LoggedIn() ){
				$url = \gp\tool::GetUrl('Admin_Configuration');
				msg($langmessage['enable_contact'],$url);
			}

			echo $langmessage['not_enabled'];
			return;
		}

		$cmd = \gp\tool::GetCommand();
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


	public function SendMessage(){
		global $langmessage, $config;


		$headers = array();
		$_POST += array('subject'=>'','contact_nonce'=>'','message'=>'');

		if( empty($_POST['message']) ){
			msg($langmessage['OOPS'].'(Invalid Message)');
			return;
		}

		//check nonce
		if( !\gp\tool::verify_nonce('contact_post',$_POST['contact_nonce'],true) ){
			msg($langmessage['OOPS'].'(Invalid Nonce)');
			return;
		}
		if( !empty($_POST['contact_void']) ){
			msg($langmessage['OOPS'].'(Robot Detected)');
			return;
		}


		//captcha
		if( !\gp\tool\Recaptcha::Check() ){
			return;
		}

		if( !\gp\tool\Plugins::Filter('contact_form_check',array(true)) ){
			return;
		}

		$mailer = new \gp\tool\Emailer();

		//subject
		$_POST['subject'] = strip_tags($_POST['subject']);

		//message
		$tags = '<p><div><span><font><b><i><tt><em><i><a><strong><blockquote>';
		$message = nl2br(strip_tags($_POST['message'],$tags));


		//reply name
		if( !empty($_POST['email']) ){

			//check format
			if( !$this->ValidEmail($_POST['email']) ){
				msg($langmessage['invalid_email']);
				return false;
			}

			$replyName = str_replace(array("\r","\n"),array(' '),$_POST['name']);
			$replyName = strip_tags($replyName);
			$replyName = htmlspecialchars($replyName);

			$mailer->AddReplyTo($_POST['email'],$replyName);

			if( \gp\tool::ConfigValue('from_use_user',false) ){
				$mailer->SetFrom($_POST['email'],$replyName);
			}
		}


		//check for required values
		$require_email =& $config['require_email'];
		if( strpos($require_email,'email') !== false ){
			if( empty($_POST['email']) ){
				$field = \gp\tool\Output::SelectText('your_email');
				msg($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
		}
		if( strpos($require_email,'none') === false ){

			if( empty($_POST['subject']) ){
				$field = \gp\tool\Output::SelectText('subject');
				msg($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
			if( empty($message) ){
				$field = \gp\tool\Output::SelectText('message');
				msg($langmessage['OOPS_REQUIRED'],$field);
				return false;
			}
		}



		if( $mailer->SendEmail($config['toemail'], $_POST['subject'], $message) ){
			msg($langmessage['message_sent']);
			return true;
		}

		msg($langmessage['OOPS'].' (Send Failed)');
		return false;
	}

	public function ValidEmail($email){
		return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
	}

	public function ShowForm(){
		global $langmessage,$config;

		$attr = '';
		if( $this->sent ){
			$attr = ' readonly="readonly" ';
		}

		$_GET += array('name'=>'','email'=>'','subject'=>'','message'=>'');
		$_POST += array('name'=>$_GET['name'],'email'=>$_GET['email'],'subject'=>$_GET['subject'],'message'=>$_GET['message']);

		$require_email =& $config['require_email'];

		echo '<div class="GPAREA filetype-special_contactform">';
		echo '<form class="contactform" action="'.\gp\tool::GetUrl($this->page->title).'" method="post">';

		//nonce fields
		echo '<div style="display:none !important">';
		echo '<input type="hidden" name="contact_nonce" value="'.htmlspecialchars(\gp\tool::new_nonce('contact_post',true)).'" />';
		echo '<input type="text" name="contact_void" value="" />';
		echo '</div>';



			echo '<label for="contact_name"><span class="title">';
			echo \gp\tool\Output::ReturnText('your_name');
			echo '</span><input id="contact_name" class="input text" type="text" name="name" value="'.htmlspecialchars($_POST['name']).'" '.$attr.' />';
			echo '</label>';

			echo '<label for="contact_email"><span class="title">';
			echo \gp\tool\Output::ReturnText('your_email');
			if( strpos($require_email,'email') !== false ){
				echo '*';
			}
			echo '</span><input id="contact_email" class="input text" type="text" name="email" value="'.htmlspecialchars($_POST['email']).'" '.$attr.'/>';
			echo '</label>';

			echo '<label for="contact_subject"><span class="title">';
			echo \gp\tool\Output::ReturnText('subject');
			if( strpos($require_email,'none') === false ){
				echo '*';
			}
			echo '</span><input id="contact_subject" class="input text" type="text" name="subject" value="'.htmlspecialchars($_POST['subject']).'" '.$attr.'/>';
			echo '</label>';

			echo '<label for="contact_message">';
			echo \gp\tool\Output::ReturnText('message');
			if( strpos($require_email,'none') === false ){
				echo '*';
			}
			echo '</label>';
			echo '<textarea id="contact_message" name="message" '.$attr.' rows="10" cols="10">';
			echo htmlspecialchars($_POST['message']);
			echo '</textarea>';

		\gp\tool\Plugins::Action('contact_form_pre_captcha');

		if( !$this->sent && \gp\tool\Recaptcha::isActive() ){
			echo '<div class="captchaForm">';
			echo \gp\tool\Output::ReturnText('captcha');
			\gp\tool\Recaptcha::Form();
			echo '</div>';
		}

			if( $this->sent ){
				echo \gp\tool\Output::ReturnText('message_sent','%s','message_sent');
			}else{
				echo '<input type="hidden" name="cmd" value="gp_send_message" />';

				$key = 'send_message';
				$text = \gp\tool\Output::SelectText($key);

				if( \gp\tool\Output::ShowEditLink('Admin_Theme_Content') ){
					$query = 'cmd=EditText&key='.urlencode($key);
					echo \gp\tool\Output::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],$query,' title="'.$key.'" data-cmd="gpabox" ');
					echo '<input type="submit" class="submit editable_area" id="ExtraEditArea'.$edit_index.'" name="aaa" value="'.$text.'" />';
				}else{
					echo '<input type="submit" class="submit" name="aaa" value="'.$text.'" />';
				}

			}




		echo '</form>';
		echo '</div>';
	}
}
