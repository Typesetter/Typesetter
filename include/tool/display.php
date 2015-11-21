<?php
defined('is_running') or die('Not an entry point...');


/**
 *	Objects of this class handle the display of standard gpEasy pages
 *  The classes for admin pages and special pages extend the display class
 *
 */
class display{
	var $pagetype			= 'display';
	var $gp_index;
	var $title;
	var $label;
	var $file				= false;
	var $contentBuffer;
	var $TitleInfo;
	var $fileType			= '';
	var $ajaxReplace		= array('#gpx_content');
	var $admin_links		= array();
	var $visibility			= false;

	var $fileModTime		= 0; /* @deprecated 3.0 */
	var $file_stats			= array();

	//layout & theme
	var $theme_name			= false;
	var $theme_color		= false;
	var $get_theme_css		= true;
	var $theme_dir;
	var $theme_path;
	var $theme_rel;
	var $theme_addon_id		= false;
	var $theme_is_addon		= false;/* @deprecated 3.5 */
	var $menu_css_ordered	= true;
	var $menu_css_indexed	= true;
	var $gpLayout;


	//<head> content
	var $head				= '';
	var $head_js			= array();
	var $head_script		= '';
	var $jQueryCode			= false;
	var $admin_js			= false;
	var $head_force_inline	= false;
	var $meta_description	= '';
	var $meta_keywords		= array();

	//css arrays
	var $css_user			= array();
	var $css_admin			= array();


	var $editable_content	= true;
	var $editable_details	= true;

	function __construct($title){
		$this->title = $title;
	}


	/**
	 * Get page content or do redirect for non-existant titles
	 * see special_missing.php and admin_missing.php
	 */
	function Error_404($requested){
		includeFile('special/special_missing.php');
		ob_start();
		new special_missing($requested);
		$this->contentBuffer = ob_get_clean();
	}

	function SetVars(){
		global $gp_index, $gp_titles, $gp_menu;

		if( !isset($gp_index[$this->title]) ){
			$this->Error_404($this->title);
			return false;
		}

		$this->gp_index		= $gp_index[$this->title];
		$this->TitleInfo	=& $gp_titles[$this->gp_index]; //so changes made by rename are seen
		$this->label		= common::GetLabel($this->title);
		$this->file			= gpFiles::PageFile($this->title);

		if( !$this->CheckVisibility() ){
			return false;
		}

		gpPlugin::Action('PageSetVars');

		return true;
	}

	/**
	 * Check the page's visibility
	 *
	 */
	function CheckVisibility(){

		$this->visibility	= display::OrConfig($this->gp_index,'vis');
		if( !common::LoggedIn() && $this->visibility ){
			$this->Error_404($this->title);
			return false;
		}

		return true;
	}


	function RunScript(){

		if( !$this->SetVars() ){
			return;
		}

		//allow addons to effect page actions and how a page is displayed
		$cmd = common::GetCommand();
		$cmd_after = gpPlugin::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		$this->GetFile();

		includeFile('tool/SectionContent.php');
		$this->contentBuffer = section_content::Render($this->file_sections,$this->title,$this->file_stats);
	}

	/**
	 * Retreive the data file for the current title and update the data if necessary
	 *
	 */
	function GetFile($file = false){

		if( $file === false ){
			$file = $this->file;
		}

		$this->file_sections	= gpFiles::Get($file,'file_sections');
		$this->meta_data		= gpFiles::$last_meta;
		$this->fileModTime		= gpFiles::$last_modified;
		$this->file_stats		= gpFiles::$last_stats;

		if( count($this->file_sections) == 0 ){
			$this->file_sections[0] = array(
				'type' => 'text',
				'content' => '<p>Oops, this page no longer has any content.</p>',
				);
		}
	}


