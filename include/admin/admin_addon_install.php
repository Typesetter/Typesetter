<?php
defined('is_running') or die('Not an entry point...');



global $langmessage;
$langmessage['Sorry, nothing matched'] = 'Sorry, nothing met your search criteria.';
$langmessage['Sorry, data not fetched'] = 'Sorry, the addon data could not be fetched from gpEasy.com.';


includeFile('admin/admin_addons_tool.php');

class admin_addon_install extends admin_addons_tool{

	var $developer_mode = false;
	var $source_folder_name;
	var $source_folder;
	var $addon_name;
	var $install_steps = array(1=>'addon_install_check',2=>'addon_install_copy',3=>'addon_install_save');
	var $config_cache;
	var $data_folder;


	//new
	var $upgrade_key = false;
	var $temp_folder_name;
	var $temp_folder_path;
	var $install_folder_name;
	var $install_folder_path;


	//plugin vs theme
	var $config_index = 'addons';
	var $addon_folder_name = '_addoncode';
	var $addon_folder;
	var $path_root = 'Admin_Addons';
	var $path_remote = 'Admin_Addons/Remote';
	var $can_install_links = true;

	var $find_label;


	/*
	 *
	 * Plugin vs Theme Installation
	 *
	 *
	 */

	function Init_PT(){
		global $config,$dataDir;

		if( !isset($config[$this->config_index]) ){
			$config[$this->config_index] = array();
		}

		$this->config =& $config[$this->config_index];
		$this->config_cache = $config;

		$this->addon_folder = $dataDir.'/data/'.$this->addon_folder_name;

		gpFiles::CheckDir($this->addon_folder);

	}


	/*
	 *
	 * Install Local Packages
	 *
	 *
	 */
	function admin_addon_install($cmd){
		global $langmessage;

		$this->Init_PT();

		if( !$this->InitInstall() ){
			return false;
		}
		$this->GetAddonData(); //for addonHistory


		$success = false;
		ob_start();
		switch($cmd){
			case 'develop':
				$this->Develop();
			return true;
			case 'step2':
				$success = $this->Install_Step2();
				$step = 2;
			break;
			case 'step3':
				$success = $this->Install_Step3();
				$step = 3;
			break;
			default:
				$success = $this->Install_Step1();
				$step = 1;
			break;
		}
		$content = ob_get_clean();

		if( $success ){
			$step++;
		}

		//output
		$this->Install_Progress($step-1);

		echo $content;

		$this->InstallForm($step);


		if( !empty($this->ini_contents['About']) ){
			echo '<div id="addon_about">';
			echo '<h3>'.$langmessage['about'].'</h3>';
			echo strip_tags($this->ini_contents['About'],'<div><p><a><b><br/><span><tt><em><i><b><sup><sub><strong><u>');
			echo '</div>';
		}


		return true;
	}

	function Develop(){
		global $langmessage;

		echo '<h2>';
		echo $langmessage['Install'];
		if( $this->addon_name ){
			echo ' &#187; ';
			echo htmlspecialchars($this->addon_name);
		}
		echo '</h2>';
		echo '<p>You have chosen to develop <em>'.htmlspecialchars($this->addon_name).'</em>.</p>';
		echo '<p>';
		echo ' Instead of copying the source folder, a developer installation will access the code in the source folder directly (via a symbolic link). ';
		echo ' This will allow you to test changes to your addon code without having to repeatedly re-install. ';
		//echo ' Compared to a normal installation, a developer installation will allow you to test changes to your addon code without having to repeatedly re-install. ';
		echo ' </p>';

		echo '<p>';
		echo ' If you aren\'t actively developing this addon, a normal installation is recommended which can help insulate the active code from changes that will periodically occur. ';
		//echo 'If you don\'t plan on making changes to the source code, a normal installation can help insulate the active code, notably during future upgrades and backups. ';
		echo '</p>';

		echo '<p>';
		echo '<form action="'.common::GetUrl($this->path_root).'" method="post">';
		echo '<input type="hidden" name="cmd" value="step1" />';
		echo '<input type="hidden" name="source" value="'.htmlspecialchars($this->source_folder_name).'" />';
		echo '<button type="submit" name="mode" value="dev" class="gpsubmit" >Continue with Developer Installation ...</button>';
		echo ' &nbsp; ';
		echo '<button type="submit" name="mode" value="" class="gpsubmit">Install Normally ...</button>';
		echo '</form>';
		echo '</p>';

		echo '<p>Take a look at the <a href="http://docs.gpeasy.com">gpEasy Documentation</a> for  more information about <a href="http://docs.gpeasy.com/Main/Plugins">plugin development</a></p>';
	}

	function InstallForm($step){
		global $langmessage;

		if( $step > 3 ){
			$this->Installed();
			return;
		}


		echo '<form action="'.common::GetUrl($this->path_root).'" method="post">';
		echo '<input type="hidden" name="cmd" value="step'.$step.'" />';
		echo '<input type="hidden" name="source" value="'.htmlspecialchars($this->source_folder_name).'" />';
		echo '<input type="hidden" name="upgrade_key" value="'.htmlspecialchars($this->upgrade_key).'" />';

		if( isset($this->temp_folder_name) ){
			echo '<input type="hidden" name="temp_folder_name" value="'.htmlspecialchars($this->temp_folder_name).'" />';
		}
		echo '<input type="hidden" name="install_folder_name" value="'.htmlspecialchars($this->install_folder_name).'" />';


		echo '<p>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel" />';
		if( $this->developer_mode ){
			echo ' <input type="hidden" name="mode" value="dev" />';
			echo ' &nbsp; <em>'.$langmessage['developer_install'].'</em>';
		}
		echo '</p>';

		echo '</form>';
	}

