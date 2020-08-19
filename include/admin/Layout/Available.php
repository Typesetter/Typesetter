<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Available extends \gp\admin\Layout{

	public $searchUrl = 'Admin_Theme_Content/Available';


	public function __construct($args){

		parent::__construct($args);

		$this->page->head_js[] = '/include/js/auto_width.js';
	}


	/**
	 * Show available themes and style variations
	 *
	 */
	public function ShowAvailable(){

		$cmd = \gp\tool::GetCommand();

		switch($cmd){
			case 'preview':
			case 'preview_iframe':
			case 'newlayout':
			case 'addlayout':
				if( $this->NewLayout($cmd) ){
					return;
				}
			break;

			case 'DeleteTheme':
				$this->DeleteTheme();
				$this->GetPossible();
			break;
		}

		$this->GetAddonData();

		$this->ShowHeader();

		$this->AvailableList();

		$this->InvalidFolders();
	}


	public function AvailableList( $show_options = true ){
		global $langmessage, $config;

		//search settings
		$this->searchPerPage						= 10;
		$this->searchOrderOptions					= [];
		$this->searchOrderOptions['modified']		= $langmessage['Recently Updated'];
		$this->searchOrderOptions['rating_score']	= $langmessage['Highest Rated'];
		$this->searchOrderOptions['downloads']		= $langmessage['Most Downloaded'];

		$this->SearchOrder();
		$this->SortAvailable();

		// pagination
		$this->searchMax	= count($this->avail_addons);
		$this->searchPage	= \gp\special\Search::ReqPage('page', $this->searchMax);

		$start				= $this->searchPage * $this->searchPerPage;
		$possible			= array_slice($this->avail_addons, $start, $this->searchPerPage, true);

		if( $show_options ){
			$this->SearchOptions();
			echo '<hr/>';
		}

		// show themes
		echo '<div id="gp_avail_themes">';
		foreach($possible as $theme_id => $info){
			$this->AvailableTheme($theme_id, $info, $show_options);
		}
		echo '</div>';

 		if( $show_options ){
			$this->SearchNavLinks();
		}
	}


	protected function AvailableTheme($theme_id, $info, $show_options){
		global $langmessage, $config;

		$theme_label	= str_replace('_', ' ', $info['name']);
		$version		= '';
		$id				= false;
		if( isset($info['version']) ){
			$version = $info['version'];
		}
		if( isset($info['addon_id']) && is_numeric($info['addon_id']) ){
			$id = $info['addon_id'];
		}

		//screenshot
		if( file_exists($info['full_dir'] . '/screenshot.png') ){

			$screenshot_url = \gp\tool::GetDir($info['rel'] . '/screenshot.png');
			echo '<div class="expand_child_click">';
			echo	'<b class="gp_theme_head">' . $theme_label . ' ' . $version . '</b>';
			echo	'<div style="background-image:url(\'' . $screenshot_url . '\')">';

		}elseif( file_exists($info['full_dir'] . '/screenshot.jpg') ){

			$screenshot_url = \gp\tool::GetDir($info['rel'] . '/screenshot.jpg');
			echo '<div class="expand_child_click">';
			echo	'<b class="gp_theme_head">' . $theme_label . ' ' . $version . '</b>';
			echo	'<div style="background-image:url(\'' . $screenshot_url . '\')">';

		}else{

			echo '<div>';
			echo	'<b class="gp_theme_head">' . $theme_label . ' ' . $version . '</b>';
			echo	'<div>';

		}

		//options
		echo '<div class="gp_theme_options">';

		//colors
		if( $show_options ){
			$color_q	= 'cmd=preview' . $this->searchQuery;
			$color_a	= '';
		}else{
			$color_q	= 'cmd=preview_iframe';
			$color_a	= ' target="gp_layout_iframe" data-cmd="SetPreviewTheme" ';
		}

		echo '<b>' . $langmessage['preview'] . '</b>';
		echo '<ul>';
		foreach($info['colors'] as $color){
			echo '<li>';

			$theme	= $theme_id . '/' . $color;
			$q		= $color_q . '&theme=' . rawurlencode($theme);
			$a		= $color_a . ' data-arg="' . htmlspecialchars($theme) . '"';

			echo \gp\tool::Link(
					'Admin_Theme_Content/Available',
					str_replace('_', '&nbsp;', $color),
					$q,
					$a
				);
			echo '</li>';
		}
		echo '</ul>';

		$options = $this->AvailableThemeOptions($id, $info, $theme_label);

		if( !empty($options) ){
			echo '<b>' . $langmessage['options'] . '</b>';
			echo '<ul>';
			echo	$options;
			echo '</ul>';
		}

		echo '</div>'; // /.gp_theme_options

		echo '</div>';

		//remote upgrade
		if( gp_remote_themes &&
			$id &&
			isset(\gp\admin\Tools::$new_versions[$id]) &&
			version_compare(\gp\admin\Tools::$new_versions[$id]['version'], $version ,'>')
		){
			$version_info = \gp\admin\Tools::$new_versions[$id];
			echo \gp\tool::Link(
					'Admin_Theme_Content',
					$langmessage['new_version'],
					'cmd=RemoteInstall&id=' . $id . '&name=' . rawurlencode($version_info['name'])
				);
		}

		echo '</div>';
	}


	protected function AvailableThemeOptions($id, $info, $theme_label){
		global $langmessage, $config;

		ob_start();

		if( $id ){
			//more info
			echo '<li>';
			echo	self::DetailLink('theme', $id, 'More Info&hellip;');
			echo '</li>';

			//support
			$forum_id = 1000 + $id;
			echo '<li>';
			echo	'<a href="' . addon_browse_path . '/Forum?show=f' . $forum_id . '"';
			echo		' target="_blank">';
			echo			$langmessage['Support'];
			echo	'</a>';
			echo '</li>';

			//rating
			$rating = 0;
			if( $info['rt'] > 0 ){
				$rating = $info['rt'];
			}
			echo '<li>';
			echo	'<span class="nowrap">';
			echo		$langmessage['rate'] . ' ';
			echo		$this->ShowRating($info['rel'], $rating);
			echo	'</span>';
			echo '</li>';

			//downloads
			if( $info['dn'] > 0 ){
				echo '<li>';
				echo	'<span class="nowrap">';
				echo		'Downloads: ' . number_format($info['dn']);
				echo	'</span>';
				echo '</li>';
			}
		}

		//last updated
		if( $info['tm'] > 0 ){
			echo '<li>';
			echo	'<span class="nowrap">';
			echo		$langmessage['Modified'] . ': ';
			echo		\gp\tool::date($langmessage['strftime_datetime'], $info['tm']);
			echo	'</span>';
			echo '</li>';
		}

		if( $info['is_addon'] ){
			//delete
			$folder	= $info['folder'];
			$title	= sprintf($langmessage['generic_delete_confirm'], $theme_label);
			$attr	= [
				'data-cmd'	=> 'cnreq',
				'class'		=> 'gpconfirm',
				'title'		=> $title,
			];
			echo '<li>';
			echo \gp\tool::Link(
					'Admin_Theme_Content/Available',
					$langmessage['delete'],
					'cmd=DeleteTheme&folder=' . rawurlencode($folder),
					$attr
				);
			echo '</li>';

			//order
			if( isset($config['themes'][$folder]['order']) ){
				echo '<li>';
				echo	'Order: ' . $config['themes'][$folder]['order'];
				echo '</li>';
			}
		}

		return ob_get_clean();
	}


	/**
	 * Sort the list available addons
	 *
	 */
	private function SortAvailable(){

		// get addon information for ordering
		\gp\admin\Tools::VersionData($version_data);
		$version_data = $version_data['packages'];

		// combine remote addon information
		foreach($this->avail_addons as $theme_id => $info){

			if( isset($info['id']) ){
				$id = $info['id'];

				if( isset($version_data[$id]) ){
					$info = array_merge($info, $version_data[$id]);
					$info['rt'] *= 5;
				}

				// use local rating
				if( isset($this->addonReviews[$id]) ){
					$info['rt'] = $this->addonReviews[$id]['rating'];
				}
			}else{
				// give local themes a high rating to make them appear first
				// rating won't actually display
				$info['rt'] = 6; 
			}

			$info += ['dn' => 0, 'rt' => 0];

			//modified time
			if( !isset($info['tm']) ){
				$info['tm'] = self::ModifiedTime( $info['full_dir'] );
			}

			$this->avail_addons[$theme_id] = $info;
		}

		// sort by
		uasort($this->avail_addons, [$this, 'SortUpdated']);
		switch($this->searchOrder){

			case 'downloads':
				uasort($this->avail_addons, [$this, 'SortDownloads']);
				break;

			case 'modified':
				uasort($this->avail_addons, [$this, 'SortRating']);
				uasort($this->avail_addons, [$this, 'SortUpdated']);
				break;

			case 'rating_score':
			default:
				uasort($this->avail_addons, [$this, 'SortRating']);
				break;
		}
	}


	public static function ModifiedTime($directory){

		$files	= scandir( $directory );
		$time	= filemtime( $directory );

		foreach($files as $file){
			if( $file == '..' || $file == '.' ){
				continue;
			}

			$full_path = $directory . '/' . $file;

			if( is_dir($full_path) ){
				$time = max($time, self::ModifiedTime($full_path));
			}else{
				$time = max($time, filemtime($full_path));
			}
		}

		return $time;
	}


	public function SortDownloads($a, $b){
		return $b['dn'] > $a['dn'];
	}


	public function SortRating($a, $b){
		return $b['rt'] > $a['rt'];
	}


	public function SortUpdated($a, $b){
		return $b['tm'] > $a['tm'];
	}


	/**
	 * Manage adding new layouts
	 *
	 */
	public function NewLayout($cmd){
		global $langmessage;

		//check the requested theme
		$theme			=& $_REQUEST['theme'];
		$theme_info		= $this->ThemeInfo($theme);

		if( $theme_info === false ){
			msg($langmessage['OOPS'] . ' (Invalid Theme)');
			return false;
		}

		// three steps of installation
		switch($cmd){
			case 'preview':
				if( $this->PreviewTheme($theme, $theme_info) ){
					return true;
				}
				break;

			case 'preview_iframe':
				$this->PreviewThemeIframe($theme, $theme_info);
				return true;

			case 'newlayout':
				$this->NewLayoutPrompt($theme, $theme_info);
				return true;

			case 'addlayout':
				$this->AddLayout($theme_info);
				break;
		}

		return false;
	}


	/**
	 * Preview a theme and give users the option of creating a new layout
	 *
	 */
	public function PreviewTheme($theme, $theme_info){
		global $langmessage, $config;

		//force showing only the body as a complete html document
		$_REQUEST += ['gpreq' => 'body'];

		\gp\admin\Tools::$show_toolbar	= false;
		$this->page->get_theme_css		= false;
		$this->page->head_js[]			= '/include/js/auto_width.js';
		$this->page->head_js[]			= '/include/js/theme_content_outer.js';
		$this->page->head_js[]			= '/include/js/preview_themes.js';
		$this->page->css_admin[]		= '/include/css/theme_content_outer.scss';

		$iframe_src = \gp\tool::GetUrl(
			'Admin_Theme_Content/Available',
			'cmd=preview_iframe&theme=' . rawurlencode($theme)
		);

		//show site in iframe
		echo '<div id="gp_iframe_wrap">';
		echo	'<iframe src="' . $iframe_src . '" id="gp_layout_iframe"';
		echo		' name="gp_layout_iframe"></iframe>';
		echo '</div>'; // /#gp_iframe_wrap

		ob_start();

		//new
		echo '<div id="theme_editor">';
		echo	'<div class="gp_scroll_area">';

		echo		'<div>';
		echo			\gp\tool::Link(
							'Admin_Theme_Content/Available',
							'&#171; ' . $langmessage['available_themes']
						);
		echo			\gp\tool::Link(
							'Admin_Theme_Content/Available',
							$langmessage['use_this_theme'],
							'cmd=newlayout&theme=' . rawurlencode($theme),
							['data-cmd' => 'gpabox', 'class' => 'add_layout']
						);
		echo		'</div>';

		echo		'<div class="separator"></div>';
		echo		'<div id="available_wrap"><div>';

		$this->searchUrl = 'Admin_Theme_Content/Available';
		$this->AvailableList(false);

		//search options
		$this->searchQuery .= '&cmd=preview&theme=' . rawurlencode($theme);
		$this->SearchOptions(false);

		echo	'</div>'; // /.gp_scroll_area
		echo '</div>'; // /#theme_editor

		echo '</div>';
		echo '</div>';

		$this->page->admin_html = ob_get_clean();

		return true;
	}


	public function PreviewThemeIframe($theme, $theme_info){
		global $langmessage, $config;

		\gp\admin\Tools::$show_toolbar = false;

		$this->page->gpLayout		= false;
		$this->page->theme_name		= $theme_info['folder'];
		$this->page->theme_color	= $theme_info['color'];
		$this->page->theme_dir		= $theme_info['full_dir'];
		$this->page->theme_rel		= $theme_info['rel'] . '/' . $theme_info['color'];

		$this->LoremIpsum();

		if( isset($theme_info['id']) ){
			$this->page->theme_addon_id = $theme_info['id'];
		}

		$this->page->theme_path = \gp\tool::GetDir($this->page->theme_rel);

		$this->page->show_admin_content = false;
	}


	/**
	 * Give users a few options before creating the new layout
	 *
	 */
	public function NewLayoutPrompt($theme, $theme_info ){
		global $langmessage;

		$label = substr($theme_info['name'] . '/' . $theme_info['color'], 0, 25);

		echo '<h2>' . $langmessage['new_layout'] . '</h2>';

		echo '<form action="' . \gp\tool::GetUrl('Admin_Theme_Content/Available') . '" method="post">';

		echo	'<table class="bordered full_width">';
		echo		'<tr>';
		echo			'<th colspan="2">';
		echo				$langmessage['options'];
		echo			'</th>';
		echo		'</tr>';
		echo		'<tr>';
		echo			'<td>';
		echo				$langmessage['label'];
		echo			'</td>';
		echo			'<td>';
		echo				'<input type="text" name="label" class="gpinput"';
		echo					' value="' . htmlspecialchars($label) . '" />';
		echo			'</td>';
		echo		'</tr>';
		echo		'<tr>';
		echo			'<td>';
		echo				$langmessage['make_default'];
		echo			'</td>';
		echo			'<td>';
		echo				'<input type="checkbox" name="default" value="default" />';
		echo			'</td>';
		echo		'</tr>';
		echo	'</table>';

		echo	'<p>';
		echo		'<input type="hidden" name="theme" value="' . htmlspecialchars($theme) . '" /> ';
		echo		'<button type="submit" name="cmd" value="addlayout" class="gpsubmit">';
		echo			$langmessage['save'];
		echo		'</button> ';
		echo		'<input type="button" name="" value="Cancel" class="admin_box_close gpcancel" /> ';
		echo	'</p>';

		echo '</form>';
	}


	/**
	 * Add a new layout to the installation
	 *
	 */
	public function AddLayout($theme_info){
		global $gpLayouts, $langmessage, $config;

		$new_layout						= [];
		$new_layout['theme']			= $theme_info['folder'] . '/' . $theme_info['color'];
		$new_layout['color']			= self::GetRandColor();
		$new_layout['label']			= htmlspecialchars($_POST['label']);

		$customizer_file				= $theme_info['full_dir'] . '/' . $theme_info['color'] . '/customizer.php';
		$new_layout['config']			= \gp\tool\Output::GetCustomizerDefaults('php', $customizer_file);
		$js_vars						= \gp\tool\Output::GetCustomizerDefaults('js', $customizer_file);
		$js_vars						= "\n" . 'var layout_config = ' . json_encode($js_vars) . ';' . "\n";
		$new_layout['js_vars']			= $js_vars;

		if( $theme_info['is_addon'] ){
			$new_layout['is_addon']		= true;
		}

		$installer						= new \gp\admin\Addon\Installer();
		$installer->addon_folder_rel	= dirname($theme_info['rel']);
		$installer->code_folder_name	= '_themes';
		$installer->source				= $theme_info['full_dir'];
		$installer->new_layout			= $new_layout;

		if( !empty($_POST['default']) && $_POST['default'] != 'false' ){
			$installer->default_layout	= true;
		}

		$success = $installer->Install();
		$installer->OutputMessages();

		if( $success && $installer->default_layout ){
			$this->page->SetTheme();
			$this->SetLayoutArray();
		}
	}


	/**
	 * Delete a remote theme
	 *
	 */
	public function DeleteTheme(){
		global $langmessage, $dataDir, $gpLayouts, $config;

		$config_before		= $config;
		$gpLayoutsBefore	= $gpLayouts;
		$theme_folder_name	=& $_POST['folder'];

		if( empty($theme_folder_name) || !ctype_alnum($theme_folder_name) ){
			msg($langmessage['OOPS'] . ' (Invalid Request)');
			return false;
		}

		$order = false;
		if( isset($config['themes'][$theme_folder_name]['order']) ){
			$order = $config['themes'][$theme_folder_name]['order'];
		}

		if( !$this->CanDeleteTheme($theme_folder_name, $message) ){
			msg($message);
			return false;
		}

		//remove layouts
		$rm_addon = false;
		foreach($gpLayouts as $layout_id => $layout_info){

			if( !isset($layout_info['is_addon']) || !$layout_info['is_addon'] ){
				continue;
			}

			$layout_folder = dirname($layout_info['theme']);
			if( $layout_folder != $theme_folder_name ){
				continue;
			}

			if( array_key_exists('addon_key', $layout_info) ){
				$rm_addon = $layout_info['addon_key'];
			}

			$this->RmLayoutPrep($layout_id);
			unset($gpLayouts[$layout_id]);
		}


		//remove from settings
		unset($config['themes'][$theme_folder_name]);

		if( $rm_addon ){

			$installer = new \gp\admin\Addon\Installer();
			if( !$installer->Uninstall($rm_addon) ){
				$gpLayouts = $gpLayoutsBefore;
			}
			$installer->OutputMessages();

		}else{

			if( !\gp\admin\Tools::SaveAllConfig() ){
				$config = $config_before;
				$gpLayouts = $gpLayoutsBefore;
				msg($langmessage['OOPS'] . ' (s1)');
				return false;
			}

			msg($langmessage['SAVED']);
			if( $order ){
				$img_path = \gp\tool::IdUrl('ci');
				\gp\tool::IdReq($img_path);
			}

		}

		//delete the folder if it hasn't already been deleted by addon installer
		$dir = $dataDir . '/data/_themes/' . $theme_folder_name;
		if( file_exists($dir) ){
			\gp\tool\Files::RmAll($dir);
		}
	}


	public function CanDeleteTheme($folder, &$message){
		global $gpLayouts, $config, $langmessage;

		foreach($gpLayouts as $layout_id => $layout){

			if( !isset($layout['is_addon']) || !$layout['is_addon'] ){
				continue;
			}

			$layout_folder = dirname($layout['theme']);
			if( $layout_folder == $folder ){
				if( $config['gpLayout'] == $layout_id ){
					$message = $langmessage['delete_default_layout'];
					return false;
				}
			}
		}
		return true;
	}

}
