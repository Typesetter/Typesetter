<?php

namespace gp\admin\Settings{

	defined('is_running') or die('Not an entry point...');

	class Preferences extends \gp\admin\Settings\Users{

		public $username;
		protected $user_info;

		public function __construct($args){
			global $gpAdmin, $langmessage;

			parent::__construct($args);

			//only need to return messages if it's ajax request
			$this->page->ajaxReplace = array();


			$this->GetUsers();
			$this->username = $gpAdmin['username'];
			if( !isset($this->users[$this->username]) ){
				msg($langmessage['OOPS']);
				return;
			}

			$this->user_info		=  $this->users[$this->username];
			$cmd					= \gp\tool::GetCommand();


			switch($cmd){
				case 'changeprefs':
					$this->DoChange();
				break;
			}

			$this->Form();

		}

		public function DoChange(){
			global $gpAdmin;

			$this->ChangeEmail();
			$this->ChangePass();

			$this->SaveUserFile();
		}

		public function ChangeEmail(){
			global $langmessage;

			if( empty($_POST['email']) ){
				$this->users[$this->username]['email'] = '';
				return;
			}

			if( $this->ValidEmail($_POST['email']) ){
				$this->users[$this->username]['email'] = $_POST['email'];
			}else{
				msg($langmessage['invalid_email']);
			}

		}

		public function ValidEmail($email){
			return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
		}

		/**
		 * Save a user's new password
		 *
		 */
		public function ChangePass(){
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
			$pass_hash		= \gp\tool\Session::PassAlgo($this->user_info);
			$oldpass		= \gp\tool::hash($_POST['oldpassword'],$pass_hash);

			if( $this->user_info['password'] != $oldpass ){
				msg($langmessage['couldnt_reset_pass']);
				return false;
			}

			self::SetUserPass( $this->users[$this->username], $_POST['password']);
		}


		public function Form(){
			global $langmessage, $gpAdmin;

			if( $_SERVER['REQUEST_METHOD'] == 'POST'){
				$array = $_POST;
			}else{
				$array = $this->user_info + $gpAdmin;
			}
			$array += array('email'=>'');

			echo '<h2>'.$langmessage['Preferences'].'</h2>';

			echo '<form action="'.\gp\tool::GetUrl('Admin/Preferences').'" method="post">';
			echo '<table class="bordered full_width">';
			echo '<tr><th colspan="2">'.$langmessage['general_settings'].'</th></tr>';


			//email
			echo '<tr><td>';
			echo $langmessage['email_address'];
			echo '</td><td>';
			echo '<input type="text" name="email" value="'.htmlspecialchars($array['email']).'" class="gpinput"/>';
			echo '</td></tr>';



			echo '<tr><th colspan="2">'.$langmessage['change_password'].'</th></tr>';

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

			echo '<div style="margin:1em 0">';
			echo '<input type="hidden" name="cmd" value="changeprefs" />';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit"/>';
			echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel"/>';
			echo '</div>';

			echo '<p class="admin_note">';
			echo '<b>';
			echo $langmessage['see_also'];
			echo '</b> ';
			echo \gp\tool::Link('Admin_Configuration',$langmessage['configuration'],'','data-cmd="gpabox"');
			echo '</p>';

			echo '</div>';
			echo '</form>';

		}

	}
}

namespace{
	class admin_preferences extends \gp\admin\Settings\Preferences{}
}
