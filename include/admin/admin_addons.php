<?php
defined('is_running') or die('Not an entry point...');


/**
 * Admin Plugin
 * 		/addons/<addon>/Addon.ini
 * 			- Addon_Name (required)
 * 			- link definitions: (optional)
 * 				- should be able to have multiple links,
 *
 *
 * 				- link_name (required)
 * 					an example link: Admin_<linkname>
 * 				- labels (required) should pull the language values during installation/upgrading
 * 				- script (required)
 * 				- class / function to call once opened (optional)
 *
 * 			- minimum version / max version
 * 		/addons/<addon>/<php files>
 */


includeFile('admin/admin_addon_install.php');
//includeFile('admin/admin_theme_content.php');

class admin_addons extends admin_addon_install{

	var $dataFile;
	var $avail_addons;


	function __construct(){
		global $langmessage,$config,$page;

		//header links
		$this->header_paths = array(
			'Admin_Addons'			=> $langmessage['Manage Plugins'],
			);

		if( gp_remote_plugins ){
			$this->header_paths['Admin_Addons/Remote'] = $langmessage['Find Plugins'];
		}



		parent::__construct();
		$this->InitRating();
		$this->GetData();

		$page->head_js[]		= '/include/js/auto_width.js';
		$this->avail_addons		= $this->GetAvailAddons();


		//single addon
		if( strpos($page->requested,'/') ){
			$request_parts = explode('/',$page->requested);
			switch(strtolower($request_parts[1])){
				case 'remote':
					if( gp_remote_plugins ){
						$this->RemoteBrowse();
					}
				return;
				default:
					$this->ShowAddon($request_parts[1]);
				return;

			}
		}


		$cmd = common::GetCommand();
		switch($cmd){

			case 'LocalInstall':
				$this->LocalInstall();
			break;

			case 'remote_install':
				$this->RemoteInstall();
			return;
			case 'remote_install_confirmed':
				$this->RemoteInstallConfirmed();
			break;


			case 'Update Review';
			case 'Send Review':
			case 'rate':
				$this->admin_addon_rating('plugin','Admin_Addons');
				if( $this->ShowRatingText ){
					return;
				}
			break;

			case 'enable':
			case 'disable':
				$this->GadgetVisibility($cmd);
			return;

			case 'uninstall':
				$this->Uninstall();
			return;

			case 'confirm_uninstall':
				$this->Confirm_Uninstall();
			break;

			case 'history':
				$this->History();
			return;
		}

		$this->Select();
		$this->CleanAddonFolder();
	}


	/**
	 * Remove unused code folders created by incomplete addon installations
	 *
	 */
	function CleanAddonFolder(){
		global $config;


		//get a list of all folders
		$folder = '/data/_addoncode';
		$code_folders = $this->GetCleanFolders($folder);
		$folder = '/data/_addondata';
		$data_folders = $this->GetCleanFolders($folder);

		//check against folders used by addons
		$addons = $config['addons'];
		foreach($addons as $addon_key => $info){
			$addon_config = gpPlugin::GetAddonConfig($addon_key);
			if( array_key_exists($addon_config['code_folder_part'],$code_folders) ){
				$code_folders[$addon_config['code_folder_part']] = false;
			}
			if( array_key_exists($addon_config['data_folder_part'],$data_folders) ){
				$data_folders[$addon_config['data_folder_part']] = false;
			}
		}

		//remove unused folders
		$folders = array_filter($code_folders) + array_filter($data_folders);
		foreach($folders as $folder => $full_path){
			gpFiles::RmAll($full_path);
		}

	}


	/**
	 * Get a list of folders within $dir that
	 *
	 */
	function GetCleanFolders($relative){
		global $dataDir;

		$dir = $dataDir.$relative;
		$folders = array();

		if( file_exists($dir) ){
			$files = scandir($dir);
			foreach($files as $file){
				if( $file == '.' || $file == '..' ){
					continue;
				}
				$full_path = $dir.'/'.$file;
				if( !is_dir($full_path) ){
					continue;
				}
				$mtime = filemtime($full_path);
				$diff = time() - $mtime;
				if( $diff < 3600 ){
					continue;
				}
				$folders[$relative.'/'.$file] = $full_path;
			}
		}
		return $folders;
	}



	function GadgetVisibility($cmd){
		global $config, $langmessage, $page;

		$page->ajaxReplace = array();
		$gadget = $_GET['gadget'];

		if( !isset($config['gadgets']) || !is_array($config['gadgets']) || !isset($config['gadgets'][$gadget]) ){
			message($langmessage['OOPS'].' (Invalid Gadget)');
			return;
		}

		$gadgetInfo =& $config['gadgets'][$gadget];

		switch($cmd){
			case 'enable':
				unset($gadgetInfo['disabled']);
			break;
			case 'disable':
				$gadgetInfo['disabled']	= true;
			break;

		}

		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS'].' (Not Saved)');
			return;
		}

