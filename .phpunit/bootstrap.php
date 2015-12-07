<?php

ob_start();

echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('is_running') or define('is_running',true);
defined('gp_unit_testing') or define('gp_unit_testing',true);

global $dataDir;
$dataDir = $_SERVER['PWD'];
include('include/common.php');

common::SetLinkPrefix();

includeFile('tool/display.php');
includeFile('tool/Files.php');
includeFile('tool/gpOutput.php');
includeFile('tool/functions.php');
includeFile('tool/Plugins.php');
includeFile('tool/sessions.php');

gpsession::init();

spl_autoload_register( array('common','Autoload') );


class gptest_bootstrap extends PHPUnit_Framework_TestCase{

	function setUP(){
		common::GetLangFile();
	}

	public function SessionStart(){

		common::GetConfig();

		$username		= 'phpunit-username';
		$users			= gpFiles::Get('_site/users');
		$userinfo		= $users[$username];

		$session_id		= gpsession::create($userinfo, $username, $sessions);
		$logged_in		= gpsession::start($session_id,$sessions);

		self::AssertTrue($logged_in,'Not Logged In');

	}

	public function SessionEnd(){
		ob_get_clean();
	}

	static function log($msg){
		static $fp;

		if( !$fp ){
			$log	= __DIR__ . '/phpunit.log';
			$fp		= fopen($log, 'a');
		}
		fwrite($fp, "\n".print_r($msg, TRUE));
	}

}