<?php
defined('is_running') or die('Not an entry point...');

class admin_errors{

	private $readable_log = false;

	private static $types = array (
				E_ERROR				=> 'Fatal Error',
				E_WARNING			=> 'Warning',
				E_PARSE				=> 'Parsing Error',
				E_NOTICE 			=> 'Notice',
				E_CORE_ERROR		=> 'Core Error',
				E_CORE_WARNING 		=> 'Core Warning',
				E_COMPILE_ERROR		=> 'Compile Error',
				E_COMPILE_WARNING 	=> 'Compile Warning',
				E_USER_ERROR		=> 'User Error',
				E_USER_WARNING 		=> 'User Warning',
				E_USER_NOTICE		=> 'User Notice',
				E_STRICT			=> 'Strict Notice',
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED		=> 'Deprecated',
				E_USER_DEPRECATED	=> 'User Deprecated',
			 );

	function __construct(){
		global $page;

		$sub_page = '';
		if( strpos($page->requested,'/') ){
			$parts = explode('/',$page->requested,2);
			$sub_page = $parts[1];
		}
		switch($sub_page){
			case 'Log':
			$this->ErrorLog();
			return;

			default:
			$this->FatalErrors();
			return;
		}
	}

	/**
	 * Display a list of fatal errors caught by gpeasy
	 *
	 */
	function FatalErrors(){
		global $dataDir;

		echo '<h2 class="hmargin">';
		echo 'Fatal Errors';
		echo ' <span> | </span>';
		echo common::Link('Admin_Errors/Log','Error Log');
		echo '</h2>';


		//actions
		$cmd = common::GetCommand();
		switch($cmd){
			case 'clear_error':
			self::ClearError($_REQUEST['hash']);
			break;
		}


		//get unique errors
		$dir = $dataDir.'/data/_site';
		$files = scandir($dir);
		$errors = array();
		foreach($files as $file){
			if( strpos($file,'fatal_') === false ){
				continue;
			}
			$full_path = $dir.'/'.$file;

			$md5 = md5_file($full_path);
			$errors[$md5] = $full_path;
		}

		if( count($errors) ){
			echo '<p>Found '.count($errors).' Unique Error(s)</p>';
		}else{
			echo '<p>Hooray! No fatal errors found</p>';
		}

		//display errors
		foreach($errors as $md5 => $error_file){
			self::DisplayFatalError($error_file);
		}
	}


	/**
	 * Display details about a single fatal error
	 *
	 */
	static function DisplayFatalError($error_file){

		$hash = substr(basename($error_file),6);

		//modified time
		echo '<p>';
		$filemtime = filemtime($error_file);
		echo self::Elapsed( time() - $filemtime ).' ago';
		echo ' - ';
		echo common::Link('Admin_Errors','Clear Error','cmd=clear_error&hash='.$hash,array('data-cmd'=>'postlink'));
		echo '</p>';


		//get info
		$contents = file_get_contents($error_file);
		if( $contents[0] == '{' && $error_info = json_decode($contents,true) ){
			//continue below
		}else{
			echo '<pre>';
			echo $contents;
			echo '</pre>';
			return;
		}


		//display details
		$error_info = array_diff_key($error_info,array('file_modified'=>'','file_size'=>''));

		echo '<pre style="font-family:monospace">';
		foreach($error_info as $key => $value){

			echo "\n".str_pad($key,'20',' ');

			switch($key){

				case 'request':
				echo '<a href="'.$value.'">'.$value.'</a>';
				break;

				case 'type':
				echo self::$types[$value].' ('.$value.')';
				break;

				default:
				echo $value;
				break;
			}
		}
		echo '</pre>';
	}


	/**
	 * Display the error log
	 *
	 */
	function ErrorLog(){

		$error_log = ini_get('error_log');

		echo '<h2 class="hmargin">';
		echo common::Link('Admin_Errors','Fatal Errors');
		echo ' <span> | </span>';
		echo ' Error Log';
		echo '</h2>';

		if( !self::ReadableLog() ){
			echo '<p>Sorry, an error log could not be found or could not be read.</p>';
			echo '<p>Log File: '.$error_log.'</p>';
			return;
		}

		echo '<p><b>Please Note:</b> The following errors are not limited to your installation of gpEasy.</p>';

		$lines = file($error_log);
		$lines = array_reverse($lines);

		$time = null;
		$displayed = array();
		foreach($lines as $line){
			$line = trim($line);
			if( empty($line) ){
				continue;
			}
			preg_match('#^\[[a-zA-Z0-9:\- ]*\]#',$line,$date);
			if( count($date) ){
				$date = $date[0];
				$line = substr($line,strlen($date));
				$date = trim($date,'[]');
				$new_time = strtotime($date);
				if( $new_time !== $time ){
					if( $time ){
						echo '</pre>';
					}
					echo '<p>';
					echo self::Elapsed( time() - $new_time ).' ago ('.$date.')';
					echo '</p>';
					echo '<pre>';
					$time = $new_time;
					$displayed = array();
				}
			}


			$line_hash = md5($line);
			if( in_array($line_hash,$displayed) ){
				continue;
			}
			echo $line;
			$displayed[] = $line_hash;
			echo "\n";
		}
		echo '</pre>';

	}


	/**
	 * Clear an error
	 *
	 */
	static function ClearError($hash){
		global $dataDir;

		if( empty($hash) || !ctype_alnum($hash) ){
			message('Invalid Request');
			return;
		}

		$dir = $dataDir.'/data/_site';
		$file = $dir.'/fatal_'.$hash;
		if( !file_exists($file) ){
			return;
		}

		$hash = md5_file($file);
		unlink($file);


		//remove matching errors
		$files = scandir($dir);
		foreach($files as $file){
			if( strpos($file,'fatal_') !== 0 ){
				continue;
			}

			$full_path = $dir.'/'.$file;
			if( $hash == md5_file($full_path) ){
				unlink($full_path);
			}
		}

	}



	static function ReadableLog(){
		$error_log = ini_get('error_log');

		if( !file_exists($error_log) ){
			return false;
		}

		if( !is_readable($error_log) ){
			return false;
		}
		return true;
	}

	static function Elapsed($difference){
		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");

		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		   $difference /= $lengths[$j];
		}

		$difference = round($difference);

		if($difference != 1) {
		   $periods[$j].= "s";
		}

		return $difference.' '.$periods[$j];
	}

}

