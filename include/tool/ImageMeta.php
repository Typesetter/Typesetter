<?php

namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	/* 
	 * based on parts from 
	 * WP Improved GD Image Editor by Marko Heijnen
	 * iptc_make_tag() function by Thies C. Arntzen
	 * wp_read_image_metadata() by WordPress developers
	 */

	class ImageMeta {

		static $file = null;
		static $meta_exif = array();
		static $meta_iptc = array();


		public static function getMeta($img_path){
			self::$file = $img_path;
			$meta = array(
				'created_date' => date('Ymd'),
				'created_time' => date('His') . '+0000',
				'title' => '',
				'credit' => '',
				'copyright' => '',
				'caption' => '',
				'errors' => array(),
			);

			if( is_callable('iptcparse') ){
				self::metaFromIptc();
				$meta = array_merge($meta, self::$meta_iptc);
			}else{
				$meta['errors'][] = 'iptcparse not callable';
			}
			if( is_callable('exif_read_data') ){
				self::metaFromExif();
				$meta = array_merge($meta, self::$meta_exif);
			}else{
				$meta['errors'][] = 'exif_read_data not callable';
			}
			foreach( $meta as $key => $value){
				// self::$meta[$key] = \gp\tool\Editing::Sanitize($value);
			}
			return $meta;
		}



		public static function saveMeta($img_path, $meta){
			$tags = array(
				"created_date" => "055",
				"created_time" => "060",
				"credit" => "080",
				"title" => "105",
				"copyright" => "116",
				"caption" => "120",
			);

			if( !is_callable('iptcembed') ){
				return false;
			}

			$bin_data = "";
			foreach( $tags as $tag => $data ){
				$bin_data .= self::makeTag(2, $data, $meta[$tag]);
			}

			$content = iptcembed($bin_data, $img_path, 0);

			if( file_exists($img_path) ){
				unlink($img_path);
			}

			$fp = fopen($img_path, "w");
			$result = fwrite($fp, $content);
			fclose($fp);

			return $result;
		}



		private static function makeTag($rec, $data, $value){
			$length = strlen($value);
			$retval = chr(0x1C) . chr($rec) . chr($data);

			if( $length < 0x8000 ){
				$retval .= chr($length >> 8) .  chr($length & 0xFF);
			}else{
				$retval .= chr(0x80)
					. chr(0x04)
					. chr(($length >> 24) & 0xFF)
					. chr(($length >> 16) & 0xFF)
					. chr(($length >> 8) & 0xFF) 
					. chr($length & 0xFF);
			}
			return $retval . $value;
		}




		private static function metaFromIptc(){
			$meta = array(
				'iptc_errors' => array(),
			);

			$size = getimagesize(self::$file, $info);
			if( empty($info['APP13']) ){
				$meta['iptc_errors'][] = 'APP13 section is empty';
				self::$meta_iptc = $meta;
				return false;
			}

			$iptc = iptcparse( $info['APP13'] );
			if( !$iptc ){
				$meta['iptc_errors'][] = 'iptcparse of APP13 section returns false';
				self::$meta_iptc = $meta;
				return false;
			}

			// headline
			if( !empty($iptc['2#105'][0]) ){
				$meta['title'] = trim($iptc['2#105'][0]);
			}elseif( !empty($iptc['2#005'][0]) ){
				$meta['title'] = trim( $iptc['2#005'][0]);
			}

			// description
			if( !empty($iptc['2#120'][0]) ){ 
				$caption = trim($iptc['2#120'][0]);
				$caption_length = strlen(bin2hex($caption)) / 2;
				if( empty($meta['title']) && $caption_length < 80 ){
					$meta['title'] = $caption;
				}
				$meta['caption'] = $caption;
			}

			// credit/creator
			if( !empty($iptc['2#110'][0]) ){ // credit
				$meta['credit'] = trim($iptc['2#110'][0]);
			}elseif( !empty($iptc['2#080'][0]) ){ // creator/legacy byline
				$meta['credit'] = trim($iptc['2#080'][0]);
			}

			// created date and time
			if( !empty($iptc['2#055'][0]) && !empty($iptc['2#060'][0]) ){
				$meta['created_date'] = $iptc['2#055'][0];
				$meta['created_time'] = $iptc['2#060'][0];
			}

			// copyright
			if( !empty($iptc['2#116'][0]) ){
				$meta['copyright'] = trim($iptc['2#116'][0]);
			}

			self::$meta_iptc = $meta;

			return true;
		}


		private static function metaFromExif(){
			$meta = array(
				'exif_errors' => array(),
			);

			$exif = @exif_read_data(self::$file);
			if( !$exif ){
				$meta['exif_errors'][] = 'exif_read_data returns false';
				self::$meta_exif = $meta;
				return false;
			}

			if( !empty($exif['ImageDescription']) ){
				$description_length = strlen(bin2hex($exif['ImageDescription'])) / 2;

				if( empty($meta['title']) && $description_length < 80 ){
					$meta['title'] = trim($exif['ImageDescription']);
				}

				if( empty($meta['caption']) && !empty($exif['COMPUTED']['UserComment']) ){
					$meta['caption'] = trim($exif['COMPUTED']['UserComment']);
				}

				if( empty($meta['caption']) ){
					$meta['caption'] = trim($exif['ImageDescription']);
				}
			}elseif( empty($meta['caption']) && !empty($exif['Comments']) ){
				$meta['caption'] = trim($exif['Comments']);
			}

			if( empty($meta['credit']) ){
				if( !empty($exif['Artist']) ){
					$meta['credit'] = trim($exif['Artist']);
				}elseif( !empty($exif['Author']) ){
					$meta['credit'] = trim($exif['Author']);
				}
			}

			if( !empty($exif['Copyright']) ){
				$meta['copyright'] = trim($exif['Copyright']);
			}

			if( !empty($exif['DateTimeDigitized']) ){
				$exif_date = $exif['DateTimeDigitized'];
				@list($date, $time) = explode(' ', trim($exif_date));
				@list($y, $m, $d) = explode(':', $date);
				@list($h, $i, $s) = explode(':', $time);
				$meta['created_date'] = $y . $m . $d; // YYYYMMDD
				$meta['created_time'] = $h . $i . $s . '+0000'; // HHMMSS+TZO
			}

			if( function_exists('mb_detect_encoding') ){ 
				foreach( $meta as $key => $value ){
					if( !is_array($value) && !mb_detect_encoding($value, 'UTF-8', true) ){
						$meta[$key] = utf8_encode($value);
					}
				}
			}

			self::$meta_exif = $meta;

			return true;
		}


	}

}
