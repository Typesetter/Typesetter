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
	public static function Cache($file_array, $type='scss'){
		global $dataDir;

		//generage the name of the css file from the modified times and content length of each imported file
		$file_array		= (array)$file_array;
		$type			= strtolower($type);
		$files_hash		= \gp\tool::ArrayHash($file_array);
		$list_file		= $dataDir . '/data/_cache/' . $type . '_' . $files_hash . '.list';

		if( file_exists($list_file) ){

			$list = explode("\n", file_get_contents($list_file));

			//pop the etag
			$etag = array_pop($list);

			// generate an etag if needed or if logged in
			if( \gp\tool::LoggedIn() ){
				$etag = \gp\tool::FilesEtag($list);
			}

			$compiled_name = $type . '_' . $files_hash . '_' . $etag . '.css';
			$compiled_file = '/data/_cache/' . $compiled_name;

			if( file_exists($dataDir . $compiled_file) ){
				return $compiled_file;
			}

		}

		if( $type == 'less' ){
			$parsed_data			= self::ParseLess($file_array);
			$compiled				= $parsed_data[0];
			$temp_sourcemap_name	= $parsed_data[1];
		}else{
			$parsed_data			= self::ParseScss($file_array);
			$compiled				= $parsed_data[0];
			$temp_sourcemap_name	= $parsed_data[1];
		}

		if( !$compiled ){
			return false;
		}

		// generate the file name
		$etag			= \gp\tool::FilesEtag($file_array);
		$compiled_name	= $type . '_' . $files_hash . '_' . $etag . '.css';
		$compiled_file	= '/data/_cache/' . $compiled_name;
		$sourcemap_name	= $type . '_' . $files_hash . '_' . $etag . '.map';

		// save the cache
		// use the last line for the etag
		$list			= $file_array;
		$list[]			= $etag;
		$cache			= implode("\n", $list);
		if( !\gp\tool\Files::Save($list_file, $cache) ){
			return false;
		}

		//rename the temp source map, append the comment/URL to the compiled css file
		if( self::RenameSourceMap($temp_sourcemap_name, $sourcemap_name) ){
			//remove possible existing sourceMapping comments
			$compiled = preg_replace('%/\*#\ssourceMappingURL.*\*/%s', '', $compiled);
			//append final comment
			$compiled = $compiled . "\n" . '/*# sourceMappingURL=' . $sourcemap_name . ' */' . "\n";
		};

		//save the css
		if( \gp\tool\Files::Save($dataDir . $compiled_file, $compiled) ){
			return $compiled_file;
		}

		return false;
	}



	/**
	 * Rename source map file and set permissions
	 *
	 * @param string temporary sourcemap filename, created by parser
	 * @param string target sourcemap filename to match compiled css
	 *
	 */
	private static function RenameSourceMap($temp_sourcemap_name, $sourcemap_name){
		global $dataDir;

		// for debugging only! \gp\tool\Files::SaveData($dataDir . '/data/_cache/debug.php', 'debug', array($temp_sourcemap_name, $sourcemap_name));
		if( $temp_sourcemap_name == false ){
			return false;
		}

		if( file_exists($dataDir . '/data/_cache/' . $temp_sourcemap_name) ){
			if( !rename( $dataDir . '/data/_cache/' . $temp_sourcemap_name,	$dataDir . '/data/_cache/' . $sourcemap_name) ){
				return false;
			}
			@chmod($dataDir . '/data/_cache/' . $sourcemap_name, gp_chmod_file);
		}
		return true;
	}



	/**
	 * Create a css file from one or more scss files
	 *
	 */
	public static function ParseScss( &$scss_files ){
		global $dataDir, $dirPrefix;

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
			// set 'compressed' format for compiled css
			$compiler->setFormatter('ScssPhp\ScssPhp\Formatter\Compressed');


			$temp_sourcemap_name = false;
			// create sourcemaps, see gpconfig.php to enable it
			if( \create_css_sourcemaps ){

				\gp\tool\Files::CheckDir($dataDir . '/data/_cache');

				$temp_sourcemap_name = 'tmp_' . \gp\tool::RandomString(12) . ".map";  // will be renamed later

				// see https://github.com/leafo/scssphp/wiki/Source-Maps
				$compiler->setSourceMap(Scss::SOURCE_MAP_FILE);

				$compiler->setSourceMapOptions(array(
					// absolute path to write .map file
					'sourceMapWriteTo'		=> $dataDir . '/data/_cache/' . $temp_sourcemap_name,

					// relative or full url to the above .map file
					'sourceMapURL'			=> $dirPrefix . '/data/_cache/' . $temp_sourcemap_name,

					// (optional) relative or full url to the .css file
					// 'sourceMapFilename' 	=> $css_fname,  // url location of .css file

					// partial path (server root) removed (normalized) to create a relative url
					// difference between file & url locations, removed from ALL source files in .map
					'sourceMapBasepath'		=> strlen($dirPrefix) != 0 ? substr($dataDir, 0, strlen($dirPrefix) * -1) : $dataDir,

					// (optional) prepended to 'source' field entries for relocating source files
					'sourceRoot'			=> '/',
				));

			}

			$compiled	= $compiler->compile(implode("\n", $combined));

		}catch( \Exception $e){
			if( \gp\tool::LoggedIn() ){
				msg('SCSS Compile Failed: ' . $e->getMessage());
			}
			return false;
		}

		$scss_files = $compiler->getParsedFiles();
		$scss_files = array_keys($scss_files);

		return array($compiled, $temp_sourcemap_name);
	}



	/**
	 * Handle the processing of multiple less files into css
	 *
	 * @return mixed Compiled css string or false
	 *
	 */
	static function ParseLess( &$less_files ){
		global $dataDir, $dirPrefix;

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

		$temp_sourcemap_name = false;
		// create sourcemaps, see gpconfig.php to enable it
		if( \create_css_sourcemaps ){
			\gp\tool\Files::CheckDir($dataDir . '/data/_cache');
			$temp_sourcemap_name 			= 'tmp_' . \gp\tool::RandomString(12) . ".map";  // will be renamed later
			$options['sourceMap'] 			= true;
			$options['sourceMapWriteTo']	= $dataDir . '/data/_cache/' . $temp_sourcemap_name;
			$options['sourceMapURL']		= $dirPrefix . '/data/_cache/' . $temp_sourcemap_name;
		}

		// set 'compressed' format for compiled css
		$options['compress'] = 'true';

		//prepare the compiler
		includeFile('thirdparty/less.php/Autoloader.php');
		\Less_Autoloader::register();
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
		return array($compiled, $temp_sourcemap_name);
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
