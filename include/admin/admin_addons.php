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


	function admin_addons(){
		global $langmessage,$config,$page;

		$this->find_label = $langmessage['Find Plugins'];
		$this->manage_label = $langmessage['Manage Plugins'];

		$page->css_admin[] = '/include/css/addons.css';
		$page->head_js[] = '/include/js/auto_width.js';
		$page->head_js[] = '/include/js/rate.js';

		$this->InitRating();

		if( !isset($config['admin_links']) ){
			$config['admin_links'] = array();
		}

		if( !isset($config['gadgets']) ){
			$config['gadgets'] = array();
		}

		$this->GetData();
		$cmd = common::GetCommand();


		//
		$display = 'main';
		if( strpos($page->requested,'/') ){
			$parts = explode('/',$page->requested);
			switch(strtolower($parts[1])){
				case 'remote':
					$this->RemoteBrowse();
				return;
			}
		}


		switch($cmd){

			case 'local_install':
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

			case 'show':
				$this->ShowAddon();
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



	/*
	Addon Data
	*/

	function GetData(){
		global $dataDir,$config;

		//new
		if( !isset($config['addons']) ){
			$config['addons'] = array();
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
	function ShowAddon(){
		global $config, $langmessage;

		$addon_key =& $_REQUEST['addon'];
		if( !isset($config['addons'][$addon_key]) ){
			message($langmessage['OOPS'].'(Addon Not Found)');
			$this->Select();
			return;
		}

		$info = $config['addons'][$addon_key];

		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo common::Link('Admin_Addons',$langmessage['Manage Plugins']);
		echo ' &#187; ';
		echo $info['name'];
		echo '</h2>';


		echo '<div id="adminlinks2">';
		$this->PluginPanelGroup($addon_key,$info);
		echo '</div>';
	}



	function GetAvailAddons(){
		global $dataDir;

		$addonPath = $dataDir.'/addons';
		$installed_path  = $dataDir.'/data/_addoncode';


		if( !file_exists($addonPath) ){
			message('Warning: The /addons folder "<em>'.$addonPath.'</em>" does not exist on your server.');
			return array();
		}


		$folders = gpFiles::ReadDir($addonPath,1);
		$avail = array();

		foreach($folders as $key => $value){
			$fullPath = $addonPath .'/'.$key;
			$info = admin_addons_tool::GetAvailInstall($fullPath);

			if( !$info ){
				continue;
			}
			$info['upgrade_key'] = admin_addons_tool::UpgradePath($info);
			$avail[$key] = $info;
		}


		return $avail;
	}


	function Instructions(){
		global $langmessage;

		$lang = 'To install a new addon, you\'ll need to first <a href="http://gpeasy.com/index.php/Special_Addons" target="_blank">download the addon package</a> and unzip/untar the package. Then upload the contents of the uncompressed package to your /addons directory.';
		$lang .= ' Once the code for the addon has been uploaded to your server, <a href="%s">refresh</a> this page and follow the install instructions for the addon.';

		echo '<p>';
		echo sprintf($lang,common::GetUrl('Admin_Addons','cmd=new'));
		echo '</p>';
	}


	/**
	 * Show installed and locally available plugins
	 *
	 */
	function Select(){
		global $langmessage,$config;
		$instructions = true;
		$available = $this->GetAvailAddons();

		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo $langmessage['Manage Plugins'];
		echo ' <span>|</span> ';
		echo common::Link($this->path_remote,$langmessage['Find Plugins']);
		echo '</h2>';


		if( !$this->ShowInstalled($available) ){
			$this->Instructions();
			$instructions = false;
		}


		//show available addons
		echo '<br/>';
		echo '<h2>'.$langmessage['available_plugins'].'</h2>';

		echo '<div class="nodisplay" id="gpeasy_addons"></div>';

		if( count($available) == 0 ){
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
			foreach($available as $folder => $info ){

				if( $info['upgrade_key'] ){
					continue;
				}

				$info += array('About'=>'');

				echo '<tr class="'.($i % 2 ? 'even' : '').'"><td>';
				echo $info['Addon_Name'];
				echo '<br/><em class="admin_note">/addons/'.$folder.'</em>';
				echo '</td><td>';
				echo $info['Addon_Version'];
				echo '</td><td>';
				echo common::Link('Admin_Addons',$langmessage['Install'],'cmd=local_install&source='.$folder,'data-cmd="creq"');
				echo '</td><td>';
				echo $info['About'];
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
	function ShowInstalled(&$available){
		global $config;

		//show installed addons
		$show = $config['addons'];
		if( !is_array($show) ){
			return false;
		}


		//versions available online
		includeFile('tool/update.php');
		update_class::VersionsAndCheckTime($new_versions);

		//set upgrade_from
		foreach($available as $folder => $info){
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


		echo '<div id="adminlinks2">';
		foreach($show as $addon_key => $info){
			$this->PluginPanelGroup($addon_key,$info);
		}
		echo '</div>';

		return true;
	}


	function PluginPanelGroup($addon_key,$info){
		global $config, $langmessage, $gpLayouts;

		$addon_config = gpPlugin::GetAddonConfig($addon_key);

		$addon_config += $info; //merge the upgrade info

		echo '<div class="panelgroup" id="panelgroup_'.md5($addon_key).'">';

		echo '<span class="icon_plug">';
		echo common::Link('Admin_Addons',$addon_config['name'],'cmd=show&addon='.rawurlencode($addon_key));
		echo '</span>';

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
					echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=local_install&source='.$addon_config['upgrade_from'],'data-cmd="creq"');
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
		if( isset($addon_config['id']) && isset($new_versions[$addon_config['id']]) ){
			$version_info = $new_versions[$addon_config['id']];
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
				echo common::Link('Admin_Addons',$label,'cmd=local_install&source='.$addon_config['upgrade_from'],'data-cmd="creq"');
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

		$_REQUEST += array('source'=>'','mode'=>'');

		includeFile('admin/admin_addon_installer.php');
		$installer = new admin_addon_installer();
		$installer->source = $dataDir.'/addons/'.$_REQUEST['source'];
		$installer->Install();
		$installer->OutputMessages();
	}
}