<?php


class phpunit_Update extends gptest_bootstrap{

	private $FileSystem;

	/**
	 *
	 * @runInSeparateProcess
	 */
	function testUpdate(){

		$this->UpdateOutputTest();
		$this->UpdateFilesystem();
		$this->UpdatePackageInfo();
		$this->DownloadSource();
		$this->UnpackAndSort();
		$this->ReplaceDirs();

	}


	static function AssertTrue($condition, $msg = '' ){
		global $page;

		if( $condition !== true && $page->update_msgs ){
			echo "\n --".implode("\n --",$page->update_msgs);
		}
		parent::assertTrue($condition,$msg);
	}



	/**
	 * Very rough integration test of the updater
	 * Passes if no errors are thrown
	 * Also defines $page for subsequent tests
	 *
	 */
	function UpdateOutputTest(){
		global $page;

		ob_start();
		$page = new \gp\admin\Update();
		\gp\tool\Output::HeadContent();
		includeFile('install/template.php');
		ob_get_clean();

	}


	/**
	 * Filesystem method detection
	 *
	 */
	function UpdateFilesystem(){
		global $page;

		$filesystem_method = $page->DetectFileSystem();
		self::AssertEquals($filesystem_method,'gp_filesystem_direct');


		$this->FileSystem = \gp\tool\FileSystem::set_method('gp_filesystem_direct');
	}


	/**
	 * Getting package info from gpeasy
	 *
	 */
	function UpdatePackageInfo(){
		global $page;

		$success = $page->DoRemoteCheck2();
		self::AssertTrue($success,'DoRemoteCheck2 Failed');

	}


	/**
	 * Make sure we can get the new source from gpeasy
	 *
	 */
	function DownloadSource(){
		global $page;

		$success = $page->DownloadSource();
		self::AssertTrue($success,'Download Source Failed');

	}


	/**
	 * Make sure the unzip and file replacement works
	 *
	 */
	function UnpackAndSort(){
		global $page;

		$success = $page->UnpackAndSort($page->core_package['file']);

		self::AssertTrue($success,'UnpackAndSort Failed');
	}


	/**
	 * Make sure we can replace the directories
	 *
	 */
	function ReplaceDirs(){
		global $page;


		$extra_dirs		= array();
		$success		= $this->FileSystem->ReplaceDirs( $page->replace_dirs, $extra_dirs );
		print_r($page->replace_dirs);
		self::AssertTrue($success,'ReplaceDirs Failed');

		if( !$success ){
			return;
		}


		//remove what we just installed
		$remove = array_keys($page->replace_dirs);
		$this->FileSystem->CleanUpFolders($remove, $not_deleted);


		//reverse it
		$replace_dirs	= $extra_dirs;
		$extra_dirs		= array();
		$success		= $this->FileSystem->ReplaceDirs( $replace_dirs, $extra_dirs );
		self::AssertTrue($success,'ReplaceDirs Failed (2)');

	}


}