	/**
	 * Set the page's theme name and path information according to the specified $layout
	 * If $layout is not found, use the installation's default theme
	 *
	 */
	function SetTheme($layout=false){
		global $dataDir;

		if( $layout === false ){
			$layout = display::OrConfig($this->gp_index,'gpLayout');
		}

		$layout_info = common::LayoutInfo($layout);


		if( !$layout_info ){
			$default_theme		= explode('/',gp_default_theme);
			$this->gpLayout		= false;
			$this->theme_name	= $default_theme[0];
			$this->theme_color	= $default_theme[1];
			$this->theme_rel	= '/themes/'.$this->theme_name.'/'.$this->theme_color;
			$this->theme_dir	= $dataDir.'/themes/'.$this->theme_name;

		}else{
			$this->gpLayout		= $layout;
			$this->theme_name	= $layout_info['theme_name'];
			$this->theme_color	= $layout_info['theme_color'];
			$this->theme_rel	= $layout_info['path'];
			$this->theme_dir	= $layout_info['dir'];

			if( isset($layout_info['addon_id']) ){
				$this->theme_addon_id = $layout_info['addon_id'];
			}
			$this->theme_is_addon = $layout_info['is_addon'];//if installed in /themes or /data/_themes

			//css preferences
			if( isset($layout_info['menu_css_ordered']) && !$layout_info['menu_css_ordered'] ){
				$this->menu_css_ordered = false;
			}
			if( isset($layout_info['menu_css_indexed']) && !$layout_info['menu_css_indexed'] ){
				$this->menu_css_indexed = false;
			}
		}

		$this->theme_path = common::GetDir($this->theme_rel);

	}


	/**
	 * Return the most relevant configuration value for a configuration option ($var)
	 * Check configuration for a page ($id) first, then parent pages (determined by main menu), then the site $config
	 *
	 * @return mixed
	 *
	 */
	static function OrConfig($id,$var){
		global $config, $gp_titles;

		if( $id ){
			if( !empty($gp_titles[$id][$var]) ){
				return $gp_titles[$id][$var];
			}

			if( display::ParentConfig($id,$var,$value) ){
				return $value;
			}
		}

		if( isset($config[$var]) ){
			return $config[$var];
		}

		return false;
	}

	/**
	 * Traverse the main menu upwards looking for a configuration setting for $var
	 * Start at the title represented by $checkId
	 * Set $value to the configuration setting if a parent page has the configuration setting
	 *
	 * @return bool
	 */
	static function ParentConfig($checkId,$var,&$value){
		global $gp_titles,$gp_menu;

		$parents = common::Parents($checkId,$gp_menu);
		foreach($parents as $parent_index){
			if( !empty($gp_titles[$parent_index][$var]) ){
				$value = $gp_titles[$parent_index][$var];
				return true;
			}
		}
		return false;
	}



	/*
	 * Get functions
	 *
	 * Missing:
	 *		$#sitemap#$
	 * 		different menu output
	 *
	 */

	function GetSiteLabel(){
		global $config;
		echo $config['title'];
	}
	function GetSiteLabelLink(){
		global $config;
		echo common::Link('',$config['title']);
	}
	function GetPageLabel(){
		echo $this->label;
	}

	function GetContent(){

		$this->GetGpxContent();

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent(){
		$class = '';
		if( isset($this->meta_data['file_number']) ){
			$class = 'filenum-'.$this->meta_data['file_number'];
		}

		echo '<div id="gpx_content" class="'.$class.' cf">';

		echo $this->contentBuffer;


		echo '</div>';
	}

	/* Deprecated functions
	 */
	function GetHead(){
		trigger_error('deprecated functions');
		gpOutput::GetHead();
	}
	function GetExtra($area,$info=array()){
		trigger_error('deprecated functions');
		gpOutput::GetExtra($area,$info);
	}
	function GetMenu(){
		trigger_error('deprecated functions');
		gpOutput::GetMenu();
	}
	function GetFullMenu(){
		trigger_error('deprecated functions');
		gpOutput::GetFullMenu();
	}
	function GetAllGadgets(){
		trigger_error('deprecated functions');
		gpOutput::GetAllGadgets();
	}
	function GetAdminLink(){
		trigger_error('deprecated functions');
		gpOutput::GetAdminLink();
	}


}


