<?php

namespace phpunit\Admin;

class LayoutTest extends \gptest_bootstrap{

	private $admin_layout;

	public function setUp(){
		parent::setUp();

		$this->admin_layout = new \gp\admin\Layout([]);
	}


	public function testLayout(){

		$this->UseAdmin();


		$this->GetRequest('Admin_Theme_Content');
		$this->GetRequest('Admin_Theme_Content/Available');

		$themes = [
					['Bootswatch4_Scss','cerulean'],
					['Bootswatch_Scss','Cerulian'],
					['Bootswatch_Flatly','1_Starter_Template'],
				];

		foreach($themes as $theme){
			$this->InstallLayout($theme);
		}

	}


	public function InstallLayout($theme){
		global $gpLayouts;

		\gp\tool::GetPagesPHP();
		$layouts_before		= $gpLayouts;
		$count_before		= count($gpLayouts);
		$theme_str			= $theme[0].'(local)/'. $theme[1];


		// install preview
		// http://localhost/gpeasy/dev/index.php/Admin_Theme_Content/Available?cmd=preview&theme=Bootswatch_Scss%28local%29%2FCerulian
		$this->GetRequest('Admin_Theme_Content/Available','cmd=preview&theme='.rawurlencode($theme_str));

		$params = [
			'theme'			=> $theme_str,
			'label'			=> $theme[0].'/'.$theme[1],
			'cmd'			=> 'addlayout',
			'verified'		=> \gp\tool::new_nonce('post', true),
		];

		$this->PostRequest('Admin_Theme_Content/Available',$params);

		// confirm we have a new layout in the configuration
		\gp\tool::GetPagesPHP();
		$this->AssertEquals( $count_before+1, count($gpLayouts) );

		$installed			= array_diff_key($gpLayouts, $layouts_before);
		$layout_key 		= key($installed);


		// delete the layout
		// http://localhost/gpeasy/dev/index.php/Admin_Theme_Content?cmd=deletelayout&layout=v9duzd9
		$params = [
			'layout'		=> $layout_key,
			'cmd'			=> 'deletelayout',
			'verified'		=> \gp\tool::new_nonce('post', true),
		];

		$this->PostRequest('Admin_Theme_Content',$params);

		// confirm we have a new layout in the configuration
		\gp\tool::GetPagesPHP();
		$this->assertArrayNotHasKey($layout_key,$gpLayouts);


	}


}
