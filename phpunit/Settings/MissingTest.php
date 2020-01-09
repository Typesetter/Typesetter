<?php

namespace phpunit\Admin;

class MissingTest extends \gptest_bootstrap{

	public function testMissing(){

		$url		= 'http://localhost:8081' . \gp\tool::GetUrl( 'a-missing-page','',false);
		self::GuzzleRequest('GET',$url,404);

	}

}
