<?php
defined('is_running') or die('Not an entry point...');




global $langmessage;
$langmessage['Sorry, nothing matched'] = 'Sorry, nothing met your search criteria.';
$langmessage['Sorry, data not fetched'] = 'Sorry, the addon data could not be fetched from gpEasy.com.';


includeFile('admin/admin_addons_tool.php');


class admin_addon_install extends admin_addons_tool{


	//remote browsing
	var $config_index = 'addons';
	var $path_root = 'Admin_Addons';
	var $path_remote = 'Admin_Addons/Remote';
	var $find_label;
	var $code_folder_name = '_addoncode';
	var $can_install_links = true;



	/**
	 * Remote Install Functions
	 *
	 */
	function RemoteInstall(){
		global $langmessage, $page;

		echo '<h2>'.$langmessage['Installation'].'</h2>';

		$name = '<em>'.htmlspecialchars($_REQUEST['name']).'</em>';
		echo '<p class="gp_notice">'.$langmessage['Addon_Install_Warning'].'</p>';
		echo '<p>'.sprintf($langmessage['Selected_Install'],$name,'gpEasy.com').'</p>';

		$_REQUEST += array('order'=>'');

		echo '<form action="'.common::GetUrl($page->requested).'" method="post">';
		echo '<input type="hidden" name="cmd" value="remote_install_confirmed" />';
		echo '<input type="hidden" name="type" value="'.htmlspecialchars($_REQUEST['type']).'" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($_REQUEST['id']).'" />';
		echo '<input type="hidden" name="order" value="'.htmlspecialchars($_REQUEST['order']).'" />';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';

		echo '<input type="submit" value="'.$langmessage['continue'].'" class="gpsubmit">';
		echo '</form>';
	}

	function RemoteInstallConfirmed(){

		$_POST += array('order'=>'');

		includeFile('admin/admin_addon_installer.php');

		$installer = new admin_addon_installer();

		$installer->code_folder_name = $this->code_folder_name;
		$installer->config_index = $this->config_index;
		$installer->can_install_links = $this->can_install_links;

		$installer->InstallRemote( $_POST['type'], $_POST['id'], $_POST['order'] );
		$installer->OutputMessages();
	}


	/**
	 * Check the ini values of the addon being installed
	 * @return bool
	 *
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
			if( $use_cache ) unlink($cache_file);
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (f1)</p>';
			return;
		}
		if( strpos($result,'a:') !== 0 ){
			if( $use_cache ) unlink($cache_file);
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (f2)</p>';
			return;
		}

		$data = @unserialize($result);

		if( !is_array($data) || count($data) == 0 ){
			if( $use_cache ) unlink($cache_file);
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
			echo common::Link($this->path_remote,$langmessage['Off'],$this->searchQuery.'&search_option=noversion',' data-cmd="gpajax"');

		}else{
			echo common::Link($this->path_remote,$langmessage['On'],$this->searchQuery.'&search_option=version',' data-cmd="gpajax"');
			echo ' &nbsp;  <b>'.$langmessage['Off'].'</b>';
		}
		echo '</li>';
		echo '</ul>';
	}

	function DetailLink($row,$label = 'Details',$q = '',$attr=''){
		echo '<a href="'.$this->DetailUrl($row,$q).'" data-cmd="remote" '.$attr.'>'.$label.'</a>';
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
		echo '<span style="width:'.$pos.'px"></span>';
		echo '<span style="background-position:'.$pos2.'px -16px;width:'.$pos2.'px"></span>';
		echo '</span> ';
	}

}

