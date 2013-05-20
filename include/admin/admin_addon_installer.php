<?php
defined('is_running') or die('Not an entry point...');

/**
 * Things that could be done previous to installer
 *	- Install_CheckIni() (warning about installing a lesser version)
 *
 */
class admin_addon_installer extends admin_addons_tool{

	//configuration options
	var $source = '';
	var $can_install_links = true;
	var $config_index = 'addons';
	var $code_folder_name = '_addoncode';

	var $new_layout = array();
	var $default_layout = false;


	//remote install
	var $remote_install = false;
	var $type;
	var $id;
	var $order;


	//uninstall
	var $rm_folders = true;


	//used internally
	var $addon_folder;
	var $addon_folder_rel = false;
	var $dest = '';
	var $dest_name;
	var $trash_path;
	var $config_cache;
	var $layouts_cache;
	var $ini_contents;
	var $ini_text = '';
	var $upgrade_key = false;
	var $has_hooks = false;
	var $display_name = '';

	var $messages = array();


	function __construct(){}


	/**
	 * Install an addon
	 * $this->source should already be set
	 *
	 */
	function Install(){
		global $langmessage;

		$success = $this->InstallSteps();

		if( !$this->remote_install && !is_dir($this->source) ){
			$this->message($langmessage['OOPS'].' (Source not found)');
			return false;
		}

		if( $success ){
			$this->message( sprintf($langmessage['installed'], $this->display_name ) );
		}else{
			$this->Failed();
		}

		$this->CleanInstallFolder();

		return $success;
	}



	/**
	 * Get and install addon from a remote source
	 * @param string $type Type of addon (plugin or theme)
	 * @param int $id Addon id
	 * @param int $order Purchase order id
	 *
	 */
	function InstallRemote( $type, $id, $order = false ){

		$this->remote_install = true;
		$this->type = $type;
		$this->id = $id;
		$this->order = $order;

		return $this->Install();
	}

	function OutputMessages(){
		foreach($this->messages as $msg){
			msg($msg);
		}
	}


	/**
	 * Remove an addon from the site configuration
	 * Delete code folders if needed
	 *
	 */
	function Uninstall( $addon ){
		global $config, $langmessage, $gp_titles, $gp_menu, $gp_index;

		$this->GetAddonData();

		$addon_config = gpPlugin::GetAddonConfig($addon);
		if( !$addon_config ){
			$this->message($langmessage['OOPS'].' (Already uninstalled)');
			return;
		}


		$order = false;
		if( isset($config['addons'][$addon]['order']) ){
			$order = $config['addons'][$addon]['order'];
		}


		//tracking
		$history = array();
		$history['name'] = $config['addons'][$addon]['name'];
		$history['action'] = 'uninstalled';
		if( isset($config['addons'][$addon]['id']) ){
			$history['id'] = $config['addons'][$addon]['id'];
		}

		unset($config['addons'][$addon]);


		//remove links
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$addon);
		$this->RemoveFromHandlers($installedGadgets);


		//remove from gp_index, gp_menu
		$installedLinks = $this->GetInstalledComponents($gp_titles,$addon);
		foreach($installedLinks as $index){
			if( isset($gp_menu[$index]) ){
				unset($gp_menu[$index]);
			}
			$title = common::IndexToTitle($index);
			if( $title ){
				unset($gp_index[$title]);
			}
		}

		$this->RemoveFromConfig($config['gadgets'],$addon);
		$this->RemoveFromConfig($config['admin_links'],$addon);
		$this->RemoveFromConfig($gp_titles,$addon);
		$this->CleanHooks($addon);

		if( !admin_tools::SaveAllConfig() ){
			$this->message($langmessage['OOPS']);
			return false;
		}


		//Delete the code & code folders
		if( $this->rm_folders ){

			//only delete code if remote installation
			if( isset($addon_config['remote_install']) && $addon_config['remote_install'] ){

				$installFolder = $addon_config['code_folder_full'];
				if( file_exists($installFolder) ){
					gpFiles::RmAll($installFolder);
				}

			}

			$dataFolder = $addon_config['data_folder_full'];
			if( file_exists($dataFolder) ){
				gpFiles::RmAll($dataFolder);
			}
		}