	function Installed(){
		global $langmessage, $installed_addon;

		echo '<form action="'.common::GetUrl($this->path_root).'" method="get">';
		echo '<input type="hidden" name="cmd" value="show" />';
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($this->install_folder_name).'" />';

		$installed_addon = $this->install_folder_name;

		echo '<p>';
		echo sprintf($langmessage['installed'],$this->ini_contents['Addon_Name']);
		echo '</p>';
		echo '<p>';
		echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="gpsubmit"/>';
		if( $this->developer_mode ){
			echo ' <em>'.$langmessage['developer_install'].'</em>';
		}
		echo '</p>';
		echo '</form>';
	}

	function Install_Progress($step){
		global $langmessage;

		echo '<h2>';
		if( $this->upgrade_key ){
			echo $langmessage['upgrade'];
		}else{
			echo $langmessage['Install'];
		}
		if( $this->addon_name ){
			echo ' &#187; ';
			echo htmlspecialchars($this->addon_name);
		}
		echo '</h2>';


		echo '<p id="addon_install_progress">';
		foreach($this->install_steps as $steps_step => $step_label){
			if( $steps_step < $step ){
				echo '<span class="completed">';
			}elseif( $steps_step == $step ){
				echo '<span class="current">';
			}else{
				echo '<span>';
			}
			echo ($steps_step).'. ';
			echo $langmessage[$step_label];
			echo '</span>';
		}
		echo '</p>';
	}



	/*
	 * Initialize the installation
	 * 	Check ini file
	 * 	Set folder variables
	 *
	 */
	function InitInstall(){
		global $dataDir, $langmessage;

		if( empty($_REQUEST['source']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//developer mode
		if( isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'dev') ){
			if( !function_exists('symlink') ){
				message($langmessage['OOPS']);
				return false;
			}
			$this->developer_mode = true;
		}


		//init vars
		$this->source_folder_name = $_REQUEST['source'];
		$this->source_folder = $dataDir.'/addons/'.$_REQUEST['source'];
		$this->InitInstall_Vars();


		//check folders
		if( !file_exists($this->source_folder) ){
			message( sprintf($langmessage['File_Not_Found'],' <em>'.$this->source_folder.'</em>') );
			return false;
		}


		//get ini contents
		if( !$this->Install_Ini($this->source_folder) ){
			return false;
		}

		return true;
	}

	function InitInstall_Vars(){

		if( !empty($_POST['temp_folder_name']) ){
			$this->temp_folder_name = $_POST['temp_folder_name'];
			$this->temp_folder_path = $this->addon_folder.'/'.$this->temp_folder_name;
		}

		if( !empty($_POST['upgrade_key']) ){
			$this->upgrade_key = $_POST['upgrade_key'];
		}

		if( !empty($_POST['install_folder_name']) ){
			$this->install_folder_name = $_POST['install_folder_name'];
			$this->install_folder_path = $this->addon_folder.'/'.$this->install_folder_name;
		}


		if( isset($this->config[$this->install_folder_name]['data_folder']) ){
			$this->data_folder = $this->config[$this->install_folder_name]['data_folder'];
		}else{
			$this->data_folder = $this->install_folder_name;
		}
	}


	/**
	 * Set $this->ini_contents with the settings for the addon in $ini_dir
	 * @return bool
	 */
	function Install_Ini($ini_dir){
		global $langmessage, $dataDir, $dirPrefix;


		$ini_file = $ini_dir.'/Addon.ini';

		if( !file_exists($ini_file) ){
			message( sprintf($langmessage['File_Not_Found'],' <em>'.$ini_file.'</em>') );
			return false;
		}

		$variables = array(
					'{$addon}'				=> $this->install_folder_name,
					'{$dataDir}'			=> $dataDir,
					'{$dirPrefix}'			=> $dirPrefix,
					'{$addonRelativeData}'	=> common::GetDir('/data/_addondata/'.$this->data_folder),
					'{$addonRelativeCode}'	=> common::GetDir('/data/'.$this->addon_folder_name.'/'.$this->install_folder_name),
					);


		//get ini contents
		$this->ini_contents = gp_ini::ParseFile($ini_file,$variables);

		if( !$this->ini_contents ){
			message($langmessage['Ini_Error'].' '.$langmessage['Ini_Submit_Bug']);
			return false;
		}

		if( !isset($this->ini_contents['Addon_Name']) ){
			message($langmessage['Ini_No_Name'].' '.$langmessage['Ini_Submit_Bug']);
			return false;
		}

		if( isset($this->ini_contents['Addon_Unique_ID']) && !is_numeric($this->ini_contents['Addon_Unique_ID']) ){
			message('Invalid Unique ID');
			return false;
		}

		$this->addon_name = $this->ini_contents['Addon_Name'];

		return true;
	}


	/*
	 * Determine if the addon (identified by $ini_info and $source_folder) is an upgrade to an existing addon
	 *
	 * @return mixed
	 */
	function UpgradePath($ini_info,$config_key='addons'){
		global $config;

		if( !isset($config[$config_key]) ){
			return false;
		}

		//by id
		if( isset($ini_info['Addon_Unique_ID']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( !isset($data['id']) || !is_numeric($data['id']) ){
					continue;
				}

				if( (int)$data['id'] == (int)$ini_info['Addon_Unique_ID'] ){
					return $addon_key;
				}
			}
		}

		//by name
		if( isset($ini_info['Addon_Name']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( $data['name'] == $ini_info['Addon_Name'] ){
					return $addon_key;
				}
			}
		}

		return false;
	}



	/*
	 * Step 1
	 * Check the contents of the addon that is to be installed
	 *
	 */
	function Install_Step1(){
		global $langmessage;

		//start with a clean addoncode folder
		$this->CleanInstallFolder();
		$this->upgrade_key = $this->UpgradePath($this->ini_contents);


		//warn about unique id
		if( !isset($this->ini_contents['Addon_Unique_ID']) ){
			echo '<p class="gp_notice">';
			echo $langmessage['Ini_No_ID'];
			echo '</p>';

			if( $this->upgrade_key && isset($this->config[$this->upgrade_key]['id']) ){
				$name = $this->config[$this->upgrade_key]['name'];
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['incorrect_update'],' <em>'.$name.'</em> ');
				echo '</p>';
				return false;
			}
		}

		if( !$this->Install_CheckName($this->ini_contents['Addon_Name']) ){
			return false;
		}


		if( !$this->Install_CheckIni() ){
			return false;
		}

		echo '<p>';
		echo sprintf($langmessage['Selected_Install'],' <em>'.htmlspecialchars($this->ini_contents['Addon_Name']).'</em> ',' <em>'.htmlspecialchars($this->source_folder).'</em>');
		echo '</p>';


		//Addon Custom Install_Check()
		return $this->Install_CheckFile($this->source_folder);
	}


