<?php

namespace phpunit\Admin;

class PreferencesTest extends \gptest_bootstrap{

	/**
	 * Test changing the admin user's password
	 *
	 */
	function testChangePassword(){

		$this->Login();

		// password_hash -> password_hash
		$this->ChangePassword( self::user_pass, 'new-password', 'password_hash' );

		// password_hash -> sha512
		$this->ChangePassword( 'new-password', 'new-password2', 'sha512' );

		// sha512 -> password_hash
		$this->ChangePassword( 'new-password2', self::user_pass, 'password_hash' );
	}
	

	/**
	 * Helper function for changing password from old to new
	 *
	 */
	function ChangePassword( $old_pass, $new_pass, $algo){

		// get user info before changing password
		$users				= \gp\tool\Files::Get('_site/users');
		$this->assertArrayHasKey(static::user_name, $users);

		$user_before		= $users[static::user_name];


		$params = [
			'verified'		=> \gp\tool::new_nonce('post', true),
			'email'			=> self::user_email,
			'oldpassword'	=> $old_pass,
			'password'		=> $new_pass,
			'password1'		=> $new_pass,
			'algo'			=> $algo,
			'cmd'			=> 'changeprefs',
		];

		$response			= $this->PostRequest('Admin/Preferences',$params);

		// password should be different
		$users				= \gp\tool\Files::Get('_site/users');
		$user_after			= $users[static::user_name];

		$this->assertNotEquals($user_before['password'], $user_after['password']);

	}

}
