<?php

namespace gp\admin\Tools;

defined('is_running') or die('Not an entry point...');

class Errors extends \gp\special\Base{

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

	public function __construct($args){

		parent::__construct($args);

		$sub_page	= '';
		$parts		= explode('/',$this->page->requested);
		if( count($parts) > 2 ){
			$sub_page = $parts[2];
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
	 * Display a list of fatal errors
	 *
	 */
	public function FatalErrors(){
		global $dataDir;

		echo '<h2 class="hmargin">';
		echo 'Fatal Errors';
		echo ' <span> | </span> ';
		echo \gp\tool::Link('Admin/Errors/Log','Error Log');
		echo '</h2>';


		//actions
		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'ClearError':
			self::ClearError($_REQUEST['hash']);
			break;
			case 'ClearAll':
			self::ClearAll();
			break;
		}


		//get unique errors
		$dir		= $dataDir.'/data/_site';
		$files		= scandir($dir);
		$errors		= [];
		foreach($files as $file){
			if( strpos($file,'fatal_') === false ){
				continue;
			}
			$full_path	= $dir.'/'.$file;
			$errors[]	= $full_path;
		}

		echo '<p>';
		if( empty($errors) ){
			echo 'Hooray! No fatal errors found';

		}else{
			echo 'Found '.count($errors).' Unique Error(s) - ';
			echo \gp\tool::Link('Admin/Errors','Clear All Errors','cmd=ClearAll','data-cmd="cnreq"','ClearErrors');
		}
		echo '</p>';
		echo '<hr/>';

		//display errors
		foreach($errors as $error_file){
			self::DisplayFatalError($error_file);
		}
	}


	/**
	 * Display details about a single fatal error
	 *
	 */
	public static function DisplayFatalError($error_file){
		global $langmessage;


		$hash = substr(basename($error_file),6);

		//modified time
		echo '<p>';
		$filemtime	= filemtime($error_file);
		$elapsed	= \gp\admin\Tools::Elapsed( time() - $filemtime );
		echo sprintf($langmessage['_ago'],$elapsed);
		echo ' - ';
		echo \gp\tool::Link('Admin/Errors','Clear Error','cmd=ClearError&hash='.$hash,['data-cmd'=>'cnreq']);
		echo '</p>';


		//get info
		if( is_dir($error_file) ){
			$files = scandir($error_file);
			foreach($files as $file){
				if( $file == '.' || $file == '..' || $file == 'index.html' ){
					continue;
				}
				$file = $error_file.'/'.$file;
				self::_DisplayFatalError($file);
			}
		}else{
			self::_DisplayFatalError($error_file);
		}

	}

	public static function _DisplayFatalError($error_file){
		global $langmessage;

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

		if( isset($error_info['time']) ){
			$error_info['elapsed'] = \gp\admin\Tools::Elapsed( time() - $error_info['time'] );
			$error_info['elapsed'] = sprintf($langmessage['_ago'],$error_info['elapsed']);
		}

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
	public function ErrorLog(){
		global $langmessage;

		$error_log = ini_get('error_log');

		echo '<h2 class="hmargin">';
		echo \gp\tool::Link('Admin/Errors','Fatal Errors');
		echo ' <span> | </span>';
		echo ' Error Log';
		echo '</h2>';

		if( !self::ReadableLog() ){
			echo '<p>Sorry, an error log could not be found or could not be read.</p>';
			echo '<p>Log File: '.$error_log.'</p>';
			return;
		}

		echo '<p><b>Please Note:</b> The following errors are not limited to your installation of '.\CMS_NAME.'.';
		echo '</p>';

		$lines			= file($error_log);

		if( $lines === false ){
			return;
		}


		$lines			= array_reverse($lines);
		$time			= null;
		$displayed		= [];

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
					$elapsed = \gp\admin\Tools::Elapsed( time() - $new_time );
					echo sprintf($langmessage['_ago'],$elapsed);
					echo ' ('.$date.')';

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
	public static function ClearError($hash){
		global $dataDir;

		if( !preg_match('#^[a-zA-Z0-9_]+$#',$hash) ){
			msg('Invalid Request');
			return;
		}

		$error_file = $dataDir.'/data/_site/fatal_'.$hash;
		if( !file_exists($error_file) ){
			msg('Error doesn\'t exist');
			return;
		}


		if( is_dir($error_file) ){
			\gp\tool\Files::RmAll($error_file);
		}else{
			unlink($error_file);
		}

	}


	/**
	 * Clear all fatal errors
	 *
	 */
	public static function ClearAll(){
		global $dataDir;


		if( !\gp\tool\Nonce::Verify( 'ClearErrors' ) ){
			return;
		}


		$dir = $dataDir.'/data/_site';

		//remove matching errors
		$files = scandir($dir);
		foreach($files as $file){

			if( strpos($file,'fatal_') !== 0 ){
				continue;
			}

			$full_path = $dir.'/'.$file;
			\gp\tool\Files::RmAll($full_path);
		}
	}


	public static function ReadableLog(){
		$error_log = ini_get('error_log');

		if( empty($error_log) || !file_exists($error_log) ){
			return false;
		}

		if( !is_readable($error_log) ){
			return false;
		}
		return true;
	}


}
