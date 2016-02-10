<?php


namespace gp\tool{

	defined('is_running') or die('Not an entry point...');


	class Plugins{


		/**
		 * Holds the configuration values of the current plugin if there is an active plugin
		 *
		 */
		public static $current = false;

		private static $stack = array();


		/**
		 * Aliases of gpPlugin_incl()
		 * @deprecated 3.5.3
		 */
		public static function incl($file){
			return gpPlugin_incl($file);
		}

		public static function inc($file){
			return gpPlugin_incl($file);
		}


		/**
		 * Add a css file to the page
		 * @since 4.0
		 * @param string $file The path of the css file relative to the addon folder
		 * @param bool $combine Set to false to keep the file from being combined with other css files
		 */
		public static function css($file, $combine = true){
			global $page;

			$file 					= \gp\tool::WinPath( $file );

			if( $combine ){
				$page->css_admin[] 	= self::$current['code_folder_part'].'/'.ltrim($file,'/');
				return self::$current['code_folder_part'].'/'.ltrim($file,'/');
			}


			//less file
			$ext = \gp\tool::Ext($file);
			if( $ext === 'less' || $ext === 'scss' ){
				$full_path			= self::$current['code_folder_full'].'/'.ltrim($file,'/');
				$path				= \gp\tool\Output\Css::Cache($full_path,$ext);
			}else{
				$path				= self::$current['code_folder_part'].'/'.ltrim($file,'/');
			}

			if( $path !== false ){
				$page->head			.= "\n".'<link rel="stylesheet" type="text/css" href="'.\gp\tool::GetDir($path).'"/>';
			}

			return $path;
		}


		/**
		 * Add a js file to the page
		 * @since 4.0
		 * @param string $file The path of the js file relative to the addon folder
		 * @param bool $combine Set to false to keep the file from being combined with other js files
		 */
		public static function js($file, $combine = true ){
			global $page;

			$file = \gp\tool::WinPath( $file );

			if( $combine ){
				$page->head_js[] = self::$current['code_folder_part'].'/'.ltrim($file,'/');
			}else{
				$url = self::$current['code_folder_rel'].'/'.ltrim($file,'/');
				$page->head .= "\n".'<script type="text/javascript" src="'.$url.'"></script>';
			}
		}

		public static function GetDir($path='',$ampersands = false){
			$path = self::$current['code_folder_part'].'/'.ltrim($path,'/');
			return \gp\tool::GetDir($path, $ampersands);
		}

		/**
		 * Similar to php's register_shutdown_function()
		 * Will keep track of the active plugin and make sure global path variables are set properly before callting $function
		 * Example: \gp\tool\Plugins::RegisterShutdown(array('class_name','method_name'));  or  \gp\tool\Plugins::RegisterShutdown(array('class_name','method_name'),'argument1'....);
		 *
		 */
		public static function RegisterShutdown(){
			global $addonFolderName;
			if( gp_safe_mode ) return;
			$args = func_get_args();
			register_shutdown_function(array('\\gp\\tool\\Plugins','ShutdownFunction'),$addonFolderName,$args);
		}

		/**
		 * Handle functions passed to \gp\tool\Plugins::RegisterShutdown()
		 * This function should not be called directly.
		 */
		public static function ShutdownFunction($addonFolderName,$args){

			if( gp_safe_mode ) return;

			if( !is_array($args) || count($args) < 1 ){
				return false;
			}

			self::SetDataFolder($addonFolderName);

			$function = array_shift($args);

			if( count($args) > 0 ){
				call_user_func_array( $function , $args );
			}else{
				call_user_func( $function  );
			}

			self::ClearDataFolder();
		}


		/**
		 * Similar to wordpress apply_filters_ref_array()
		 *
		 */
		public static function Filter($hook, $args = array() ){
			global $gp_hooks;

			if( !self::HasHook($hook) ){
				if( isset($args[0]) ){
					return $args[0];
				}
				return false;
			}

			foreach($gp_hooks[$hook] as $hook_info){
				$args[0] = self::ExecHook($hook,$hook_info,$args);
			}

			if( isset($args[0]) ){
				return $args[0];
			}
			return false;
		}


