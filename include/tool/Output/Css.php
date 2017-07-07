<?php

namespace gp\tool\Output;

class Css{


	/**
	 * Convert a .scss or .less files to .css and include it in the page
	 *
	 * @param mixed $scss_files A string or array of scss files
	 * @param string $type
	 * @return bool
	 */
	public static function Cache( $file_array, $type = 'scss' ){
		global $dataDir;


		//generage the name of the css file from the modified times and content length of each imported file
		$file_array		= (array)$file_array; 
		$type			= strtolower($type);
		$files_hash		= \gp\tool::ArrayHash($file_array);
		$list_file		= $dataDir.'/data/_cache/'.$type.'_'.$files_hash.'.list';


		if( file_exists($list_file) ){

			$list = explode("\n",file_get_contents($list_file));

			//pop the etag
			$etag = array_pop($list);

			// generate an etag if needed or if logged in
			if( \gp\tool::LoggedIn() ){
				$etag = \gp\tool::FilesEtag( $list );
			}

			$compiled_name = $type.'_'.$files_hash.'_'.$etag.'.css';
			$compiled_file = '/data/_cache/'.$compiled_name;

			if( file_exists($dataDir.$compiled_file) ){
				return $compiled_file;
			}

		}


		if( $type == 'less' ){
			$compiled = self::ParseLess( $file_array );
		}else{
			$compiled = self::ParseScss( $file_array );
		}

		if( !$compiled ){
			return false;
		}


		// generate the file name
		$etag			= \gp\tool::FilesEtag( $file_array );
		$compiled_name	= $type.'_'.$files_hash.'_'.$etag.'.css';
		$compiled_file	= '/data/_cache/'.$compiled_name;


		// save the cache
		// use the last line for the etag
		$list			= $file_array;
		$list[]			= $etag;
		$cache			= implode("\n",$list);
		if( !\gp\tool\Files::Save( $list_file, $cache ) ){
			return false;
		}


		//save the css
		if( \gp\tool\Files::Save( $dataDir.$compiled_file, $compiled ) ){
			return $compiled_file;
		}

		return false;
	}

	/**
	 * Create a css file from one or more scss files
	 *
	 */
	public static function ParseScss( &$scss_files ){
		global $dataDir;

		$first_file 		= current($scss_files);
		$relative			= self::GetRelPath($first_file);


		$compiler			= new \gp\tool\Output\Scss();
		$compiler->url_root = \gp\tool::GetDir(dirname($relative));
		$compiled			= false;
		$combined			= array();


		//add variables for url paths
		$combined[] = '$icon-font-path: "../../include/thirdparty/Bootstrap3/fonts/";';


 		try{
			foreach($scss_files as $file){

				//treat as scss markup if there are newline characters
				if( strpos($file,"\n") !== false ){
					$combined[] = $file;
					continue;
				}


				// handle relative and absolute paths
				if( !empty($dataDir) && strpos($file,$dataDir) === false ){
					$file		= $dataDir.'/'.ltrim($file,'/');
				}

				$combined[]	= '@import "'.$file.'";';
			}

			$compiler->addImportPath($dataDir);

			$compiled = $compiler->compile(implode("\n",$combined));

		}catch( \Exception $e){
			if( \gp\tool::LoggedIn() ){
				msg('SCSS Compile Failed: '.$e->getMessage());
			}
			return false;
		}

		$scss_files = $compiler->allParsedFiles();
		$scss_files = array_keys($scss_files);

		return $compiled;
	}



	/**
	 * Handle the processing of multiple less files into css
	 *
	 * @return mixed Compiled css string or false
	 *
	 */
	static function ParseLess( &$less_files ){
		global $dataDir;

		$compiled = false;

		// don't use less if the memory limit is less than 64M
		$limit = @ini_get('memory_limit');
		if( $limit ){
			$limit = \gp\tool::getByteValue( $limit );

			//if less than 64M, disable less compiler if we can't increase
			if( $limit < 67108864 && @ini_set('memory_limit','96M') === false ){
				if( \gp\tool::LoggedIn() ){
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
		$import_dirs[$dataDir] = \gp\tool::GetDir('/');
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
				$relative	= self::GetRelPath($less);
				$less		= $dataDir.'/'.ltrim($relative,'/');

				$parser->ParseFile( $less, \gp\tool::GetDir(dirname($relative)) );
			}

			$compiled = $parser->getCss();

		}catch( \Exception $e){
			if( \gp\tool::LoggedIn() ){
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

	/**
	 * Return the relative path of a file
	 *
	 */
	public static function GetRelPath($path){
		global $dataDir;


		if( !empty($dataDir) && strpos($path,$dataDir) === 0 ){
			$path = substr($path,strlen($dataDir));
		}

		return rtrim($path,'/');
	}

}

