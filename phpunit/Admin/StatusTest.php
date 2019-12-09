<?php


class phpunit_StatusTest extends gptest_bootstrap{

	/**
	 * Test the admin/status health check
	 * @runInSeparateProcess
	 */
	function testStatus(){

		$admin_port = new \gp\admin\Tools\Status();

		$this->AssertGreaterThan(0,$admin_port->GetValue('passed_count'));
		$this->AssertEquals(0,$admin_port->GetValue('failed_count'));

	}

}
