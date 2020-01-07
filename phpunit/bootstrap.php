<?php



echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('gpdebug') or define('gpdebug',true);
defined('is_running') or define('is_running',true);
defined('gp_unit_testing') or define('gp_unit_testing',true);
defined('gp_nonce_algo') or define('gp_nonce_algo','sha512');


if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');



class gptest_bootstrap extends \PHPUnit_Framework_TestCase{

	protected static $process;
	protected static $client;
	protected static $logged_in		= false;
	protected static $installed		= false;
	protected static $requests		= 0;
	protected static $proc_output	= [];
	protected static $phpinfo;


	const user_name		= 'phpunit_username';
	const user_pass		= 'phpunit-test-password';
	const user_email	= 'test@typesettercms.com';


	function setUP(){
		\gp\tool::GetLangFile();
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

		if( empty(static::$phpinfo) ){
			static::Console('set phpinfo');
			$url				= 'http://localhost:8081/phpinfo.php';
			$response			= self::GuzzleRequest('GET',$url);
			$body				= $response->getBody();
			static::$phpinfo	= (string)$body;
		}


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


		static::$process = new \Symfony\Component\Process\Process($proc);
        static::$process->start(function($type,$buffer){
			static::$proc_output[] = ['type'=>$type,'buffer'=>(string)$buffer];
		});
        usleep(100000); //wait for server to get going

		static::$client				= new \GuzzleHttp\Client(['http_errors' => false,'cookies' => true]);
		static::$logged_in			= false;
	}


	/**
	 * Print process output
	 *
	 */
	public static function ProcessOutput($type,$url){

		echo "\n\n----------------------------------------------------------------";
		static::Console('Begin Process Output: '.$type.' '.$url);
		echo "\n";

		if( !empty(static::$proc_output) ){
			static::Console('Proc Output');
			print_r(static::$proc_output);
		}
		echo "\nEnd Process Output\n----------------------------------------------------------------\n\n";
	}


	/**
	 * Stop web-server process
	 */
	public static function tearDownAfterClass(){
        static::$process->stop();
    }


	/**
	* Send a GET request to the test server
	 *
	 */
	public static function GetRequest($slug,$query=''){
		$url		= 'http://localhost:8081' . \gp\tool::GetUrl($slug,$query,false);
		return self::GuzzleRequest('GET',$url);
	}


	/**
	 * Send a POST request to the test server
	 *
	 */
	public static function PostRequest($slug, $params = []){

		$url		= 'http://localhost:8081' . \gp\tool::GetUrl($slug);
		$options	= ['form_params' => $params];

		return self::GuzzleRequest('POST',$url,200,$options);
	}

	/**
	 * Send a request to the test server and check the response
	 *
	 */
	public static function GuzzleRequest($type,$url,$expected_resonse = 200, $options = []){
		global $dataDir;

		$response = null;

		try{
			static::$proc_output	= [];
			$options['headers']		= ['X-REQ-ID' => static::$requests];
			$response				= static::$client->request($type, $url, $options);
			$debug_file				= $dataDir . '/data/response-' . static::$requests . '-' . $type . '-' . str_replace('/','_',$url);
			$body					= $response->getBody();

			file_put_contents($debug_file, $body);
			static::$requests++;

			static::$process->getOutput(); # makes symfony/process populate our static::$proc_output


			if( $expected_resonse !== $response->getStatusCode() ){
				static::ProcessOutput($type,$url);
				static::Console('PHPINFO()');
				echo (string)static::$phpinfo;
			}
			static::assertEquals($expected_resonse, $response->getStatusCode());

		}catch( \Exception $e ){
			static::ServerErrors($type,$url);
			static::Fail('Exception fetching url '.$url.$e->getMessage());
		}


		return $response;
	}


	/**
	 * Output Error log
	 *
	 */
	public static function ServerErrors($type,$url){
		global $dataDir;

		$error_log = $dataDir . '/data/request-errors.log';
		if( !file_exists($error_log) ){
			return;
		}

		$content = file_get_contents($error_log);
		if( empty($content) ){
			return;
		}

		echo "\n\n----------------------------------------------------------------";
		static::Console('Error Log for '.$type.' '.$url);
		echo "\n";

		echo $content;
		echo "\n\nEnd Error Log\n----------------------------------------------------------------\n\n";

		$fp = fopen($error_log, "r+");
		ftruncate($fp, 0);
		fclose($fp);

		static::assertEmpty($content,'php error log was not empty');
	}


	/**
	 * Log In
	 *
	 */
	public function LogIn(){
		global $config;

		if( static::$logged_in ){
			return;
		}


		// load login page to set cookies
		$response					= self::GetRequest('Admin');

		$params						= [];
		$params['cmd']				= 'login';
		$params['username']			= static::user_name;
		$params['password']			= static::user_pass;
		$params['login_nonce']		= \gp\tool::new_nonce('login_nonce',true,300);
		$response					= self::PostRequest('Admin',$params);
		static::$logged_in			= true;

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



		// get a clean temporary install folder
		$dataDir	= sys_get_temp_dir().'/typesetter-test';
		$old_dir	= sys_get_temp_dir().'/typesetter-test-old';
		if( file_exists($dataDir) ){
			rename($dataDir,$old_dir);
		}
		mkdir($dataDir);
		mkdir($dataDir.'/data');




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


		// create a phpinfo.php file
		$file		= $dataDir.'/phpinfo.php';
		$content	= '<?php phpinfo();';
		file_put_contents($file,$content);



		include('include/common.php');
		spl_autoload_register( array('\\gp\\tool','Autoload') );
		require dirname(__DIR__) . '/vendor/autoload.php';
		includeFile('tool/functions.php');

		static::Console('datadir = '.$dataDir);
		static::Console('gp_data_type = '.gp_data_type);





		// delete old installation
		\gp\tool\Files::RmAll($old_dir);


		// reset coverage folder
		$cov_dir	= dirname(__DIR__).'/x_coverage';
		if( file_exists($cov_dir) ){
			static::Console('resetting coverage folder: '.$cov_dir);
			\gp\tool\Files::RmAll($cov_dir);
		}
		mkdir($cov_dir);
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

		//ob_start();
		//includeFile('install/install.php');
		//$installer->Form_Entry();
		//$content = ob_get_clean();
		//self::assertNotEmpty($content);



		//attempt to install
		$params					= [];
		$params['site_title']	= 'unit tests';
		$params['email']		= static::user_email;
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
