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


		//mimic POST
		$_POST				= array();
		$_POST['email']		= 'test@example.com';
		$_POST['username']	= 'phpunit-username';
		$_POST['password']	= 'phpunit-test-password';
		$_POST['password1']	= $_POST['password'];


		//attempt to install
		ob_start();
		includeFile('tool/install.php');
		$success = Install_Tools::Install_DataFiles_New();
		ob_get_clean();
		self::AssertTrue($success,'Installation Failed');


		//double check
		self::AssertFileExists($config_file);
	}

}