<?php

/**
 * Test the gp\tool\Archive class
 *
 */
class phpunit_Archive extends gptest_bootstrap{

	private $dir;
	private $types		= array('tbz','tgz','tar','zip');
	private $files		= array(
							'index.html'					=> '<html><body></body></html>',
							'foo/text.txt'					=> 'lorem ipsum',
							'foo/index.html'				=> '<html><body></body></html>',
							'/foo/bar'						=> 'foo bar',
							'foo/unicode/Kødpålæg.tst'		=> 'Die style.css hatte ich an dieser Stelle zuvor nicht überarbeitet.',
							'foo/unicode/index.html' 		=> '<html><body></body></html>',
							);

	/**
	 * Create the files and folders
	 *
	 */
	function __construct(){

		$this->dir		= sys_get_temp_dir().'/test-'.rand(1,10000000);

		foreach($this->files as $name => $content){
			$full = $this->dir.'/'.$name;
			\gp\tool\Files::Save($full,$content);
		}
	}

	/**
	 * Test creation
	 *
	 */
	function testCreate(){

		foreach($this->types as $type){
			$archive = $this->FromFiles($type);
			$list = $archive->ListFiles();
			self::AssertEquals( count($this->files), $archive->Count() );
		}

	}


	/**
	 * Test archive creation from string
	 *
	 */
	function testCreateString(){
		foreach($this->types as $type){
			$archive = $this->FromString($type);
			self::AssertEquals( count($this->files), $archive->Count() );
		}
	}


	/**
	 * Extract from a tar archive
	 *
	 */
	function testExtract(){

		foreach($this->types as $type){
			$archive	= $this->FromString($type);
			foreach($this->files as $name => $content){
				$extracted	= $archive->getFromName($name);
				self::AssertEquals($content, $extracted );
			}
		}
	}


	/**
	 * Test ListFiles()
	 *
	 */
	function testListFiles(){

		foreach($this->types as $type){
			$archive	= $this->FromString($type);
			$list		= $archive->ListFiles();
			self::AssertEquals( count($list), count($this->files) );
		}
	}


	/**
	 * Test GetRoot()
	 *
	 */
	function testGetRoot(){

		foreach($this->types as $type){
			$archive	= $this->FromString($type);
			$root		= $archive->GetRoot('text.txt');
			self::AssertEquals( 'foo', $root );
		}
	}


	/**
	 * Create an archive, add a file using AddFromString()
	 *
	 */
	function FromString($type){
		$path = $this->ArchivePath($type);

		try{
			$archive	= new \gp\tool\Archive($path);
			foreach($this->files as $name => $content){
				$added		= $archive->addFromString($name, $content);
			}
		}catch( Exception $e){
			self::AssertTrue( false, 'FromString() Failed with message: '.$e->getMessage() );
			return;
		}

		$archive->Compress();
		self::AssertFileExists( $path );

		//return a readable archive
		return new \gp\tool\Archive($path);
	}


	/**
	 * Create archive from files
	 *
	 */
	function FromFiles($type){
		$path = $this->ArchivePath($type);


		$path = $this->ArchivePath($type);

		try{
			$archive	= new \gp\tool\Archive($path);
			$archive->Add($this->dir);

		}catch( Exception $e){
			self::AssertTrue( false, 'FromFiles() Failed with message: '.$e->getMessage() );
			return;
		}

		$archive->Compress();
		self::AssertFileExists( $path );

		return new \gp\tool\Archive($path); //return a readable archive
	}



	function ArchivePath($type){
		return sys_get_temp_dir().'/archive-'.rand(0,100000).'.'.$type;
	}


}
