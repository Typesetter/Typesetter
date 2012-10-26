<?php
defined('is_running') or die('Not an entry point...');

class gpftp{

	//try to get the ftp_server
	static function GetFTPServer(){

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}elseif( isset($_SERVER['SERVER_NAME']) ){
			$server = $_SERVER['SERVER_NAME'];
		}else{
			return '';
		}

		$conn_id = @ftp_connect($server,21,6);

		if( $conn_id ){

			@ftp_quit($conn_id);
			return $server;
		}
		return '';
	}

	static function GetFTPRoot($conn_id,$testDir){
		$ftp_root = false;

		//attempt to find the ftp_root
		$testDir = $testDir.'/';
		$array = ftp_nlist( $conn_id, '.');
		if( !$array ){
			return false;
		}
		$possible = array();
		foreach($array as $file){
			if( $file{0} == '.' ){
				continue;
			}

			//is the $file within the $testDir.. not the best test..
			$pos = strpos($testDir,'/'.$file.'/');
			if( $pos === false ){
				continue;
			}


			$possible[] = substr($testDir,$pos);
		}
		$possible[] = '/'; //test this too


		foreach($possible as $file){

			if( gpftp::TestFTPDir($conn_id,$file,$testDir) ){
				$ftp_root = $file;
				break;
			}

		}
		return $ftp_root;
	}


	//test the $file by adding a directory and seeing if it exists in relation to the $testDir
	//uses output buffering to prevent warnings from showing when we try a directory that doesn't exist
	static function TestFTPDir($conn_id,$file,$testDir){
		$success = false;

		ftp_chdir( $conn_id, '/');

		$random_name = 'gpeasy_random_'.rand(1000,9999);
		$random_full = rtrim($file,'/').'/'.$random_name;
		$test_full = rtrim($testDir,'/').'/'.$random_name;

		ob_start();
		if( !@ftp_mkdir($conn_id,$random_full) ){
			ob_end_clean();
			return false;
		}
		ob_end_clean();

		if( file_exists($test_full) ){
			$success = true;
		}

		ftp_rmdir($conn_id,$random_full);

		return $success;
	}

}
