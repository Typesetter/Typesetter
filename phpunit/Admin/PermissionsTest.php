<?php

namespace phpunit\Admin;

class PermissionsTest extends \gptest_bootstrap{

	public function testFilePermissions(){
		global $gp_index;

		\gp\tool::GetPagesPHP();

		$this->CheckEditing('all');


		// load form
		$this->GetRequest('Admin/Permissions','index=a');

		// adding index=a shouldn't change the permissions
		$params = [
			'verified'		=> \gp\tool\Nonce::Create('post', true),
			'users'			=> [static::user_name => static::user_name],
			'cmd'			=> 'SaveFilePermissions',
			'index'			=> 'a',
		];

		$this->PostRequest('/Admin/Permissions',$params);
		$this->CheckEditing('all');

		// removing index=a
		$params = [
			'verified'		=> \gp\tool\Nonce::Create('post', true),
			'users'			=> [],
			'cmd'			=> 'SaveFilePermissions',
			'index'			=> 'a',
		];
		$expected		= array_diff( array_values( $gp_index) , ['a'] );
		$expected		= ','.implode(',',$expected).',';

		$this->PostRequest('/Admin/Permissions',$params);
		$this->CheckEditing($expected);


		// re-add index=a
		$params = [
			'verified'		=> \gp\tool\Nonce::Create('post', true),
			'users'			=> [static::user_name => static::user_name],
			'cmd'			=> 'SaveFilePermissions',
			'index'			=> 'a',
		];
		$expected		= array_values( $gp_index);
		$expected		= ','.implode(',',$expected).',';

		$this->PostRequest('/Admin/Permissions',$params);
		$this->CheckEditing($expected);

	}


	public function CheckEditing($editing_value){

		$users			= \gp\tool\Files::Get('_site/users');
		$this->assertEquals( count($users), 1, 'More than one user found');
		$this->assertArrayHasKey(static::user_name, $users,'Default user not found');
		$this->assertEquals( $users[static::user_name]['editing'], $editing_value, 'Expected editing value of '.$editing_value.' != '.$users[static::user_name]['editing'] );

	}

}
