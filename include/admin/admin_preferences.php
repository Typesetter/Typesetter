<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_users.php');
includeFile('admin/admin_configuration.php');


class admin_preferences extends admin_users{
	var $username;
	var $variables;

	function __construct(){
		global $gpAdmin,$langmessage,$page;

		//only need to return messages if it's ajax request
		$page->ajaxReplace = array();


		$this->GetUsers();
		$this->username = $gpAdmin['username'];
		if( !isset($this->users[$this->username]) ){
			message($langmessage['OOPS']);
			return;
		}

		$this->user_info		=  $this->users[$this->username];
		$cmd					= common::GetCommand();


		$this->variables			= array(
			'gpui_ctx'			=> array('enabled'=>$langmessage['enabled'],'disabled'=>$langmessage['disabled'])
		);

		switch($cmd){
			case 'changeprefs':
				$this->DoChange();
			break;
		}

		$this->Form();

	}

	function DoChange(){
		global $gpAdmin;

		$this->ChangeEmail();
		$this->ChangePass();

		gpsession::SetGPUI();

		$this->SaveUserFile();
	}

	function ChangeEmail(){
		global $langmessage;

		if( empty($_POST['email']) ){
			$this->users[$this->username]['email'] = '';
			return;
		}

		if( $this->ValidEmail($_POST['email']) ){
			$this->users[$this->username]['email'] = $_POST['email'];
		}else{
			message($langmessage['invalid_email']);
		}

	}

	function ValidEmail($email){
		return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
	}

	/**
	 * Save a user's new password
	 *
	 */
	function ChangePass(){
		global $langmessage, $config;


		$fields = 0;
		if( !empty($_POST['oldpassword']) ){
			$fields++;
		}
		if( !empty($_POST['password']) ){
			$fields++;
		}
		if( !empty($_POST['password1']) ){
			$fields++;
		}
		if( $fields < 2 ){
			return; //assume user didn't try to reset password
		}


		//make sure password and password1 match
		if( !$this->CheckPasswords() ){
			return false;
		}


		//check the old password
		$pass_hash		= gpsession::PassAlgo($this->user_info);
		$oldpass		= common::hash($_POST['oldpassword'],$pass_hash);

		if( $this->user_info['password'] != $oldpass ){
			message($langmessage['couldnt_reset_pass']);
			return false;
		}

		self::SetUserPass( $this->users[$this->username], $_POST['password']);
	}


	function Form(){
		global $langmessage, $gpAdmin;

		if( $_SERVER['REQUEST_METHOD'] == 'POST'){
			$array = $_POST;
		}else{
			$array = $this->user_info + $gpAdmin;

		}
		$array += array('email'=>'');

		echo '<h2>'.$langmessage['Preferences'].'</h2>';

		echo '<form action="'.common::GetUrl('Admin_Preferences').'" method="post">';
		echo '<div class="collapsible">';

		echo '<h4 class="head"><a data-cmd="collapsible">'.$langmessage['general_settings'].'</a></h4>';
		echo '<div>';
		echo '<table class="bordered configuration">';


		//email
		echo '<tr><td>';
		echo $langmessage['email_address'];
		echo '</td><td>';
		echo '<input type="text" name="email" value="'.htmlspecialchars($array['email']).'" class="gpinput"/>';
		echo '</td></tr>';

		//context menu
		echo '<tr><td>';
		echo $langmessage['context menu'];
		echo '</td><td>';
		admin_configuration::formSelect('gpui_ctx',$this->variables['gpui_ctx'],$array['gpui_ctx']);
		echo '</td></tr>';


		echo '</table>';
		echo '</div>';


		echo '<h4 class="head"><a data-cmd="collapsible">'.$langmessage['change_password'].'</a></h4>';

		echo '<div>';
		echo '<table class="bordered configuration">';
		echo '<tr><td>';
			echo $langmessage['old_password'];
			echo '</td><td>';
			echo '<input type="password" name="oldpassword" value="" class="gpinput"/>';
			echo '</td></tr>';
		echo '<tr><td>';
			echo $langmessage['new_password'];
			echo '</td><td>';
			echo '<input type="password" name="password" value="" class="gpinput"/>';
			echo '</td></tr>';
		echo '<tr><td>';
			echo $langmessage['repeat_password'];
			echo '</td><td>';
			echo '<input type="password" name="password1" value="" class="gpinput"/>';
			echo '</td></tr>';

		$this->AlgoSelect();

		echo '</table>';
		echo '</div>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="changeprefs" />';
		echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</p>';

		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo common::Link('Admin_Configuration',$langmessage['configuration'],'','data-cmd="gpabox"');
		echo '</p>';

		echo '</div>';
		echo '</form>';

	}

}

