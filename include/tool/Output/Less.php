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



	/**
	 * Handle the processing of multiple less files into css
	 *
	 * @return mixed Compiled css string or false
	 *
	 */
	static function Parse( &$less_files ){
		global $dataDir;

		$compiled = false;

		// don't use less if the memory limit is less than 64M
		$limit = @ini_get('memory_limit');
		if( $limit ){
			$limit = \common::getByteValue( $limit );

			//if less than 64M, disable less compiler if we can't increase
			if( $limit < 67108864 && @ini_set('memory_limit','96M') === false ){
				if( \common::LoggedIn() ){
					msg('LESS compilation disabled. Please increase php\'s memory_limit');
				}
				return false;

			//if less than 96M, try to increase
			}elseif( $limit < 100663296 ){
				@ini_set('memory_limit','96M');
			}
		}


		//compiler options
		$options = array();

		//prepare the compiler
		includeFile('thirdparty/less.php/Less.php');
		$parser = new \Less_Parser($options);
		$import_dirs[$dataDir] = \common::GetDir('/');
		$parser->SetImportDirs($import_dirs);


		$parser->cache_method = 'php';
		$parser->SetCacheDir( $dataDir.'/data/_cache' );


		// combine files
 		try{
			foreach($less_files as $less){

				//treat as less markup if there are newline characters
				if( strpos($less,"\n") !== false ){
					$parser->Parse( $less );
					continue;
				}

				// handle relative and absolute paths
				if( !empty($dataDir) && strpos($less,$dataDir) === false ){
					$relative = $less;
					$less = $dataDir.'/'.ltrim($less,'/');
				}else{
					$relative = substr($less,strlen($dataDir));
				}

				$parser->ParseFile( $less, \common::GetDir(dirname($relative)) );
			}

			$compiled = $parser->getCss();

		}catch(Exception $e){
			if( \common::LoggedIn() ){
				msg('LESS Compile Failed: '.$e->getMessage());
			}
			return false;
		}


		// significant difference in used memory 15,000,000 -> 6,000,000. Max still @ 15,000,000
		if( function_exists('gc_collect_cycles') ){
			gc_collect_cycles();
		}


		$less_files = $parser->allParsedFiles();
		return $compiled;
	}

}
