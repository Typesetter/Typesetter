<?php

namespace phpunit\Admin;

class MissingTest extends \gptest_bootstrap{

	public function test404(){

		$this->UseAnon();

		$url		= \gp\tool::GetUrl( 'a-missing-page','',false);
		self::GuzzleRequest('GET',$url,404);

	}

	public function testRedir(){

		$this->UseAnon();

		$options	= ['allow_redirects'=>false];
		$url		= \gp\tool::GetUrl( 'Child_Pag','',false);
		$response	= self::GuzzleRequest('GET', $url, 302, $options);
		$location	= $response->getHeader('Location');

		$this->AssertEquals($location[0],'/index.php/Child_Page');

	}

}
