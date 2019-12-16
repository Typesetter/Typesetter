<?php


class phpunit_Export extends gptest_bootstrap{

	/**
	 * Test the export and import admin functionality
	 *
	 */
	function testExport(){
		global $wbMessageBuffer, $langmessage;

		$this->Login();

		// load
		$this->GetRequest('Admin/Port');


		// generate an export
		$params = [
			'pca'			=> 'on',
			'media'			=> 'on',
			'themes'		=> 'on',
			'trash'			=> 'on',
			'compression'	=> 'zip',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'cmd'			=> 'do_export',
		];

		$this->PostRequest('Admin/Port',$params);

		$archive_path	= $this->GetNewExport();
		$archive		= new \gp\tool\Archive($archive_path);
		$list			= $archive->ListFiles();


		$expected_files = [
			'gpexport/data/_pages',
			'gpexport/data/_extra',
			'gpexport/data/_site',
			'gpexport/data/_uploaded',
			'gpexport/Export.ini',
		];

		foreach($expected_files as $expected){
			foreach($list as $file){
				if( strpos($file['name'],$expected) === false ){
					continue;
				}
				continue 2;
			}

			echo 'Expected file not found in export. File = '.$expected;
			print_r($list);
			$this->fail('Expected file not found');
		}


		// revert
		$params = [
			'archive'		=> basename($archive_path),
			'verified'		=> \gp\tool::new_nonce('post', true),
			'cmd'			=> 'revert_confirmed',
		];

		$response = $this->PostRequest('Admin/Port',$params);

		if( strpos($response->GetBody(), $langmessage['Revert_Finished']) === false ){
			$this->fail('Revert failed');
		}

	}

	/**
	 * Get the newest export
	 *
	 */
	public function GetNewExport(){
		global $dataDir;

		$dir	= $dataDir . '/data/_exports';
		$files	= scandir($dir, SCANDIR_SORT_DESCENDING);
		foreach($files as $file){
			if( $file != 'index.html' ){
				return $dir.'/'.$file;
			}
		}

	}

}
