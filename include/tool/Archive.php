<?php

namespace gp\tool;

defined('is_running') or die('Not an entry point...');

class Archive{

	var $path;
	var $php_class				= 'PharData';
	var $php_object;
	var $extenstion;


	function __construct($path){

		$this->path				= $path;
		$this->extension		= $this->Extension($path);

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
	function InitTar(){

		if( file_exists($this->path) ){
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
	function InitZip(){

		$this->php_class	= 'ZipArchive';
		$this->php_object	= new \ZipArchive();

		if( file_exists($this->path) ){
			$this->php_object->open($this->path);
		}else{
			$this->php_object->open($this->path, \ZipArchive::CREATE);
		}
	}


	/**
	 * Get the extension of the file
	 *
	 */
	function Extension($path){

		$parts		= explode('.',$path);
		return array_pop($parts);
	}


	/**
	 * Call method on the archive object
	 *
	 */
	function __call( $name , $arguments ){
		return call_user_func_array( array($this->php_object,$name), $arguments);
	}


	/**
	 * Get the contents of a file within the archive
	 *
	 */
	function getFromName($name){

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
	function Compress(){

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

}
