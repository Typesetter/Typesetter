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
	protected static $client_user;			// guzzle client for making anonymous user requests
	protected static $client_admin;			// guzzle client for making admin user requests
	protected static $client_current;
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

		if( empty(self::$phpinfo) ){
			self::Console('set phpinfo');
			$url				= 'http://localhost:8081/phpinfo.php';
			$response			= self::GuzzleRequest('GET',$url);
			$body				= $response->getBody();
			self::$phpinfo		= (string)$body;
		}


		self::Install();
    }

	public static function StartServer(){
		global $dataDir;

		if( self::$process ){
			return;
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


		self::$process = new \Symfony\Component\Process\Process($proc);
        self::$process->start(function($type,$buffer){
			self::$proc_output[] = ['type'=>$type,'buffer'=>(string)$buffer];
		});
        usleep(100000); //wait for server to get going


		// create client for user requests
		self::$client_user			= new \GuzzleHttp\Client(['http_errors' => false]);


		// create client for admin requests
		self::$client_admin			= new \GuzzleHttp\Client(['http_errors' => false,'cookies' => true]);
		self::UseAdmin();
		self::LogIn();


		register_shutdown_function(function(){
			self::Console('Stopping server process');
			gptest_bootstrap::$process->stop();
		});
	}

	/**
	 * Switch current guzzle client to $client_admin
	 */
 	public static function UseAdmin(){
		self::$client_current		= self::$client_admin;
 	}

	/**
	 * Switch current guzzle client to $client_user
	 */
	public static function UseAnon(){
		self::$client_current		= self::$client_user;
	}

	/**
	 * Print process output
	 *
	 */
	public static function ProcessOutput($type,$url){

		echo "\n\n----------------------------------------------------------------";
		self::Console('Begin Process Output: '.$type.' '.$url);
		echo "\n";

		if( !empty(self::$proc_output) ){
			self::Console('Proc Output');
			print_r(self::$proc_output);
		}
		echo "\nEnd Process Output\n----------------------------------------------------------------\n\n";
	}


	/**
	* Send a GET request to the test server
	 *
	 */
	public static function GetRequest( $slug, $query='', $nonce_action=false ){
		$url		= 'http://localhost:8081' . \gp\tool::GetUrl( $slug, $query, false, $nonce_action);
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
			self::$proc_output		= [];
			$options['headers']		= ['X-REQ-ID' => self::$requests];
			$response				= self::$client_current->request($type, $url, $options);
			$debug_file				= $dataDir . '/data/response-' . self::$requests . '-' . $type . '-' . str_replace('/','_',$url);
			$body					= $response->getBody();

			file_put_contents($debug_file, $body);
			self::$requests++;

			self::$process->getOutput(); # makes symfony/process populate our self::$proc_output


			if( $expected_resonse !== $response->getStatusCode() ){
				self::ProcessOutput($type,$url);
				self::Console('PHPINFO()');
				echo (string)self::$phpinfo;
			}
			self::assertEquals($expected_resonse, $response->getStatusCode());

		}catch( \Exception $e ){
			self::ServerErrors($type,$url);
			self::Fail('Exception fetching url '.$url.$e->getMessage());
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
		self::Console('Error Log for '.$type.' '.$url);
		echo "\n";

		echo $content;
		echo "\n\nEnd Error Log\n----------------------------------------------------------------\n\n";

		$fp = fopen($error_log, "r+");
		ftruncate($fp, 0);
		fclose($fp);

		self::assertEmpty($content,'php error log was not empty');
	}


	/**
	 * Log In
	 *
	 */
	public static function LogIn(){

		// load login page to set cookies
		$response					= self::GetRequest('Admin');

		$params						= [];
		$params['cmd']				= 'login';
		$params['username']			= self::user_name;
		$params['password']			= self::user_pass;
		$params['login_nonce']		= \gp\tool::new_nonce('login_nonce',true,300);
		$response					= self::PostRequest('Admin',$params);
	}



	/**
	 * Create an install folder in the temporary directory
	 *
	 */
	public static function PrepInstall(){
		global $dataDir, $languages, $config;

		if( function_exists('showError') ){
			self::$installed		= true;
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
				self::Console('symlink target does not exist: '. $target);
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

		self::Console('datadir = '.$dataDir);
		self::Console('gp_data_type = '.gp_data_type);





		// delete old installation
		\gp\tool\Files::RmAll($old_dir);


		// reset coverage folder
		$cov_dir	= dirname(__DIR__).'/x_coverage';
		if( file_exists($cov_dir) ){
			self::Console('resetting coverage folder: '.$cov_dir);
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
		if( self::$installed ){
			return;
		}

		self::$installed		= true;



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
		$params['email']		= self::user_email;
		$params['username']		= self::user_name;
		$params['password']		= self::user_pass;
		$params['password1']	= self::user_pass;
		$params['cmd']			= 'Install';
		$response				= self::PostRequest('',$params);



		//double check
		$installed			= \gp\tool::Installed();
		self::AssertTrue($installed,'Not installed');



		\gp\tool::GetConfig();
	}

	public static function assertStrpos( $haystack, $needle , $msg = 'String not found' ){

		if( strpos($haystack, $needle) === false ){
			self::fail($msg);
		}

	}

}
