<?php

namespace phpunit\Admin;

class ExtraTest extends \gptest_bootstrap{

	private $admin_extra;

	public function testChangePassword(){

		$this->admin_extra = new \gp\admin\Content\Extra([]);

		$this->Login();
		$this->GetRequest('Admin/Extra');
		$this->GetRequest('Admin/Extra','cmd=EditExtra&file=Footer');


		$types = \gp\tool\Output\Sections::GetTypes();
		foreach($types as $type => $type_info){
			$this->AddType($type);
		}
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
		$this->PostRequest('/Admin/Extra',$params);

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
		$this->PostRequest('/Admin/Extra',$params);

		$this->admin_extra->GetAreas();
		$this->assertEquals( count($this->admin_extra->areas), $area_count , 'Extra area not deleted');

	}



}
