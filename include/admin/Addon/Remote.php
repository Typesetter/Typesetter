<?php

namespace gp\admin\Addon;

defined('is_running') or die('Not an entry point...');


class Remote extends \gp\admin\Addon\Install{


	/**
	 * Get remote addon data and display to user
	 *
	 */
	public function DefaultDisplay(){
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

		$this->SearchOrder(); // \gp\Addon\Install

		$slug = 'Plugins';
		if( $this->config_index == 'themes' ){
			$slug = 'Themes';
		}
		$src = addon_browse_path.'/'.$slug.'?cmd=remote&format=json&'.$this->searchQuery.'&page='.$this->searchPage; // format=json added 4.6b3

		$this->ShowHeader(); // \gp\Addon\Install

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

		$this->SearchOptions(); // \gp\Addon\Install

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
}
