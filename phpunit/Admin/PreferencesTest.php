<?php

namespace phpunit\Admin;

class PreferencesTest extends \gptest_bootstrap{

	/**
	 * Test changing the admin user's password
	 *
	 */
	public function testChangePassword(){

		$this->UseAdmin();

		// password_hash -> password_hash
		$params = [
			'oldpassword'	=> self::user_pass,
			'password'		=> 'new-password',
			'password1'		=> 'new-password',
			'algo'			=> 'password_hash',
		];
		$user_info_1		= $this->GetUserInfo();
		$this->ChangePreferences( $params );
		$user_info_2		= $this->GetUserInfo();
		$this->assertEquals($user_info_2['passhash'], 'password_hash');
		$this->assertNotEquals($user_info_1['password'], $user_info_2['password']);


		// password_hash -> sha512
		$params = [
			'oldpassword'	=> 'new-password',
			'password'		=> 'new-password2',
			'password1'		=> 'new-password2',
			'algo'			=> 'sha512',
		];
		$this->ChangePreferences( $params );
		$user_info_3		= $this->GetUserInfo();
		$this->assertEquals($user_info_3['passhash'], 'sha512');
		$this->assertNotEquals($user_info_2['password'], $user_info_3['password']);


		// sha512 -> password_hash
		$params = [
			'oldpassword'	=> 'new-password2',
			'password'		=> self::user_pass,
			'password1'		=> self::user_pass,
			'algo'			=> 'password_hash',
		];
		$this->ChangePreferences( $params );
		$user_info_4		= $this->GetUserInfo();
		$this->assertEquals($user_info_4['passhash'], 'password_hash');
		$this->assertNotEquals($user_info_3['password'], $user_info_4['password']);


		// sha512 -> password_hash
		$params = [
			'email'			=> 'test2@typesettercms.com',
		];
		$this->ChangePreferences( $params );
		$user_info_5		= $this->GetUserInfo();
		$this->assertEquals($user_info_5['email'], 'test2@typesettercms.com');

	}


	/**
	 * Helper function for changing password from old to new
	 *
	 */
	public function ChangePreferences( $params ){

		$params += [
			'verified'		=> \gp\tool::new_nonce('post', true),
			'email'			=> self::user_email,
			'oldpassword'	=> self::user_pass,
			'password'		=> self::user_pass,
			'password1'		=> self::user_pass,
			'algo'			=> 'password_hash',
			'cmd'			=> 'changeprefs',
		];

		$this->PostRequest('Admin/Preferences',$params);
	}

	public function GetUserInfo(){
		$users				= \gp\tool\Files::Get('_site/users');
		$this->assertArrayHasKey(static::user_name, $users);

		return $users[static::user_name];
	}



}
