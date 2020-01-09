<?php

namespace phpunit\Admin;

class UploadTest extends \gptest_bootstrap{


	public function testUpload(){
		global $dataDir;

		$this->Login();

		$file = $dataDir . '/include/imgs/stars.png';
		$this->UploadRequest('Admin/Uploaded',$file);

		// confirm the uploaded file exists
		$upload		 	= $dataDir.'/data/_uploaded/image/stars.png';
		$thumb			= $dataDir.'/data/_uploaded/image/thumbnails/image/stars.png.jpg';
		$this->assertFileExists($upload);
		$this->assertFileExists($thumb);

		// delete the file
		$params = [
			'file_cmd'		=> 'delete',
			'show'			=> 'inline',
			'file'			=> 'stars.png',
			'verified'		=> \gp\tool::new_nonce('post', true),
		];

		$this->PostRequest('Admin/Uploaded/image',$params);
		$this->assertFileNotExists($upload);
		$this->assertFileNotExists($thumb);
	}


	public function testInvalidUpload(){
		global $dataDir;

		$this->Login();

		$file			= $dataDir . '/include/main.php';
		$upload		 	= $dataDir.'/data/_uploaded/image/main.php';
		$this->UploadRequest('Admin/Uploaded',$file);
		$this->assertFileNotExists($upload);

	}


	/**
	 * Send a POST request to the test server
	 *
	 */
	public static function UploadRequest($slug, $file){

		$url		= 'http://localhost:8081' . \gp\tool::GetUrl($slug);

		$options	= [
					    'multipart' => [
					        [
					            'name'     => 'userfiles[]',
					            'contents' => file_get_contents($file),
					            'filename' => basename($file)
					        ],
							[
								'name'		=> 'MAX_FILE_SIZE',
								'contents'	=> 2097152,
							],
							[
								'name'		=> 'file_cmd',
								'contents'	=> 'inline_upload',
							],
							[
								'name'		=> 'dir',
								'contents'	=> '/image',
							],
							[
								'name'		=> 'verified',
								'contents'	=> \gp\tool::new_nonce('post', true),
							],
							[
								'name'		=> 'output',
								'contents'	=> 'gallery',
							],

					    ],
					];


		return self::GuzzleRequest('POST',$url,200,$options);
	}
}
