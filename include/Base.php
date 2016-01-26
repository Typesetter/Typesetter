<?php

namespace gp;

defined('is_running') or die('Not an entry point...');

abstract class Base{

	//executable commands
	protected $cmds				= array();


	/**
	 * Run Commands
	 *
	 */
	protected function RunCommands($cmd){

		$this->cmds	= array_change_key_case($this->cmds, CASE_LOWER);
		$cmd		= strtolower($cmd);

		if( !isset($this->cmds[$cmd]) ){
			$this->DefaultDisplay();
			return;
		}

		$cmds = (array)$this->cmds[$cmd];
		array_unshift($cmds, $cmd);

		foreach($cmds as $cmd){
			if( method_exists($this,$cmd) ){
				$this->$cmd();
			}elseif( is_callable($cmd) ){
				call_user_func($cmd, $this);
			}
		}

	}

	/**
	 * Set the executable commands
	 *
	 */
	protected function SetCommands(){


	}


	/**
	 * The method to execute if RunCommands()
	 *
	 */
	public function DefaultDisplay(){


	}

}