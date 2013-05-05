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

	IncludeScript($addonPathCode.'/'.$file);
}


class gpPlugin{


	/**
	 * Holds the configuration values of the current plugin if there is an active plugin
	 *
	 */
	static $current = false;

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
	 * Add a css file to the page
	 * @since gpEasy 4.0
	 * @param string $file The path of the css file relative to the addon folder
	 *
	 */
	function css($file){
		global $page;
		$file = common::WinPath( $file );
		$page->css_admin[] = self::$current['code_folder_part'].'/'.ltrim($file,'/');
	}


	/**
	 * Add a js file to the page
	 * @since gpEasy 4.0
	 * @param string $file The path of the js file relative to the addon folder
	 *
	 */
	function js($file){
		global $page;
		$file = common::WinPath( $file );
		$page->head_js[] = self::$current['code_folder_part'].'/'.ltrim($file,'/');
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


	static function OneFilter( $hook, $args=array(), $addon = false ){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			return false;
		}

		if( $addon === false ){
			$hook_info = end($config['hooks'][$hook]);
			return gpPlugin::ExecHook($hook,$hook_info,$args);
		}

		foreach($config['hooks'][$hook] as $addon_key => $hook_info){
			if( $addon_key === $addon ){
				return gpPlugin::ExecHook($hook,$hook_info,$args);
			}
		}

		return false;
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
		self::$current = self::GetAddonConfig($addon_key);

		$addonFolderName					= $addon_key;
		$addon_current_id					= self::$current['id'];
		$addon_current_version				= self::$current['version'];
		$addonPathCode = $addonCodeFolder 	= self::$current['code_folder_full'];
		$addonPathData = $addonDataFolder	= self::$current['data_folder_full'];
		$addonRelativeCode					= self::$current['code_folder_rel'];
		$addonRelativeData					= self::$current['data_folder_rel'];
	}


	/**
	 * Return settings of addon defined by $addon_key
	 *
	 */
	static function GetAddonConfig($addon_key){
		global $config, $dataDir;

		if( !array_key_exists($addon_key,$config['addons']) ){
			return false;
		}


		$addon_config = $config['addons'][$addon_key];
		if( !is_array($addon_config) ){
			trigger_error('Corrupted configuration for addon: '.$addon_key); //.pre($config['addons']));
			return false;
		}
		$addon_config += array( 'version'=>false, 'id'=>false, 'data_folder'=>$addon_key, 'order'=>false, 'code_folder_part'=>'/data/_addoncode/'.$addon_key );

		//data folder
		$addon_config['data_folder_part'] = '/data/_addondata/'.$addon_config['data_folder'];
		$addon_config['data_folder_full'] = $dataDir.$addon_config['data_folder_part'];
		$addon_config['data_folder_rel'] = common::GetDir($addon_config['data_folder_part']);


		// Code folder
		//$addon_config['code_folder_part'] = $addon_config['code_folder'].'/'.$addon_key;
		$addon_config['code_folder_full'] = $dataDir.$addon_config['code_folder_part'];
		$addon_config['code_folder_rel'] = common::GetDir($addon_config['code_folder_part']);

		return $addon_config;
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


	/**
	 * Get plugin configuration values
	 * @since 3.6
	 *
	 */
	static function GetConfig(){
		global $addonPathData;
		$config = array();
		$file = $addonPathData.'/_config.php';
		if( file_exists($file) ){
			include($file);
		}
		return $config;
	}


	/**
	 * Get plugin configuration values
	 * @since 3.6
	 *
	 */
	function SaveConfig($config){
		global $addonPathData;

		$file = $addonPathData.'/_config.php';

		if( gpFiles::SaveArray($file,'config',$config) ){
			return true;
		}
		return false;
	}

}


