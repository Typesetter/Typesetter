<?php

namespace phpunit\Admin;

class ErrorTest extends \gptest_bootstrap{


	public function testError(){


		// create an exception
		//$this->expectException('\Exception');
		//\gp\tool\Output::InvalidMethod();

		$response = $this->GetRequest('Admin/Errors');
	}

}
