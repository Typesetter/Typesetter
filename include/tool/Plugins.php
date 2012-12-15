<?php
defined('is_running') or die('Not an entry point...');

/**
 * Include a file in the current plugin directory
 * @param string $file File to include relative to the current plugin directory
 * @since 3.5.3
 */
function gpPlugin_incl($file){
	global $addonPathCode, $dataDir;
	if( gp_safe_mode ){
		return;
	}
	//IncludeScript($addonPathCode.'/'.$file); //actually makes debugging more difficult because of cascading errors
	include_once($addonPathCode.'/'.$file);
}


class gpPlugin{

	private static $stack = array();


	/**
	 * Aliases of gpPlugin_incl()
	 * @deprecated 3.5.3
	 */
	function incl($file){
		return gpPlugin_incl($file);
	}

	function inc($file){
		return gpPlugin_incl($file);
	}

	/**
	 * Similar to php's register_shutdown_function()
	 * This gpEasy specific version will keep track of the active plugin and make sure global path variables are set properly before callting $function
	 * Example: gpPlugin::RegisterShutdown(array('class_name','method_name'));  or  gpPlugin::RegisterShutdown(array('class_name','method_name'),'argument1'....);
	 *
	 */
	static function RegisterShutdown(){
		global $addonFolderName;
		if( gp_safe_mode ) return;
		$args = func_get_args();
		register_shutdown_function(array('gpPlugin','ShutdownFunction'),$addonFolderName,$args);
	}

	/**
	 * Handle functions passed to gpPlugin::RegisterShutdown()
	 * This function should not be called directly.
	 */
	static function ShutdownFunction($addonFolderName,$args){

		if( gp_safe_mode ) return;

		if( !is_array($args) || count($args) < 1 ){
			return false;
		}

		gpPlugin::SetDataFolder($addonFolderName);

		$function = array_shift($args);

		if( count($args) > 0 ){
			call_user_func_array( $function , $args );
		}else{
			call_user_func( $function  );
		}

		gpPlugin::ClearDataFolder();
	}


	/**
	 * Similar to wordpress apply_filters_ref_array()
	 *
	 */
	static function Filter($hook, $args = array() ){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			if( isset($args[0]) ){
				return $args[0];
			}
			return false;
		}

		foreach($config['hooks'][$hook] as $hook_info){
			$args[0] = gpPlugin::ExecHook($hook,$hook_info,$args);
		}

		if( isset($args[0]) ){
			return $args[0];
		}
		return false;
	}


	static function OneFilter($hook,$args=array()){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			return false;
		}

		$hook_info = end($config['hooks'][$hook]);

		return gpPlugin::ExecHook($hook,$hook_info,$args);
	}

	static function Action($hook, $args = array() ){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			return;
		}

		foreach($config['hooks'][$hook] as $hook_info){
			gpPlugin::ExecHook($hook,$hook_info,$args);
		}
	}

	/**
	 * Check to see if there area any hooks matching $hook
	 * @param string $hook The name of the hook
	 * @return bool
	 *
	 */
	static function HasHook($hook){
		global $config;
		if( empty($config['hooks']) || empty($config['hooks'][$hook]) ){
			return false;
		}
		return true;
	}

	static function ArgReturn($args){
		if( is_array($args) && isset($args[0]) ){
			return $args[0];
		}
	}


	/**
	 * Execute the php code associated with a $hook
	 * @param string $hook
	 * @param array $hook_info
	 * @param array $args
	 *
	 */
	static function ExecHook($hook,$info,$args = array()){
		global $dataDir, $gp_current_hook;

		if( gp_safe_mode ){
			if( isset($args[0]) ){
				return $args[0];
			}
			return;
		}

		if( !is_array($args) ){
			$args = array($args);
		}
		$gp_current_hook[] = $hook;

		//value
		if( !empty($info['value']) ){
			$args[0] = $info['value'];
		}

		$args = gpOutput::ExecInfo($info,$args);

		array_pop( $gp_current_hook );
		if( isset($args[0]) ){
			return $args[0];
		}
		return false;
	}

	/**
	 * Set global path variables for the current addon
	 * @param string $addon_key Key used to identify a plugin uniquely in the configuration
	 *
	 */
	static function SetDataFolder($addon_key){
		global $dataDir, $config;
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id,$addon_current_version;

		if( !isset($config['addons'][$addon_key]) ){
			return;
		}

		gpPlugin::StackPush();

		$data_folder = gpPlugin::GetDataFolder($addon_key);

		$addon_current_id = $addon_current_version = false;
		if( isset($config['addons'][$addon_key]['id']) ){
			$addon_current_id = $config['addons'][$addon_key]['id'];
		}

		if( isset($config['addons'][$addon_key]['version']) ){
			$addon_current_version = $config['addons'][$addon_key]['version'];
		}

		$addonFolderName = $addon_key;
		$addonPathCode = $addonCodeFolder = $dataDir.'/data/_addoncode/'.$addon_key;
		$addonPathData = $addonDataFolder = $dataDir.'/data/_addondata/'.$data_folder;
		$addonRelativeCode = common::GetDir('/data/_addoncode/'.$addon_key);
		$addonRelativeData = common::GetDir('/data/_addondata/'.$data_folder);

	}

	/**
	 * If there's a current addon folder or addon id, push it onto the stack
	 *
	 */
	static function StackPush(){
		global $addonFolderName, $addon_current_id;

		if( !$addon_current_id && !$addonFolderName ){
			return;
		}
		self::$stack[] = array('folder'=>$addonFolderName,'id'=>$addon_current_id);
	}


	/**
	 * Reset global path variables
	 */
	static function ClearDataFolder(){
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id,$addon_current_version;


		$addonFolderName = false;
		$addonDataFolder = $addonCodeFolder = false;
		$addonRelativeCode = $addonRelativeData = $addonPathData = $addonPathCode = $addon_current_id = $addon_current_version = false;

		//Make the most recent addon folder or addon id in the stack the current addon
		if( count(self::$stack) > 0 ){
			$info = array_pop(self::$stack);
			if( $info['folder'] ){
				gpPlugin::SetDataFolder($info['folder']);
			}elseif( $info['id'] ){
				$addon_current_id = $info['id'];
			}
		}
	}

	/**
	 * Some installations my still have plugins that rely on this setting
	 * data_folder was briefly used during the development of 2.0.
	 * @deprecated
	 */
	static function GetDataFolder($addon_key){
		global $config;
		if( isset($config['addons'][$addon_key]['data_folder']) ){
			return $config['addons'][$addon_key]['data_folder'];
		}
		return $addon_key;
	}

	/**
	 * Get the addon_key of an addon by it's id
	 * @static
	 * @param int $addon_id
	 * @return mixed Returns addon_key string if found, false otherwise
	 *
	 */
	static function AddonFromId($addon_id){
		global $config;
		if( empty($config['addons']) ){
			return false;
		}
		foreach($config['addons'] as $addon_key => $addon_info){
			if( isset($addon_info['id']) && $addon_info['id'] == $addon_id ){
				return $addon_key;
			}
		}
		return false;
	}
}


