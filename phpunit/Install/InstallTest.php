<?php


class phpunit_Install extends gptest_bootstrap{

	/**
	 *
	 * @runInSeparateProcess
	 */
	function testInstall(){
		global $dataDir;

		//make sure it's not installed
		$config_file = $dataDir.'/data/_site/config.php';
		self::AssertFileNotExists($config_file,'Cannot test installation (Already Installed)');


		// make the install checks passed
		$installer		= new \gp\install\Installer();
		$this->assertGreaterThan($installer->can_install, 1,'Cant install: '.pre($installer->statuses));



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
		self::AssertFileExists($config_file);
	}

}