		public static function OneFilter( $hook, $args=array(), $addon = false ){
			global $gp_hooks;

			if( !self::HasHook($hook) ){
				return false;
			}

			if( $addon === false ){
				$hook_info = end($gp_hooks[$hook]);
				return self::ExecHook($hook,$hook_info,$args);
			}

			foreach($gp_hooks[$hook] as $addon_key => $hook_info){
				if( $addon_key === $addon ){
					return self::ExecHook($hook,$hook_info,$args);
				}
			}

			return false;
		}

		public static function Action($hook, $args = array() ){
			global $gp_hooks;

			if( !self::HasHook($hook) ){
				return;
			}

			foreach($gp_hooks[$hook] as $hook_info){
				self::ExecHook($hook,$hook_info,$args);
			}
		}

		/**
		 * Check to see if there area any hooks matching $hook
		 * @param string $hook The name of the hook
		 * @return bool
		 *
		 */
		public static function HasHook($hook){
			global $gp_hooks;
			if( empty($gp_hooks) || empty($gp_hooks[$hook]) ){
				return false;
			}
			return true;
		}

		public static function ArgReturn($args){
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
		public static function ExecHook($hook,$info,$args = array()){
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

			$args = \gp\tool\Output::ExecInfo($info,$args);

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
		public static function SetDataFolder($addon_key){
			global $dataDir, $config;
			global $addonDataFolder,$addonCodeFolder; //deprecated
			global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id,$addon_current_version;

			if( !isset($config['addons'][$addon_key]) ){
				return;
			}

			self::StackPush();
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
		public static function GetAddonConfig($addon_key){
			global $config, $dataDir;

			if( !array_key_exists($addon_key,$config['addons']) ){
				return false;
			}


			$addon_config = $config['addons'][$addon_key];
			if( !is_array($addon_config) ){
				trigger_error('Corrupted configuration for addon: '.$addon_key); //.pre($config['addons']));
				return false;
			}
			$addon_config += array( 'version'=>false, 'id'=>false, 'data_folder'=>$addon_key, 'order'=>false, 'code_folder_part'=>'/data/_addoncode/'.$addon_key,'name'=>$addon_key );

			//data folder
			$addon_config['data_folder_part'] = '/data/_addondata/'.$addon_config['data_folder'];
			$addon_config['data_folder_full'] = $dataDir.$addon_config['data_folder_part'];
			$addon_config['data_folder_rel'] = \gp\tool::GetDir($addon_config['data_folder_part']);


			// Code folder
			//$addon_config['code_folder_part'] = $addon_config['code_folder'].'/'.$addon_key;
			$addon_config['code_folder_full'] = $dataDir.$addon_config['code_folder_part'];
			$addon_config['code_folder_rel'] = \gp\tool::GetDir($addon_config['code_folder_part']);

			return $addon_config;
		}

		/**
		 * If there's a current addon folder or addon id, push it onto the stack
		 *
		 */
		public static function StackPush(){
			global $addonFolderName, $addon_current_id;

			if( !$addon_current_id && !$addonFolderName ){
				return;
			}
			self::$stack[] = array('folder'=>$addonFolderName,'id'=>$addon_current_id);
		}


		/**
		 * Reset global path variables
		 */
		public static function ClearDataFolder(){
			global $addonDataFolder,$addonCodeFolder; //deprecated
			global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id,$addon_current_version;


			self::$current		= array();
			$addonFolderName	= false;
			$addonDataFolder	= false;
			$addonCodeFolder	= false;
			$addonRelativeCode	= $addonRelativeData = $addonPathData = $addonPathCode = $addon_current_id = $addon_current_version = false;

			//Make the most recent addon folder or addon id in the stack the current addon
			if( count(self::$stack) > 0 ){
				$info = array_pop(self::$stack);
				if( $info['folder'] ){
					self::SetDataFolder($info['folder']);
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
		public static function AddonFromId($addon_id){
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
		public static function GetConfig(){

			$file = self::$current['data_folder_full'].'/_config.php';
			return \gp\tool\Files::Get($file,'config');
		}


		/**
		 * Get plugin configuration values
		 * @since 3.6
		 *
		 */
		function SaveConfig($config){

			$file = self::$current['data_folder_full'].'/_config.php';

			if( \gp\tool\Files::SaveData($file,'config',$config) ){
				return true;
			}
			return false;
		}

	}
}

namespace{


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

		return IncludeScript($addonPathCode.'/'.$file);  // return added in 5.0b3
	}


	class gpPlugin extends \gp\tool\Plugins{}

}