		//Record the history
		$history['time'] = time();
		$this->addonHistory[] = $history;
		$this->SaveAddonData();
		if( $order ){
			$img_path = common::IdUrl('ci');
			common::IdReq($img_path);
		}


		$this->message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Run through the installation process
	 *
	 */
	function InstallSteps(){
		global $dataDir;

		$this->GetAddonData();			// addonHistory
		$this->Init_PT();				// $this->config


		//get from remote
		if( $this->remote_install && !$this->GetRemote() ){
			return false;
		}

		//check ini contents
		$this->display_name = basename($this->source);
		if( !$this->GetINI($this->source,$error) ){

			//local themes don't need addon.ini files
			if( empty($this->new_layout) ){
				$this->message( $error );
				return false;
			}
		}


		// upgrade/destination
		$this->upgrade_key = $this->config_key = admin_addons_tool::UpgradePath($this->ini_contents,$this->config_index);
		if( $this->remote_install ){
			if( $this->config_key ){
				$this->dest = $this->addon_folder.'/'.$this->config_key;
			}else{
				$this->dest = $this->TempFile();
			}
		}else{
			$this->dest = $this->source;
		}
		$this->dest_name = basename($this->dest);

		if( !$this->config_key ){
			$this->config_key = $this->dest_name;
		}


		//the data folder will not always be the same as the addon folder
		if( isset($this->config[$this->config_key]['data_folder']) ){
			$this->data_folder = $this->config[$this->config_key]['data_folder'];
		}elseif( $this->upgrade_key && file_exists( $dataDir.'/data/_addondata/'.$this->upgrade_key) ){
			$this->data_folder = $this->upgrade_key;
		}else{
			$this->data_folder = $this->dest_name;
		}

		$this->IniContents();

		if( !$this->PrepConfig() ){
			return false;
		}

		if( !$this->CheckFile() ){
			return false;
		}

		//hooks
		if( !$this->Hooks() ){
			return false;
		}

		//layout
		if( !$this->Layout() ){
			return false;
		}

		//move new addon folder into place
		if( !$this->FinalizeFolder() ){
			return false;
		}

		if( !$this->FinalizeConfig() ){
			return false;
		}


		// Save
		if( !admin_tools::SaveAllConfig() ){
			$this->message($langmessage['OOPS'].' (Configuration not saved)');
			return false;
		}

		if( $this->order ){
			$img_path = common::IdUrl('ci');
			common::IdReq($img_path);
		}

		$this->UpdateHistory();

		return true;

	}


	/**
	 * Prepare $this->config and make sure $this->addon_folder exists
	 *
	 */
	function Init_PT(){
		global $config, $dataDir, $gpLayouts;

		if( !isset($config[$this->config_index]) ){
			$config[$this->config_index] = array();
		}

		$this->config =& $config[$this->config_index];
		$this->config_cache = $config;
		$this->layouts_cache = $gpLayouts;


		if( !$this->addon_folder_rel ){
			if( $this->remote_install ){
				$this->addon_folder_rel = '/data/'.$this->code_folder_name;
			}else{
				$this->addon_folder_rel = '/'.basename( dirname($this->source) );
			}
		}
		$this->addon_folder = $dataDir.$this->addon_folder_rel;

		gpFiles::CheckDir($this->addon_folder);
	}


	/**
	 * Prepare the configuration array for installation
	 *
	 */
	function PrepConfig(){

		if( !$this->has_hooks ){
			return true;
		}


		//make sure we have an array
		if( !isset($this->config[$this->config_key]) ){
			$this->config[$this->config_key] = array();
		}elseif( !is_array($this->config[$this->config_key]) ){
			$this->message('$this->config[addon] is not an array');
			return false;
		}

		return true;
	}


