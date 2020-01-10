<?php

namespace phpunit\Admin;

class ExtraTest extends \gptest_bootstrap{

	private $admin_extra;

	public function setUp(){
		parent::setUp();

		$this->admin_extra = new \gp\admin\Content\Extra([]);
	}


	public function testChangePassword(){


		$this->Login();
		$this->GetRequest('Admin/Extra');


		$types = \gp\tool\Output\Sections::GetTypes();
		foreach($types as $type => $type_info){
			$this->AddType($type);
		}
	}

	public function testEditFooter(){

		$this->GetRequest('Admin/Extra','cmd=EditExtra&file=Footer');

		$text = '<p>New Text</p>';

		$params = [
			'cmd'			=> 'SaveText',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'gpcontent'		=> $text,
			'file'			=> 'Footer',
		];
		$this->PostRequest('Admin/Extra',$params);

		// make sure the new text shows in the preview
		$response	= $this->GetRequest('Admin/Extra','cmd=PreviewText&file=Footer');
		$body		= $response->getBody();
		$this->assertStrpos( $body, $text );


		// make sure the draft exits
		$this->admin_extra->GetAreas();
		$area_info = $this->admin_extra->ExtraExists('Footer');
		$this->assertFileExists($area_info['draft_path']);


		// publish draft ... make sure the draft file no longer exists
		$params = [
			'cmd'		=> 'PublishDraft',
			'file'		=> 'Footer',
			'verified'		=> \gp\tool::new_nonce('post', true),
		];
		$this->PostRequest('Admin/Extra',$params);
		$this->assertFileNotExists($area_info['draft_path']);

		// make sure the new text still shows
		$response	= $this->GetRequest('Admin/Extra','cmd=PreviewText&file=Footer');
		$body		= $response->getBody();
		$this->assertStrpos( $body, $text );

	}


	/**
	 * Add an extra area of $type
	 * @param string $type
	 */
	public function AddType($type){

		$area_count			= count($this->admin_extra->areas);
		$name				= 'new-'.$type;

		$params = [
			'cmd'			=> 'NewSection',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'new_title'		=> $name,
			'type'			=> $type,
		];
		$this->PostRequest('Admin/Extra',$params);

		$this->admin_extra->GetAreas();
		$this->assertEquals( count($this->admin_extra->areas), $area_count + 1 , 'Extra area not added');


		// preview
		$this->GetRequest('Admin/Extra','cmd=PreviewText&file='.$name);


		// delete
		$params = [
			'cmd'			=> 'DeleteArea',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'file'			=> $name,
		];
		$this->PostRequest('Admin/Extra',$params);

		$this->admin_extra->GetAreas();
		$this->assertEquals( count($this->admin_extra->areas), $area_count , 'Extra area not deleted');

	}



}