	/**
	 * Run the Install_Check.php file if it exists
	 * @return bool
	 */
	function Install_CheckFile($dir){
		$check_file = $dir.'/Install_Check.php';
		if( !file_exists($check_file) ){
			return true;
		}

		include($check_file);
		if( !function_exists('Install_Check') ){
			return true;
		}

		if( !Install_Check() ){
			return false;
		}

		return true;
	}


	/**
	 * Check the ini values of the addon being installed
	 * @return bool
	 */
	function Install_CheckIni(){
		global $langmessage;

		//warn if attempting to install lesser version of same addon
		if( !empty($this->upgrade_key) ){
			$info = $this->config[$this->upgrade_key];
			if( !empty($info['version']) ){
				if( empty($this->ini_contents['Addon_Version']) ){
					echo '<p class="gp_warning">'.sprintf($langmessage['downgrade']).'</p>';
				}elseif( version_compare($this->ini_contents['Addon_Version'], $info['version'],'<') ){
					echo '<p class="gp_warning">'.sprintf($langmessage['downgrade']).'</p>';
				}
			}
		}

		//Check Versions
		if( isset($this->ini_contents['min_gpeasy_version']) ){
			if(version_compare($this->ini_contents['min_gpeasy_version'], gpversion,'>') ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['min_version'],$this->ini_contents['min_gpeasy_version']);
				echo ' '.$langmessage['min_version_upgrade'];
				echo '</p>';
				return false;
			}
		}

