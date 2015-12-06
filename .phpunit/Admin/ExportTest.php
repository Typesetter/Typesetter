<?php


class phpunit_Export extends gptest_bootstrap{

	/**
	 * Test the export and import admin functionality
	 * @runInSeparateProcess
	 */
	function testExport(){
		global $wbMessageBuffer;

		$this->LogIn();

		includeFile('admin/admin_port.php');
		$admin_port = new admin_port();


		//create an export
		$_POST = array();
		foreach($admin_port->export_fields as $key => $info){
			$_POST[$key] = 'on';
		}

		$exported = $admin_port->DoExport();
		self::AssertTrue($exported,'Export Failed');


		//restore the archive
		$admin_port->SetExported();

		$archive			= current($admin_port->exported);
		$_REQUEST			= array('archive'=>$archive);
		$_POST				= array('cmd'=>'revert_confirmed');
		$reverted			= $admin_port->Revert('revert_confirmed');

		echo implode("\n\n",$wbMessageBuffer);

		self::AssertTrue($reverted,'Revert Failed');


	}

}