		$link = $this->GadgetLink($gadget);
		$page->ajaxReplace[] = array('replace','.gadget_link_'.$gadget,$link);
	}

	function GadgetLink($name){
		global $config, $langmessage;
		$info =& $config['gadgets'][$name];
		if( !$info ){
			return '';
		}

		if( isset($info['disabled']) ){
			return common::Link('Admin_Addons',str_replace('_',' ',$name).' ('.$langmessage['disabled'].')','cmd=enable&addon='.rawurlencode($info['addon']).'&gadget='.rawurlencode($name),'data-cmd="gpajax" class="gadget_link_'.$name.'"');
		}else{
			return common::Link('Admin_Addons',str_replace('_',' ',$name) .' ('.$langmessage['enabled'].')','cmd=disable&addon='.rawurlencode($info['addon']).'&gadget='.rawurlencode($name),'data-cmd="gpajax" class="gadget_link_'.$name.'"');
		}

	}


	/**
	 * Addon Data
	 *
	 */
	function GetData(){
		global $dataDir,$config;

		//new
		if( !isset($config['addons']) ){
			$config['addons'] = array();
		}

		if( !isset($config['admin_links']) ){
			$config['admin_links'] = array();
		}

		if( !isset($config['gadgets']) ){
			$config['gadgets'] = array();
		}


		//fix data
		$firstValue = current($config['addons']);
		if( is_string($firstValue) ){

			foreach($config['addons'] as $addon => $addonName){
				$config['addons'][$addon] = array();
				$config['addons'][$addon]['name'] = $addonName;
			}
		}
	}


	/**
	 * Prompt User about uninstalling an addon
	 */
	function Uninstall(){
		global $config,$langmessage;

		echo '<div class="inline_box">';
		echo '<h3>'.$langmessage['uninstall'].'</h3>';
		echo '<form action="'.common::GetUrl('Admin_Addons').'" method="post">';

		$addon =& $_REQUEST['addon'];
		if( !isset($config['addons'][$addon]) ){
			echo $langmessage['OOPS'];
			echo '<p>';
			echo ' <input type="submit" value="'.$langmessage['Close'].'" class="admin_box_close" /> ';
			echo '</p>';

		}else{

			echo $langmessage['confirm_uninstall'];

			echo '<p>';
			echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />';
			echo '<input type="hidden" name="cmd" value="confirm_uninstall" />';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="gpsubmit"/> ';
			echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
			echo '</p>';
		}


		echo '</form>';
		echo '</div>';
	}

	function Confirm_Uninstall(){

		$addon =& $_POST['addon'];

		includeFile('admin/admin_addon_installer.php');
		$installer = new admin_addon_installer();
		$installer->Uninstall($addon);
		$installer->OutputMessages();
	}


	/**
	 * Display addon details
	 *
	 */
	function ShowAddon($encoded_key){
		global $config, $langmessage;

		$addon_key	= admin_tools::decode64($encoded_key);

		if( !isset($config['addons'][$addon_key]) ){
			message($langmessage['OOPS'].'(Addon Not Found)');
			$this->Select();
			return;
		}


		//commands
		$cmd = common::GetCommand();
		switch($cmd){
			case 'LocalInstall':
				$this->LocalInstall();
			break;
		}


		$show						= $this->GetDisplayInfo();
		$info						= $show[$addon_key];


		$this->ShowHeader($info['name']);

		if( !empty($info['About']) ){
			echo '<p style="font-size:20px">';
			echo $info['About'];
			echo '</p>';
		}


		echo '<div id="adminlinks2">';
		$this->PluginPanelGroup($addon_key,$info);
		echo '</div>';
	}


	/**
	 * Get a list of available addons
	 *
	 */
	function GetAvailAddons(){
		global $dataDir;

		$addonPath = $dataDir.'/addons';
		$installed_path  = $dataDir.'/data/_addoncode';


		if( !file_exists($addonPath) ){
			message('Warning: The /addons folder "<em>'.$addonPath.'</em>" does not exist on your server.');
			return array();
		}


		$folders = gpFiles::ReadDir($addonPath,1);
		$versions = $avail = array();

		foreach($folders as $key => $value){
			$fullPath = $addonPath .'/'.$key;
			$info = admin_addons_tool::GetAvailInstall($fullPath);

			if( !$info ){
				continue;
			}
			$info['upgrade_key'] = admin_addons_tool::UpgradePath($info);
			$avail[$key] = $info;


			if( isset($info['Addon_Version']) && isset($info['Addon_Unique_ID']) ){
				$id = $info['Addon_Unique_ID'];
				$version = $info['Addon_Version'];
				if( !isset($versions[$id]) ){
					$versions[$id] = $version;
					continue;
				}
				if( version_compare($versions[$id],$version,'<') ){
					$versions[$id] = $version;
				}
			}
		}

		if( !gp_unique_addons ){
			return $avail;
		}

		//show only the most recent versions
		$temp = array();
		foreach($avail as $key => $info){

			if( !isset($info['Addon_Version']) || !isset($info['Addon_Unique_ID']) ){
				$temp[$key] = $info;
				continue;
			}

			$id = $info['Addon_Unique_ID'];
			$version = $info['Addon_Version'];

			if( version_compare($versions[$id], $version,'>') ){
				continue;
			}

			$temp[$key] = $info;
		}


		return $temp;
	}


	function Instructions(){
		echo '<a href="http://gpeasy.org/Docs/Plugins">Plugin Documentation</a>';
	}


	/**
	 * Show installed and locally available plugins
	 *
	 */
	function Select(){
		global $langmessage,$config;
		$instructions = true;

		$this->ShowHeader();

		if( !$this->ShowInstalled() ){
			$this->Instructions();
			$instructions = false;
		}


		//show available addons
		echo '<br/>';
		echo '<h2>'.$langmessage['available_plugins'].'</h2>';

		echo '<div class="nodisplay" id="gpeasy_addons"></div>';

		if( count($this->avail_addons) == 0 ){
			//echo ' -empty- ';
		}else{
			echo '<table class="bordered" style="min-width:700px">';
			echo '<tr><th>';
			echo $langmessage['name'];
			echo '</th><th>';
			echo $langmessage['version'];
			echo '</th><th>';
			echo $langmessage['options'];
			echo '</th><th>';
			echo $langmessage['description'];
			echo '</th></tr>';

			$i=0;
			foreach($this->avail_addons as $folder => $info ){

				if( $info['upgrade_key'] ){
					continue;
				}

				$info += array('About'=>'');

				echo '<tr class="'.($i % 2 ? 'even' : '').'"><td>';
				echo str_replace(' ','&nbsp;',$info['Addon_Name']);
				echo '<br/><em class="admin_note">/addons/'.$folder.'</em>';
				echo '</td><td>';
				echo $info['Addon_Version'];
				echo '</td><td>';
				echo common::Link('Admin_Addons',$langmessage['Install'],'cmd=LocalInstall&source='.$folder, array('data-cmd'=>'creq'));
				echo '</td><td>';
				echo $info['About'];
				if( isset($info['Addon_Unique_ID']) && is_numeric($info['Addon_Unique_ID']) ){
					echo '<br/>';
					echo $this->DetailLink('plugin', $info['Addon_Unique_ID'],'More Info...');
				}
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';

		}


		if( $instructions ){
			echo '<h3>'.$langmessage['about'].'</h3>';
			$this->Instructions();
		}

	}


	/**
	 * Show installed addons
	 *
	 */
	function ShowInstalled(){
		global $config;

		$show = $this->GetDisplayInfo();

		echo '<div id="adminlinks2">';
		foreach($show as $addon_key => $info){
			$this->PluginPanelGroup($addon_key,$info);
		}
		echo '</div>';

		return true;
	}


	/**
	 * Get addon configuration along with upgrade info
	 *
	 */
	function GetDisplayInfo(){
		global $config;

		//show installed addons
		$show = $config['addons'];
		if( !is_array($show) ){
			return array();
		}


		//set upgrade_from
		foreach($this->avail_addons as $folder => $info){
			if( !$info['upgrade_key'] ){
				continue;
			}

			$upgrade_key = $info['upgrade_key'];
			if( !isset($show[$upgrade_key]) ){
				continue;
			}
			$show[$upgrade_key]['upgrade_from'] = $folder;
			if( isset($info['Addon_Version']) ){
				$show[$upgrade_key]['upgrade_version'] = $info['Addon_Version'];
			}
		}

		return $show;
	}


	function PluginPanelGroup($addon_key,$info){
		global $config, $langmessage, $gpLayouts;

		$addon_config = gpPlugin::GetAddonConfig($addon_key);

		$addon_config += $info; //merge the upgrade info

		echo '<div class="panelgroup" id="panelgroup_'.md5($addon_key).'">';

		$label = '<i class="gpicon_plug"></i>'.$addon_config['name'];
		echo common::Link('Admin_Addons/'.admin_tools::encode64($addon_key),$label);

		echo '<div class="panelgroup2">';
		echo '<ul class="submenu">';


		$this->AddonPanelGroup($addon_key, $addon_config);


		//options
		if( !isset($addon_config['is_theme']) || !$addon_config['is_theme'] ){
			echo '<li class="expand_child_click">';
			echo '<a>'.$langmessage['options'].'</a>';
			echo '<ul>';

				//editable text
				if( isset($config['addons'][$addon_key]['editable_text']) && admin_tools::HasPermission('Admin_Theme_Content') ){
					echo '<li>';
					echo common::Link('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addon_key),array('title'=>urlencode($langmessage['editable_text']),'data-cmd'=>'gpabox'));
					echo '</li>';
				}

				//upgrade link
				if( isset($addon_config['upgrade_from']) ){
					echo '<li>';
					//echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=LocalInstall&source='.$addon_config['upgrade_from'],array('data-cmd'=>'creq'));
					echo '<a href="?cmd=LocalInstall&source='.rawurlencode($addon_config['upgrade_from']).'" data-cmd="creq">'.$langmessage['upgrade'].'</a>';

					echo '</li>';
				}

				//uninstall
				echo '<li>';
				echo common::Link('Admin_Addons',$langmessage['uninstall'],'cmd=uninstall&addon='.rawurlencode($addon_key),'data-cmd="gpabox"');
				echo '</li>';


				//version
				if( !empty($addon_config['version']) ){
					echo '<li><a>'.$langmessage['Your_version'].' '.$addon_config['version']. '</a></li>';
				}

				//rating
				if( isset($addon_config['id']) && is_numeric($addon_config['id']) ){
					$id = $addon_config['id'];

					$rating = 5;
					if( isset($this->addonReviews[$id]) ){
						$rating = $this->addonReviews[$id]['rating'];
					}
					$label = $langmessage['rate_this_addon'].' '.$this->ShowRating($id,$rating);
					echo '<li><span>'.$label. '</span></li>';
				}
			echo '</ul></li>';
		}else{

			//show list of themes using these addons
			echo '<li class="expand_child_click">';
			echo '<a>'.$langmessage['layouts'].'</a>';
			echo '<ul>';
			foreach($gpLayouts as $layout_id => $layout_info){
				if( !isset($layout_info['addon_key']) || $layout_info['addon_key'] !== $addon_key ){
					continue;
				}
				echo '<li>';
				echo '<span>';
				echo '<span class="layout_color_id" style="background:'.$layout_info['color'].'"></span> ';
				echo common::Link('Admin_Theme_Content',$layout_info['label']);
				echo ' ( ';
				echo common::Link('Admin_Theme_Content/'.$layout_id,$langmessage['edit']);
				echo ' )';
				echo '</span>';

				//echo '<a>';
				//echo $layout_info['label'];
				//echo '</a>';
				//echo pre($layout_info);
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
		}

		echo '</ul>';

		//upgrade gpeasy.com
		if( isset($addon_config['id']) && isset(admin_tools::$new_versions[$addon_config['id']]) ){
			$version_info = admin_tools::$new_versions[$addon_config['id']];
			echo '<div class="gp_notice">';
			echo '<a href="'.addon_browse_path.'/Plugins?id='.$addon_config['id'].'" data-cmd="remote">';
			echo $langmessage['new_version'];
			echo ' &nbsp; '.$version_info['version'].' (gpEasy.com)</a>';
			echo '</div>';
		}

		//upgrade local
		if( isset($addon_config['upgrade_from']) && isset($addon_config['upgrade_version']) ){
			if(version_compare($addon_config['upgrade_version'],$addon_config['version'] ,'>') ){
				echo '<div class="gp_notice">';
				$label = $langmessage['new_version'].' &nbsp; '.$addon_config['upgrade_version'];
				echo '<a href="?cmd=LocalInstall&source='.rawurlencode($addon_config['upgrade_from']).'" data-cmd="creq">'.$label.'</a>';
				//echo common::Link('Admin_Addons',$label,'cmd=LocalInstall&source='.$addon_config['upgrade_from'],array('data-cmd'=>'creq'));
				echo '</div>';
			}
		}


		echo '</div>';
		echo '</div>';
	}


	/**
	 * Install Local Packages
	 *
	 */
	function LocalInstall(){
		global $dataDir;

		includeFile('admin/admin_addon_installer.php');

		$_REQUEST				+= array('source'=>'');

		if( (strpos($_REQUEST['source'],'/') !== false ) || (strpos($_REQUEST['source'],'\\') !== false) ){
			message($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}


		$installer				= new admin_addon_installer();
		$installer->source		= $dataDir.'/addons/'.$_REQUEST['source'];

		$installer->Install();
		$installer->OutputMessages();
	}

}