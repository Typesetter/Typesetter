<?php

namespace phpunit\Admin;

class ErrorTest extends \gptest_bootstrap{


	public function testError(){


		// create an exception
		//$this->expectException('\Exception');
		//\gp\tool\Output::InvalidMethod();

		$this->Login();

		$response = $this->GetRequest('Admin/Errors');
	}

}