		return true;
	}


	function Install_CheckName($check_name){
		global $langmessage;

		//check for duplicate name
		foreach($this->config as $addon_key => $data){
			if( $this->upgrade_key && ($this->upgrade_key == $addon_key) ){
				continue;
			}

			if( $data['name'] == $check_name ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['already_installed'],' <em>'.$check_name.'</em> ');
				echo '</p>';
				return false;
			}
		}

		return true;
	}




	/*
	 * Step 2
	 * Copy the files (or create symlink) of the addon to be installed to /data/_addoncode/<addon_folder>
	 *
	 *
	 */
	function Install_Step2(){
		global $langmessage;


		if( !$this->developer_mode ){

			if( $this->upgrade_key ){
				$this->install_folder_name = $this->upgrade_key;
				$this->temp_folder_name = $this->NewTempFolder();
				$copy_to = $this->addon_folder.'/'.$this->temp_folder_name;
			}else{
				$this->install_folder_name = $this->NewTempFolder();
				$copy_to = $this->addon_folder.'/'.$this->install_folder_name;
			}

			if( !admin_addon_install::CopyAddonDir($this->source_folder,$copy_to) ){
				echo '<p>';
				echo $langmessage['OOPS'];
				echo '</p>';
				return false;
			}

			echo '<p>';
			echo $langmessage['copied_addon_files'];
			echo '</p>';

			return true;
		}


		//developer mode
		//...


		if( $this->upgrade_key ){
			$this->install_folder_name = $this->upgrade_key;
			echo '<p>';
			echo $langmessage['copied_addon_files'];
			echo '</p>';
			return true;
		}

		$this->install_folder_name = $this->NewTempFolder();
		$this->install_folder_path = $this->addon_folder.'/'.$this->install_folder_name;

		if( !symlink($this->source_folder,$this->install_folder_path) ){
			echo '<p>';
			echo $langmessage['OOPS'];
			echo '</p>';
			return false;
		}

		echo '<p>';
		echo $langmessage['copied_addon_files'];
		echo '</p>';
		return true;

	}


	function NewTempFolder(){

		do{
			$folder = common::RandomString(7,false);
			$full_dest = $this->addon_folder.'/'.$folder;

		}while( is_numeric($folder) || isset($this->config[$folder]) || file_exists($full_dest) );

		return $folder;
	}


	function CopyAddonDir($fromDir,$toDir){

		$dh = @opendir($fromDir);
		if( !$dh ){
			return false;
		}

		if( !gpFiles::CheckDir($toDir) ){
			message('Copy failed: '.$fromDir.' to '.$toDir.' (1)');
			return false;
		}


		while( ($file = readdir($dh)) !== false){

			if( strpos($file,'.') === 0){
				continue;
			}

			$fullFrom = $fromDir.'/'.$file;
			$fullTo = $toDir.'/'.$file;


			//directories
			if( is_dir($fullFrom) ){
				if( !admin_addon_install::CopyAddonDir($fullFrom,$fullTo) ){
					closedir($dh);
					return false;
				}
				continue;
			}

			//files
			//If the destination file already exists, it will be overwritten.
			if( !copy($fullFrom,$fullTo) ){
				message('Copy failed: '.$fullFrom.' to '.$fullTo.' (2)');
				closedir($dh);
				return false;
			}
		}
		closedir($dh);


		return true;
	}



	/*
	 *
	 * Step 3
	 * 		Update configuration
	 *
	 */

	function Install_Step3(){
		global $langmessage, $config;

		if( empty($this->install_folder_name) ){
			message($langmessage['OOPS']);
			return false;
		}


		if( $this->upgrade_key && !$this->developer_mode ){
			if( !file_exists($this->install_folder_path) ){
				message($langmessage['OOPS']);
				return false;
			}

			if( empty($this->temp_folder_name) || !file_exists($this->temp_folder_path) ){
				message($langmessage['OOPS']);
				return false;
			}
		}

		$old_config = $config;
		$this->Step3_Links();


		if( !isset($this->config[$this->install_folder_name]) ){
			$this->config[$this->install_folder_name] = array();
		}

		echo '<p>';
		echo 'Saving Settings';
		echo '</p>';


		//general configuration
		$this->UpdateConfigInfo('Addon_Name','name');
		$this->UpdateConfigInfo('Addon_Version','version');
		$this->UpdateConfigInfo('Addon_Unique_ID','id');
		$this->UpdateConfigInfo('Addon_Unique_ID','id');


		//proof of purchase
		$order = false;
		if( isset($this->ini_contents['Proof of Purchase']) && isset($this->ini_contents['Proof of Purchase']['order']) ){
			$order = $this->ini_contents['Proof of Purchase']['order'];
			$this->config[$this->install_folder_name]['order'] = $order;
		}else{
			// don't delete any purchase id's
			// unset($this->config[$this->install_folder_name]['order']);
		}


		if( $this->can_install_links ){
			$this->UpdateConfigInfo('editable_text','editable_text');
			$this->UpdateConfigInfo('html_head','html_head');
		}

		if( !$this->Step3_Folders() ){
			message($langmessage['OOPS']);
			$config = $old_config;
			return false;
		}

		if( !admin_tools::SaveAllConfig() ){
			message($langmessage['OOPS']);
			$config = $old_config;
			return false;
		}


		// History
		$history = array();
		$history['name'] = $this->config[$this->install_folder_name]['name'];
		$history['action'] = 'installed';
		if( isset($this->config[$this->install_folder_name]['id']) ){
			$history['id'] = $this->config[$this->install_folder_name]['id'];
		}
		$history['time'] = time();

		$this->addonHistory[] = $history;
		$this->SaveAddonData();
		if( $order ){
			$img_path = common::IdUrl('ci');
			common::IdReq($img_path);
		}


		//completed install, clean up /data/_addoncode/ folder
		$this->CleanInstallFolder();

		return true;
	}


	function Step3_Folders(){

		if( $this->developer_mode ){
			return true;
		}

		if( !$this->upgrade_key ){
			return true;
		}


		$trash_name = $this->NewTempFolder();
		$trash_path = $this->addon_folder.'/'.$trash_name;
		if( !@rename($this->install_folder_path,$trash_path) ){
			return false;
		}

		//rename temp folder
		if( !@rename($this->temp_folder_path,$this->install_folder_path) ){
			@rename($trash_path,$this->install_folder_path);
			return false;
		}
		return true;
	}


	function Step3_Links(){
		global $langmessage, $config;

		if( !$this->can_install_links ){
			return;
		}

		echo '<p>Adding Gadgets</p>';

		//needs to be before other gadget functions
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$this->install_folder_name);

		//echo showArray($this->ini_contents);
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


		echo '<p>Adding Links</p>';

		//admin links
		$Admin_Links = $this->ExtractFromInstall($this->ini_contents,'Admin_Link:');
		$Admin_Links = $this->CleanLinks($Admin_Links,'Admin_');
		$this->PurgeExisting($config['admin_links'],$Admin_Links);
		$this->AddToConfig($config['admin_links'],$Admin_Links);



		//special links
		$Special_Links = $this->ExtractFromInstall($this->ini_contents,'Special_Link:');
		$Special_Links = $this->CleanLinks($Special_Links,'Special_','special');
		$this->UpdateSpecialLinks($Special_Links);


		echo '<p>Adding Hooks</p>';


		//generic hooks
		$this->AddHooks();

	}



	function UpdateConfigInfo($ini_var,$config_var){

		if( isset($this->ini_contents[$ini_var]) ){
			$this->config[$this->install_folder_name][$config_var] = $this->ini_contents[$ini_var];
		}elseif( isset($this->config[$this->install_folder_name][$config_var]) ){
			unset($this->config[$this->install_folder_name][$config_var]);
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

			if( $linkInfo['addon'] !== $this->install_folder_name ){
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

				if( !isset($curr_info['addon']) || $this->install_folder_name === false ){
					$addlink = false;
				}elseif( $curr_info['addon'] != $this->install_folder_name ){
					$addlink = false;
				}

				if( !$addlink ){
					echo '<p class="gp_notice">';
					echo sprintf($langmessage['addon_key_defined'],' <em>Special_Link: '.$new_title.'</em>');
					echo '<p>';
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

		$this->CleanHooks($this->install_folder_name,$installed);
	}

	function CleanHooks($addon,$keep_hooks = array()){
		global $config;

		if( !isset($config['hooks']) ){
			return;
		}

		foreach($config['hooks'] as $hook_name => $hook_array){

			foreach($hook_array as $hook_dir => $hook_args){

				//not cleaning other addons
				if( $hook_dir != $addon ){
					continue;
				}

				if( !isset($keep_hooks[$hook_name]) ){
					unset($config['hooks'][$hook_name][$hook_dir]);
					//message('remove this hook: '.$hook_name);
				}
			}
		}

		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
		}

	}

	function AddHook($hook,$hook_args){
		global $config;

		$add = array();
		$this->UpdateLinkInfo($add,$hook_args);
		$config['hooks'][$hook][$this->install_folder_name] = $add;

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



	function CheckName($name){

		$test = str_replace(array('.','_',' '),array(''),$name );
		if( empty($test) || !ctype_alnum($test) ){
			echo '<p class="gp_notice">';
			echo 'Could not install <em>'.$name.'</em>. Link and gadget names can only contain alphanumeric characters with underscore "_", dot "." and space " " characters.';
			echo '</p>';
			return false;
		}
		return true;
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

				if( !isset($lower_add_to[$lower_key]['addon']) || $this->install_folder_name === false ){
					$addlink = false;
				}elseif( $lower_add_to[$lower_key]['addon'] != $this->install_folder_name ){
					$addlink = false;
				}

				if( !$addlink ){
					echo '<p class="gp_notice">';
					echo sprintf($langmessage['addon_key_defined'],' <em>'.$Config_Key.'</em>');
					echo '<p>';
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

		$link_array['addon'] = $this->install_folder_name;
		if( !empty($new_info['script']) ){
			$link_array['script'] = '/data/_addoncode/'.$this->install_folder_name .'/'.$new_info['script'];
		}else{
			unset($link_array['script']);
		}
		if( !empty($new_info['data']) ){
			$link_array['data'] = '/data/_addondata/'.$this->data_folder.'/'.$new_info['data'];
		}else{
			unset($link_array['data']);
		}
		if( !empty($new_info['class']) ){
			$link_array['class'] = $new_info['class'];
		}else{
			unset($link_array['class']);
		}
		if( !empty($new_info['method']) ){

			$method = $new_info['method'];
			if( strpos($method,'::') > 0 ){
				$method = explode('::',$method);
			}

			$link_array['method'] = $method;
		}else{
			unset($link_array['method']);
		}

		if( !empty($new_info['value']) ){
			$link_array['value'] = $new_info['value'];
		}else{
			unset($link_array['value']);
		}

	}




	/*
	 * Purge Links from $purgeFrom that were once defined for $this->install_folder_name
	 *
	 */
	function PurgeExisting(&$purgeFrom,$NewLinks){

		if( $this->install_folder_name === false || !is_array($purgeFrom) ){
			return;
		}

		foreach($purgeFrom as $linkName => $linkInfo){
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['addon'] !== $this->install_folder_name ){
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


	//remove gadgets from $gpLayouts
	function RemoveFromHandlers($gadgets){
		global $gpLayouts;

		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}


		foreach($gpLayouts as $theme => $containers){
			if( !is_array($containers) || !isset($containers['handlers']) || !is_array($containers['handlers']) ){
				continue;
			}
			foreach($containers['handlers'] as $container => $handlers){
				if( !is_array($handlers) ){
					continue;
				}

				foreach($handlers as $index => $handle){
					$pos = strpos($handle,':');
					if( $pos > 0 ){
						$handle = substr($handle,0,$pos);
					}

					foreach($gadgets as $gadget){
						if( $handle === $gadget ){
							$handlers[$index] = false; //set to false
						}
					}
				}

				$handlers = array_diff($handlers, array(false)); //remove false entries
				$handlers = array_values($handlers); //reset keys
				$gpLayouts[$theme]['handlers'][$container] = $handlers;
			}
		}
	}


	/*
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
				echo '<p class="gp_notice">';
				echo sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>');
				echo '<p>';
				continue;
			}

			//check against other gadgets
			if( isset($config['gadgets'][$gadgetName]) && ($config['gadgets'][$gadgetName]['addon'] !== $this->install_folder_name) ){
				echo '<p class="gp_notice">';
				echo sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>');
				echo '<p>';
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







	/*
	 * Remote Install Functions
	 *
	 *
	 *
	 */
	function RemoteInstallMain($cmd){
		global $langmessage;

		$this->Init_PT();
		$this->GetAddonData(); //for addonHistory
		if( !$this->RemoteInit() ){
			return;
		}

		$success = false;
		ob_start();
		switch($cmd){
			case 'remote_install3':
				$success = $this->RemoteInstall3();
				$step = 3;
			break;

			case 'remote_install2':
				$success = $this->RemoteInstall2();
				$step = 2;
			break;
			default:
				$success = $this->RemoteInstall1();
				$step = 1;
			break;
		}
		$content = ob_get_clean();

		if( $success ){
			$step++;
		}

		$this->Install_Progress($step-1);

		echo $content;

		$this->RemoteInstall_Form($step);

	}

	function RemoteInstall_Form($step){
		global $langmessage;

		if( $step > 3 ){
			$this->Installed();
			return;
		}

		echo '<form action="'.common::GetUrl($this->path_root).'" method="post">';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';
		echo '<input type="hidden" name="type" value="'.htmlspecialchars($_REQUEST['type']).'" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($_REQUEST['id']).'" />';
		if( isset($_REQUEST['order']) ){
			echo '<input type="hidden" name="order" value="'.htmlspecialchars($_REQUEST['order']).'" />';
		}
		echo '<input type="hidden" name="upgrade_key" value="'.htmlspecialchars($this->upgrade_key).'" />';

		if( isset($this->temp_folder_name) ){
			echo '<input type="hidden" name="temp_folder_name" value="'.htmlspecialchars($this->temp_folder_name).'" />';
		}
		echo '<input type="hidden" name="install_folder_name" value="'.htmlspecialchars($this->install_folder_name).'" />';


		echo '<p>';
		echo '<input type="hidden" name="cmd" value="remote_install'.$step.'" />';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel" />';
		echo '</p>';

		echo '</form>';
	}

	function RemoteInit(){
		global $langmessage;

		if( empty($_REQUEST['name'])
			|| empty($_REQUEST['type'])
			|| empty($_REQUEST['id'])
			|| !is_numeric($_REQUEST['id'])
			){
				message($langmessage['OOPS']);
				return false;
		}
		if( $_REQUEST['type'] != 'plugin' && $_REQUEST['type'] != 'theme' ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !admin_tools::CanRemoteInstall() ){
			message($langmessage['OOPS']);
			return false;
		}

		$addonName =& $_REQUEST['name'];
		$this->addon_name = $addonName;

		$this->InitInstall_Vars();

		return true;
	}


	function RemoteInstall1(){
		global $langmessage;

		//start with a clean addoncode folder
		$this->CleanInstallFolder();


		//upgrade?
		foreach($this->config as $addon_key => $addon_info){
			if( !isset($addon_info['id']) ){
				continue;
			}
			if( $addon_info['id'] == $_REQUEST['id'] ){
				$this->upgrade_key = $addon_key;
			}
		}

		if( !$this->Install_CheckName($this->addon_name) ){
			return false;
		}


		echo '<p class="gp_notice">';
		echo $langmessage['Addon_Install_Warning'];
		echo '</p>';

		echo '<p>';
		echo sprintf($langmessage['Selected_Install'],' <em>'.htmlspecialchars($this->addon_name).'</em> ',' gpEasy.com');
		echo '</p>';


		return true;
	}


	function RemoteInstall2(){
		global $langmessage;

		includeFile('tool/RemoteGet.php');

		if( $this->upgrade_key ){
			$this->install_folder_name = $this->upgrade_key;
			$this->temp_folder_name = $this->NewTempFolder();
			$copy_to = $this->addon_folder.'/'.$this->temp_folder_name;

		}else{
			$this->install_folder_name = $this->NewTempFolder();
			$copy_to = $this->addon_folder.'/'.$this->install_folder_name;
		}

		//download
		$download_link = addon_browse_path;
		if( $_POST['type'] == 'theme' ){
			$download_link .= '/Themes';
		}else{
			$download_link .= '/Plugins';
		}
		$download_link .= '?cmd=install&id='.rawurlencode($_POST['id']);
		if( isset($_POST['order']) ){
			$download_link .= '&order='.rawurlencode($_POST['order']);
		}elseif( isset($this->config[$this->install_folder_name]['order']) ){
			$download_link .= '&order='.rawurlencode($this->config[$this->install_folder_name]['order']);
		}

		$full_result = gpRemoteGet::Get($download_link);
		if( (int)$full_result['response']['code'] < 200 && (int)$full_result['response']['code'] >= 300 ){
			echo '<p class="gp_notice">'.$langmessage['download_failed'].' (1)</p>';
			return false;
		}

		//download failed and a message was sent
		if( isset($full_result['headers']['x-error']) ){
			echo '<p class="gp_notice">'.htmlspecialchars($full_result['headers']['x-error']).'</p>';
			echo '<p>'.sprintf($langmessage['download_failed_xerror'],'href="'.$this->DetailUrl($_POST).'" name="remote"').'</p>';
			return false;
		}

		$result = $full_result['body'];
		$md5 =& $full_result['headers']['x-md5'];

		//check md5
		$package_md5 = md5($result);
		if( $package_md5 != $md5 ){
			echo '<p class="gp_notice">'.$langmessage['download_failed_md5'].' <br/> (Package Checksum '.$package_md5.' != Expected Checksum '.$md5.')</p>';
			return false;
		}

		//save contents
		$tempfile = $this->tempfile();
		if( !gpFiles::Save($tempfile,$result) ){
			echo '<p class="gp_notice">'.$langmessage['download_failed'].' (Package not saved)</p>';
			return false;
		}

		// Unzip uses a lot of memory, but not this much hopefully
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip($tempfile);
		$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		unlink($tempfile);

		if( !$this->write_package($copy_to,$archive_files) ){
			return false;
		}

		echo '<p>';
		echo $langmessage['copied_addon_files'];
		echo '</p>';

		if( !$this->Install_Ini($copy_to) ){
			return false;
		}

		if( !$this->Install_CheckIni() ){
			return false;
		}

		return $this->Install_CheckFile($copy_to);
	}

	function RemoteInstall3(){

		if( !empty($this->temp_folder_name) ){
			$ini_folder = $this->temp_folder_path;
		}else{
			$ini_folder = $this->install_folder_path;
		}
		if( !$this->Install_Ini($ini_folder) ){
			return false;
		}

		return $this->Install_Step3();
	}



	function tempfile(){
		global $dataDir;

		do{
			$tempfile = $dataDir.'/data/_temp/'.rand(1000,9000).'.zip';
		}while(file_exists($tempfile));

		return $tempfile;
	}


	function write_package($dir,&$files){
		global $langmessage;

		if( !gpFiles::CheckDir($dir) ){
			echo '<p class="gp_warning">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],$folder);
			echo '</p>';
			return false;
		}

		//get archive root
		$archive_root = false;
		foreach( $files as $file ){
			if( strpos($file['filename'],'/Addon.ini') !== false ){
				$root = dirname($file['filename']);
				if( !$archive_root || ( strlen($root) < strlen($archive_root) ) ){
					$archive_root = $root;
				}
			}
		}
		$archive_root_len = strlen($archive_root);


		foreach($files as $file_info){

			$filename = $file_info['filename'];

			if( $archive_root ){
				if( strpos($filename,$archive_root) !== 0 ){
					continue;

/*
					trigger_error('$archive_root not in path');
					echo '<p class="gp_warning">';
					echo $langmessage['error_unpacking'];
					echo '</p>';
					return false;
*/
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
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['COULD_NOT_SAVE'],$folder);
				echo '</p>';
				return false;
			}
			if( $file_info['folder'] ){
				continue;
			}
			if( !gpFiles::Save($full_path,$file_info['content']) ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['COULD_NOT_SAVE'],$full_path);
				echo '</p>';
				return false;
			}
		}
		return true;
	}




	/*
	 *
	 *
	 *
	 */

	function GetInstalledComponents($from,$addon){
		$result = array();
		if( !is_array($from) ){
			return $result;
		}

		foreach($from as $name => $info){
			if( !isset($info['addon']) ){
				continue;
			}
			if( $info['addon'] !== $addon ){
				continue;
			}
			$result[] = $name;
		}
		return $result;
	}

	function CleanInstallFolder(){

		$addoncode = $this->addon_folder;
		$folders = gpFiles::readDir($addoncode,1);

		foreach($folders as $folder){
			if( !isset($this->config[$folder]) ){
				$full_path = $addoncode.'/'.$folder;
				gpFiles::RmAll($full_path);
			}
		}
	}

	/**
	 * Get addon data from gpEasy.com and display to user
	 *
	 */
	function RemoteBrowse(){
		global $langmessage, $config, $dataDir;


		//search options
		if( isset($_GET['search_option']) ){
			$save = true;
			switch($_GET['search_option']){
				case 'version':
					unset($config['search_version']);
				break;
				case 'noversion':
					$config['search_version'] = false;
				break;
				default:
					$save = false;
				break;
			}
			if( $save )	admin_tools::SaveConfig();
		}


		//make a list of installed addon id's
		$this->installed_ids = array();
		if( isset($config['addons']) && is_array($config['addons']) ){
			foreach($config['addons'] as $addon_info){
				if( isset($addon_info['id']) ){
					$this->installed_ids[] = $addon_info['id'];
				}
			}
		}

		includeFile('tool/RemoteGet.php');

		$orderby = array();
		$orderby['rating_score']	= $langmessage['Highest Rated'];
		$orderby['downloads']		= $langmessage['Most Downloaded'];
		$orderby['modified']		= $langmessage['Recently Updated'];
		$orderby['created']			= $langmessage['Newest'];

		$_GET += array('q'=>'');
		$this->searchPage = 0;
		if( isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ){
			$this->searchPage = $_REQUEST['page'];
		}

		$this->searchQuery = '';

		//version specific search
		$search_version = false;
		if( !isset($config['search_version']) || $config['search_version'] ){
			$this->searchQuery .= '&ug='.rawurlencode(gpversion);
			$search_version = true;
		}

		if( !empty($_GET['q']) ){
			$this->searchQuery .= '&q='.rawurlencode($_GET['q']);
		}
		if( isset($_GET['order']) && isset($orderby[$_GET['order']]) ){
			$this->searchOrder = $_GET['order'];
			$this->searchQuery .= '&order='.rawurlencode($_GET['order']);
		}else{
			reset($orderby);
			$this->searchOrder = key($orderby);
		}

		$slug = 'Plugins';
		if( $this->config_index == 'themes' ){
			$slug = 'Themes';
		}
		$src = addon_browse_path.'/'.$slug.'?cmd=remote&'.$this->searchQuery.'&page='.$this->searchPage;

		//check cache
		$cache_file = $dataDir.'/data/_remote/'.sha1($src).'.txt';
		$use_cache = false;
		if( file_exists($cache_file) && (filemtime($cache_file)+ 26100) > time() ){
			$result = file_get_contents($cache_file);
			$use_cache = true;
		}else{
			$result = gpRemoteGet::Get_Successful($src);
		}

		if( !$result ){
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (f1)</p>';
			return;
		}
		if( strpos($result,'a:') !== 0 ){
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (f2)</p>';
			return;
		}

		$data = unserialize($result);

		if( count($data) == 0 ){
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (f3)</p>';
			return;
		}

		//save the cache
		if( !$use_cache ){
			gpFiles::Save($cache_file,$result);
		}


		$this->searchMax = $data['max'];
		if( isset($data['per_page']) && $data['per_page'] ){
			$this->searchPerPage = $data['per_page'];
		}else{
			$this->searchPerPage = count($data['rows']);
		}
		$this->searchOffset = $this->searchPage*$this->searchPerPage;

		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo common::Link($this->path_root,$this->manage_label);
		echo ' <span>|</span> ';
		if( !empty($_GET['q']) ){
			echo common::Link($this->path_remote,$this->find_label);
			echo ' &#187; ';
			echo htmlspecialchars($_GET['q']);
		}else{
			echo $this->find_label;
		}
		echo '</h2>';

		echo '<div class="gp_search_options">';
		$this->SearchNavLinks();

		echo '<div class="search_order">';
		foreach($orderby as $key => $label){
			if( $key === $this->searchOrder ){
				echo '<span>'.$label.'</span>';
			}else{
				echo common::Link($this->path_remote,$label,$this->searchQuery.'&order='.$key);
			}
		}
		echo '</div></div>';

		echo '<table class="bordered full_width">';
		echo '<tr><th></th><th>'.$langmessage['name'].'</th><th>'.$langmessage['version'].'</th><th>'.$langmessage['Statistics'].'</th><th>'.$langmessage['description'].'</th></tr>';

		$i = 0;
		if( count($data['rows']) ){
			foreach($data['rows'] as $row){
				echo '<tr class="'.($i % 2 ? 'even' : '').'">';
				echo '<td>';
				$this->DetailLink($row,'<img src="'.$row['icon'].'" height="100" width="100" alt=""/>','',' class="shot"');
				echo '</td>';
				echo '<td class="nowrap">';
				echo '<b>'.$row['name'].'</b>';
				echo '<br/>';
				$this->DetailLink($row);
				echo ' | ';
				$this->InstallLink($row);
				echo '</td><td>';
				echo $row['version'];
				echo '</td><td class="nowrap">';
				echo sprintf($langmessage['_downloads'],number_format($row['downloads']));
				echo '<br/>';
				$this->CurrentRating($row['rating_weighted']);
				echo '<br/>';
				echo $row['rating_count'].' ratings';
				echo '</td><td>';
				echo $row['short_description'];
				//echo showArray($row);
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';
			$this->SearchNavLinks();
		}else{
			echo '</table>';
			echo '<p>'.$langmessage['Sorry, nothing matched'].'</p>';
		}

		echo '<h3>Search Options</h3>';
		echo '<ul>';
		echo '<li>Limit results to addons that are compatible with your version of gpEasy ('.gpversion.') &nbsp; ';

		if( $search_version ){
			echo '<b>'.$langmessage['On'].'</b> &nbsp; ';
			echo common::Link($this->path_remote,$langmessage['Off'],$this->searchQuery.'&search_option=noversion',' name="gpajax"');

		}else{
			echo common::Link($this->path_remote,$langmessage['On'],$this->searchQuery.'&search_option=version',' name="gpajax"');
			echo ' &nbsp;  <b>'.$langmessage['Off'].'</b>';
		}
		echo '</li>';
		echo '</ul>';
	}

	function DetailLink($row,$label = 'Details',$q = '',$attr=''){
		echo '<a href="'.$this->DetailUrl($row,$q).'" name="remote" '.$attr.'>'.$label.'</a>';
	}
	function DetailUrl($row,$q=''){
		$url = 'Themes';
		if( $row['type'] == 'plugin' ){
			$url = 'Plugins';
		}
		return addon_browse_path.'/'.$url.'?id='.$row['id'].$q;
	}

	function FindForm(){
		global $langmessage;

		$_GET += array('q'=>'');

		echo '<div class="gp_find_form">';
		echo '<form action="'.common::GetUrl($this->path_remote).'" method="get">';
		echo '<input type="text" name="q" value="'.htmlspecialchars($_GET['q']).'" size="15" class="gpinput" /> ';
		echo '<input type="submit" name="" value="'.$langmessage['Search'].'" class="gpbutton" />';
		echo '</form>';
		echo '</div>';
	}

	function InstallLink($row){
		global $config,$langmessage;

		$installed = in_array($row['id'],$this->installed_ids);

		if( !$installed && ($row['price_unit'] > 0) ){
			$label = ' Install For $'.$row['price_unit'];
			$this->DetailLink($row,$label,'&amp;cmd=install_info');
			return;
		}

		if( $installed ){
			$label = $langmessage['Update Now'];
		}else{
			$label = $langmessage['Install Now'];
		}

		if( $row['type'] == 'theme' ){
			$url = 'Admin_Theme_Content';
		}else{
			$url = 'Admin_Addons';
		}

		$link = 'cmd=remote_install';
		$link .= '&name='.rawurlencode($row['name']);
		$link .= '&type='.rawurlencode($row['type']);
		$link .= '&id='.rawurlencode($row['id']);

		echo common::Link($url,$label,$link);
	}

	function SearchNavLinks(){
		global $langmessage;

		echo '<div class="search_pages">';
		if( $this->searchPage > 0 ){
			//previous
			if( $this->searchPage > 1 ){
				echo common::Link($this->path_remote,$langmessage['Previous'],$this->searchQuery.'&page='.($this->searchPage-1));
			}else{
				echo common::Link($this->path_remote,$langmessage['Previous'],$this->searchQuery);
			}
		}else{
			echo '<span>'.$langmessage['Previous'].'</span>';
		}


		//always show link for first page
		$start_page = max(0,$this->searchPage-5);
		if( $start_page > 0 ){
			echo common::Link($this->path_remote,'1',$this->searchQuery); //.'&offset=0');
			if( $start_page > 1 ){
				echo '<span>...</span>';
			}
		}

		$pages = ceil($this->searchMax/$this->searchPerPage);
		$max_page = min($start_page + 9,$pages);

		for($j=$start_page;$j<$max_page;$j++){
			$new_offset = ($j*$this->searchPerPage);
			if( $this->searchOffset == $new_offset ){
				echo '<span>'.($j+1).'</span>';
			}else{
				if( $j == 0 ){
					echo common::Link($this->path_remote,($j+1),$this->searchQuery);
				}else{
					echo common::Link($this->path_remote,($j+1),$this->searchQuery.'&page='.($j));
				}
			}
		}

		//always show link to last page
		if( $max_page < $pages ){
			if( ($max_page+1) < $pages ){
				echo '<span>...</span>';
			}
			echo common::Link($this->path_remote,($pages),$this->searchQuery.'&page='.($pages-1));
		}


		$last = $this->searchOffset+$this->searchPerPage;
		if( $last < $this->searchMax ){
			//next
			echo common::Link($this->path_remote,$langmessage['Next'],$this->searchQuery.'&page='.($this->searchPage+1));
		}else{
			echo '<span>'.$langmessage['Next'].'</span>';
		}
		echo '</div>';
	}

	function CurrentRating($rating){

		$width = 16*5;
		$pos = min($width,ceil($width*$rating));
		$pos2 = ($width-ceil($pos));

		echo '<span title="'.number_format(($rating*100),0).'%" class="addon_rating">';
		echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="'.$pos.'" />';
		echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="'.$pos2.'" style="background-position:'.$pos2.'px -16px" />';
		echo '</span> ';
	}
}
