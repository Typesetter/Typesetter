<?php


class phpunit_Export extends gptest_bootstrap{

	/**
	 * Test the export and import admin functionality
	 *
	 */
	function testExport(){
		global $wbMessageBuffer;

		/*
		$this->SessionStart();

		$admin_port = new \gp\admin\Tools\Port();


		//create an export
		$_POST = array();
		foreach($admin_port->export_fields as $key => $info){
			$_POST[$key] = 'on';
		}

		$_POST['compression'] = 'zip';
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


		//clean up
		$_POST = array('old_folder' => array_values($admin_port->extra_dirs));
		$admin_port->RevertClean();


		$this->SessionEnd();
		*/
	}

}
