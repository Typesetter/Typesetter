<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Gadget extends EasyComments{

	function EasyComments_Gadget(){

		$this->Init();
		$this->Run();
	}

}
