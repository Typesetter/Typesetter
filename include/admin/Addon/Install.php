<?php

namespace gp\admin\Addon;

defined('is_running') or die('Not an entry point...');




global $langmessage;
$langmessage['Sorry, nothing matched'] = 'Sorry, nothing met your search criteria.';
$langmessage['Sorry, data not fetched'] = 'Sorry, the addon data could not be fetched from gpEasy.com.';



class Install extends \gp\admin\Addon\Tools{

	protected $scriptUrl		= 'Admin/Addons';
	public $avail_addons		= array();
	public $avail_count			= 0;


	//remote browsing
	public $config_index		= 'addons';
	public $path_remote			= 'Admin/Addons/Remote';
	public $code_folder_name	= '_addoncode';
	public $can_install_links	= true;


	//searching
	public $searchUrl			= '';
	public $searchPage			= 0;
	public $searchMax			= 0;
	public $searchPerPage		= 20;
	public $searchOrder			= '';
	public $searchQuery 		= '';
	public $searchOrderOptions	= array();


	function __construct(){
		global $page;

		// css and js
		$page->css_admin[]	= '/include/css/addons.css';
		$page->head_js[]	= '/include/js/rate.js';
	}

	/**
	 * Output addon heading
	 *
	 */
	function ShowHeader( $addon_name = false ){
		global $page, $langmessage;

		//build links
		$header_paths[$this->scriptUrl]					= $langmessage['manage'];
		$header_paths[$this->scriptUrl.'/Available']	= $langmessage['Available'];

		if( $this->avail_count > 0 ){
			$header_paths[$this->scriptUrl.'/Available']	= $langmessage['Available'].' ('.$this->avail_count.')';
		}



		if( $this->config_index == 'themes' ){
			$root_label = $langmessage['themes'];
			if( gp_remote_themes ){
				$this->FindForm();
				$header_paths[$this->scriptUrl.'/Remote'] = $langmessage['Search'];
			}

		}elseif( $this->config_index == 'addons' ){
			$root_label = $langmessage['plugins'];
			if( gp_remote_plugins ){
				$this->FindForm();
				$header_paths[$this->scriptUrl.'/Remote'] = $langmessage['Search'];
			}
		}

		if( $addon_name ){
			$header_paths = array();
			$header_paths[$this->scriptUrl]					= $langmessage['manage'];
			$header_paths[$page->requested]					= $addon_name;
		}


		$list = array();
		foreach($header_paths as $slug => $label){

			if( $page->requested == $slug ){
				$list[] = '<span>'.$label.'</span>';
			}else{
				$list[] = \gp\tool::Link($slug,$label);
			}
		}


		echo '<h2 class="hmargin_tabs">';
		echo $root_label;
		echo ' &#187;';

		echo implode('', $list );



		echo '</h2>';

	}


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

		echo '<form action="'.\gp\tool::GetUrl($page->requested).'" method="post">';
		echo '<input type="hidden" name="cmd" value="remote_install_confirmed" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($_REQUEST['id']).'" />';
		echo '<input type="hidden" name="order" value="'.htmlspecialchars($_REQUEST['order']).'" />';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';

