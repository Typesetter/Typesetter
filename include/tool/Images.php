<?php
defined('is_running') or die('Not an entry point...');

class thumbnail{

	/**
	 * Reduce the size of an image according to $max_area
	 *
 	 * @since 1.7
	 *
	 * @param string $img_path location of the image
	 * @param int $max_area Maximum area of the image: 600x800 pixels = 480,000 sq/pixels
	 * @return bool true if the image doesn't need to be reduced or if a reduction is made
	 */
	static function CheckArea($img_path,$max_area=1024000){

		$src_img = thumbnail::getSrcImg($img_path); //memory usage before and after this call are small 1093804 vs 1094616
		if( !$src_img ){
			return false;
		}

		//Original Size
		$old_x = imagesx($src_img);
		$old_y = imagesy($src_img);
		$old_area = ($old_x * $old_y);

		//don't enlarge check 1
		if( $old_area < $max_area ){
			return true;
		}

		//Calculate the new size
		$inv_ratio = $old_y / $old_x;

		$new_y = sqrt($max_area * $inv_ratio);
		$new_y = round($new_y);
		$new_x = round($max_area / $new_y);

		//don't enlarge check 2
		$new_area = ($new_y * $new_x);
		if( $new_area > $old_area ){
			return true;
		}

		return thumbnail::createImg($src_img, $img_path, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);
	}



	/**
	 * Create a square image at $dest_path from an image at $source_path
	 *
	 * @param string $source_path location of the source image
	 * @param string $dest_path location of the image to be created
	 * @param int $size the dimension of the image
	 * @param string $type_file a string representing the type of the source file (png, jpg)
	 * @return bool
	 */
	static function createSquare($source_path,$dest_path,$size=50,$type_file=false){
		$new_w = $new_h = $size;

		$src_img = thumbnail::getSrcImg($source_path,$type_file);
		if( !$src_img ){
			return false;
		}

		//Size
		$old_x = imagesx($src_img);
		$old_y = imagesy($src_img);


		//
		if( $old_x > $old_y ){
			$off_w = ($old_x - $old_y) / 2;
			$off_h = 0;
			$old_x = $old_y;
		}elseif( $old_y > $old_x ){
			$off_w = 0;
			$off_h = ($old_y - $old_x) / 2;
			$old_y = $old_x;
		}else{
			$off_w = 0;
			$off_h = 0;
		}

		//don't make the thumbnail larger
		if( ($old_x < $size) && ($old_y < $size ) ){
			$new_w = $new_h = max($old_x,$old_y);
		}

		return thumbnail::createImg($src_img, $dest_path, 0, 0, $off_w, $off_h, $new_w, $new_h, $old_x, $old_y);
	}


	/**
	 * Create a rectangular image at $dest_path from an image at $source_path
	 *
	 * @param string $source_path location of the source image
	 * @param string $dest_path location of the image to be created
	 * @param int $new_w The width of the new image
	 * @param int $new_h The height of the new image
	 * @param string $img_type A string representing the type of the source file (png, jpg)
	 * @return bool
	 */
	static function CreateRect($source_path,$dest_path,$new_w=50,$new_h=50,$img_type=false){

		$src_img = thumbnail::getSrcImg($source_path,$img_type);
		if( !$src_img ){
			return false;
		}

		//Size
		$old_w = imagesx($src_img);
		$old_h = imagesy($src_img);

		$dst_x = 0;
		$dst_y = 0;


		$width_ratio = $new_w / $old_w;
		$height_ratio = $new_h / $old_h;

		if( $width_ratio < $height_ratio ){
			$temp_h = round($width_ratio * $old_h);
			$temp_w = $new_w;

		}else{
			$temp_w = round($height_ratio * $old_w);
			$temp_h = $new_h;
		}

		return thumbnail::createImg($src_img, $dest_path, $dst_x, $dst_y, 0, 0, $temp_w, $temp_h, $old_w, $old_h, $new_w, $new_h);
	}


	/**
	 * Return a type file type of a given file given by it's $path on the file system
	 * @static
	 */
	static function getType($path){
		$nameParts = explode('.',$path);
		$type = array_pop($nameParts);
		return strtolower($type);
	}

