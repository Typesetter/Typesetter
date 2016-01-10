<?php

namespace gp\install;

defined('is_running') or die('Not an entry point...');

class FilePermissions{

	static function GetExpectedPerms($file){

		if( !self::HasFunctions() ){
			return '777';
		}

		//if user id's match
		$puid = posix_geteuid();
		$suid = self::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '755';
		}

		//if group id's match
		$pgid = posix_getegid();
		$sgid = self::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '775';
		}

		//if user is a member of group
		$snam = self::file_owner($file);
		$pmem = self::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '775';
		}

		return '777';
	}

	public static function GetExpectedPerms_file($file){

		if( !self::HasFunctions() ){
			return '666';
		}

		//if user id's match
		$puid = posix_geteuid();
		$suid = self::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '644';
		}

		//if group id's match
		$pgid = posix_getegid();
		$sgid = self::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '664';
		}

		//if user is a member of group
		$snam = self::file_owner($file);
		$pmem = self::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '664';
		}

		return '666';
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
	 * @description   Gets name of the file owner
	 * @return string The name of the file owner
	 *
	 */
	public static function file_owner($file) {
		$info = self::file_info($file);

		if (is_array($info)) {
			if (isset($info['name'])) {
				return $info['name'];
			}
			else if (isset($info['uid'])) {
				return $info['uid'];
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
		if (is_array($info)) {
			if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}

	/**
	 * @description Gets Group ID of the file owner
	 * @return int  The user Group of the file owner
	 *
	 */
	public static function file_group($file) {
		$info = self::file_info($file);
		if (is_array($info) && isset($info['gid'])) {
			return $info['gid'];
		}
		return false;
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
