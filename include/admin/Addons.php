<?php

namespace gp\admin;

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


class Addons extends \gp\admin\Addon\Install{

	public $dataFile;


	function __construct( $args ){
		global $langmessage;

		parent::__construct($args);

		$this->InitRating();
		$this->GetData();

		$this->page->head_js[]		= '/include/js/auto_width.js';
		$this->avail_addons			= $this->GetAvailAddons();

	}

	function RunScript(){

		$cmd = \gp\tool::GetCommand();
		switch($cmd){

			case 'LocalInstall':
				$this->LocalInstall();
			break;

			case 'remote_install':
			case 'RemoteInstall':
				$this->RemoteInstall();
			return;
			case 'RemoteInstallConfirmed':
				$this->RemoteInstallConfirmed();
			break;


			case 'SendAddonReview':
			case 'ReviewAddonForm':
				$this->AdminAddonRating();
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
		}



		//single addon
		$request_parts = explode('/',$this->page->requested);
		if( count($request_parts) > 2 ){
			$this->ShowAddon($request_parts[2]);
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
			$addon_config = \gp\tool\Plugins::GetAddonConfig($addon_key);
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
			\gp\tool\Files::RmAll($full_path);
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
		global $config, $langmessage;

		$this->page->ajaxReplace = array();
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

		if( !\gp\admin\Tools::SaveConfig(true) ){
			return;
		}

		$link = $this->GadgetLink($gadget);
		$this->page->ajaxReplace[] = array('replace','.gadget_link_'.md5($gadget),$link);
	}

	function GadgetLink($name){
		global $config, $langmessage;
		$info =& $config['gadgets'][$name];
		if( !$info ){
			return '';
		}

		if( isset($info['disabled']) ){
			return \gp\tool::Link('Admin/Addons',str_replace('_',' ',$name).' ('.$langmessage['disabled'].')','cmd=enable&addon='.rawurlencode($info['addon']).'&gadget='.rawurlencode($name),'data-cmd="gpajax" class="gadget_link_'.md5($name).'"');
		}else{
			return \gp\tool::Link('Admin/Addons',str_replace('_',' ',$name) .' ('.$langmessage['enabled'].')','cmd=disable&addon='.rawurlencode($info['addon']).'&gadget='.rawurlencode($name),'data-cmd="gpajax" class="gadget_link_'.md5($name).'"');
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
		echo '<form action="'.\gp\tool::GetUrl('Admin/Addons').'" method="post">';

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

		$installer = new \gp\admin\Addon\Installer();
		$installer->Uninstall($addon);
		$installer->OutputMessages();
	}


	/**
	 * Display addon details
	 *
	 */
	function ShowAddon($encoded_key){
		global $config, $langmessage;

		$addon_key	= \gp\admin\Tools::decode64($encoded_key);

		if( !isset($config['addons'][$addon_key]) ){
			message($langmessage['OOPS'].'(Addon Not Found)');
			$this->Select();
			return;
		}

		$show						= $this->GetDisplayInfo();
		$info						= $show[$addon_key];


		$this->ShowHeader($info['name']);


		$this->UpgradeLinks($info);

		//about
		if( !empty($info['About']) ){
			echo '<hr/>';
			echo '<div class="lead">';
			echo $info['About'];
			echo '</div>';
			echo '<hr/><br/>';
		}


		echo '<div id="adminlinks2">';

		$format = array();
		$format['end']		= '</div></div>';
		$format['start']	= '<div class="panelgroup"><h3>%s</h3><div class="panelgroup2">';

		$this->AddonPanel_Special($addon_key,$format);
		$this->AddonPanel_Admin($addon_key,$format);
		$this->AddonPanel_Gadget($addon_key,$format);
		$this->AddonPanel_Hooks($addon_key, $format);
		$this->OptionLinks($addon_key, $info, $format);



		echo '</div>';

	}


	/**
	 * Get a list of available addons
	 *
	 */
	function GetAvailAddons(){
		global $dataDir;

		$addonPath			= $dataDir.'/addons';

		if( !file_exists($addonPath) ){
			message('Warning: The /addons folder "<em>'.$addonPath.'</em>" does not exist on your server.');
			return array();
		}


		$folders			= \gp\tool\Files::ReadDir($addonPath,1);
		$versions			= array();
		$avail				= array();

		foreach($folders as $value){
			$fullPath		= $addonPath .'/'.$value;
			$info			= $this->GetAvailInstall($fullPath);

			if( !$info ){
				continue;
			}


			$info['source_folder']		= $addonPath .'/'. $value;
			$info['upgrade_key']		= \gp\admin\Addon\Tools::UpgradePath($info);
			$avail[$value]				= $info;

			if( isset($info['Addon_Version']) && isset($info['Addon_Unique_ID']) ){

				$id = $info['Addon_Unique_ID'];
				if( !isset($versions[$id]) ){
					$versions[$id] = $info['Addon_Version'];
				}elseif( version_compare($versions[$id],$info['Addon_Version'],'<') ){
					$versions[$id] = $info['Addon_Version'];
					continue;
				}
			}


			if( !$info['upgrade_key'] ){
				$this->avail_count++;
			}
		}

		if( gp_unique_addons ){
			$avail = self::FilterUnique($avail, $versions);
		}

		return $avail;
	}


	/**
	 * Filter the list of addons so we only have a list of the most recent versions
	 *
	 */
	protected function FilterUnique($addons, $versions){

		$temp = array();
		foreach($addons as $key => $info){

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
		echo '<hr/>';
		echo '<a href="'.CMS_DOMAIN.'/Docs/Plugins">Plugin Documentation</a>';
	}


	/**
	 * Show installed and locally available plugins
	 *
	 */
	function Select(){
		$this->ShowHeader();
		$this->ShowInstalled();
		$this->Instructions();
	}

	function ShowAvailable(){
		global $langmessage;

		$this->ShowHeader();

		echo '<div class="nodisplay" id="gpeasy_addons"></div>';

		if( count($this->avail_addons) == 0 ){
			//echo ' -empty- ';
		}else{
			echo '<table class="bordered full_width">';
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
				echo \gp\tool::Link('Admin/Addons',$langmessage['Install'],'cmd=LocalInstall&source='.$folder, array('data-cmd'=>'cnreq'));
				echo '</td><td>';
				echo $info['About'];
				if( isset($info['Addon_Unique_ID']) && is_numeric($info['Addon_Unique_ID']) ){
					echo '<br/>';
					echo $this->DetailLink('plugins', $info['Addon_Unique_ID'],'More Info...');
				}
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';

		}

		$this->InvalidFolders();
		$this->Instructions();

	}


	/**
	 * Show installed addons
	 *
	 */
	function ShowInstalled(){

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


			if( !isset($info['Addon_Version']) ){
				$show[$upgrade_key]['upgrade_from'] = $folder;
				continue;
			}

			if( !isset($show[$upgrade_key]['upgrade_version']) || version_compare($show[$upgrade_key]['upgrade_version'], $info['Addon_Version'], '<') ){
				$show[$upgrade_key]['upgrade_from']		= $folder;
				$show[$upgrade_key]['upgrade_version']	= $info['Addon_Version'];
			}

		}

		return $show;
	}


	function PluginPanelGroup($addon_key,$info){
		global $langmessage, $gpLayouts;

		$addon_config = \gp\tool\Plugins::GetAddonConfig($addon_key);

		$addon_config += $info; //merge the upgrade info

		echo '<div class="panelgroup" id="panelgroup_'.md5($addon_key).'">';

		echo '<h3>';
		echo \gp\tool::Link('Admin/Addons/'.\gp\admin\Tools::encode64($addon_key),$addon_config['name']);
		echo '</h3>';

		echo '<div class="panelgroup2">';
		echo '<ul class="submenu">';
		$this->AddonPanelGroup($addon_key);
		$this->OptionLinks($addon_key, $addon_config);
		echo '</ul>';

		$this->UpgradeLinks($addon_config);

		echo '</div>';
		echo '</div>';
	}


	/**
	 * Plugin Upgrade links
	 *
	 */
	function UpgradeLinks($addon_config){
		global $langmessage;

		//upgrade local
		if( isset($addon_config['upgrade_from']) && isset($addon_config['upgrade_version']) ){
			if(version_compare($addon_config['upgrade_version'],$addon_config['version'] ,'>') ){
				echo '<div class="gp_notice">';
				$label = $langmessage['new_version'].' &nbsp; '.$addon_config['upgrade_version'];
				echo '<a href="?cmd=LocalInstall&source='.rawurlencode($addon_config['upgrade_from']).'" data-cmd="cnreq">'.$label.'</a>';
				echo '</div>';
			}
		}

		//upgrade cms
		if( isset($addon_config['id']) && isset(\gp\admin\Tools::$new_versions[$addon_config['id']]) ){

			$new_version = \gp\admin\Tools::$new_versions[$addon_config['id']];

			if( version_compare($new_version['version'],$addon_config['version'],'>') ){

				echo '<div class="gp_notice">';
				echo '<a href="'.addon_browse_path.'/Plugins?id='.$addon_config['id'].'" data-cmd="remote">';
				echo $langmessage['new_version'];
				echo ' &nbsp; '.$new_version['version'].' ('.CMS_READABLE_DOMAIN.')</a>';
				echo '</div>';
			}
		}

	}


	/**
	 * Plugin option links
	 *
	 */
	function OptionLinks($addon_key, $addon_config, $format = false){
		global $langmessage, $gpLayouts;

		$list	= array();

		if( !isset($addon_config['is_theme']) || !$addon_config['is_theme'] ){

			//editable text
			if( isset($addon_config['editable_text']) && \gp\admin\Tools::HasPermission('Admin_Theme_Content') ){
				$list[] = \gp\tool::Link('Admin_Theme_Content/Text',$langmessage['editable_text'],'cmd=AddonTextForm&addon='.urlencode($addon_key),array('title'=>urlencode($langmessage['editable_text']),'data-cmd'=>'gpabox'));
			}

			//upgrade link
			if( isset($addon_config['upgrade_from']) ){
				$list[] = '<a href="?cmd=LocalInstall&source='.rawurlencode($addon_config['upgrade_from']).'" data-cmd="cnreq">'.$langmessage['upgrade'].'</a>';
			}

			//uninstall
			$list[] = \gp\tool::Link('Admin/Addons',$langmessage['uninstall'],'cmd=uninstall&addon='.rawurlencode($addon_key),'data-cmd="gpabox"');


			//version
			if( !empty($addon_config['version']) ){
				$list[] = '<a>'.$langmessage['Your_version'].' '.$addon_config['version']. '</a>';
			}

			//rating
			if( isset($addon_config['id']) && is_numeric($addon_config['id']) ){
				$id = $addon_config['id'];

				$rating = 5;
				if( isset($this->addonReviews[$id]) ){
					$rating = $this->addonReviews[$id]['rating'];
				}
				$label = $langmessage['rate_this_addon'].' '.$this->ShowRating($id,$rating);
				$list[] = '<span>'.$label. '</span>';
			}

			echo $this->FormatList($list,$langmessage['options'],$format);
			return;
		}

		//show list of themes using these addons
		foreach($gpLayouts as $layout_id => $layout_info){
			if( !isset($layout_info['addon_key']) || $layout_info['addon_key'] !== $addon_key ){
				continue;
			}

			$item = '<span><span class="layout_color_id" style="background:'.$layout_info['color'].'"></span> ';
			$item .= \gp\tool::Link('Admin_Theme_Content',$layout_info['label']);
			$item .= ' ( ';
			$item .= \gp\tool::Link('Admin_Theme_Content/Edit/'.$layout_id,$langmessage['edit']);
			$item .= ' )</span>';

			$list[] = $item;
		}

		echo $this->FormatList($list,$langmessage['layouts'],$format);
	}


	/**
	 * Install Local Packages
	 *
	 */
	function LocalInstall(){
		global $dataDir, $langmessage;

		$_REQUEST				+= array('source'=>'');

		if( !isset($this->avail_addons[$_REQUEST['source']]) ){
			message($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}


		$installer				= new \gp\admin\Addon\Installer();
		$installer->source		= $this->avail_addons[$_REQUEST['source']]['source_folder'];

		$installer->Install();
		$installer->OutputMessages();
	}

}