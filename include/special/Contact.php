<?php

namespace gp\special{

	defined('is_running') or die('Not an entry point...');

	class Contact extends ContactGadget{

		public function ShowForm(){
			echo \gp\tool\Output::GetExtra('Contact');
			parent::ShowForm();
		}

	}
}

namespace{
	class special_contact extends \gp\special\Contact{}
	class special_contact_gadget extends \gp\special\ContactGadget{}
}
