<?php

namespace gp\tool;

defined('is_running') or die('Not an entry point...');

class Nonce{


	/**
	 * Generate a nerw nonce
	 * @param string $action Should be the same $action that is passed to Verify()
	 * @param bool $anon True if the nonce is being used for anonymous users
	 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
	 * @return string
	 *
	 */
	public static function Create($action='none', $anon=false, $factor=43200){
		global $gpAdmin;

		$nonce = $action;
		if( !$anon && !empty($gpAdmin['username']) ){
			$nonce .= $gpAdmin['username'];
		}

		return self::Hash($nonce, 0, $factor);
	}


	/**
	 * Verify a nonce ($check_nonce)
	 *
	 * @param string $action Should be the same $action that is passed to new_nonce()
	 * @param mixed $check_nonce The user submitted nonce or false if $_REQUEST['_gpnonce'] can be used
	 * @param bool $anon True if the nonce is being used for anonymous users
	 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
	 * @return bool Return false if the $check_nonce did not pass. true if passed
	 *
	 */
	public static function Verify($action='none', $check_nonce=false, $anon=false, $factor=43200 ){
		global $gpAdmin;

		if( $check_nonce === false ){
			$check_nonce =& $_REQUEST['_gpnonce'];
		}

		if( empty($check_nonce) ){
			return false;
		}

		$nonce = $action;
		if( !$anon ){
			if( empty($gpAdmin['username']) ){
				return false;
			}
			$nonce .= $gpAdmin['username'];
		}

		// Nonce generated 0-12 hours ago
		if( self::Hash( $nonce, 0, $factor ) == $check_nonce ){
			return true;
		}

		// Nonce generated 12-24 hours ago
		if( self::Hash( $nonce, 1, $factor ) == $check_nonce ){
			return true;
		}

		// Invalid nonce
		return false;
	}



	/**
	 * Generate a nonce hash
	 *
	 * @param string $nonce
	 * @param int $tick_offset
	 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
	 *
	 */
	public static function Hash($nonce, $tick_offset=0, $factor=43200){
		global $config;

		$nonce_tick		= ceil(time() / $factor) - $tick_offset;
		$nonce			= $nonce . $config['gpuniq'] . $nonce_tick;


		//nonces before version 5.0
		if( gp_nonce_algo === 'legacy' ){
			return substr( md5($nonce), -12, 10);
		}

		return \gp\tool::hash($nonce,gp_nonce_algo, 2);
	}
}
