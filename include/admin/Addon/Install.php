<?php

namespace gp\admin\Addon;

defined('is_running') or die('Not an entry point...');


class Install extends \gp\admin\Addon\Tools{

	protected $scriptUrl		= 'Admin/Addons';
	public $avail_addons		= array();
	public $avail_count			= 0;


	//remote browsing
	public $path_remote			= 'Admin/Addons/Remote';
	public $code_folder_name	= '_addoncode';
	private $installed_ids		= array();
	public $config;


	//searching
	public $searchUrl			= '';
	public $searchPage			= 0;
	public $searchMax			= 0;
	public $searchPerPage		= 20;
	public $searchOrder			= '';
	public $searchQuery 		= '';
	public $searchOrderOptions	= array();


	public function __construct($args){

		parent::__construct($args);

		// css and js
		$this->page->css_admin[]	= '/include/css/addons.css';
		$this->page->head_js[]	= '/include/js/rate.js';
	}

	/**
	 * Output addon heading
	 *
	 */
	public function ShowHeader( $addon_name = false ){
		global $langmessage;

		//build links
		$header_paths									= array();
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

		}else{
			$root_label = $langmessage['plugins'];
			if( gp_remote_plugins ){
				$this->FindForm();
				$header_paths[$this->scriptUrl.'/Remote'] = $langmessage['Search'];
			}
		}

		if( $addon_name ){
			$header_paths = array();
			$header_paths[$this->scriptUrl]					= $langmessage['manage'];
			$header_paths[$this->page->requested]			= $addon_name;
		}