	/**
	 * Get the Ini contents and check values
	 * @return bool
	 *
	 */
	function GetINI($ini_dir,&$error){
		global $langmessage;

		$error = false;

		$ini_file = $ini_dir.'/Addon.ini';

		if( !file_exists($ini_file) ){
			$error = sprintf($langmessage['File_Not_Found'],' <em>'.$ini_file.'</em>');
			return false;
		}


		$this->ini_text = file_get_contents($ini_file);
		$this->ini_contents = gp_ini::ParseString($this->ini_text);

		if( !$this->ini_contents ){
			$error = $langmessage['Ini_Error'].' '.$langmessage['Ini_Submit_Bug'];
			return false;
		}

		if( !isset($this->ini_contents['Addon_Name']) ){
			$error = $langmessage['Ini_No_Name'].' '.$langmessage['Ini_Submit_Bug'];
			return false;
		}

		if( isset($this->ini_contents['Addon_Unique_ID']) && !is_numeric($this->ini_contents['Addon_Unique_ID']) ){
			$error = 'Invalid Unique ID';
			return false;
		}

		// Check Versions
		if( !empty($this->ini_contents['min_gpeasy_version']) && version_compare($this->ini_contents['min_gpeasy_version'], gpversion,'>') ){
			$error = sprintf($langmessage['min_version'],$this->ini_contents['min_gpeasy_version']).' '.$langmessage['min_version_upgrade'];
			return false;
		}


		$this->HasHooks();
		$this->display_name = $this->ini_contents['Addon_Name'];

		return true;
	}


	/**
	 * Does it have addon hooks?
	 *
	 */
	function HasHooks(){

		foreach($this->ini_contents as $key => $value){
			if( is_array($value) ){
				$this->has_hooks = true;
				return;
			}
		}
	}





	/**
	 * Parse the ini a second time with variables
	 *
	 */
	function IniContents(){
		global $dataDir, $dirPrefix;
		$folder = basename($this->dest);

		$variables = array(
					'{$addon}'				=> $folder,
					'{$plugin}'				=> $folder,
					'{$dataDir}'			=> $dataDir,
					'{$dirPrefix}'			=> $dirPrefix,
					'{$addonRelativeData}'	=> common::GetDir('/data/_addondata/'.$this->data_folder),
					'{$addonRelativeCode}'	=> common::GetDir($this->addon_folder_rel.'/'.$folder),
					);

		$this->ini_contents = gp_ini::ParseString($this->ini_text,$variables);
	}


	/**
	 * Add hooks to configuration
	 *
	 */
	function Hooks(){
		global $langmessage, $config;

		if( !$this->has_hooks ){
			return true;
		}

		if( !$this->can_install_links && !$this->upgrade_key ){
			return true;
		}

		//needs to be before other gadget functions
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$this->config_key);

		$gadgets = $this->ExtractFromInstall($this->ini_contents,'Gadget:');
		$gadgets = $this->CleanGadgets($gadgets);
		$this->PurgeExisting($config['gadgets'],$gadgets);
		$this->AddToConfig($config['gadgets'],$gadgets);

		//remove gadgets that were installed but are no longer part of package
		$gadgetNames = array_keys($gadgets);
		$toRemove = array_diff($installedGadgets,$gadgetNames);
		$this->RemoveFromHandlers($toRemove);

		//add new gadgets to GetAllGadgets handler
		$toAdd = array_diff($gadgetNames,$installedGadgets);
		$this->AddToHandlers($toAdd);


		//admin links
		$Admin_Links = $this->ExtractFromInstall($this->ini_contents,'Admin_Link:');
		$Admin_Links = $this->CleanLinks($Admin_Links,'Admin_');
		$this->PurgeExisting($config['admin_links'],$Admin_Links);
		$this->AddToConfig($config['admin_links'],$Admin_Links);



		//special links
		$Special_Links = $this->ExtractFromInstall($this->ini_contents,'Special_Link:');
		$Special_Links = $this->CleanLinks($Special_Links,'Special_','special');
		$this->UpdateSpecialLinks($Special_Links);


		//generic hooks
		$this->AddHooks();

