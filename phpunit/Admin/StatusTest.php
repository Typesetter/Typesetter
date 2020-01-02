<?php


class phpunit_StatusTest extends gptest_bootstrap{

	/**
	 * Test the admin/status health check
	 *
	 */
	public function testStatus(){

		$admin_status = new \gp\admin\Tools\Status();
		$admin_status->CheckDataDir();

		$this->AssertGreaterThan(0,$admin_status->GetValue('passed_count'));
		$this->AssertEquals(0,$admin_status->GetValue('failed_count'));

	}

}