		$list = array();
		foreach($header_paths as $slug => $label){

			if( $this->page->requested == $slug ){
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
	public function RemoteInstall(){
		global $langmessage;

		echo '<h2>'.$langmessage['Installation'].'</h2>';

		$name = '<em>'.htmlspecialchars($_REQUEST['name']).'</em>';
		echo '<p class="gp_notice">'.$langmessage['Addon_Install_Warning'].'</p>';
		echo '<p>'.sprintf($langmessage['Selected_Install'],$name,CMS_READABLE_DOMAIN).'</p>';

		$_REQUEST += array('order'=>'');

		echo '<form action="'.\gp\tool::GetUrl($this->page->requested).'" method="post">';
		echo '<input type="hidden" name="cmd" value="RemoteInstallConfirmed" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($_REQUEST['id']).'" />';
		echo '<input type="hidden" name="order" value="'.htmlspecialchars($_REQUEST['order']).'" />';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';

		echo '<input type="submit" value="'.$langmessage['continue'].'" class="gpsubmit">';
		echo '</form>';
	}

	public function RemoteInstallConfirmed($type = 'plugin'){

		$_POST += array('order'=>'');


		$installer = new \gp\admin\Addon\Installer();

		$installer->code_folder_name	= $this->code_folder_name;
		$installer->config_index		= $this->config_index;

		$installer->InstallRemote( $type, $_POST['id'], $_POST['order'] );
		$installer->OutputMessages();

		return $installer;
	}



	/**
	 * Get remote addon data and display to user
	 *
	 */
	public function RemoteBrowse(){
		global $langmessage, $config;


		$this->SearchOptionSave();

		//make a list of installed addon id's
		$this->installed_ids						= self::InstalledIds();


		//search settings
		$this->searchUrl							= $this->path_remote;
		$this->searchOrderOptions['rating_score']	= $langmessage['Highest Rated'];
		$this->searchOrderOptions['downloads']		= $langmessage['Most Downloaded'];
		$this->searchOrderOptions['modified']		= $langmessage['Recently Updated'];
		$this->searchOrderOptions['created']		= $langmessage['Newest'];


		$_GET				+= array('q'=>'');
		$this->searchPage 	= \gp\special\Search::ReqPage('page');


		//version specific search
		if( !isset($config['search_version']) || $config['search_version'] ){
			$this->searchQuery .= '&ug='.rawurlencode(gpversion);
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

		$this->ShowHeader();

		$data = $this->RemoteBrowseResponse($src);
		if( $data === false ){
			return;
		}

		$this->searchMax = $data['max'];
		if( isset($data['per_page']) && $data['per_page'] ){
			$this->searchPerPage = $data['per_page'];
		}else{
			$this->searchPerPage = count($data['rows']);
		}


		$this->RemoteBrowseRows($data);

		$this->VersionOption();
	}


	/**
	 * Display option to limit search results to addons that are compat with the current cms version
	 *
	 */
	public function VersionOption(){
		global $langmessage, $config;

		echo '<h3>'.$langmessage['options'].'</h3>';
		echo '<p>';
		echo 'Limit results to addons that are compatible with your version of '.CMS_NAME.' ('.gpversion.') &nbsp; ';

		if( !isset($config['search_version']) || $config['search_version'] ){
			echo '<b>'.$langmessage['On'].'</b> &nbsp; ';
			echo \gp\tool::Link($this->searchUrl,$langmessage['Off'],$this->searchQuery.'&search_option=noversion',' data-cmd="gpajax"');

		}else{
			echo \gp\tool::Link($this->searchUrl,$langmessage['On'],$this->searchQuery.'&search_option=version',' data-cmd="gpajax"');
			echo ' &nbsp;  <b>'.$langmessage['Off'].'</b>';
		}
		echo '</p>';

		$this->ViewOnline();
	}


	/**
	 * Link to view search resuls on typesettercms.com
	 *
	 */
	public function ViewOnline(){
		$slug = 'Plugins';
		if( $this->config_index == 'themes' ){
			$slug = 'Themes';
		}
		$url = addon_browse_path.'/'.$slug.'?'.$this->searchQuery.'&page='.$this->searchPage;
		echo '<p>View search results on <a href="'.$url.'" target="_blank">'.CMS_READABLE_DOMAIN.'</p>';
	}


	/**
	 * Output the rows found by a RemoteBrowse search
	 *
	 */
	public function RemoteBrowseRows($data){
		global $langmessage;

		if( count($data['rows']) == 0 ){
			echo '<hr/>';
			echo '<h2>'.$langmessage['Sorry, nothing matched'].'</h2>';
			echo '<hr/>';
			return;
		}

		$this->SearchOptions();

		echo '<table class="bordered full_width">';
		echo '<tr><th></th><th>'.$langmessage['name'].'</th><th>'.$langmessage['version'].'</th><th>'.$langmessage['Statistics'].'</th><th>'.$langmessage['description'].'</th></tr>';

		foreach($data['rows'] as $row){
			echo '<tr><td>';
			echo self::DetailLink($row['type'], $row['id'], '<img src="'.$row['icon'].'" height="100" width="100" alt=""/>','',' class="shot"');
			echo '</td>';
			echo '<td class="nowrap">';
			echo '<b>'.$row['name'].'</b>';
			echo '<br/>';
			echo self::DetailLink($row['type'], $row['id'] );
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
		}
		echo '</table>';
		$this->SearchNavLinks();
	}


	/**
	 * Return the list of installed addon ids
	 *
	 */
	public static function InstalledIds(){
		global $config;

		$ids = array();

		if( isset($config['addons']) && is_array($config['addons']) ){
			foreach($config['addons'] as $addon_info){
				if( isset($addon_info['id']) ){
					$ids[] = $addon_info['id'];
				}
			}
		}
		return $ids;
	}


	/**
	 * Save the search option
	 *
	 */
	private function SearchOptionSave(){
		global $config;

		if( !isset($_GET['search_option']) ){
			return;
		}

		switch($_GET['search_option']){
			case 'version':
				unset($config['search_version']);
			break;
			case 'noversion':
				$config['search_version'] = false;
			break;

			default:
			return;
		}

		\gp\admin\Tools::SaveConfig();

	}


	/**
	 * Get cached data or fetch new response from server and cache it
	 *
	 */
	public function RemoteBrowseResponse($src){
		global $dataDir, $langmessage;

		$cache_file		= $dataDir.'/data/_remote/'.sha1($src).'.txt';
		$cache_used		= false;

		//check cache
		if( file_exists($cache_file) && (filemtime($cache_file)+ 26100) > time() ){
			$result			= file_get_contents($cache_file);
			$cache_used 	= true;
		}else{
			$result			= \gp\tool\RemoteGet::Get_Successful($src);
		}

		$data = $this->ParseResponse($result);

		if( $data === false ){
			$this->ViewOnline();
			return false;
		}

		//not unserialized?
		if( count($data) == 0 ){
			echo '<p>';
			echo $langmessage['search_no_results'];
			echo '</p>';
			return false;
		}

		//save the cache
		if( !$cache_used ){
			\gp\tool\Files::Save($cache_file,$result);
		}

		return $data;
	}

	/**
	 * Convert the response string to an array
	 * Serialized or json (serialized data may be cached)
	 *
	 */
	protected function ParseResponse($result){

		//no response
		if( !$result ){
			echo '<p>'.\gp\tool\RemoteGet::Debug('Sorry, data not fetched').'</p>';
			return false;
		}

		$data = false;
		if( strpos($result,'a:') === 0 ){
			$data = unserialize($result);

		}elseif( strpos($result,'{') === 0 ){
			$data = json_decode($result,true);
		}

		if( !is_array($data) ){
			$debug				= array();
			$debug['Two']		= substr($result,0,2);
			$debug['Twotr']		= substr(trim($result),0,2);
			echo '<p>'.\gp\tool\RemoteGet::Debug('Sorry, data not fetched',$debug).'</p>';
			return false;
		}

		return $data;
	}

	public function SearchOrder(){

		if( isset($_REQUEST['order']) && isset($this->searchOrderOptions[$_REQUEST['order']]) ){
			$this->searchOrder = $_REQUEST['order'];
			$this->searchQuery .= '&order='.rawurlencode($_REQUEST['order']);
		}else{
			reset($this->searchOrderOptions);
			$this->searchOrder = key($this->searchOrderOptions);
		}

	}

	/**
	 * Display available search options
	 *
	 */
	public function SearchOptions( $nav_on_top = true ){
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


	public function FindForm(){
		global $langmessage;

		$_GET += array('q'=>'');

		echo '<div class="gp_find_form">';
		echo '<form action="'.\gp\tool::GetUrl($this->path_remote).'" method="get">';
		echo '<input type="text" name="q" value="'.htmlspecialchars($_GET['q']).'" size="15" class="gpinput" /> ';
		echo '<input type="submit" name="" value="'.$langmessage['Search'].'" class="gpbutton" />';
		echo '</form>';
		echo '</div>';
	}

	public function InstallLink($row){
		global $config,$langmessage;

		$installed = in_array($row['id'],$this->installed_ids);

		if( !$installed && ($row['price_unit'] > 0) ){
			$label = ' Install For $'.$row['price_unit'];
			echo self::DetailLink($row['type'], $row['id'], $label, '&amp;cmd=install_info');
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

		$link = 'cmd=RemoteInstall';
		$link .= '&name='.rawurlencode($row['name']);
		$link .= '&type='.rawurlencode($row['type']);
		$link .= '&id='.rawurlencode($row['id']);

		echo \gp\tool::Link($url,$label,$link);
	}

	public function SearchNavLinks(){

		$pages = ceil($this->searchMax/$this->searchPerPage);

		echo '<div class="search_pages">';
		\gp\special\Search::PaginationLinks( $this->searchPage, $pages, $this->searchUrl, $this->searchQuery, 'page');
		echo '</div>';
	}

	public function CurrentRating($rating){

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
	public function InvalidFolders(){
		global $langmessage;

		if( empty($this->invalid_folders) ){
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