		return true;
	}


	/**
	 * Create a layout
	 *
	 */
	function Layout(){
		global $gpLayouts, $langmessage, $config, $page;

		if( empty($this->new_layout) ){
			return true;
		}


		if( $this->has_hooks ){
			$this->new_layout['addon_key'] = $this->config_key;
		}
		if( isset($this->ini_contents['id']) && is_numeric($this->ini_contents['id']) ){
			$this->new_layout['addon_id'] = $this->ini_contents['id']['id'];
		}

		$temp = $this->TempFile();
		$layout_id = basename($temp);
		$gpLayouts[$layout_id] = $this->new_layout;

		if( $this->default_layout ){
			$config['gpLayout'] = $layout_id;
		}


		return true;
	}


	/**
	 * Rename the temp folder to the dest folder
	 *
	 */
	function FinalizeFolder(){

		if( !$this->remote_install ){
			return true;
		}

		if( file_exists($this->dest) ){
			$this->trash_path = $this->TempFile();
			if( !@rename($this->dest,$this->trash_path) ){
				$this->message('Existing destination not renamed');
				return false;
			}
		}

		//rename temp folder
		if( rename($this->source,$this->dest) ){
			return true;
		}

		$this->message('Couldn\'t rename to destination');
		return false;
	}


	/**
	 * Finalize the configuration
	 *
	 *
	 */
	function FinalizeConfig(){
		global $langmessage, $config;

		if( !$this->has_hooks ){
			return true;
		}

		//code folder
		$this->config[$this->config_key]['code_folder_part'] = $this->addon_folder_rel.'/'.$this->dest_name;
		$this->config[$this->config_key]['data_folder'] = $this->data_folder;


		//general configuration
		$this->UpdateConfigInfo('Addon_Name','name');
		$this->UpdateConfigInfo('Addon_Version','version');
		$this->UpdateConfigInfo('Addon_Unique_ID','id');


		//remote
		unset($this->config[$this->config_key]['remote_install']);
		if( $this->remote_install ){
			$this->config[$this->config_key]['remote_install'] = true;
		}

		//layout
		if( count($this->new_layout) ){
			$this->config[$this->config_key]['is_theme'] = true;
		}


		//proof of purchase
		if( isset($this->ini_contents['Proof of Purchase']) && isset($this->ini_contents['Proof of Purchase']['order']) ){
			$this->order = $this->ini_contents['Proof of Purchase']['order'];
			$this->config[$this->config_key]['order'] = $this->order;
		}else{
			// don't delete any purchase id's
			// unset($this->config[$this->config_key]['order']);
		}


		if( $this->can_install_links ){
			$this->UpdateConfigInfo('editable_text','editable_text');
			$this->UpdateConfigInfo('html_head','html_head');
		}

		return true;
	}


	/**
	 *
	 *
	 */
	function UpdateHistory(){

		if( !$this->has_hooks ){
			return;
		}

		// History
		$history = array();
		$history['name'] = $this->config[$this->config_key]['name'];
		$history['action'] = 'installed';
		if( isset($this->config[$this->config_key]['id']) ){
			$history['id'] = $this->config[$this->config_key]['id'];
		}
		$history['time'] = time();

		$this->addonHistory[] = $history;
		$this->SaveAddonData();

	}


	/**
	 * Run the Install_Check.php file if it exists
	 * @return bool
	 *
	 */
	function CheckFile(){
		$check_file = $this->source.'/Install_Check.php';
		if( !file_exists($check_file) ){
			return true;
		}
		$success = true;

		ob_start();
		include($check_file);
		if( function_exists('Install_Check') ){
			$success = Install_Check();
		}
		$msg = ob_get_clean();
		if( !empty($msg) ){
			$this->message($msg);
		}

		return $success;
	}


	/**
	 * Return the path of a non-existant file
	 * Make sure the name won't conflict with names of addons or layouts
	 *
	 */
	function TempFile($type=''){
		global $config, $gpLayouts, $dataDir;

		do{
			$file = common::RandomString(7,false);
			$full_dest = $this->addon_folder.'/'.$file.$type;
			$data_dest = $dataDir.'/data/_addondata/'.$file.$type;

		}while(
			is_numeric($file)
			|| array_key_exists($file, $config['addons'])
			|| array_key_exists($file, $config['themes'])
			|| array_key_exists($file, $gpLayouts)
			|| file_exists($full_dest)
			|| file_exists($data_dest)
			);

		return $full_dest;
	}



	/**
	 * Recursive copy folder
	 *
	 */
	function CopyAddonDir($fromDir,$toDir){

		if( !gpFiles::CheckDir($toDir) ){
			return 'Copy failed: '.$fromDir.' to '.$toDir;
		}

		$files = scandir($fromDir);
		if( $files === false ){
			return 'scandir failed: '.$fromDir;
		}


		foreach($files as $file){

			if( strpos($file,'.') === 0){
				continue;
			}

			$fullFrom = $fromDir.'/'.$file;
			$fullTo = $toDir.'/'.$file;


			//directories
			if( is_dir($fullFrom) ){
				$result = self::CopyAddonDir($fullFrom,$fullTo);
				if( $result !== true ){
					return $result;
				}
				continue;
			}

			//files
			//If the destination file already exists, it will be overwritten.
			if( !copy($fullFrom,$fullTo) ){
				return 'Copy failed: '.$fullFrom.' to '.$fullTo.' (2)';
			}
		}

		return true;
	}


	/**
	 * Undo changes
	 *
	 */
	function Failed(){
		global $config;

		if( isset($this->config_cache) ){
			$config = $this->config_cache;
		}

		if( isset($this->trash_path) && file_exists($this->trash_path) ){
			@rename($this->trash_path,$this->dest);
		}

	}

	function message($message){
		$this->messages[] = $message;
	}

	/**
	 * Get a stored order/purchase id
	 * @param int addon id
	 *
	 */
	function GetOrder($id){
		if( !is_numeric($id) ){
			return;
		}

		foreach( $this->config as $folder => $info ){
			if( !empty($info['id'])
				&& $id == $info['id']
				&& !empty($info['order'])
				){
					return $info['order'];
			}
		}
	}


	/**
	 * Get the remote package
	 *
	 */
	function GetRemote(){
		global $langmessage;
		includeFile('tool/RemoteGet.php');


		// check values
		if( empty($this->type)
			|| empty($this->id)
			|| !is_numeric($this->id)
			){
				$this->message($langmessage['OOPS'].' (Invalid Request)');
				return false;
		}

		if( $this->type != 'plugin' && $this->type != 'theme' ){
			$this->message($langmessage['OOPS'].' (Invalid Type)');
			return false;
		}

		if( !admin_tools::CanRemoteInstall() ){
			$this->message($langmessage['OOPS'].' (Can\'t remote install)');
			return false;
		}


		// download
		$download_link = addon_browse_path;
		if( $this->type == 'theme' ){
			$download_link .= '/Themes';
		}else{
			$download_link .= '/Plugins';
		}
		$download_link .= '?cmd=install&id='.rawurlencode($this->id);


		// purchase order id
		if( !$this->order ){
			$this->order = $this->GetOrder($this->id);
		}
		if( $this->order ){
			$download_link .= '&order='.rawurlencode($this->order);
		}


		// get package from remote
		$full_result = gpRemoteGet::Get($download_link);
		if( (int)$full_result['response']['code'] < 200 && (int)$full_result['response']['code'] >= 300 ){
			$this->message( $langmessage['download_failed'] .' (1)');
			return false;
		}

		// download failed and a message was sent
		if( isset($full_result['headers']['x-error']) ){
			$this->message( htmlspecialchars($full_result['headers']['x-error']) );
			$this->message( sprintf($langmessage['download_failed_xerror'],'href="'.$this->DetailUrl($_POST).'" data-cmd="remote"') );
			return false;
		}

		$result = $full_result['body'];
		$md5 =& $full_result['headers']['x-md5'];

		//check md5
		$package_md5 = md5($result);
		if( $package_md5 != $md5 ){
			$this->message( $langmessage['download_failed_md5'].' <br/> (Package Checksum '.$package_md5.' != Expected Checksum '.$md5.')' );
			return false;
		}

		//save contents
		$tempfile = $this->TempFile('.zip');
		if( !gpFiles::Save($tempfile,$result) ){
			$this->message( $langmessage['download_failed'].' (Package not saved)' );
			return false;
		}

		$this->source = $this->TempFile();

		$success = $this->ExtractArchive($this->source,$tempfile);

		unlink($tempfile);

		return $success;
	}



	/**
	 * Write Archive
	 *
	 */
	function ExtractArchive($dir,$archive_path){
		global $langmessage;

		// Unzip uses a lot of memory, but not this much hopefully
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip($archive_path);
		$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);


		if( !gpFiles::CheckDir($dir) ){
			$this->message( sprintf($langmessage['COULD_NOT_SAVE'],$folder) );
			return false;
		}

		//get archive root
		$archive_root = false;
		foreach( $archive_files as $file ){
			if( strpos($file['filename'],'/Addon.ini') !== false ){
				$root = dirname($file['filename']);
				if( !$archive_root || ( strlen($root) < strlen($archive_root) ) ){
					$archive_root = $root;
				}
			}
		}
		$archive_root_len = strlen($archive_root);


		foreach($archive_files as $file_info){

			$filename = $file_info['filename'];

			if( $archive_root ){
				if( strpos($filename,$archive_root) !== 0 ){
					continue;
				}

				$filename = substr($filename,$archive_root_len);
			}

			$filename = '/'.trim($filename,'/');
			$full_path = $dir.'/'.$filename;

			if( $file_info['folder'] ){
				$folder = $full_path;
			}else{
				$folder = dirname($full_path);
			}

			if( !gpFiles::CheckDir($folder) ){
				$this->message( sprintf($langmessage['COULD_NOT_SAVE'],$folder) );
				return false;
			}
			if( $file_info['folder'] ){
				continue;
			}
			if( !gpFiles::Save($full_path,$file_info['content']) ){
				$this->message( sprintf($langmessage['COULD_NOT_SAVE'],$full_path) );
				return false;
			}
		}

		return true;
	}



	/**
	 * Set config value based on ini setting
	 *
	 */
	function UpdateConfigInfo($ini_var,$config_var){

		if( isset($this->ini_contents[$ini_var]) ){
			$this->config[$this->config_key][$config_var] = $this->ini_contents[$ini_var];
		}elseif( isset($this->config[$this->config_key][$config_var]) ){
			unset($this->config[$this->config_key][$config_var]);
		}
	}



	/**
	 * Add an addon's special links to the configuration
	 *
	 */
	function UpdateSpecialLinks($Special_Links){
		global $gp_index, $gp_titles, $gp_menu, $langmessage;

		$lower_links = array_change_key_case($Special_Links,CASE_LOWER);

		//purge links no longer defined ... similar to PurgeExisting()
		foreach($gp_index as $linkName => $index){

			$linkInfo = $gp_titles[$index];
			if( !isset($linkInfo['addon']) ){
				continue;
			}

			if( $linkInfo['addon'] !== $this->config_key ){
				continue;
			}

			if( isset($lower_links[$index]) ){
				continue;
			}

			unset($gp_index[$linkName]);
			unset($gp_titles[$index]);
			if( isset($gp_menu[$index]) ){
				unset($gp_menu[$index]);
			}
		}


		//prepare a list with all titles converted to lower case
		$lower_titles = array_keys($gp_index);
		$lower_titles = array_combine($lower_titles, $lower_titles);
		$lower_titles = array_change_key_case($lower_titles, CASE_LOWER);


		//add new links ... similar to AddToConfig()
		foreach($Special_Links as $new_title => $linkInfo){

			$index = strtolower($new_title);
			$title = common::IndexToTitle($index);

			//if the title already exists, see if we need to update it
			if( $title ){
				$addlink = true;
				$curr_info = $gp_titles[$index];

				if( !isset($curr_info['addon']) || $this->config_key === false ){
					$addlink = false;
				}elseif( $curr_info['addon'] != $this->config_key ){
					$addlink = false;
				}

				if( !$addlink ){
					$this->message( sprintf($langmessage['addon_key_defined'],' <em>Special_Link: '.$new_title.'</em>') );
					continue;
				}

				//this will overwrite things like label which are at times editable by users
				//$AddTo[$new_title] = $linkInfo + $AddTo[$new_title];

			// if it doesn't exist, just add it
			}else{

				// we don't need the Special_ prefix, but we don't want duplicates
				$temp = $new_title = substr($new_title,8);
				$temp_lower = $new_lower = strtolower($new_title);
				$i = 1;
				while( isset($lower_titles[$new_lower]) ){
					$new_lower = $temp_lower.'_'.$i;
					$new_title = $temp.'_'.$i;
					$i++;
				}

				$gp_index[$new_title] = $index;
				$gp_titles[$index] = $linkInfo;
			}

			$this->UpdateLinkInfo($gp_titles[$index],$linkInfo);
		}
	}



	function AddHooks(){

		$installed = array();
		foreach($this->ini_contents as $hook => $hook_args){
			if( !is_array($hook_args) ){
				continue;
			}

			if( strpos($hook,'Gadget:') === 0
				|| strpos($hook,'Admin_Link:') === 0
				|| strpos($hook,'Special_Link:') === 0
				){
					continue;
			}

			if( $this->AddHook($hook,$hook_args) ){
				$installed[$hook] = $hook;
			}
		}

		$this->CleanHooks($this->config_key,$installed);
	}

	function AddHook($hook,$hook_args){
		global $config;

		$add = array();
		$this->UpdateLinkInfo($add,$hook_args);
		$config['hooks'][$hook][$this->config_key] = $add;

		return true;
	}


	//extract the configuration type (extractArg) from $Install
	function ExtractFromInstall(&$Install,$extractArg){
		if( !is_array($Install) || (count($Install) <= 0) ){
			return array();
		}

		$extracted = array();
		$removeLength = strlen($extractArg);

		foreach($Install as $InstallArg => $ArgInfo){
			if( strpos($InstallArg,$extractArg) !== 0 ){
				continue;
			}
			$extractName = substr($InstallArg,$removeLength);
			if( !$this->CheckName($extractName) ){
				continue;
			}

			$extracted[$extractName] = $ArgInfo;
		}
		return $extracted;
	}


	/*
	 * Add to $AddTo
	 * 	Don't add elements already defined by gpEasy or other addons
	 *
	 */
	function AddToConfig(&$AddTo,$New_Config){
		global $langmessage;

		if( !is_array($New_Config) || (count($New_Config) <= 0) ){
			return;
		}

		$lower_add_to = array_change_key_case($AddTo,CASE_LOWER);

		foreach($New_Config as $Config_Key => $linkInfo){

			$lower_key = strtolower($Config_Key);

			if( isset($lower_add_to[$lower_key]) ){
				$addlink = true;

				if( !isset($lower_add_to[$lower_key]['addon']) || $this->config_key === false ){
					$addlink = false;
				}elseif( $lower_add_to[$lower_key]['addon'] != $this->config_key ){
					$addlink = false;
				}

				if( !$addlink ){
					$this->message( sprintf($langmessage['addon_key_defined'],' <em>'.$Config_Key.'</em>') );
					continue;
				}

				//this will overwrite things like label which are at times editable by users
				//$AddTo[$Config_Key] = $linkInfo + $AddTo[$Config_Key];

			}else{
				$AddTo[$Config_Key] = $linkInfo;
			}

			$this->UpdateLinkInfo($AddTo[$Config_Key],$linkInfo);
		}
	}



	function UpdateLinkInfo(&$link_array,$new_info){

		$link_array['addon'] = $this->config_key;

		unset($link_array['script'], $link_array['data'], $link_array['class'], $link_array['method'], $link_array['value']);

		if( !empty($new_info['script']) ){
			$link_array['script'] = $this->addon_folder_rel.'/'.$this->dest_name .'/'.$new_info['script'];
		}

		if( !empty($new_info['data']) ){
			$link_array['data'] = '/data/_addondata/'.$this->data_folder.'/'.$new_info['data'];
		}

		if( !empty($new_info['class']) ){
			$link_array['class'] = $new_info['class'];
		}

		if( !empty($new_info['method']) ){

			$method = $new_info['method'];
			if( strpos($method,'::') > 0 ){
				$method = explode('::',$method);
			}

			$link_array['method'] = $method;
		}

		if( !empty($new_info['value']) ){
			$link_array['value'] = $new_info['value'];
		}

	}



	/**
	 * Purge Links from $purgeFrom that were once defined for $this->config_key
	 *
	 */
	function PurgeExisting(&$purgeFrom,$NewLinks){

		if( $this->config_key === false || !is_array($purgeFrom) ){
			return;
		}

		foreach($purgeFrom as $linkName => $linkInfo){
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['addon'] !== $this->config_key ){
				continue;
			}

			if( isset($NewLinks[$linkName]) ){
				continue;
			}

			unset($purgeFrom[$linkName]);
		}

	}


	/**
	 * Make sure the extracted links are valid
	 *
	 */
	function CleanLinks(&$links,$prefix,$linkType=false){

		$lower_prefix = strtolower($prefix);

		if( !is_array($links) || (count($links) <= 0) ){
			return array();
		}

		$result = array();
		foreach($links as $linkName => $linkInfo){
			if( !isset($linkInfo['script']) ){
				continue;
			}
			if( !isset($linkInfo['label']) ){
				continue;
			}

			if( strpos(strtolower($linkName),$lower_prefix) !== 0 ){
				$linkName = $prefix.$linkName;
			}


			$result[$linkName] = array();
			$result[$linkName]['script'] = $linkInfo['script'];
			$result[$linkName]['label'] = $linkInfo['label'];

			if( isset($linkInfo['class']) ){
				$result[$linkName]['class'] = $linkInfo['class'];
			}

			/*	method only available for gadgets as of 1.7b1
			if( isset($linkInfo['method']) ){
				$result[$linkName]['method'] = $linkInfo['method'];
			}
			*/

			if( $linkType ){
				$result[$linkName]['type'] = $linkType;
			}

		}

		return $result;
	}



	/*
	 * Gadget Functions
	 *
	 *
	 */
	function AddToHandlers($gadgets){
		global $gpLayouts;

		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}

		foreach($gpLayouts as $layout => $containers){
			if( !is_array($containers) ){
				continue;
			}

			if( isset($containers['handlers']['GetAllGadgets']) ){
				$container =& $gpLayouts[$layout]['handlers']['GetAllGadgets'];
				if( !is_array($container) ){
					$container = array();
				}
				$container = array_merge($container,$gadgets);
			}
		}
	}


	function CheckName($name){

		$test = str_replace(array('.','_',' '),array(''),$name );
		if( empty($test) || !ctype_alnum($test) ){
			$this->message( 'Could not install <em>'.$name.'</em>. Link and gadget names can only contain alphanumeric characters with underscore "_", dot "." and space " " characters.');
			return false;
		}
		return true;
	}


	/**
	 * similar to CleanLinks()
	 *
	 */
	function CleanGadgets(&$gadgets){
		global $gpOutConf, $langmessage, $config;

		if( !is_array($gadgets) || (count($gadgets) <= 0) ){
			return array();
		}

		$result = array();
		foreach($gadgets as $gadgetName => $gadgetInfo){

			//check against $gpOutConf
			if( isset($gpOutConf[$gadgetName]) ){
				$this->message( sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>') );
				continue;
			}

			//check against other gadgets
			if( isset($config['gadgets'][$gadgetName]) && ($config['gadgets'][$gadgetName]['addon'] !== $this->config_key) ){
				$this->message( sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>') );
				continue;
			}


			$temp = array();
			if( isset($gadgetInfo['script']) ){
				$temp['script'] = $gadgetInfo['script'];
			}
			if( isset($gadgetInfo['class']) ){
				$temp['class'] = $gadgetInfo['class'];
			}
			if( isset($gadgetInfo['data']) ){
				$temp['data'] = $gadgetInfo['data'];
			}
			if( isset($gadgetInfo['method']) ){
				$temp['method'] = $gadgetInfo['method'];
			}

			if( count($temp) > 0 ){
				$result[$gadgetName] = $temp;
			}
		}

		return $result;
	}


	/**
	 * Remove unused code folders created by incomplete addon installations
	 *
	 */
	function CleanInstallFolder(){

		if( !$this->remote_install ){
			return;
		}

		if( file_exists($this->source) ){
			gpFiles::RmAll($this->source);
		}

		if( file_exists($this->trash_path) ){
			gpFiles::RmAll($this->trash_path);
		}
	}


	function RemoveFromConfig(&$configFrom,$addon){

		if( !is_array($configFrom) ){
			return;
		}
		foreach($configFrom  as $key => $value ){
			if( !isset($value['addon']) ){
				continue;
			}
			if( $value['addon'] == $addon ){
				unset($configFrom[$key]);
			}
		}
	}


}
