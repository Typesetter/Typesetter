<?php


class phpunit_Install extends gptest_bootstrap{

	/**
	 *
	 * @runInSeparateProcess
	 */
	function testInstall(){



		//make sure it's not installed
		$installed = \gp\tool::Installed();
		self::AssertFalse($installed,'Cannot test installation (Already Installed)');


		// test install checks
		// one of the checks actually fails
		$values			= [1,1,-1,1,1,1];
		$installer		= new \gp\install\Installer();

		foreach($values as $i => $val){
			$this->assertGreaterThanOrEqual( $val, $installer->statuses[$i]['can_install'], 'Unexpected status ('.$i.') '.pre($installer->statuses[$i]) );
		}



		// test rendering of the install template
		ob_start();
		includeFile('install/install.php');
		$installer->Form_Entry();
		$content = ob_get_clean();
		$this->assertNotEmpty($content);


		//mimic POST
		$_POST				= array();
		$_POST['email']		= 'test@example.com';
		$_POST['username']	= 'phpunit-username';
		$_POST['password']	= 'phpunit-test-password';
		$_POST['password1']	= $_POST['password'];


		//attempt to install
		ob_start();
		$success = \gp\install\Tools::Install_DataFiles_New();
		ob_get_clean();
		self::AssertTrue($success,'Installation Failed');


		//double check
		$installed = \gp\tool::Installed();
		self::AssertTrue($installed);
	}

}
