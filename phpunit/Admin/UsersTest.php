<?php

namespace phpunit\Admin;

class UsersTest extends \gptest_bootstrap{

	/**
	 * Test add and deleting users
	 *
	 */
	public function testNewUser(){

		$this->Login();

		$this->GetRequest('Admin/Users');

		$this->GetRequest('/Admin/Users','cmd=newuserform');

		$users				= \gp\tool\Files::Get('_site/users');
		$this->assertEquals( count($users), 1, 'More than one user found');

		// create the new user
		$params = [
			'verified'		=> \gp\tool::new_nonce('post', true),
			'username'		=> 'newuser',
			'password'		=> 'newpass',
			'password1'		=> 'newpass',
			'algo'			=> 'password_hash',
			'email'			=> 'test2@typesettercms.com',
			'grant_all'		=> 'all',
			'editing_all'	=> 'all',
			'cmd'			=> 'CreateNewUser',
		];

		$this->PostRequest('/Admin/Users',$params);

		$users				= \gp\tool\Files::Get('_site/users');
		$user_info			= $users['newuser'];
		$this->assertEquals( count($users), 2);
		$this->assertEquals( $user_info['granted'], 'all');
		$this->assertEquals( $user_info['editing'], 'all');
		$this->assertEquals( $user_info['email'], 'test2@typesettercms.com');



		// edit user details
		$params = [
			'verified'		=> \gp\tool::new_nonce('post', true),
			'username'		=> 'newuser',
			'email'			=> 'test3@typesettercms.com',
			'grant_all'		=> '',
			'editing_all'	=> '',
			'cmd'			=> 'SaveChanges',
		];

		$this->GetRequest('/Admin/Users','cmd=details&username=newuser');
		$this->PostRequest('/Admin/Users',$params);

		$users				= \gp\tool\Files::Get('_site/users');
		$user_info			= $users['newuser'];
		$this->assertEquals( $user_info['granted'], '');
		$this->assertEquals( $user_info['editing'], '');
		$this->assertEquals( $user_info['email'], 'test3@typesettercms.com');


		// change password
		$params = [
			'verified'		=> \gp\tool::new_nonce('post', true),
			'username'		=> 'newuser',
			'password'		=> 'resetpass',
			'password1'		=> 'resetpass',
			'algo'			=> 'password_hash',
			'cmd'			=> 'resetpass',
		];

		$this->GetRequest('/Admin/Users','cmd=changepass&username=newuser');
		$this->PostRequest('/Admin/Users',$params);

		$users				= \gp\tool\Files::Get('_site/users');
		$user_info2			= $users['newuser'];
		$this->assertNotEquals( $user_info2['password'],$user_info['password'],'Password reset failed');



		// delete user
		$params = [
			'cmd'			=> 'RemoveUser',
			'username'		=> 'newuser',
			'verified'		=> \gp\tool::new_nonce('post', true),
		];

		$this->PostRequest('/Admin/Users',$params);

		$users				= \gp\tool\Files::Get('_site/users');
		$this->assertEquals( count($users), 1,'Failed removing user');
		$this->assertArrayHasKey(static::user_name, $users);

	}

}