	/**
	 * Attempt to increase php's memory limit using the current memory used and the post_max_size value
	 * @static
	 */
	static function AdjustMemoryLimit(){
		//Generally speaking, memory_limit should be larger than post_max_size http://php.net/manual/en/ini.core.php


		//get memory limit in bytes
		$limit = @ini_get('memory_limit') or '8M';
		$limit = thumbnail::getByteValue($limit);


		//get memory usage or use a default value
		if( function_exists('memory_get_usage') ){
			$memoryUsed = memory_get_usage();
		}else{
			$memoryUsed = 3*1048576; //sizable buffer 3MB
		}

		//since imageHeight and imageWidth aren't always available
		//use post_max_size to figure maximum memory limit
		$max_post = @ini_get('post_max_size') or '8M'; //defaults to 8M
		$max_post = thumbnail::getByteValue($max_post);

		$needed = $max_post + $memoryUsed;
		if( $limit < $needed ){
			@ini_set( 'memory_limit', $needed);
		}
	}

	/**
	 * Convert a string representation of a byte value to an number
	 * @param string $value
	 * @return int
	 */
	static function getByteValue($value){

		if( is_numeric($value) ){
			return (int)$value;
		}
		$value = strtolower($value);

		$lastChar = $value{strlen($value)-1};
		$num = (int)substr($value,0,-1);

		switch($lastChar){

			case 'g':
				$num *= 1024;
			case 'm':
				$num *= 1024;
			case 'k':
				$num *= 1024;
			break;
		}
		return $num;
	}


	/**
	 * Using the given $source_path of an image, return the GD image if possible
	 * @param string $source_path The path of the source image
	 * @param string $type_file The file type of the source image
	 * @return mixed GD image if successful, false otherwise
	 */
	static function getSrcImg($source_path,$type_file=false){
		if( !function_exists('imagetypes') ){
			return false;
		}

		thumbnail::AdjustMemoryLimit();


		if( $type_file !== false ){
			$img_type = thumbnail::getType($type_file);
		}else{
			$img_type = thumbnail::getType($source_path);
		}

		$supported_types = imagetypes();


		//start
		switch($img_type){
			case 'jpg':
			case 'jpeg':
				if( $supported_types & IMG_JPG ){
					return imagecreatefromjpeg($source_path);
				}
			break;
			case 'gif':
				return imagecreatefromgif($source_path);
			break;
			case 'png':
				if( $supported_types & IMG_PNG) {
					return imagecreatefrompng($source_path);
				}
			break;
			case 'bmp';
				if( $supported_types & IMG_WBMP) {
					return imagecreatefromwbmp($source_path);
				}
			break;
		}
		//message('not supported for thumbnail: '.$img_type);
		return false;
	}


	/**
	 * Save the GD image ($src_img) to the desired location ($dest_path) with the sizing arguments
	 *
	 */
	static function createImg($src_img, $dest_path, $dst_x, $dst_y, $off_w, $off_h, $dst_w, $dst_h, $old_x, $old_y, $new_w = false, $new_h = false){
		if( !$new_w ) $new_w = $dst_w;
		if( !$new_h ) $new_h = $dst_h;

		$dst_img = imagecreatetruecolor($new_w,$new_h);
		if( !$dst_img ){
			trigger_error('dst_img not created');
			return false;
		}
		$img_type = thumbnail::getType($dest_path);


		// allow gif & png to have transparent background
		switch($img_type){
			case 'gif':
			case 'png':
				$dst_img = self::Transparency($dst_img);
			break;
		}


		if( !imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $off_w, $off_h, $dst_w, $dst_h, $old_x, $old_y) ){
			trigger_error('copyresample failed');
			imagedestroy($dst_img);
			imagedestroy($src_img);
			return false;
		}

		imagedestroy($src_img);

		return thumbnail::SrcToImage($dst_img,$dest_path,$img_type);
	}

	static function Transparency($image){
		if( function_exists('imagesavealpha') ){
			imagesavealpha($image,true);
			$bgcolor = imagecolorallocatealpha($image, 133, 134, 135, 127);
			imagefill($image, 0, 0, $bgcolor);
		}
		return $image;
	}




	/**
	 * Output image to path based on type
	 * will already have checked for support via the getSrcImg function
	 *
	 */
	static function SrcToImage($src,$path,$type){
		$result = false;
		switch($type){
			case 'jpeg':
			case 'jpg':
				$result = imagejpeg($src,$path,90);
			break;
			case 'gif':
				$result = imagegif($src,$path);
			break;
			case 'png':
				$result = imagepng($src,$path,9,PNG_ALL_FILTERS);
			break;
			case 'bmp':
				$result = imagewbmp($src,$path);
			break;
		}

		if( $result === false ){
			@imagedestroy($src);
			return false;
		}

		@chmod( $path, 0666 );

		imagedestroy($src);
		return true;
	}

}
