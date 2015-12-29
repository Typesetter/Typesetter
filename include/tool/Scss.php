<?php

namespace gp\tool;

class Scss extends \Leafo\ScssPhp\Compiler{


	/**
	 * Convert a .scss file to .css and include it in the page
	 * @param mixed $scss_files A strin or array of scss filesThe absolute or relative path of the .scss file
	 *
	 */
	function Cache( $scss_files ){
		global $dataDir;


		//generage the name of the css file from the modified times and content length of each imported scss file
		$scss_files = (array)$scss_files;
		$files_hash	= \common::ArrayHash($scss_files);
 		$list_file	= $dataDir.'/data/_cache/scss_list_'.$files_hash.'.list';

 		if( file_exists($list_file) ){

			$list = explode("\n",file_get_contents($list_file));

			//pop the etag
			$etag = array_pop($list);

			// generate an etag if needed or if logged in
			if( \common::LoggedIn() ){
				$etag = \common::FilesEtag( $list );
			}

			$compiled_name = 'scss_'.$files_hash.'_'.$etag.'.css';
			$compiled_file = '/data/_cache/'.$compiled_name;

			if( file_exists($dataDir.$compiled_file) ){
				return $compiled_file;
			}

		}


		$compiled = $this->Parse( $scss_files );
		if( !$compiled ){
			return false;
		}


		// generate the file name
		$etag			= \common::FilesEtag( $this->importedFiles );
		$compiled_name	= 'scss_'.$files_hash.'_'.$etag.'.css';
		$compiled_file	= '/data/_cache/'.$compiled_name;


		// save the cache
		// use the last line for the etag
		$list			= $this->importedFiles;
		$list[]			= $etag;
		$cache			= implode("\n",$list);
		if( !\gpFiles::Save( $list_file, $cache ) ){
			return false;
		}


		//save the css
		if( \gpFiles::Save( $dataDir.$compiled_file, $compiled ) ){
			return $compiled_file;
		}

		return false;
	}

	/**
	 * Create a css file from one or more scss files
	 *
	 */
	public function Parse( $scss_files ){
		global $dataDir;

		$compiled	= false;
		$combined	= array();


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

			$this->addImportPath($dataDir);

			$compiled = $this->compile(implode("\n",$combined));

		}catch( \Exception $e){
			if( \common::LoggedIn() ){
				msg('SCSS Compile Failed: '.$e->getMessage());
			}
			return false;
		}

		return $compiled;
	}


}

