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
 *
 *
 *
 *
 *
 *
 */


includeFile('admin/admin_addon_install.php'); // admin_addon_install extends admin_addon_tool

class admin_addons extends admin_addon_install{

	var $dataFile;
	var $develop = false;


	function admin_addons(){
		global $langmessage,$config,$page;


		$this->find_label = $langmessage['Find Plugins'];
		$this->manage_label = $langmessage['Manage Plugins'];

		$page->css_admin[] = '/include/css/addons.css';

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

			/* testing */
			case 'package':
				includeFile('admin/x_admin_addon_package.php');
				new addon_package();
			break;

			case 'develop':
				$this->Develop();
			break;

			case 'local_install':
				$this->LocalInstall();
				$this->Select();
			break;

			case 'remote_install':
				$this->RemoteInstall($cmd);
				$this->Select();
			break;


			case 'Update Review';
			case 'Send Review':
			case 'rate':
				$this->admin_addon_rating('plugin','Admin_Addons');
				if( $this->ShowRatingText ){
					return;
				}
				$this->Select();
			break;

			case 'enable':
			case 'disable':
			case 'show':
				$this->ShowAddon();
			break;

			case 'uninstall':
				$this->Uninstall();
			break;

			case 'confirm_uninstall':
				$this->Confirm_Uninstall();
				$this->Select();
			break;

			case 'history':
				$this->History();
			break;

			default:
				$this->Select();
			break;
		}
	}



	function GadgetVisibility($addon,$cmd){
		global $config,$langmessage;

		$gadget = $_GET['gadget'];

		if( !isset($config['gadgets']) || !is_array($config['gadgets']) || !isset($config['gadgets'][$gadget]) ){
			message($langmessage['OOPS']);
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
			message($langmessage['OOPS']);
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
		global $config, $langmessage, $dataDir, $gp_titles, $gp_menu, $gp_index;

		$addon =& $_POST['addon'];
		$addon_config = gpPlugin::GetAddonConfig($addon);
		if( !$addon_config ){
			message($langmessage['OOPS']);
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
			message($langmessage['OOPS']);
			$this->Uninstall();
			return false;
		}


		/*
		 * Delete the data folders
		 */
		$installFolder = $addon_config['code_folder_full'];
		if( file_exists($installFolder) ){
			gpFiles::RmAll($installFolder);
		}


		$dataFolder = $addon_config['data_folder_full'];
		if( file_exists($dataFolder) ){
			gpFiles::RmAll($dataFolder);
		}

		/*
		 * Record the history
		 */
		$history['time'] = time();
		$this->addonHistory[] = $history;
		$this->SaveAddonData();
		if( $order ){
			$img_path = common::IdUrl('ci');
			common::IdReq($img_path);
		}


		message($langmessage['SAVED']);
	}


	function RemoveHooks(){
		global $config;
		if( !isset($config['hooks']) ){
			return;
		}

		foreach($config['hooks'] as $hook_name => $hook_array){

			foreach($hook_array as $hook_dir => $hook_args){

				//not cleaning other addons
				if( $hook_dir != $addonDir ){
					continue;
				}

				unset($config['hooks'][$hook_name][$hook_dir ]);
			}
		}

		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
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


	function ShowAddon($addon=false){
		global $config, $langmessage;

		if( $addon === false ){
			$addon =& $_REQUEST['addon'];
		}
		if( !isset($config['addons'][$addon]) ){
			message($langmessage['OOPS'].'(s1)');
			$this->Select();
			return;
		}

		$cmd = common::GetCommand();
		switch( $cmd ){
			case 'enable':
			case 'disable':
				$this->GadgetVisibility($addon,$cmd);
			break;
		}

		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo common::Link('Admin_Addons',$langmessage['Manage Plugins']);
		echo ' &#187; ';
		echo $config['addons'][$addon]['name'];
		echo '</h2>';

		echo '<table class="bordered" style="width:90%">';

		//show Special Links
			$sublinks = admin_tools::GetAddonTitles( $addon);
			if( !empty($sublinks) ){
				echo '<tr><th>';
				echo 'Special Links';
				echo '</th><th>';
				echo $langmessage['options'];
				echo '</th></tr>';

				foreach($sublinks as $linkName => $linkInfo){
					echo '<tr><td>';
					echo common::Link($linkName,$linkInfo['label']);
					echo '</td><td>-</td></tr>';
				}
			}

		//show Admin Links
			$sublinks = admin_tools::GetAddonComponents($config['admin_links'],$addon);
			if( !empty($sublinks) ){
				echo '<tr><th>';
				echo 'Admin Links';
				echo '</th>';
				echo '<th>';
				echo $langmessage['options'];
				echo '</th></tr>';

				foreach($sublinks as $linkName => $linkInfo){
					echo '<tr><td>';
						echo common::Link($linkName,$linkInfo['label']);
						echo '</td>';
						echo '<td>';
						echo '-';
						echo '</td></tr>';
				}
			}


		//show Gadgets
			$gadgets = admin_tools::GetAddonComponents($config['gadgets'],$addon);
			if( is_array($gadgets) && (count($gadgets) > 0) ){
				echo '<tr><th>';
				echo $langmessage['gadgets'];
				echo '</th><th>';
				echo $langmessage['options'];
				echo '</th></tr>';

				foreach($gadgets as $name => $value){
					echo '<tr><td>';
					echo str_replace('_',' ',$name);
					echo '</td><td>';
					if( isset($value['disabled']) ){
						echo common::Link('Admin_Addons',$langmessage['enable'],'cmd=enable&addon='.rawurlencode($addon).'&gadget='.rawurlencode($name),'data-cmd="creq"');
						echo ' - ';
						echo '<b>'.$langmessage['disabled'].'</b>';
					}else{
						echo ' <b>'.$langmessage['enabled'].'</b>';
						echo ' - ';
						echo common::Link('Admin_Addons',$langmessage['disable'],'cmd=disable&addon='.rawurlencode($addon).'&gadget='.rawurlencode($name),'data-cmd="creq"');
					}
					echo '</td></tr>';
				}
			}

		//editable text
		if( isset($config['addons'][$addon]['editable_text']) && admin_tools::HasPermission('Admin_Theme_Content') ){
				echo '<tr><th>';
					echo $langmessage['editable_text'];
					echo '</th>';
					echo '<th>';
					echo $langmessage['options'];
					echo '</th></tr>';
				echo '<tr><td>';
					echo $config['addons'][$addon]['editable_text'];
					echo '</td>';
					echo '<td>';
					echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=addontext&addon='.urlencode($addon),array('title'=>urlencode($langmessage['editable_text']),'data-cmd'=>'gpabox'));
					echo '</td></tr>';


		}

		//hooks
		$hooks = admin_addons::AddonHooks($addon);
		if( count($hooks) > 0 ){
			echo '<tr><th>Hooks</th><th>';
			echo $langmessage['options'];
			echo '</th></tr>';

			foreach($hooks as $name => $info){
				echo '<tr><td>';
				echo str_replace('_',' ',$name);
				echo '</td><td>';
				echo '&nbsp;';
				echo '</td></tr>';
			}
		}

		echo '</table>';

		if( !isset($config['addons'][$addon]['id']) ){
			return;
		}

		echo '<h3>'.$langmessage['rate_this_addon'].'</h3>';

		$id = $config['addons'][$addon]['id'];

		if( isset($this->addonReviews[$id]) ){

			$review =& $this->addonReviews[$id];
			$review += array('time'=>time());
			echo 'You posted the following review on '.common::date($langmessage['strftime_date'],$review['time']);


			echo '<table class="rating_table">';
			echo '<tr><td>Rating</td><td>';
			$this->ShowRating($id,$review['rating']);
			echo '</td></tr>';

			echo '<tr><td>Review</td><td>';
			echo nl2br(htmlspecialchars($review['review']));
			echo '</td></tr>';

			echo '<tr><td></td><td>';
			echo common::Link('Admin_Addons','Edit Review','cmd=rate&arg='.$id);
			echo '</td></tr>';
			echo '</table>';


		}else{
			echo '<table class="rating_table">';
			echo '<tr><td>Rating</td><td>';
			$this->ShowRating($id,5);
			echo '</td></tr>';
			echo '</table>';
		}

	}

	/**
	 *
	 * @static
	 */
	function AddonHooks($addon){
		global $config;
		$hooks = array();

		if( !isset($config['hooks']) || !is_array($config['hooks']) ){
			return $hooks;
		}
		foreach($config['hooks'] as $hook => $hook_array){
			if( isset($hook_array[$addon]) ){
				$hooks[$hook] = $hook_array[$addon];
			}
		}
		return $hooks;
	}


	function GetAvailAddons(){
		global $dataDir;

		$addonPath = $dataDir.'/addons';

		if( !file_exists($addonPath) ){
			message('Warning: The /addons folder "<em>'.$addonPath.'</em>" does not exist on your server.');
			return array();
		}


		$folders = gpFiles::ReadDir($addonPath,1);
		$avail = array();

		foreach($folders as $key => $value){
			$fullPath = $addonPath .'/'.$key;
			$info = $this->GetAvailInstall($fullPath);

			if( !$info ){
				continue;
			}
			$info['upgrade_key'] = $this->UpgradePath($info);
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


	function Select(){
		global $langmessage,$config;
		$instructions = true;
		$available = $this->GetAvailAddons();

		//message('available: '.showArray($available));


		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo $langmessage['Manage Plugins'];
		echo ' <span>|</span> ';
		echo common::Link($this->path_remote,$langmessage['Find Plugins']);
		echo '</h2>';

		//echo '<h3>Addons</h3>';
		//echo showArray($config['addons']);



		if( !$this->ShowInstalled($available) ){
			$this->Instructions();
			$instructions = false;
		}


		//show available addons
		echo '<h3>'.$langmessage['available_plugins'].'</h3>';

		echo '<div class="nodisplay" id="gpeasy_addons"></div>';

		if( count($available) == 0 ){
			//echo ' -empty- ';
		}else{
			echo '<table class="bordered" style="min-width:700px">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['name'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['version'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';

			$i=0;
			foreach($available as $folder => $info ){

				if( $info['upgrade_key'] ){
					continue;
				}

				echo '<tr class="'.($i % 2 ? 'even' : '').'">';
				echo '<td>';
				echo $info['Addon_Name'];
				echo '<br/><em class="admin_note">/addons/'.$folder.'</em>';
				echo '</td>';
				echo '<td>';
				echo $info['Addon_Version'];
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Addons',$langmessage['Install'],'cmd=local_install&source='.$folder,'data-cmd="creq"');
				echo ' &nbsp; ';
				if( common::function_exists('symlink') ){
					echo common::Link('Admin_Addons',$langmessage['develop'],'cmd=develop&source='.$folder,'data-cmd="creq"');
				}
				echo '</td>';

				echo '</tr>';
				$i++;
			}
			echo '</table>';

		}


		if( $instructions ){
			echo '<h3>'.$langmessage['about'].'</h3>';
			$this->Instructions();
		}

	}

	function ShowInstalled(&$available){
		global $langmessage,$config,$dataDir;

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


		echo '<table class="bordered" style="min-width:700px">';
		echo '<tr><th>';
		echo $langmessage['name'];
		echo '</th><th>';
		echo $langmessage['version'];
		echo '</th><th>';
		echo $langmessage['options'];
		echo '</th><th>';
		echo 'Order';
		echo '</th></tr>';
		$i=0;
		foreach($show as $folder => $info){

			$addonName = $info['name'];
			$developerInstall = false;
			$installFolder = $dataDir.'/data/_addoncode/'.$folder;

			echo '<tr class="'.($i % 2 ? 'even' : '').'">';
			$i++;

			echo '<td>';
			$label = $addonName;
			echo common::Link('Admin_Addons',$label,'cmd=show&addon='.rawurlencode($folder));
			if( is_link($installFolder) ){
				echo '<br/> <em class="admin_note">'.$langmessage['developer_install'].'</em>';
				$developerInstall = true;

				//check symbolic links, fix if necessary
				$link_folder = readlink($installFolder);
				if( array_key_exists('upgrade_from',$info) ){
					$source_folder = $dataDir.'/addons/'.$info['upgrade_from'];
					if( $source_folder != $link_folder && basename($source_folder) == basename($link_folder) ){
						if( unlink($installFolder) ){
							symlink($source_folder,$installFolder);
						}
					}
				}
			}
			echo '</td>';
			echo '<td>';
			if( isset($info['version']) ){
				echo $info['version'];
			}else{
				$info['version'] = '0';
				echo '&nbsp;';
			}

			if( isset($info['upgrade_from']) ){
				if( isset($info['upgrade_version']) ){
					if(version_compare($info['upgrade_version'],$info['version'] ,'>') ){
						echo ' <br/> <b>'.$langmessage['new_version'].'</b>';
					}
				}
			}
			if( isset($info['id']) && isset($new_versions[$info['id']]) ){
				echo ' <br/> <b>'.$langmessage['new_version'].' (gpEasy.com)</b>';
			}
			echo '</td>';
			echo '<td>';
			if( isset($info['id']) ){
				echo common::Link('Admin_Addons',$langmessage['rate'],'cmd=rate&arg='.$info['id']);

				echo ' &nbsp; ';
				$forum_id = 1000 + $info['id'];
				echo '<a href="'.addon_browse_path.'/Forum?show=f'.$forum_id.'" target="_blank">'.$langmessage['Support'].'</a>';

			}else{
				echo '<span class="unavail">'.$langmessage['rate'].'</span>';
				echo ' &nbsp; ';
				echo '<span class="unavail">'.$langmessage['Support'].'</span>';
			}


			//upgrade link
			if( isset($info['upgrade_from']) ){
				echo ' &nbsp; ';
				if( $developerInstall ){
					echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=local_install&mode=dev&source='.$info['upgrade_from'],'data-cmd="creq"');
				}else{
					echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=local_install&source='.$info['upgrade_from'],'data-cmd="creq"');
				}
			}

			if( isset($info['id']) && isset($new_versions[$info['id']]) ){
				echo ' &nbsp; ';
				echo ' <a href="'.addon_browse_path.'/Plugins?id='.$info['id'].'" data-cmd="remote">';
				echo $langmessage['upgrade'].' (gpEasy.com)';
				echo '</a>';
			}


			echo ' &nbsp; ';
			echo common::Link('Admin_Addons',$langmessage['uninstall'],'cmd=uninstall&addon='.rawurlencode($folder),'data-cmd="gpabox"');

			echo '</td><td>';
			if( isset($info['order']) ){
				echo $info['order'];
			}
			echo '&nbsp;</td></tr>';
		}
		echo '</table>';

		return true;
	}
}