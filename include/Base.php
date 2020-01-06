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

	private function _RunCommands($cmd, $avail_cmds){

		if( !is_string($cmd) ){
			$cmd = '';
		}

		$avail_cmds		= array_change_key_case($avail_cmds, CASE_LOWER);
		$cmd			= strtolower($cmd);

		if( !isset($avail_cmds[$cmd]) ){
			return false;
		}

		$methods = (array)$avail_cmds[$cmd];
		array_unshift($methods, $cmd);

		foreach($methods as $method){
			if( method_exists($this,$method) ){
				$this->$method();
			}elseif( is_callable($method) ){
				call_user_func($method, $this);
			}
		}

		return true;
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
