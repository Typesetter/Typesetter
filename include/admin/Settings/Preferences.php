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
			$this->page->ajaxReplace = [];
			$this->username			= $gpAdmin['username'];
			if( !isset($this->users[$this->username]) ){
				msg($langmessage['OOPS']);
				return;
			}

			$this->user_info		= $this->users[$this->username];
			$cmd					= \gp\tool::GetCommand();


			switch($cmd){
				case 'changeprefs':
					$this->DoChange();
				break;
				case 'SaveGPUI':
					$this->SaveGPUI();
				return;
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
			$passed = false;
			$pass_hash		= \gp\tool\Session::PassAlgo($this->user_info);

			if( $pass_hash == 'password_hash' ){
				$pass_sha512	= \gp\tool::hash($_POST['oldpassword'], 'sha512', 50);
				$passed			= password_verify($pass_sha512, $this->user_info['password']);
			}else{
				$oldpass		= \gp\tool::hash($_POST['oldpassword'], $pass_hash);
				$passed = $this->user_info['password'] == $oldpass;
			}

			if( !$passed ){
				msg($langmessage['couldnt_reset_pass']);
				return false;
			}

			$this->SetUserPass( $this->username, $_POST['password']);
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



		/**
		 * Save UI values for the current user
		 *
		 */
		public static function SaveGPUI(){
			global $gpAdmin;

			$possible = array();

			$possible['gpui_cmpct']	= 'integer';
			$possible['gpui_vis']	= array(
				'con'=>'con',
				'cur'=>'cur',
				'app'=>'app',
				'add'=>'add',
				'set'=>'set',
				'upd'=>'upd',
				'use'=>'use',
				'cms'=>'cms',
				'res'=>'res',
				'tool'=>'tool',
				'false'=>false
			);

			$possible['gpui_tx']	= 'integer';
			$possible['gpui_ty']	= 'integer';
			$possible['gpui_ckx']	= 'integer';
			$possible['gpui_cky']	= 'integer';
			$possible['gpui_thw']	= 'integer';

			foreach($possible as $key => $key_possible){

				if( !isset($_POST[$key]) ){
					continue;
				}
				$value = $_POST[$key];

				if( $key_possible == 'boolean' ){
					if( !$value || $value === 'false' ){
						$value = false;
					}else{
						$value = true;
					}
				}elseif( $key_possible == 'integer' ){
					$value = (int)$value;
				}elseif( is_array($key_possible) ){
					if( !isset($key_possible[$value]) ){
						continue;
					}
				}

				$gpAdmin[$key] = $value;
			}

			//remove gpui_ settings no longer in $possible
			unset($gpAdmin['gpui_pdock']);
			unset($gpAdmin['gpui_con']);
			unset($gpAdmin['gpui_cur']);
			unset($gpAdmin['gpui_app']);
			unset($gpAdmin['gpui_add']);
			unset($gpAdmin['gpui_set']);
			unset($gpAdmin['gpui_upd']);
			unset($gpAdmin['gpui_use']);
			unset($gpAdmin['gpui_edb']);
			unset($gpAdmin['gpui_brdis']);	//3.5
			unset($gpAdmin['gpui_ctx']);	//5.0
		}
	}

}

namespace{
	class admin_preferences extends \gp\admin\Settings\Preferences{}
}
