<?php

namespace gp\tool\Output;

class Less{

	/**
	 * Convert a .less file to .css and include it in the page
	 * @param mixed $less_files A strin or array of less filesThe absolute or relative path of the .less file
	 *
	 */
	static function Cache( $less_files ){
		global $dataDir;


		//generage the name of the css file from the modified times and content length of each imported less file
		$less_files = (array)$less_files;
		$files_hash	= \common::ArrayHash($less_files);
 		$list_file	= $dataDir.'/data/_cache/less_'.$files_hash.'.list';

 		if( file_exists($list_file) ){

			$list = explode("\n",file_get_contents($list_file));


			//pop the etag
			$etag = array_pop($list);
			if( !ctype_alnum($etag) ){
				$list[] = $etag;
				$etag = false;
			}

			// generate an etag if needed or if logged in
			if( !$etag || \common::LoggedIn() ){
				$etag = \common::FilesEtag( $list );
			}

			$compiled_name = 'less_'.$files_hash.'_'.$etag.'.css';
			$compiled_file = '/data/_cache/'.$compiled_name;

			if( file_exists($dataDir.$compiled_file) ){
				return $compiled_file;
			}

		}

		$compiled = self::Parse( $less_files );
		if( !$compiled ){
			return false;
		}


		// generate the file name
		$etag			= \common::FilesEtag( $less_files );
		$compiled_name	= 'less_'.$files_hash.'_'.$etag.'.css';
		$compiled_file	= '/data/_cache/'.$compiled_name;


		// save the cache
		// use the last line for the etag
		$less_files[] = $etag;
		$cache = implode("\n",$less_files);
		if( !\gpFiles::Save( $list_file, $cache ) ){
			return false;
		}


		//save the css
		if( file_put_contents( $dataDir.$compiled_file, $compiled ) ){
			return $compiled_file;
		}

		return false;
	}




}
