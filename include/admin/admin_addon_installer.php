<?php
defined('is_running') or die('Not an entry point...');


/**
 * Replace 3 step addon install process with one step
 *
 * Currnet Normal install
 *	step1: checks addon.ini, checks for duplicate addon name,
 *	step2: copies the files
 *	step3: add hooks (special/admin/gadget/hooks), adds addon to configuration
 *
 * New Install
 *	The above 3 steps in one
 *	- copy to dummy folder first then rename
 *
 * New Remote install
 * 	copy files to temp folder.. then do above
 *
 *
 * ?? how to destinguish between upgrade and unwanted install of duplicate addon
 *
 *
 * Things to check back on in the old install
 *  - $this->data_folder
 *	- Install_CheckIni()
 *  !! Install_CheckFile()
 *  !! Developer mode
 *	!! Upgrades
 * 	add CleanUp() function
 *
 *
 */
class admin_addon_installer extends admin_addon_install{

	var $source = '';
	var $can_install_links = true;


	var $dest = '';
	var $dest_name;
	var $temp_folder;
	var $trash_path;

	var $messages = array();


	function __construct(){}


	function Install(){

		$success = $this->InstallSteps();

		if( !$success ){
			$this->Failed();
		}

		$this->CleanInstallFolder();

		return $success;
	}


	/**
	 * Run through the installation process
	 *
	 */
	function InstallSteps(){

		$this->GetAddonData();			// addonHistory
		$this->Init_PT();				// $this->config

		if( !$this->CheckSource() ){
			return false;
		}

		//get ini contents
		if( !$this->Install_Ini($this->source) ){
			return false;
		}


		// upgrade/destination
		$this->install_folder_name = $this->dest_name = $this->UpgradePath($this->ini_contents);
		if( $this->dest_name ){
			$this->dest = $this->addon_folder.'/'.$this->dest_name;
		}else{
			$this->dest = $this->NewTempFolder();
			$this->install_folder_name = $this->dest_name = basename($this->dest);
		}


		//copy
		if( !$this->Copy() ){
			return false;
		}

		//hooks
		if( !$this->Hooks() ){
			return false;
		}

		//move new addon folder into place
		if( !$this->FinalizeFolder() ){
			return false;
		}

		if( !$this->FinalizeConfig() ){
			return false;
		}


		$this->UpdateHistory();

		return true;

	}

	/**
	 * Check the source folder
	 *
	 */
	function CheckSource(){
		return true;
	}


	/**
	 * Set $this->ini_contents with the settings for the addon in $ini_dir
	 * @return bool
	 *
	 */
	function Install_Ini($ini_dir){
		global $langmessage, $dataDir, $dirPrefix;

		$ini_file = $ini_dir.'/Addon.ini';

		if( !file_exists($ini_file) ){
			$this->message( sprintf($langmessage['File_Not_Found'],' <em>'.$ini_file.'</em>') );
			return false;
		}

		$folder = basename($this->dest);

		$variables = array(
					'{$addon}'				=> $folder,
					'{$plugin}'				=> $folder,
					'{$dataDir}'			=> $dataDir,
					'{$dirPrefix}'			=> $dirPrefix,
					'{$addonRelativeData}'	=> common::GetDir('/data/_addondata/'.$folder),
					'{$addonRelativeCode}'	=> common::GetDir('/data/'.$this->addon_folder_name.'/'.$folder),
					);


		//get ini contents
		$this->ini_contents = gp_ini::ParseFile($ini_file,$variables);

		if( !$this->ini_contents ){
			$this->message( $langmessage['Ini_Error'].' '.$langmessage['Ini_Submit_Bug'] );
			return false;
		}

		if( !isset($this->ini_contents['Addon_Name']) ){
			$this->message( $langmessage['Ini_No_Name'].' '.$langmessage['Ini_Submit_Bug'] );
			return false;
		}

		if( isset($this->ini_contents['Addon_Unique_ID']) && !is_numeric($this->ini_contents['Addon_Unique_ID']) ){
			$this->message('Invalid Unique ID');
			return false;
		}

		$this->addon_name = $this->ini_contents['Addon_Name'];

		return true;
	}


