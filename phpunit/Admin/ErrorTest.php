<?php

namespace phpunit\Admin;

class ErrorTest extends \gptest_bootstrap{

	/**
	 * Test the export and import admin functionality
	 *
	 */
	function testError(){


		// create an exception
		//$this->expectException('\Exception');
		//\gp\tool\Output::InvalidMethod();

		$this->Login();

		$response = $this->GetRequest('Admin/Errors');
	}

}
