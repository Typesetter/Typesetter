<?php


class phpunit_Export extends gptest_bootstrap{

	/**
	 * Test the export and import admin functionality
	 *
	 *
	 */
	function testExport(){

		includeFile('admin/admin_port.php');


		self::AssertTrue(false,'export test');
	}

}
