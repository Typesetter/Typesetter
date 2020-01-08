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
