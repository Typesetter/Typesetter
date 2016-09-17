<?php

if( !defined('is_running' ) ){

	define('is_running',true);
	define('gpdebug',false);
	define('gp_cookie_cmd',false);
	//define('gp_dev_combine',false); //prevents cache and 304 header when set to true

	require_once('common.php');
	\gp\tool::EntryPoint(1,'image.php',false);
	new gp_resized();
}


class gp_resized{

	public $img;
	public $height;
	public $width;
	public static $index = false;
	private static $index_checksum = false;
	private static $last_index = '9';


	/**
	 * Check the path of the img, return full path of image if the requested image is found
	 *
	 */
	function __construct(){
		global $dataDir;

		if( !isset($_GET['w']) || !isset($_GET['h']) || !isset($_GET['img']) ){
			self::Send404();
			//dies
		}

		$img		= $_GET['img'];
		$height		= $_GET['h'];
		$width		= $_GET['w'];

		if( !is_numeric($height) || !is_numeric($width) ){
			self::Send404();
			//dies
		}

		$width		= (int)$width;
		$height		= (int)$height;
		$img		= \gp\tool\Files::NoNull($img);

		//check file path
		if( strpos($img,'./') !== false || strpos($img,'%2f') !== false || strpos($img,'%2F') !== false ){
			return false;
		}

		//make sure the index is set
		gp_resized::SetIndex();
		$index		= \gp\tool::ArrayKey($_GET['i'], self::$index );
		if( !$index ){
			self::Send404();
			//dies
		}

		//if the image has been renamed, redirect to the new name
		$index_img = self::$index[$index];
		if( $index_img != $img ){
			$path = \gp\tool::GetDir('/include/image.php',false).'?i='.$index.'&w='.$width.'&h='.$height.'&img='.rawurlencode($index_img);
			\gp\tool::Redirect($path);
		}


		$info			= self::ImageInfo($img,$width,$height);
		$folder			= $dataDir.'/data/_resized/'.$info['index'];
		$full_path		= $folder.'/'.$info['name'];

		//if it exists return true
		if( file_exists($full_path) ){
			header('Cache-Control: public, max-age=5184000');//60 days

			//attempt to send 304
			$stats = lstat($full_path);
			if( $stats ){
				\gp\tool::Send304( \gp\tool::GenEtag( $stats['mtime'], $stats['size'] ) );
			}

			header('Content-Transfer-Encoding: binary');
			header('Content-Type: '.$info['ctype']);
			readfile($full_path);
			die();
		}


		//redirect to next largest image if available
		$usage = self::GetUsage($info['index']);
		foreach($usage as $size => $data){
			if( !$data['uses'] ){
				continue;
			}
			list($use_width,$use_height) = explode('x',$size);
			if( ($use_width >= $width && $use_height > $height)
				|| ($use_width > $width && $use_height >= $height)
				){

					$path = \gp\tool::GetDir('/include/image.php',false).'?i='.$index.'&w='.$use_width.'&h='.$use_height.'&img='.rawurlencode($img);
					\gp\tool::Redirect($path);
					//dies
			}
		}

		//redirect to full size image
		$original = \gp\tool::GetDir('/data/_uploaded'.$img,false);
		\gp\tool::Redirect($original);
		//dies
	}


	/**
	 * Send a 404 Not Found header to the client
	 */
	static function Send404(){
		\gp\tool::status_header(404,'404 Not Found');
		die();
	}


	/**
	 * Return information about a resized image
	 * 	- path
	 *  - extension
	 *  - ctype
	 *
	 */
	static function ImageInfo($img,$width,$height){
		global $dataDir;
		$info = array();

		$part_name = basename($img);
		$parts = explode('.',$part_name);
		if( count($parts) == 0 ){
			return false;
		}
		$info['extension'] = array_pop($parts);
		$part_name = implode('.',$parts);

		switch(strtolower($info['extension'])){
			case 'gif':
				$info['ctype'] = 'image/gif';
			break;
			case 'png':
				$info['ctype'] = 'image/png';
			break;
			case 'jpeg':
			case 'jpg':
				$info['ctype'] = 'image/jpg';
			break;
			default:
			return false;
		}

		//check to see if the reduced image exists
		$info['name'] = $width.'x'.$height.'.'.$info['extension'];
		$info['index'] = array_search($img,self::$index);
		return $info;
	}

	/**
	 * Get a new folder
	 *
	 */
	static function NewFolder(){
		global $dataDir;
		$new_index = gp_resized::NewIndex();
		return $dataDir.'/data/_resized/'.$new_index;
	}

	/**
	 * Get the next index
	 *
	 */
	static function NewIndex(){
		$next_numeric = base_convert(self::$last_index,36,10)+1;
		do{
			$index = array();
			$this_numeric = $next_numeric;
			do{
				$index[] = base_convert( substr($this_numeric,-2),10,36);
				$this_numeric = floor($this_numeric/100);
			}while($this_numeric >= 1);

			$index = implode('/',array_reverse($index));
			$next_numeric++;
		}while( is_numeric($index) || isset(self::$index[$index]) );

		self::$last_index = $index;
		return $index;
	}

	/**
	 * Get the image index information
	 *
	 */
	static function SetIndex(){
		global $dataDir;

		//prevent setting twice
		if( self::$index !== false ){
			return;
		}

		$index_file		= $dataDir.'/data/_site/image_index.php';
		self::$index	= \gp\tool\Files::Get($index_file,'image_index');

		if( self::$index ){
			self::$index_checksum	= self::checksum(self::$index);

			if( isset(\gp\tool\Files::$last_meta['last_index']) ){
				self::$last_index		= \gp\tool\Files::$last_meta['last_index'];
			}elseif( isset(\gp\tool\Files::$last_stats['last_index']) ){			//pre 4.3.6
				self::$last_index		= \gp\tool\Files::$last_stats['last_index'];
			}

		}
	}


	/**
	 * Save the image index information if the checksum has changed
	 *
	 */
	static function SaveIndex(){
		global $dataDir;

		if( self::$index_checksum === self::checksum(self::$index) ){
			return true;
		}

		$meta = array('last_index'=>self::$last_index);

		$index_file = $dataDir.'/data/_site/image_index.php';
		return \gp\tool\Files::SaveData($index_file,'image_index',self::$index,'meta_data',$meta);
	}

	/**
	 * Generate a checksum for the $array
	 *
	 */
	static function checksum($array){
		return md5(serialize($array) );
	}

	/**
	 * Get usage information about a image
	 *
	 */
	static function GetUsage($index){
		return \gp\tool\Files::Get('_resized/'.$index.'/data','usage');
	}

	/**
	 * Get usage information about a image
	 *
	 */
	static function SaveUsage($index,$data){
		global $dataDir;
		$data_file = $dataDir.'/data/_resized/'.$index.'/data.php';
		return \gp\tool\Files::SaveData($data_file,'usage',$data);
	}


	/**
	 * Return the folder path used for resized images of $img
	 *
	 */
	static function Folder($img){
		global $dataDir;
		$name = basename($img);
		return $dataDir.'/data/_resized'.\gp\tool::DirName($img).'/'.gp_resized::EncodePath($name);
	}

	/**
	 * Encode a path component
	 *
	 */
	static function EncodePath($path){
		$encoded = base64_encode($path);
		$encoded = rtrim($encoded, '=');
		return strtr($encoded, '+/=', '-_.');
	}

	/**
	 * Dencode a path component
	 *
	 */
	static function DecodePath($encoded){
		$encoded = strtr($encoded, '-_.', '+/=');
		return base64_decode($encoded);
	}

}


