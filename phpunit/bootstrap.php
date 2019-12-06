<?php



echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('gpdebug') or define('gpdebug',true);
defined('is_running') or define('is_running',true);
defined('gp_unit_testing') or define('gp_unit_testing',true);

global $dataDir, $config;
$dataDir = $_SERVER['PWD'];

include('include/common.php');
spl_autoload_register( array('\\gp\\tool','Autoload') );

$config = ['gpuniq'=>'test'];

\gp\tool::SetLinkPrefix();

includeFile('tool/functions.php');

\gp\tool\Session::init();


if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');

class gptest_bootstrap extends \PHPUnit_Framework_TestCase{

	function setUP(){
		\gp\tool::GetLangFile();
	}

	public function SessionStart(){

		\gp\tool::GetConfig();

		$username		= 'phpunit-username';
		$users			= gpFiles::Get('_site/users');
		$userinfo		= $users[$username];

		$session_id		= \gp\tool\Session::create($userinfo, $username, $sessions);
		$logged_in		= \gp\tool\Session::start($session_id,$sessions);

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