	/**
	 * Copy the addon files
	 *
	 */
	function Copy(){
		global $langmessage;

		$this->temp_folder = $this->NewTempFolder();

		$result = self::CopyAddonDir($this->source,$this->temp_folder);
		if( $result !== true ){
			$this->message( $result );
			return false;
		}

		$this->message( $langmessage['copied_addon_files'] );

		return true;
	}


	/**
	 * Copy code for dev mode
	 *
	 */
	function CopyDev(){

		if( $this->upgrade_key ){
			$this->message( $langmessage['copied_addon_files'] );
			return true;
		}


		if( !symlink($this->source,$this->dest) ){
			$this->message($langmessage['OOPS']);
			return false;
		}

		$this->message($langmessage['copied_addon_files']);
		return true;
	}


	/**
	 * Add hooks to configuration
	 *
	 */
	function Hooks(){
		global $langmessage, $config;

		if( !$this->can_install_links ){
			return true;
		}

		//needs to be before other gadget functions
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$this->install_folder_name);

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
	 * Rename the temp folder to the dest folder
	 *
	 */
	function FinalizeFolder(){

		if( !isset($this->temp_folder) ){
			return true;
		}

		if( file_exists($this->dest) ){
			$this->trash_path = $this->NewTempFolder();
			if( !@rename($this->dest,$this->trash_path) ){
				$this->message('Existing destination not renamed');
				return false;
			}
		}

		//rename temp folder
		if( rename($this->temp_folder,$this->dest) ){
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


		$this->install_folder_name = $this->dest_name = basename($this->dest);

		//make sure we have an array
		if( !isset($this->config[$this->dest_name]) ){
			$this->config[$this->dest_name] = array();
		}elseif( !is_array($this->config[$this->dest_name]) ){
			$this->message('$this->config[addon] is not an array');
			return false;
		}


		//general configuration
		$this->UpdateConfigInfo('Addon_Name','name');
		$this->UpdateConfigInfo('Addon_Version','version');
		$this->UpdateConfigInfo('Addon_Unique_ID','id');


		//remote
		unset($this->config[$this->dest_name]['remote_install']);
		if( $this->remote_installation ){
			$this->config[$this->dest_name]['remote_install'] = true;
		}


		//proof of purchase
		$order = false;
		if( isset($this->ini_contents['Proof of Purchase']) && isset($this->ini_contents['Proof of Purchase']['order']) ){
			$order = $this->ini_contents['Proof of Purchase']['order'];
			$this->config[$this->dest_name]['order'] = $order;
		}else{
			// don't delete any purchase id's
			// unset($this->config[$this->dest_name]['order']);
		}


		if( $this->can_install_links ){
			$this->UpdateConfigInfo('editable_text','editable_text');
			$this->UpdateConfigInfo('html_head','html_head');
		}


		if( !admin_tools::SaveAllConfig() ){
			$this->message($langmessage['OOPS'].' (Configuration not saved)');
			return false;
		}

		if( $order ){
			$img_path = common::IdUrl('ci');
			common::IdReq($img_path);
		}

		return true;
	}


	/**
	 *
	 *
	 */
	function UpdateHistory(){

		// History
		$history = array();
		$history['name'] = $this->config[$this->dest_name]['name'];
		$history['action'] = 'installed';
		if( isset($this->config[$this->dest_name]['id']) ){
			$history['id'] = $this->config[$this->dest_name]['id'];
		}
		$history['time'] = time();

		$this->addonHistory[] = $history;
		$this->SaveAddonData();

	}



	/**
	 * Return the path of a non-existant folder
	 *
	 */
	function NewTempFolder(){

		do{
			$folder = common::RandomString(7,false);
			$full_dest = $this->addon_folder.'/'.$folder;

		}while( is_numeric($folder) || isset($this->config[$folder]) || file_exists($full_dest) );

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


}
