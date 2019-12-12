<?php



echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('gpdebug') or define('gpdebug',true);
defined('is_running') or define('is_running',true);
defined('gp_unit_testing') or define('gp_unit_testing',true);
defined('gp_nonce_algo') or define('gp_nonce_algo','sha512');



/*
global $config;

include('include/common.php');
spl_autoload_register( array('\\gp\\tool','Autoload') );
require dirname(__DIR__) . '/vendor/autoload.php';

$config = ['gpuniq'=>'test','language'=>'en'];

\gp\tool::SetLinkPrefix();

includeFile('tool/functions.php');

\gp\tool\Session::init();
*/

if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');



class gptest_bootstrap extends \PHPUnit_Framework_TestCase{

	protected static $process;
	protected static $client;
	protected static $logged_in		= false;
	protected static $installed		= false;
	protected static $requests		= 0;


	const user_name		= 'phpunit_username';
	const user_pass		= 'phpunit-test-password';


	function setUP(){
		\gp\tool::GetLangFile();
	}

	public function SessionStart(){

		\gp\tool::GetConfig();

		$users			= gpFiles::Get('_site/users');
		$userinfo		= $users[static::user_name];

		$session_id		= \gp\tool\Session::create($userinfo, static::user_name, $sessions);
		$logged_in		= \gp\tool\Session::start($session_id,$sessions);

		$this->AssertTrue($logged_in,'Not Logged In');

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

	/**
	 * Use php's built-in web server
	 * https://medium.com/@peter.lafferty/start-phps-built-in-web-server-from-phpunit-9571f38c5045
	 *
	 */
	public static function setUpBeforeClass(){

		self::PrepInstall();
		self::StartServer();
		self::Install();
    }

	public static function StartServer(){
		global $dataDir;

		if( static::$process ){
			static::$process->stop();
		}


		$proc		= ['php','-S','localhost:8081'];

		// doc root
		$proc[]		= '-t';
		$proc[]		= $dataDir; // '.';

		// error log
		$proc[]		= '-d';
		$proc[]		= 'error_log='.$dataDir . '/data/request-errors.log';

		// xdebug configuration to collect code coverage
		//$proc[]		= '-c';
		//$proc[]		= __DIR__ . '/phpconfig.ini';

		$proc[]		= '-d';
		$proc[]		= 'auto_prepend_file='.__DIR__ . '/ServerPrepend.php';

		$proc[]		= '-d';
		$proc[]		= 'auto_append_file='.__DIR__ . '/ServerAppend.php'; // won't append if script ends with exit()



		static::$process = new \Symfony\Component\Process\Process($proc);
        static::$process->start();
        usleep(100000); //wait for server to get going

		static::$client = new \GuzzleHttp\Client(['http_errors' => false,'cookies' => true]);
	}


	/**
	 * Stop web-server process
	 */
	public static function tearDownAfterClass(){
		global $dataDir;

        static::$process->stop();

		$error_log = $dataDir . '/data/request-errors.log';
		if( file_exists($error_log) ){
			$content = file_get_contents($error_log);
			if( $content ){
				static::Console('Request Error Log');
				echo $content;

				$fp = fopen($error_log, "r+");
				ftruncate($fp, 0);
				fclose($fp);
			}
		}
    }



	/**
	 * Fetch a url
	 *
	 */
	public static function GetRequest($slug,$query=''){
		$url		= 'http://localhost:8081' . \gp\tool::GetUrl($slug,$query);
		return self::GuzzleRequest('GET',$url);
	}

	public static function _GetRequest($url){
		return self::GuzzleRequest('GET',$url);
	}


	/**
	 * Send a POST request tot the test server
	 *
	 */
	public static function PostRequest($slug, $params = []){

		$url		= 'http://localhost:8081' . \gp\tool::GetUrl($slug);
		$options	= ['form_params' => $params];

		return self::GuzzleRequest('POST',$url,$options);
	}

	public static function GuzzleRequest($type,$url,$options = []){
		global $dataDir;

		$options['headers']		= ['X-REQ-ID' => static::$requests];
		$response				= static::$client->request($type, $url, $options);
		$debug_file				= $dataDir . '/data/response-' . static::$requests . '-' . $type . '-' . str_replace('/','_',$url);
		$debug					= $response->getBody();

		file_put_contents($debug_file, $debug);

		static::$requests++;

		return $response;
	}


	/**
	 * Log In
	 *
	 */
	public function LogIn(){
		global $config;

		// load login page to set cookies
		$response					= self::GetRequest('Admin');
		$this->assertEquals(200, $response->getStatusCode());

		$params						= [];
		$params['cmd']				= 'login';
		$params['username']			= static::user_name;
		$params['password']			= static::user_pass;
		$params['login_nonce']		= \gp\tool::new_nonce('login_nonce',true,300);
		$response					= self::PostRequest('Admin',$params);

		$this->assertEquals(200, $response->getStatusCode());
	}


	/**
	 * Create an install folder in the temporary directory
	 *
	 */
	public static function PrepInstall(){
		global $dataDir, $languages, $config;

		if( function_exists('showError') ){
			static::$installed		= true;
			return;
		}



		$dataDir = sys_get_temp_dir().'/typesetter-test';
		if( !file_exists($dataDir) ){
			mkdir($dataDir);
		}

		// create symlinks of include, addons, and themes
		$symlinks = ['include','addons','themes','gpconfig.php','index.php','vendor'];
		foreach($symlinks as $name){

			$path		= $dataDir.'/'.$name;
			$target		= $_SERVER['PWD'].'/'.$name;

			if( !file_exists($target) ){
				static::Console('symlink target does not exist: '. $target);
				continue;
			}

			if( file_exists($path) ){
				unlink($path);
			}

			symlink( $target, $path);
		}


		//$dataDir = $_SERVER['PWD'];

		static::Console('datadir='.$dataDir);


		include('include/common.php');
		spl_autoload_register( array('\\gp\\tool','Autoload') );
		require dirname(__DIR__) . '/vendor/autoload.php';
		includeFile('tool/functions.php');


		// make sure we have a fresh /data directory

		$dir = $dataDir.'/data';
		if( file_exists($dir) ){
			\gp\tool\Files::RmAll($dir);
		}
		mkdir($dir);




		// reset coverage folder
		$cov_dir	= dirname(__DIR__).'/x_coverage';
		if( file_exists($cov_dir) ){
			static::Console('resetting coverage folder: '.$cov_dir);
			\gp\tool\Files::RmAll($cov_dir);
		}
		mkdir($cov_dir);


		/*

		\gp\tool::SetLinkPrefix();


		\gp\tool\Session::init();
		*/

	}

	/**
	 * Output a string to the console
	 *
	 */
	public static function Console($msg){
		echo "\n";
		echo "\e[0;32m";
		echo $msg;
		echo "\e[0m";
		echo "\n";
	}

	/*
	 * Create an installation
	 *
	 */
	public static function Install(){
		global $config;

		// don't attempt to install twice
		if( static::$installed ){
			return;
		}

		static::$installed		= true;



		//make sure it's not installed
		$installed = \gp\tool::Installed();
		self::AssertFalse($installed,'Cannot test installation (Already Installed)');


		// test install checks
		// one of the checks actually fails
		$values			= [1,1,-1,1,1,1];
		$installer		= new \gp\install\Installer();
		foreach($values as $i => $val){
			self::assertGreaterThanOrEqual( $val, $installer->statuses[$i]['can_install'], 'Unexpected status ('.$i.') '.pre($installer->statuses[$i]) );
		}



		// test rendering of the install template
		$response = self::GetRequest('');
		self::assertEquals(200, $response->getStatusCode());

		//ob_start();
		//includeFile('install/install.php');
		//$installer->Form_Entry();
		//$content = ob_get_clean();
		//self::assertNotEmpty($content);



		//attempt to install
		$params					= [];
		$params['site_title']	= 'unit tests';
		$params['email']		= 'test@example.com';
		$params['username']		= static::user_name;
		$params['password']		= static::user_pass;
		$params['password1']	= static::user_pass;
		$params['cmd']			= 'Install';
		$response				= self::PostRequest('',$params);



		//double check
		$installed			= \gp\tool::Installed();
		self::AssertTrue($installed,'Not installed');

		\gp\tool::GetConfig();
	}


}
