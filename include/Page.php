<?php

namespace gp{

	defined('is_running') or die('Not an entry point...');


	/**
	 *	Objects of this class handle the display of standard CMS pages
	 *  The classes for admin pages and special pages extend the display class
	 *
	 */
	class Page extends Base{
		public $pagetype			= 'display';
		public $gp_index;
		public $requested;
		public $title;
		public $label;
		public $file;
		public $contentBuffer;
		public $TitleInfo;
		public $fileType			= '';
		public $ajaxReplace			= array('#gpx_content');
		public $admin_links			= array();
		public $visibility			= null;
		public $file_sections		= array();
		public $meta_data			= array();

		public $fileModTime			= 0; /* @deprecated 3.0 */
		public $file_stats			= array();

		//layout & theme
		public $theme_name			= false;
		public $theme_color			= false;
		public $get_theme_css		= true;
		public $theme_dir;
		public $theme_path;
		public $theme_rel;
		public $theme_addon_id		= false;
		public $theme_is_addon		= false;/* @deprecated 3.5 */
		public $menu_css_ordered	= true;
		public $menu_css_indexed	= true;
		public $gpLayout;


		//<head> content
		public $head				= '';
		public $head_js				= array();
		public $head_script			= '';
		public $jQueryCode			= false;
		public $admin_js			= false;
		public $head_force_inline	= false;
		public $meta_description	= '';
		public $meta_keywords		= array();

		//css arrays
		public $css_user			= array();
		public $css_admin			= array();



		public function __construct($title, $type){
			$this->requested	= $title;
			$this->title		= $title;

			$this->head			.= '<link rel="canonical" href="'.\gp\tool::GetUrl($title).'" />'."\n";
		}


		/**
		 * Get page content or do redirect for non-existant titles
		 * see special_missing.php and /Admin/Settings/Missing
		 */
		function Error_404(){
			ob_start();

			$args		= array('page'=>$this);
			$missing	= new \gp\special\Missing($args);
			$missing->RunScript();

			$this->contentBuffer = ob_get_clean();
		}

		function SetVars(){
			global $gp_index, $gp_titles, $gp_menu;

			if( !isset($gp_index[$this->title]) ){
				$this->Error_404();
				return false;
			}

			$this->gp_index		= $gp_index[$this->title];
			$this->TitleInfo	=& $gp_titles[$this->gp_index]; //so changes made by rename are seen
			$this->label		= \gp\tool::GetLabel($this->title);
			$this->file			= \gp\tool\Files::PageFile($this->title);

			if( !$this->CheckVisibility() ){
				return false;
			}

			\gp\tool\Plugins::Action('PageSetVars');

			return true;
		}

		/**
		 * Check the page's visibility
		 *
		 */
		function CheckVisibility(){
			global $gp_titles;

			if( isset($gp_titles[$this->gp_index]['vis']) ){
				$this->visibility = $gp_titles[$this->gp_index]['vis'];
			}
			if( !\gp\tool::LoggedIn() && $this->visibility ){
				$this->Error_404();
				return false;
			}

			return true;
		}


		function RunScript(){

			if( !$this->SetVars() ){
				return;
			}

			//allow addons to effect page actions and how a page is displayed
			$cmd = \gp\tool::GetCommand();
			$cmd_after = \gp\tool\Plugins::Filter('PageRunScript',array($cmd));
			if( $cmd !== $cmd_after ){
				$cmd = $cmd_after;
				if( $cmd === 'return' ){
					return;
				}
			}

			$this->GetFile();

			$this->contentBuffer = \gp\tool\Output\Sections::Render($this->file_sections,$this->title,$this->file_stats);
		}

		/**
		 * Retreive the data file for the current title and update the data if necessary
		 *
		 */
		function GetFile(){

			$this->file_sections	= \gp\tool\Files::Get($this->file,'file_sections');
			$this->meta_data		= \gp\tool\Files::$last_meta;
			$this->fileModTime		= \gp\tool\Files::$last_modified;
			$this->file_stats		= \gp\tool\Files::$last_stats;

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
				$layout = self::OrConfig($this->gp_index,'gpLayout');
			}

			$layout_info = \gp\tool::LayoutInfo($layout);


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

			$this->theme_path = \gp\tool::GetDir($this->theme_rel);

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

				if( self::ParentConfig($id,$var,$value) ){
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

			$parents = \gp\tool::Parents($checkId,$gp_menu);
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
			echo \gp\tool::Link('',$config['title']);
		}
		function GetPageLabel(){
			echo $this->label;
		}

		function GetContent(){

			$this->GetGpxContent();

			echo '<div id="gpAfterContent">';
			\gp\tool\Output::Get('AfterContent');
			\gp\tool\Plugins::Action('GetContent_After');
			echo '</div>';
		}

		function GetGpxContent(){
			$class = '';
			if( isset($this->meta_data['file_number']) ){
				$class = 'filenum-'.$this->meta_data['file_number'];
			}

			if( $this->pagetype == 'display' ){
				$class .= ' gp_page_display';
			}

			echo '<div id="gpx_content" class="'.$class.' cf">';

			echo $this->contentBuffer;


			echo '</div>';
		}

		/* Deprecated functions
		 */
		function GetHead(){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetHead();
		}
		function GetExtra($area,$info=array()){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetExtra($area,$info);
		}
		function GetMenu(){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetMenu();
		}
		function GetFullMenu(){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetFullMenu();
		}
		function GetAllGadgets(){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetAllGadgets();
		}
		function GetAdminLink(){
			trigger_error('deprecated functions');
			\gp\tool\Output::GetAdminLink();
		}


	}
}

namespace{
	class display extends \gp\Page{}
}

