<?php

namespace gp\install;

defined('is_running') or die('Not an entry point...');

class FilePermissions{

	public static function GetExpectedPerms($file){
		return static::_GetExpectedPerms($file, ['755','775','777']);
	}

	public static function GetExpectedPerms_file($file){
		return static::_GetExpectedPerms($file, ['644','664','666']);
	}

	public static function _GetExpectedPerms($file, $expected = ['644','664','666'] ){

		$file_info		= self::file_info($file);

		if( !is_array($file_info) ){
			return $expected[2];
		}

		//if user id's match
		if( isset($file_info['uid']) ){
			$puid = posix_geteuid();
			if( $puid == $file_info['uid'] ){
				return $expected[0];
			}
		}

		//if group id's match
		if( isset($file_info['gid']) ){
			$pgid = posix_getegid();
			if( $pgid == $file_info['gid'] ){
				return $expected[1];
			}
		}

		//if user is a member of group
		$members = self::process_members();
		if( isset($file_info['name']) && in_array($file_info['name'], $members) ){
			return $expected[1];
		}

		if( isset($file_info['uid']) && in_array($file_info['uid'], $members) ){
			return $expected[1];
		}

		return $expected[2];
	}

	public static function HasFunctions(){

		return function_exists('posix_getpwuid')
			&& function_exists('posix_geteuid')
			&& function_exists('fileowner')
			&& function_exists('posix_getegid')
			&& function_exists('posix_getgrgid')
			&& function_exists('posix_getgrgid');
	}


	/*
	 * Compare Permissions
	 */
	public static function perm_compare($perm1, $perm2) {

		if( !self::ValidPermission($perm1) ){
			return false;
		}

		if( !self::ValidPermission($perm2) ){
			return false;
		}

		if (intval($perm1{0}) > intval($perm2{0})) {
			return false;
		}

		if (intval($perm1{1}) > intval($perm2{1})) {
			return false;
		}

		if (intval($perm1{2}) > intval($perm2{2})) {
			return false;
		}

		return true;
	}

	public static function ValidPermission(&$permission){
		if( strlen($permission) == 3 ){
			return true;
		}
		if( strlen($permission) == 4 ){
			if( intval($permission{0}) === 0 ){
				$permission = substr($permission,1);
				return true;
			}
		}
		return false;
	}


	/**
	 * @description  Gets Groups members of the PHP Engine
	 * @return array The Group members of the PHP Engine
	 *
	 */
	public static function process_members() {
		$info = self::process_info();
		if (isset($info['members'])) {
			return $info['members'];
		}
		return array();
	}


	/**
	 * @description Gets User ID of the file owner
	 * @return int  The user ID of the file owner
	 *
	 */
	public static function file_uid($file) {
		$info = self::file_info($file);
		if( is_array($info) && isset($info['uid']) ){
			return $info['uid'];
		}
	}


	/**
	 * @description  Gets Info array of the file owner
	 * @return array The Info array of the file owner
	 *
	 */
	public static function file_info($file) {
		return posix_getpwuid(@fileowner($file));
	}

	/**
	 * @description  Gets Group Info of the PHP Engine
	 * @return array The Group Info of the PHP Engine
	 *
	 */
	public static function process_info() {
		return posix_getgrgid(posix_getegid());
	}

}
