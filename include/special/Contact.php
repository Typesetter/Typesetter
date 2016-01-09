<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');

class Contact extends ContactGadget{

	public function ShowForm(){
		global $page,$langmessage,$config;

		echo \gpOutput::GetExtra('Contact');
		parent::ShowForm();
	}

}
