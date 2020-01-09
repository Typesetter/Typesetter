<?php

namespace phpunit\Admin;

class AddonsTest extends \gptest_bootstrap{

	private $admin_addons;

	public function setUp(){
		parent::setUp();

		$this->admin_addons = new \gp\admin\Addons([]);
	}


	public function testAddonExample(){

		$this->Login();

		$this->GetRequest('Admin/Addons');
		$this->GetRequest('Admin/Addons/Available');


		// make sure the example addon has been detected
		$avail = $this->admin_addons->GetAvailAddons();
		$this->assertArrayHasKey('Example',$avail);


		// install Example
		$params = [
			'cmd'			=> 'LocalInstall',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'source'		=> 'Example',
		];
		$this->PostRequest('Admin/Addons',$params);


		// make sure the example addon has been installed
		\gp\tool::GetConfig();
		$installed = $this->admin_addons->GetDisplayInfo();
		$this->assertArrayHasKey('Example',$installed);


		// uninstall
		$params = [
			'cmd'			=> 'confirm_uninstall',
			'verified'		=> \gp\tool::new_nonce('post', true),
			'addon'			=> 'Example',
		];
		$this->PostRequest('Admin/Addons',$params);

		\gp\tool::GetConfig();
		$installed = $this->admin_addons->GetDisplayInfo();
		$this->assertArrayNotHasKey('Example',$installed);

	}


}
