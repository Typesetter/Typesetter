<?php

namespace gp\tool;

defined('is_running') or die('Not an entry point...');

/**
 * Handle zip and tar archives with a single class
 * PharData is suppose to work for both but:
 * 	- file_get_contents('phar://...') doesn't work for zip archives
 *  - writing archives when phar.readonly = 1 does not work in hhvm: https://github.com/facebook/hhvm/issues/6647
 *
 */
class Archive{

	protected $path;
	protected $php_class				= 'PharData';
	protected $php_object;
	protected $extension;
	protected $exists;


	public function __construct($path){

		$this->path				= $path;
		$this->extension		= $this->Extension($path);
		$this->exists			= file_exists($path);

		switch( strtolower($this->extension) ){
			case 'zip':
				$this->InitZip();
			break;
			default:
				$this->InitTar();
			break;
		}

	}


	/**
	 * Initialize tar
	 *
	 */
	protected function InitTar(){

		if( $this->exists ){
			$this->php_object	= new \PharData($this->path);
			return;
		}

		switch( strtolower($this->extension) ){
			case 'tbz':
			case 'tgz':
				$this->path			= preg_replace('#\.(tgz|tbz)$#','.tar',$this->path);
			break;
		}


		$this->php_object	= new \PharData($this->path);
	}


	/**
	 * Initialize a zip archive
	 *
	 */
	protected function InitZip(){

		$this->php_class	= 'ZipArchive';
		$this->php_object	= new \ZipArchive();

		if( $this->exists ){
			$this->php_object->open($this->path);
		}else{
			$this->php_object->open($this->path, \ZipArchive::CREATE);
		}
	}


	/**
	 * Get the extension of the file
	 *
	 */
	protected function Extension($path){

		$parts		= explode('.',$path);
		return array_pop($parts);
	}


	/**
	 * Call method on the archive object
	 *
	 */
	public function __call( $name , $arguments ){
		return call_user_func_array( array($this->php_object,$name), $arguments);
	}


	/**
	 * Get the contents of a file within the archive
	 *
	 */
	public function getFromName($name){

		if( $this->php_class === 'ZipArchive' ){
			return $this->php_object->getFromName($name);
		}

		$full_path			= 'phar://'.$this->path.'/'.ltrim($name,'/');
		return file_get_contents($full_path);
	}


	/**
	 * Add the final compression to the archive
	 *
	 */
	public function Compress(){

		if( $this->php_class === 'ZipArchive' ){
			$this->php_object->close();
		}


		switch($this->extension){
			case 'tbz':
				$this->php_object->compress(\Phar::BZ2,'tbz');
				unlink($this->path);
			break;
			case 'tgz':
				$this->php_object->compress(\Phar::GZ,'tgz');
				unlink($this->path);
			break;
		}

	}

	/**
	 * Count the number of files
	 *
	 */
	public function Count(){

		if( method_exists($this->php_object,'Count') ){
			return $this->php_object->Count();
		}

		return $this->php_object->numFiles;
	}

	/**
	 * List the files in the archive
	 *
	 */
	public function ListFiles(){
		$list	= array();

		if( method_exists($this->php_object,'statIndex') ){
			$count	= $this->Count();
			for( $i = 0; $i < $count; $i++ ){
				$list[] = $this->php_object->statIndex( $i );
			}
			return $list;
		}


		return $this->GenList($list);
	}

	public function GenList($list, $dir = ''){

		$path = 'phar://'.$this->path.'/'.$dir;
		$_list = scandir($path);
		foreach($_list as $file){

			$full		= ltrim($dir.'/'.$file,'/');
			$path		= 'phar://'.$this->path.'/'.$full;


			if( is_dir($path) ){
				$list = $this->GenList($list, $full);
			}else{
				$stat		= stat($path);
				$stat['name'] = $full;
				$list[]		= array_intersect_key($stat,array('name'=>'','mtime'=>'','size'=>''));
			}
		}

		return $list;
	}


	/**
	 * Get Archive Root
	 *
	 */
	public function GetRoot($search_file = 'Addon.ini'){

		$archive_files	= $this->ListFiles();
		$archive_root	= null;

		foreach( $archive_files as $file ){

			if( strpos($file['name'],$search_file) === false ){
				continue;
			}

			$root = \gp\tool::DirName($file['name']);

			if( $root == '.' ){
				$root = '';
			}

			if( is_null($archive_root) || ( strlen($root) < strlen($archive_root) ) ){
				$archive_root = $root;
			}

		}

		return $archive_root;
	}


	/**
	 * Recursively add files to the archive
	 *
	 */
	public function Add( $path, $localname = null){

		if( !file_exists($path) ){
			return false;
		}

		if( is_null($localname) ){
			$localname = $path;
		}

		if( is_link($path) ){
			return true;
		}


		if( !is_dir($path) ){
			return $this->php_object->AddFile($path, $localname);
		}


		$files = scandir($path);
		foreach($files as $file){
			if( $file === '.' || $file === '..' ){
				continue;
			}
			$full_path		= $path.'/'.$file;
			$_localname		= $localname.'/'.$file;

			$this->Add( $full_path, $_localname);
		}
	}


}
