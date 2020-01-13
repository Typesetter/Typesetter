<?php

namespace phpunit\Admin;

class ExtraTest extends \gptest_bootstrap{

	private $admin_extra;

	public function setUp(){
		parent::setUp();

		$this->admin_extra = new \gp\admin\Content\Extra([]);
	}


	public function testCreateExtra(){


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

	public function testVisibility(){

		ob_start();
		\gp\tool\Output\Extra::GetExtra('Header');
		$content	= ob_get_clean();

		// assert the homepage does not contain extra content
		$response	= $this->GetRequest('');
		$body		= $response->GetBody();

		$this->assertNotStrpos($body,$content);


		// add footer extra to bottom of page
		// get container query from theme editor
		// look for url like http://localhost/index.php/Admin_Theme_Content/Edit/default?cmd=SelectContent&param=QWZ0ZXJDb250ZW50Og_0%7C
		$response	= $this->GetRequest('Admin_Theme_Content/Edit/default','cmd=in_iframe');
		$body		= $response->GetBody();

		preg_match('#cmd=SelectContent&amp;param=([^"]+)#',$body,$match);

		$param = rawurldecode($match[1]);


		// open dialog
		// /Admin_Theme_Content/Edit/default?cmd=SelectContent&param=QWZ0ZXJDb250ZW50Og_0%7C
		$response	= $this->GetRequest('Admin_Theme_Content/Edit/default','cmd=SelectContent&param='.$param);
		$body		= $response->GetBody();
		$count		= preg_match_all('#data-cmd="tabs"#',$body);
		$this->assertEquals( $count, 4 , 'Tab count didnt match expected');


		// add Header
		// /Admin_Theme_Content/Edit/default?cmd=addcontent&where=QWZ0ZXJDb250ZW50Og_0%7C&insert=Extra%3AHeader
		preg_match('#href="([^"]*)?([^"]*cmd=addcontent[^"]*Header[^"]*)"#',$body,$match);
		$page		= rawurldecode($match[1]);
		$query		= rawurldecode($match[2]);
		$response	= $this->GetRequest($page,$query);


		// confirm the homepage contains the extra content
		$this->UseAnon();
		$response	= $this->GetRequest('');
		$body		= $response->GetBody();

		$this->assertStrpos($body,$content,'Extra:Header content not found in body');


		// change visibility
		// /Admin/Extra?cmd=EditVisibility&file=Header
		$this->UseAdmin();
		$response	= $this->GetRequest('Admin/Extra','EditVisibility&file=Header');

		$params = [
			'cmd'				=> 'SaveVisibilityExtra',
			'verified'			=> \gp\tool::new_nonce('post', true),
			'file'				=> 'Header',
			'visibility_type'	=> 1,
		];
		$this->PostRequest('Admin/Extra',$params);


		// confirm the homepage does not contain the extra content
		$this->UseAnon();
		$response	= $this->GetRequest('');
		$body		= $response->GetBody();

		$this->assertNotStrpos($body,$content,'Extra:Header content was found in body');

	}


}
