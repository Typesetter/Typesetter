<?php

namespace phpunit\Admin;

class MissingTest extends \gptest_bootstrap{

	public function test404(){

		$url		= 'http://localhost:8081' . \gp\tool::GetUrl( 'a-missing-page','',false);
		self::GuzzleRequest('GET',$url,404);

	}

	public function testRedir(){

		$options	= ['allow_redirects'=>false];
		$url		= 'http://localhost:8081' . \gp\tool::GetUrl( 'Child_Pag','',false);
		$response	= self::GuzzleRequest('GET', $url, 302, $options);
		$location	= $response->getHeader('Location');

		$this->AssertEquals($location[0],'/index.php/Child_Page');

	}

}