		echo '<input type="submit" value="'.$langmessage['continue'].'" class="gpsubmit">';
		echo '</form>';
	}

	function RemoteInstallConfirmed($type = 'plugin'){

		$_POST += array('order'=>'');


		$installer = new \gp\admin\Addon\Installer();

		$installer->code_folder_name	= $this->code_folder_name;
		$installer->config_index		= $this->config_index;
		$installer->can_install_links	= $this->can_install_links;

		$installer->InstallRemote( $type, $_POST['id'], $_POST['order'] );
		$installer->OutputMessages();

		return $installer;
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
			if( $save )	\gp\admin\Tools::SaveConfig();
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

		//search settings
		$this->searchUrl = $this->path_remote;
		$this->searchOrderOptions['rating_score']	= $langmessage['Highest Rated'];
		$this->searchOrderOptions['downloads']		= $langmessage['Most Downloaded'];
		$this->searchOrderOptions['modified']		= $langmessage['Recently Updated'];
		$this->searchOrderOptions['created']		= $langmessage['Newest'];

		$_GET += array('q'=>'');
		if( isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ){
			$this->searchPage = $_REQUEST['page'];
		}


		//version specific search
		$search_version = false;
		if( !isset($config['search_version']) || $config['search_version'] ){
			$this->searchQuery .= '&ug='.rawurlencode(gpversion);
			$search_version = true;
		}

		if( !empty($_GET['q']) ){
			$this->searchQuery .= '&q='.rawurlencode($_GET['q']);
		}

		$this->SearchOrder();

		$slug = 'Plugins';
		if( $this->config_index == 'themes' ){
			$slug = 'Themes';
		}
		$src = addon_browse_path.'/'.$slug.'?cmd=remote&format=json&'.$this->searchQuery.'&page='.$this->searchPage; // format=json added 4.6b3

		//check cache
		$cache_file = $dataDir.'/data/_remote/'.sha1($src).'.txt';
		$use_cache = false;
		if( file_exists($cache_file) && (filemtime($cache_file)+ 26100) > time() ){
			$result = file_get_contents($cache_file);
			$use_cache = true;
		}else{
			$result = \gp\tool\RemoteGet::Get_Successful($src);
		}

		//no response
		if( !$result ){
			if( $use_cache ) unlink($cache_file);
			echo '<p>'.\gp\tool\RemoteGet::Debug('Sorry, data not fetched').'</p>';
			return;
		}

		//serialized or json (serialized data may be cached)
		if( strpos($result,'a:') === 0 ){
			$data = unserialize($result);

		}elseif( strpos($result,'{') === 0 ){
			$data = json_decode($result,true);

		}else{
			if( $use_cache ) unlink($cache_file);
			$debug				= array();
			$debug['Two']		= substr($result,0,2);
			$debug['Twotr']		= substr(trim($result),0,2);
			echo '<p>'.\gp\tool\RemoteGet::Debug('Sorry, data not fetched',$debug).'</p>';
			return;
		}


		//not unserialized?
		if( !is_array($data) || count($data) == 0 ){
			if( $use_cache ) unlink($cache_file);
			echo '<p>'.$langmessage['Sorry, data not fetched'].' (F3)</p>';
			return;
		}

		//save the cache
		if( !$use_cache ){
			\gp\tool\Files::Save($cache_file,$result);
		}


		$this->searchMax = $data['max'];
		if( isset($data['per_page']) && $data['per_page'] ){
			$this->searchPerPage = $data['per_page'];
		}else{
			$this->searchPerPage = count($data['rows']);
		}

		$this->ShowHeader();
		$this->SearchOptions();

		echo '<table class="bordered full_width">';
		echo '<tr><th></th><th>'.$langmessage['name'].'</th><th>'.$langmessage['version'].'</th><th>'.$langmessage['Statistics'].'</th><th>'.$langmessage['description'].'</th></tr>';

		$i = 0;
		if( count($data['rows']) ){
			foreach($data['rows'] as $row){
				echo '<tr class="'.($i % 2 ? 'even' : '').'">';
				echo '<td>';
				echo $this->DetailLink($row['type'], $row['id'], '<img src="'.$row['icon'].'" height="100" width="100" alt=""/>','',' class="shot"');
				echo '</td>';
				echo '<td class="nowrap">';
				echo '<b>'.$row['name'].'</b>';
				echo '<br/>';
				echo $this->DetailLink($row['type'], $row['id'] );
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
			echo \gp\tool::Link($this->searchUrl,$langmessage['Off'],$this->searchQuery.'&search_option=noversion',' data-cmd="gpajax"');

		}else{
			echo \gp\tool::Link($this->searchUrl,$langmessage['On'],$this->searchQuery.'&search_option=version',' data-cmd="gpajax"');
			echo ' &nbsp;  <b>'.$langmessage['Off'].'</b>';
		}
		echo '</li>';
		echo '</ul>';
	}

	function SearchOrder(){

		if( isset($_REQUEST['order']) && isset($this->searchOrderOptions[$_REQUEST['order']]) ){
			$this->searchOrder = $_REQUEST['order'];
			$this->searchQuery .= '&order='.rawurlencode($_REQUEST['order']);
		}else{
			reset($this->searchOrderOptions);
			$this->searchOrder = key($this->searchOrderOptions);
		}

	}

	function SearchOptions( $nav_on_top = true ){
		echo '<div class="gp_search_options">';

		if( $nav_on_top ){
			$this->SearchNavLinks();
		}

		echo '<div class="search_order">';
		foreach($this->searchOrderOptions as $key => $label){
			if( $key === $this->searchOrder ){
				echo '<span>'.$label.'</span>';
			}else{
				echo \gp\tool::Link($this->searchUrl,$label,$this->searchQuery.'&order='.$key);
			}
		}
		echo '</div>';

		if( !$nav_on_top ){
			$this->SearchNavLinks();
		}

		echo '</div>';
	}

	function DetailLink( $type, $id, $label = 'Details', $q = '', $attr='' ){
		return '<a href="'.$this->DetailUrl($type,$id,$q).'" data-cmd="remote" '.$attr.'>'.$label.'</a>';
	}

	function DetailUrl($type,$id,$q=''){
		$url = 'Themes';
		if( $type == 'plugin' ){
			$url = 'Plugins';
		}
		if( !empty($q) ){
			$q = '?'.$q;
		}
		return addon_browse_path.'/'.$url.'/'.$id.$q;
	}

	function FindForm(){
		global $langmessage;

		$_GET += array('q'=>'');

		echo '<div class="gp_find_form">';
		echo '<form action="'.\gp\tool::GetUrl($this->path_remote).'" method="get">';
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
			echo $this->DetailLink($row['type'], $row['id'], $label, '&amp;cmd=install_info');
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
			$url = 'Admin/Addons';
		}

		$link = 'cmd=remote_install';
		$link .= '&name='.rawurlencode($row['name']);
		$link .= '&type='.rawurlencode($row['type']);
		$link .= '&id='.rawurlencode($row['id']);

		echo \gp\tool::Link($url,$label,$link);
	}

	function SearchNavLinks(){
		global $langmessage;

		echo '<div class="search_pages">';
		if( $this->searchPage > 0 ){
			//previous
			if( $this->searchPage > 1 ){
				echo \gp\tool::Link($this->searchUrl,$langmessage['Previous'],$this->searchQuery.'&page='.($this->searchPage-1));
			}else{
				echo \gp\tool::Link($this->searchUrl,$langmessage['Previous'],$this->searchQuery);
			}
		}else{
			echo '<span>'.$langmessage['Previous'].'</span>';
		}


		//always show link for first page
		$start_page = max(0,$this->searchPage-5);
		if( $start_page > 0 ){
			echo \gp\tool::Link($this->searchUrl,'1',$this->searchQuery); //.'&offset=0');
			if( $start_page > 1 ){
				echo '<span>...</span>';
			}
		}

		$pages = ceil($this->searchMax/$this->searchPerPage);
		$max_page = min($start_page + 9,$pages);

		for($j=$start_page;$j<$max_page;$j++){
			$new_offset = ($j*$this->searchPerPage);
			if( $this->searchPage == $j ){
				echo '<span>'.($j+1).'</span>';
			}else{
				if( $j == 0 ){
					echo \gp\tool::Link($this->searchUrl,($j+1),$this->searchQuery);
				}else{
					echo \gp\tool::Link($this->searchUrl,($j+1),$this->searchQuery.'&page='.($j));
				}
			}
		}

		//always show link to last page
		if( $max_page < $pages ){
			if( ($max_page+1) < $pages ){
				echo '<span>...</span>';
			}
			echo \gp\tool::Link($this->searchUrl,($pages),$this->searchQuery.'&page='.($pages-1));
		}


		if( $this->searchPage < $pages ){
			echo \gp\tool::Link($this->searchUrl,$langmessage['Next'],$this->searchQuery.'&page='.($this->searchPage+1));
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


	/**
	 * Show folders in /addons that didn't make it into the available list
	 *
	 */
	function InvalidFolders(){
		global $langmessage;

		if( !$this->invalid_folders ){
			return;
		}

		echo '<br/>';
		echo '<h3>Invalid Addon Folders</h3>';
		echo '<table class="bordered full_width striped">';
		echo '<tr><th>';
		echo $langmessage['name'];
		echo '</th><th>&nbsp;</th></tr>';
		foreach($this->invalid_folders as $folder => $msg){

			if( isset($this->avail_addons[$folder]) ){
				continue; //skip false positives
			}

			echo '<tr><td>';
			echo htmlspecialchars($folder);
			echo '</td><td>';
			echo htmlspecialchars($msg);
			echo '</td></tr>';
		}
		echo '</table>';

	}

}

