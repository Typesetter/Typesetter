<?php

namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	includeFile('thirdparty/jpeg-icc/autoload.php');
	includeFile('tool/ImageMeta.php');

	class Image{

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
			global $config, $dataDir;

			$src_img = self::getSrcImg($img_path); //memory usage before and after this call are small 1093804 vs 1094616
			if( !$src_img ){
				return false;
			}

			$img_type = self::getType($img_path);
			$preserve_icc_profiles =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_icc_profiles']);
			$preserve_image_metadata =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_image_metadata']);

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

			if( $preserve_icc_profiles ){
				$jpeg_icc = new \JPEG_ICC();
				$has_icc = $jpeg_icc->LoadFromJPEG($img_path);
				// msg("Image " . basename($source_path) . " has ICC profile : " . ($has_icc?"true":"false"));
			}

			if( $preserve_image_metadata ){
				$meta = \gp\tool\ImageMeta::getMeta($img_path);
				/* FOR DEBUGGING - write a text file with acquired metadata and/or possible errors to the same directory */
				/*
					$pi = pathinfo($img_path);
					$debug_metadata_file = $pi['dirname'] . '/' . $pi['filename'] . '_meta.txt';
					$debug_metadata = 'Metadata for image file "' . basename($img_path) . '"' . "\n\n";
					foreach( $meta as $mk => $mv ){
						if( is_array($mv) ){
							$debug_metadata .= $mk . ' : ' . "\n";
							foreach( $md as $errk => $errd ){
								$debug_metadata .= '  ' . $errk . ' : ' . $errv . "\n";
							}
						}else{
							$debug_metadata .= $mk . ' : ' . $mv . "\n";
						}
					}
					\gp\tool\Files::Save($debug_metadata_file, $debug_metadata); 
				*/
			}


			$result = self::createImg($src_img, $img_path, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);


			if( $preserve_image_metadata ){
				$iptc_embedded = \gp\tool\ImageMeta::saveMeta($img_path, $meta);
			}

			if( $preserve_icc_profiles && $has_icc){
				$jpeg_icc->SaveToJPEG($img_path);
			}

			@chmod($img_path, gp_chmod_file);

			return $result;
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
			global $config;

			$img_type = self::getType($source_path);
			if( strpos('svgz', $img_type) === 0 ){
				// image is SVG
				return self::CreateRectSVG($source_path,$dest_path,$size,$size,false);
			}

			$new_w = $new_h = $size;

			$src_img = self::getSrcImg($source_path,$type_file);
			if( !$src_img ){
				return false;
			}

			$preserve_icc_profiles =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_icc_profiles']);
			$preserve_image_metadata =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_image_metadata']);

			//Size
			$old_x = imagesx($src_img);
			$old_y = imagesy($src_img);

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

			if( $preserve_icc_profiles ){
				$jpeg_icc = new \JPEG_ICC();
				$has_icc = $jpeg_icc->LoadFromJPEG($source_path);
				// msg("Image " . basename($source_path) . " has ICC profile : " . ($has_icc?"true":"false"));
			}

			if( $preserve_image_metadata ){
				$meta = \gp\tool\ImageMeta::getMeta($source_path);
			}

			$result = self::createImg($src_img, $dest_path, 0, 0, $off_w, $off_h, $new_w, $new_h, $old_x, $old_y);

			if( $preserve_image_metadata ){
				$iptc_embedded = \gp\tool\ImageMeta::saveMeta($dest_path, $meta);
			}

			if( $preserve_icc_profiles && $has_icc){
				$jpeg_icc->SaveToJPEG($dest_path);
			}

			@chmod($dest_path, gp_chmod_file);

			return $result;
		}


		/**
		 * Create a rectangular image at $dest_path from an image at $source_path
		 *
		 * @param string $source_path location of the source image
		 * @param string $dest_path location of the image to be created
		 * @param int $new_w The width of the new image
		 * @param int $new_h The height of the new image
		 * @param boolean $keep_aspect_ratio Scale to fit / Crop to cover new size
		 * @return bool
		 */
		static function CreateRect($source_path,$dest_path,$new_w=50,$new_h=50,$keep_aspect_ratio=false){
			global $config;

			$img_type = self::getType($source_path);
			if( strpos('svgz', $img_type) === 0 ){
				// image is SVG
				return self::CreateRectSVG($source_path,$dest_path,$new_w,$new_h,$keep_aspect_ratio);
			}

			$src_img = self::getSrcImg($source_path,$img_type);
			if( !$src_img ){
				return false;
			}

			$preserve_icc_profiles =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_icc_profiles']);
			$preserve_image_metadata =	($img_type == 'jpg' || $img_type == 'jpeg') && !empty($config['preserve_image_metadata']);

			// Size
			$old_w = imagesx($src_img);
			$old_h = imagesy($src_img);

			// Ratios
			$old_aspect_ratio = $old_w / $old_h;
			$new_aspect_ratio = $new_w / $new_h;

			$off_w = 0;
			$off_h = 0;

			if( $keep_aspect_ratio ){
				// scale to fit into new width/height
				if( $old_aspect_ratio > $new_aspect_ratio ){ 
					// old img is wider than new one
					$new_h = round($new_h / $old_aspect_ratio * $new_aspect_ratio);
				}else{ 
					// old img is narrower than new one
					$new_w = round($new_w / $new_aspect_ratio * $old_aspect_ratio);
				}
			}else{
				// crop to cover new width/height
				if( $old_aspect_ratio > $new_aspect_ratio ){
					// old img is wider than new one
					$old_w_tmp = round($old_h * $new_aspect_ratio);
					$off_w = round(($old_w -$old_w_tmp) / 2);
					$old_w = $old_w_tmp;
				}else{
					// old img is narrower than new one
					$old_h_tmp = round($old_w / $new_aspect_ratio);
					$off_h = round(($old_h - $old_h_tmp) / 2);
					$old_h = $old_h_tmp;
				}
			}

			/* DEBUG */
			/*
			msg(
				basename($source_path) . ": <br/>"
				. "old_w=" . $old_w . " | old_h=" . $old_h . "<br/>"
				. "off_w=" . $off_w . " | off_h=" . $off_h . "<br/>"
				. "old_aspect_ratio=" . $old_aspect_ratio . "<br/>"
				. "new_aspect_ratio=" . $new_aspect_ratio . "<br/>"
				. "new_w=" . $new_w . " | new_h=" . $new_h . "<br/>"
				// . "<br/><br/>"
			);
			*/

			if( $preserve_icc_profiles ){
				$jpeg_icc = new \JPEG_ICC();
				$has_icc = $jpeg_icc->LoadFromJPEG($source_path);
				// msg("Image " . basename($source_path) . " has ICC profile : " . ($has_icc?"true":"false"));
			}

			if( $preserve_image_metadata ){
				$meta = \gp\tool\ImageMeta::getMeta($source_path);
			}

			$result = self::createImg($src_img, $dest_path, 0, 0, $off_w, $off_h, $new_w, $new_h, $old_w, $old_h);

			if( $preserve_image_metadata ){
				$iptc_embedded = \gp\tool\ImageMeta::saveMeta($dest_path, $meta);
			}

			if( $preserve_icc_profiles && $has_icc){
				$jpeg_icc->SaveToJPEG($dest_path);
			}

			@chmod($dest_path, gp_chmod_file);

			return $result;
		}



		/**
		 * SVG -- Create a rectangular SVG image at $dest_path from an SVG image at $source_path
		 *
		 * @param string $source_path location of the source SVG image
		 * @param string $dest_path location of the SVG image to be created
		 * @param int $size the dimension of the SVG image
		 * @param string $type_file a string representing the type of the source file (svg, svgz)
		 * @return bool
		 */
		static function CreateRectSVG($source_path, $dest_path, $width=50, $height=50, $keep_aspect_ratio){

			$src_svg = @file_get_contents($source_path);
			if( !$src_svg ){
				// msg($src_svg . "does not exist!");
				return false;
			}

			/*
			if( substr($src_svg,0,2) === hex2bin('1F8B') ){
				// gzip encoded svgz, decode it
				$src_svg = gzdecode($src_svg);
			}
			*/

			$internalErrors =  libxml_use_internal_errors(true);
			$disableEntities = libxml_disable_entity_loader(true);
			libxml_clear_errors();
			$doc = new \DOMDocument();
			$doc->loadXML($src_svg, LIBXML_NONET);
			libxml_use_internal_errors($internalErrors);
			libxml_disable_entity_loader($disableEntities);
			if( $error = libxml_get_last_error() ){
				libxml_clear_errors();
				// msg("SVG processing - LibXML Error: " . $error->message );
				return false;
			}
			if( strtolower($doc->documentElement->tagName) !== 'svg'){
				// msg("SVG processing - Error: data stream is not an svg image");
				return false;
			}

			$svg = $doc->documentElement;

			// get size
			$svg_width =  self::getPxVal($svg->getAttribute('width'));
			$svg_height = self::getPxVal($svg->getAttribute('height'));
			if( $svg->hasAttribute('viewBox') ){
				$viewBox = preg_split('/[\s,]+/', $svg->getAttribute('viewBox'));
				$vb_x = $viewBox[0];
				$vb_y = $viewBox[1];
				$vb_w = $viewBox[2];
				$vb_h = $viewBox[3];
			}
			if( (!$svg_width && !$svg_height) && (!$vb_w && !$vb_h) ){
				// msg("SVG processing - Error: width and height could not be determined");
				// return false;
				// no width/height provided -> we assume a 800 x 800px as default size
				$svg_width = 800;
				$svg_height = 800;
			}
			// msg("SVG file:" . basename($source_path) .  " -- svg_width=".$svg_width." | svg_height=".$svg_height);
			if( !$svg->hasAttribute('viewBox') ){
				$svg->setAttribute('viewBox', '0 0 ' . $svg_width . ' ' . $svg_height);
				$vb_x = 0;
				$vb_y = 0;
				$vb_w = $svg_width;
				$vb_h = $svg_height;
			}

			$w = $vb_w - $vb_x;
			$h = $vb_h - $vb_y;
			// left/top offsets ### needs to be changed ###
			if( $w > $h ){
				$vb_x += ($w - $h) / 2;
				$vb_w -= ($w - $h);
			}else{
				$vb_y += ($h - $w) / 2;
				$vb_h -= ($h - $w);
			}

			$svg->setAttribute('viewBox', $vb_x . ' ' . $vb_y . ' ' . $vb_w . ' ' . $vb_h);
			$svg->setAttribute('width', $width);
			$svg->setAttribute('height', $height);

			return self::saveSVG($doc, $dest_path);
		}

		/**
		* Save SVG document to file
		*/
		static function saveSVG($doc, $path){
			global $langmessage;
			$svg_xml = $doc->saveXML();

			$result = file_put_contents($path, $svg_xml);
			if( $result ){
				@chmod($path, gp_chmod_file);
				return true;
			}
			// msg($langmessage['OOPS'] . ': Unable to save SVG to ' . $path);
			return false;
		}


		/**
		* Return pixel value from value+units 
		*/
		static function getPxVal($size) {
			$map = array('px'=>1, 'pt'=>1.3333333, 'mm'=>3.7795276, 'ex'=>8, 'em'=>16, 'pc'=>16, 'cm'=>37.795276, 'in'=>96);
			$size = trim($size);
			$value = substr($size, 0, -2);
			$unit = substr($size, -2);
			if( isset($map[$unit]) && is_numeric($value) ){
				$size = $value * $map[$unit];
			}
			if( is_numeric($size) ){
				return (int) round($size);
			}
			return 0;
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
		 * Generally speaking, memory_limit should be larger than post_max_size http://php.net/manual/en/ini.core.php
		 * @static
		 */
		static function AdjustMemoryLimit(){

			//get memory limit in bytes
			$limit = @ini_get('memory_limit') or '8M';
			$limit = \gp\tool::getByteValue($limit);


			//get memory usage or use a default value
			if( function_exists('memory_get_usage') ){
				$memoryUsed = memory_get_usage();
			}else{
				$memoryUsed = 3*1048576; //sizable buffer 3MB
			}

			//since imageHeight and imageWidth aren't always available
			//use post_max_size to figure maximum memory limit
			$max_post = @ini_get('post_max_size') or '8M'; //defaults to 8M
			$max_post = \gp\tool::getByteValue($max_post);

			$needed = $max_post + $memoryUsed;
			if( $limit < $needed ){
				@ini_set( 'memory_limit', $needed);
			}
		}

		/**
		 * @deprecated 4.3
		 */
		static function getByteValue($value){}


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

			self::AdjustMemoryLimit();


			if( $type_file !== false ){
				$img_type = self::getType($type_file);
			}else{
				$img_type = self::getType($source_path);
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
			//msg('not supported for thumbnail: '.$img_type);
			return false;
		}


		/**
		 * Save the GD image ($src_img) to the desired location ($dest_path) with the sizing arguments
		 *
		 */
		static function createImg($src_img, $dest_path, $dst_x, $dst_y, $off_w, $off_h, $dst_w, $dst_h, $old_x, $old_y, $new_w = false, $new_h = false){
			global $config;

			if( !$new_w ) $new_w = $dst_w;
			if( !$new_h ) $new_h = $dst_h;

			$dst_img = imagecreatetruecolor($new_w,$new_h);
			if( !$dst_img ){
				trigger_error('dst_img not created');
				return false;
			}
			$img_type = self::getType($dest_path);


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

			return self::SrcToImage($dst_img,$dest_path,$img_type);
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

			@chmod($path, gp_chmod_file);

			imagedestroy($src);
			return true;
		}

	}
}

namespace{
	class thumbnail extends \gp\tool\Image{}
}
