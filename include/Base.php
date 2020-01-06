<?php

namespace gp;

defined('is_running') or die('Not an entry point...');

abstract class Base{

	//executable commands
	protected $cmds				= [];
	protected $cmds_post		= [];



	/**
	 * Run Commands
	 *
	 */
	public function RunCommands($cmd){

		// POST commands
		if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cmd']) && $_POST['cmd'] === $cmd ){
			if( $this->_RunCommands($cmd, $this->cmds_post) ){
				return;
			}
		}

		// All others
		if( $this->_RunCommands($cmd, $this->cmds) ){
			return;
		}


		$this->DefaultDisplay();
	}

	private function _RunCommands($cmd, $cmds){

		if( !is_string($cmd) ){
			$cmd = '';
		}

		$cmds		= array_change_key_case($cmds, CASE_LOWER);
		$cmd		= strtolower($cmd);

		if( !isset($cmds[$cmd]) ){
			return false;
		}

		$cmds = (array)$cmds[$cmd];
		array_unshift($cmds, $cmd);

		foreach($cmds as $cmd){
			if( method_exists($this,$cmd) ){
				$this->$cmd();
			}elseif( is_callable($cmd) ){
				call_user_func($cmd, $this);
			}
		}

		return true;
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


	/**
	 * Get a property value
	 * @param string $property Name of the object property to get
	 */
	public function GetValue($property) {
		if( property_exists($this, $property) ){
		    return $this->$property;
		}
    }

}
