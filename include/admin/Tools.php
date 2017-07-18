<?php

namespace gp\admin{

	defined('is_running') or die('Not an entry point...');

	class Tools{

		public static $new_versions		= array();
		public static $update_status	= 'checklater';
		public static $show_toolbar		= true;


		/**
		 * Check available versions of cms and addons
		 * @static
		 *
		 */
		public static function VersionsAndCheckTime(){
			global $config, $dataDir, $gpLayouts;

			$data_timestamp = self::VersionData($version_data);

			//check core version
			// only report new versions if it's a root install
			if( gp_remote_update && !defined('multi_site_unique') && isset($version_data['packages']['core']) ){
				$core_version = $version_data['packages']['core']['version'];

				if( $core_version && version_compare(gpversion,$core_version,'<') ){
					self::$new_versions['core'] = $core_version;
				}
			}


			//check addon versions
			if( isset($config['addons']) && is_array($config['addons']) ){
				self::CheckArray($config['addons'],$version_data);
			}

			//check theme versions
			if( isset($config['themes']) && is_array($config['themes']) ){
				self::CheckArray($config['themes'],$version_data);
			}

			//check layout versions
			self::CheckArray($gpLayouts,$version_data);


			// checked recently
			$diff = time() - $data_timestamp;
			if( $diff < 604800 ){
				return;
			}

			//determine check in type
			if( \gp\tool\RemoteGet::Test() === false ){
				self::VersionData($version_data);
				self::$update_status = 'checkincompat';
				return;
			}

			self::$update_status = 'embedcheck';
		}


		/**
		 * Get or cache data about available versions of cms and addons
		 *
		 */
		public static function VersionData(&$update_data){
			global $dataDir;

			$file = $dataDir.'/data/_updates/updates.php';

			//set
			if( !is_null($update_data) ){
				return \gp\tool\Files::SaveData($file,'update_data',$update_data);
			}


			$update_data	= \gp\tool\Files::Get('_updates/updates','update_data');
			$update_data	+= array('packages'=>array());

			return \gp\tool\Files::$last_modified;
		}


		public static function CheckArray($array,$update_data){

			foreach($array as $addon => $addon_info){

				$addon_id = false;
				if( isset($addon_info['id']) ){
					$addon_id = $addon_info['id'];
				}elseif( isset($addon_info['addon_id']) ){ //for layouts
					$addon_id = $addon_info['addon_id'];
				}

				if( !$addon_id || !isset($update_data['packages'][$addon_id]) ){
					continue;
				}


				$installed_version = 0;
				if( isset($addon_info['version']) ){
					$installed_version = $addon_info['version'];
				}


				$new_addon_info = $update_data['packages'][$addon_id];
				$new_addon_version = $new_addon_info['version'];
				if( version_compare($installed_version,$new_addon_version,'>=') ){
					continue;
				}

				//new version found
				if( !isset($new_addon_info['name']) && isset($addon_info['name']) ){
					$new_addon_info['name'] = $addon_info['name'];
				}
				self::$new_versions[$addon_id] = $new_addon_info;
			}

		}


		public static function AdminScripts(){
			global $langmessage, $config;
			$scripts = array();


			// Content
			$scripts['Admin/Menu']						= array(	'class'		=> '\\gp\\admin\\Menu',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['file_manager'],
																	'group'		=> 'content',
																	);

			$scripts['Admin/Menu/Menus']				= array(	'class'		=> '\\gp\\admin\\Menu\\Menus',
																	'method'	=> 'RunScript',
																	);

			$scripts['Admin/Menu/Ajax']					= array(	'class'		=> '\\gp\\admin\\Menu\\Ajax',
																	'method'	=> 'RunScript',
																	);


			$scripts['Admin/Uploaded']					= array(	'class'		=> '\\gp\\admin\\Content\\Uploaded',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['uploaded_files'],
																	'group'		=> 'content',
																	);


			$scripts['Admin/Extra']						= array(	'class'		=> '\\gp\\admin\\Content\\Extra',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['theme_content'],
																	'group'		=> 'content',
																	);


			$scripts['Admin/Galleries']					= array(	'class'		=> '\\gp\\admin\\Content\\Galleries',
																	'label'		=> $langmessage['galleries'],
																	'group'		=> 'content',
																	);


			$scripts['Admin/Trash']						= array(	'class'		=> '\\gp\\admin\\Content\\Trash',
																	'label'		=> $langmessage['trash'],
																	'group'		=> 'content',
																	);


			// Appearance
			$scripts['Admin_Theme_Content']				= array(
																	'class'		=> '\\gp\\admin\\Layout',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['Appearance'],
																	'group'		=> 'appearance',
																	);


			$scripts['Admin_Theme_Content/Edit']		= array(	'class'		=> '\\gp\\admin\\Layout\\Edit',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['Appearance'],
																	);



			$scripts['Admin_Theme_Content/Available']	 = array(	'class'		=> '\\gp\\admin\\Layout\\Available',
																	'method'	=> 'ShowAvailable',
																	'label' 	=> $langmessage['Available'],
																	);

			$scripts['Admin_Theme_Content/Text']		= array(	'class'		=> '\\gp\\admin\\Layout\\Text',
																	'method'	=> 'RunScript',
																	);

			$scripts['Admin_Theme_Content/Image']		= array(	'class'		=> '\\gp\\admin\\Layout\\Image',
																	'method'	=> 'RunScript',
																	);

			if( gp_remote_themes ){
				$scripts['Admin_Theme_Content/Remote']	= array(	'class'		=> '\\gp\\admin\\Layout',
																	'method'	=> 'RemoteBrowse',
																	'label' 	=> $langmessage['Search'],
																	);
			}



			// Settings
			$scripts['Admin/Configuration']				= array(	'class'		=> '\\gp\\admin\\Configuration',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['configuration'],
																	'group'		=> 'settings',
																);

			$scripts['Admin/Configuration/CDN']			= array(	'class'		=> '\\gp\\admin\\Configuration\\CDN',
																	'method'	=> 'RunScript',
																	'label'		=> 'CDN',
																	'group'		=> 'settings',
																);

			$scripts['Admin/Users']						= array(	'class'		=> '\\gp\\admin\\Settings\\Users',
																	'label'		=> $langmessage['user_permissions'],
																	'group'		=> 'settings',
																);

			$scripts['Admin/CKEditor']					= array(	'class'		=> '\\gp\\admin\\Settings\\CKEditor',
																	'label'		=> 'CKEditor',
																	'group'		=> 'settings',
																);

			$scripts['Admin/Classes']					= array(	'class'		=> '\\gp\\admin\\Settings\\Classes',
																	'label'		=> $langmessage['Manage Classes'],
																	'group'		=> 'settings',
																);

			$scripts['Admin/Permalinks']				= array(	'class'		=> '\\gp\\admin\\Settings\\Permalinks',
																	'label'		=> $langmessage['permalinks'],
																	'group'		=> 'settings',
																);

			$scripts['Admin/Missing']					= array(	'class'		=> '\\gp\\admin\\Settings\\Missing',
																	'method'	=> 'RunScript',
																	'label'		=> $langmessage['Link Errors'],
																	'group'		=> 'settings',
																);


			if( isset($config['admin_links']) && is_array($config['admin_links']) ){
				$scripts += $config['admin_links'];
			}


			// Tools
			$scripts['Admin/Port']		= array(	'class'		=> '\\gp\\admin\\Tools\\Port',
													'label'		=> $langmessage['Export'],
													'group'		=> 'tools',
													'method'	=> 'RunScript'
												);


			$scripts['Admin/Status']	= array(	'class'		=> '\\gp\\admin\\Tools\\Status',
													'label'		=> $langmessage['Site Status'],
													'group'		=> 'tools'
												);


			$scripts['Admin/Uninstall']	= array(	'class'		=> '\\gp\\admin\\Tools\\Uninstall',
													'label'		=> $langmessage['uninstall_prep'],
													'group'		=> 'tools'
												);


			$scripts['Admin/Cache']		= array(	'class'		=> '\\gp\\admin\\Tools\\Cache',
													'label'		=> $langmessage['Resource Cache'],
													'group'		=> 'tools'
												);



			// Unlisted
			$scripts['Admin/Addons']				= array(	'class'		=> '\\gp\\admin\\Addons',
																'method'	=> 'RunScript',
																'label' 	=> $langmessage['plugins'],
													);

			$scripts['Admin/Addons/Available']		= array(	'class'		=> '\\gp\\admin\\Addons',
																'method'	=> 'ShowAvailable',
																'label' 	=> $langmessage['Available'],
													);

			if( gp_remote_plugins ){
				$scripts['Admin/Addons/Remote']		= array(	'class'		=> '\\gp\\admin\\Addons',
																'method'	=> 'RemoteBrowse',
																'label' 	=> $langmessage['Search'],
													);
			}


			$scripts['Admin/Errors']				= array(	'class'		=> '\\gp\\admin\\Tools\\Errors',
																'label' 	=> 'Errors',
													);


			$scripts['Admin/About']					= array(	'class'		=> '\\gp\\admin\\About',
																'label' 	=> 'About '.CMS_NAME,
													);

			$scripts['Admin/Browser']				= array(	'class'		=> '\\gp\\admin\\Content\\Browser',
																'permission' => 'Admin_Uploaded',
													);


			$scripts['Admin/Preferences']			= array(	'class'		=> '\\gp\\admin\\Settings\\Preferences',
																'label' 	=> $langmessage['Preferences'],
													);


			gpSettingsOverride('admin_scripts',$scripts);

			return $scripts;
		}


		/**
		 * Determine if the current user has permissions for the $script
		 * @static
		 * @return bool
		 */
		public static function HasPermission($script){
			global $gpAdmin;
			if( is_array($gpAdmin) ){
				$gpAdmin += array('granted'=>'');
				return self::CheckPermission($gpAdmin['granted'],$script);
			}
			return false;
		}


		/**
		 * Determine if a user has permissions for the $script
		 * @static
		 * @since 3.0b2
		 * @return bool
		 */
		public static function CheckPermission($granted,$script){

			if( $granted == 'all' ){
				return true;
			}

			$script		= self::WhichPermission($script);
			$granted	= ','.$granted.',';
			if( strpos($granted,','.$script.',') !== false ){
				return true;
			}

			return false;

		}


		/**
		 * Return the permission setting that should be checked against a list of grated permissions
		 * Admin_Browser -> Admin_Uploaded
		 * Admin_Theme_Content/Text -> Admin_Theme_Content
		 *
		 */
		public static function WhichPermission($script){

			// prepare list of permissions
			$scripts	= self::AdminScripts();
			$possible	= array();
			foreach($scripts as $pscript => $info){
				$pscript = str_replace('/','_',$pscript);
				if( isset($info['permission']) ){
					$possible[$pscript] = $info['permission'];

				}elseif( isset($info['label']) ){
					$possible[$pscript] = $pscript;
				}
			}


			// find the relevant permission in the list of possible permissions
			$script		= str_replace('/','_',$script);
			$parts 		= explode('_',$script);

			while($parts){

				$check = implode('_',$parts);
				if( !isset($possible[$check]) ){
					array_pop($parts);
					continue;
				}

				return $possible[$check];
			}

			return $script;
		}


		/**
		 * Determine if a user can edit a specific page
		 * @static
		 * @since 3.0b2
		 * @param string $index The data index of the page
		 * @return bool
		 */
		public static function CanEdit($index){
			global $gpAdmin;

			//pre 3.0 check
			if( !isset($gpAdmin['editing']) ){
				return self::HasPermission('file_editing');
			}

			if( $gpAdmin['editing'] == 'all' ){
				return true;
			}

			if( strpos($gpAdmin['editing'],','.$index.',') !== false ){
				return true;
			}
			return false;
		}


		/**
		 * Used to update the basic 'file_editing' permission value to the new 'editing' value used in 3.0b2+
		 * @since 3.0b2
		 * @static
		 */
		public static function EditingValue(&$user_info){
			if( isset($user_info['editing']) ){
				return;
			}
			if( self::CheckPermission($user_info['granted'],'file_editing') ){
				$user_info['editing'] = 'all';
				return 'all';
			}
			$user_info['editing'] = '';
		}



		/**
		 * Output the main admin toolbar
		 * @static
		 */
		public static function GetAdminPanel(){
			global $page, $gpAdmin;

			//don't send the panel when it's a gpreq=json request
			if( !self::$show_toolbar ){
				return;
			}

			$reqtype = \gp\tool::RequestType();
			if( $reqtype != 'template' && $reqtype != 'admin' ){
				return;
			}

			$class = '';
			$position = '';

			if( \gp\tool::RequestType() != 'admin' ){
				$position = ' style="top:'.max(-10,$gpAdmin['gpui_ty']).'px;left:'.max(-10,$gpAdmin['gpui_tx']).'px"';
				if( isset($gpAdmin['gpui_cmpct']) && $gpAdmin['gpui_cmpct'] ){
					$class = ' compact';
					if( $gpAdmin['gpui_cmpct'] === 2 ){
						$class = ' compact min';
					}elseif( $gpAdmin['gpui_cmpct'] === 3 ){
						$class = ' minb';
					}
				}
			}

			$class = ' class="keep_viewable'.$class.'"';


			echo "\n\n";
			echo '<div id="simplepanel"'.$class.$position.'><div>';

				//toolbar
				echo '<div class="toolbar">';
					echo '<a class="toggle_panel" data-cmd="toggle_panel"></a>';
					echo \gp\tool::Link('','<i class="fa fa-home"></i>');
					echo \gp\tool::Link('Admin','<i class="fa fa-cog"></i>');
					echo \gp\tool::Link('special_gpsearch','<i class="fa fa-search"></i>','',array('data-cmd'=>'gpabox'));
				echo '</div>';


				self::AdminPanelLinks(true);

			echo '</div></div>'; //end simplepanel

			echo "\n\n";

			self::AdminToolbar();
		}


		/**
		 * Show Admin Toolbar
		 *
		 */
		public static function AdminToolbar(){
			global $page, $langmessage;

			if( !method_exists($page,'AdminLinks') ){
				return;
			}

			if( isset($GLOBALS['GP_ARRANGE_CONTENT']) ){
				return;
			}

			$links = $page->AdminLinks();

			if( empty($links) ){
				return;
			}

			echo '<div id="admincontent_panel" class="fixed toolbar cf">';
			echo '<ul>';

			//admin_link
			self::FormatAdminLinks($links);

			echo '</ul>';

			self::ToolbarEditLinks();
			echo '</div>';
		}


		/**
		 * Toolbar edit links
		 *
		 */
		public static function ToolbarEditLinks(){
			global $page, $gp_titles, $langmessage;

			if( !\gp\admin\Tools::CanEdit($page->gp_index) ){
				return;
			}

			echo '<ul  class="panel_tabs" style="float:right">';
			echo '<li class="panel_tab_label">';
			echo ' <i class="fa fa-pencil"></i>';
			echo '</li>';

			//page edit
			if( $page->pagetype == 'display' ){
				echo '<li>';
				echo \gp\tool::Link(
					$page->title,
					$langmessage['Page'],
					'cmd=ManageSections',
					array('data-cmd'=>'inline_edit_generic','data-arg'=>'manage_sections')
				);
				echo '</li>';
			}

			//extra edut
			echo '<li>';
			echo \gp\tool::Link(
				$page->title,
				$langmessage['theme_content'],
				'cmd=ManageSections&mode=extra',
				array('data-cmd'=>'inline_edit_generic','data-arg'=>'manage_sections','data-mode'=>'extra','class'=>'gp_extra_edit')
			);
			echo '</li>';

			//layout edit
			$current_layout = 
				isset($gp_titles[$page->gp_index]['gpLayout']) 
				? $gp_titles[$page->gp_index]['gpLayout'] 
				: 'default'; // $page->gpLAyout is not yet set
			echo '<li>';
			echo \gp\tool::Link(
				'Admin_Theme_Content/Edit/' . urlencode($current_layout),
				$langmessage['layout'],
				'redir=' . rawurlencode($page->requested)
			);
			echo '</li>';
			echo '</ul>';

		}


		public static function FormatAdminLinks($links){
			foreach($links as $label => $link){
				echo '<li>';

				if( is_numeric($label) ){

					if( is_array($link) ){
						echo call_user_func_array(array('\\gp\\tool','Link'),$link); /* preferred */
					}else{
						echo $link; //just a text label
					}
					echo '<li>';
					continue;
				}


				if( empty($link) ){
					echo '<span>';
					echo $label;
					echo '</span>';

				}elseif( is_array($link) ){
					echo '<a data-cmd="expand"><i class="fa fa-caret-down"></i> '.$label.'</a>';
					echo '<ul>';
					self::FormatAdminLinks($link);
					echo '</ul>';

				}else{
					echo '<a href="'.$link.'">';
					echo $label;
					echo '</a>';
				}

				echo '</li>';
			}
		}



		/**
		 * Output the link areas that are displayed in the main admin toolbar and admin_main
		 * @param bool $in_panel Whether or not the links will be displayed in the toolbar
		 * @static
		 */
		public static function AdminPanelLinks($in_panel=true){
			global $langmessage, $page, $gpAdmin;

			//content
			$links = self::GetAdminGroup('content');
			self::_AdminPanelLinks($in_panel, $links, 'Content', 'fa fa-file-text-o', 'con');


			//appearance
			$links = self::GetAppearanceGroup($in_panel);
			self::_AdminPanelLinks($in_panel, $links, 'Appearance', 'fa fa-th', 'app');


			//add-ons
			$addon_links = self::GetAddonLinks($in_panel); // now returns array( (string)links, (boolean)permissions )
			$links = $addon_links[0];
			$addon_permissions = $addon_links[1];
			// msg("Any Addon Permisisons? " . pre($addon_permissions) );
			if( $addon_permissions ){
				self::_AdminPanelLinks($in_panel, $links, 'plugins', 'fa fa-plug', 'add');
			}


			//settings
			$links = self::GetAdminGroup('settings');
			self::_AdminPanelLinks($in_panel, $links, 'Settings', 'fa fa-sliders', 'set');

			//tools
			$links = self::GetAdminGroup('tools');
			self::_AdminPanelLinks($in_panel, $links, 'Tools', 'fa fa-wrench', 'tool');


			//updates
			if( count(self::$new_versions) > 0 ){

				ob_start();
				if( gp_remote_update && isset(self::$new_versions['core']) ){
					echo '<li>';
					echo '<a href="'.\gp\tool::GetDir('/include/install/update.php').'">'.CMS_NAME.' '.self::$new_versions['core'].'</a>';
					echo '</li>';
				}

				foreach(self::$new_versions as $addon_id => $new_addon_info){

					if( !is_numeric($addon_id) ){
						continue;
					}

					$label		= $new_addon_info['name'].':  '.$new_addon_info['version'];
					$url		= self::RemoteUrl( $new_addon_info['type'] );

					if( $url === false ){
						continue;
					}

					echo '<li><a href="'.$url.'/'.$addon_id.'" data-cmd="remote">'.$label.'</a></li>';

				}

				$links = ob_get_clean();

				self::_AdminPanelLinks($in_panel, $links, 'updates', 'fa fa-refresh', 'upd');
			}


			//username
			ob_start();
			self::GetFrequentlyUsed($in_panel);

			echo '<li>';
			echo \gp\tool::Link('Admin/Preferences',$langmessage['Preferences']);
			echo '</li>';

			echo '<li>';
			echo \gp\tool::Link($page->title,$langmessage['logout'],'cmd=logout',array('data-cmd'=>'creq'));
			echo '</li>';

			echo '<li>';
			echo \gp\tool::Link('Admin/About','About '.CMS_NAME);
			echo '</li>';
			$links = ob_get_clean();
			self::_AdminPanelLinks($in_panel, $links, $gpAdmin['useralias'], 'fa fa-user', 'use');



			// stats
			ob_start();
			echo '<li><span><span cms-memory-usage>?</span> Memory</span></li>';
			echo '<li><span><span cms-memory-max>?</span> Max Memory</span></li>';
			echo '<li><span><span cms-seconds>?</span> Seconds</span></li>';
			echo '<li><span><span cms-ms>?</span> Milliseconds</span></li>';
			echo '<li><span>0 DB Queries</span></li>';
			$links = ob_get_clean();
			self::_AdminPanelLinks($in_panel, $links, 'Performance', 'fa fa-bar-chart', 'cms');



			//resources
			if( $page->pagetype === 'admin_display' ){
				ob_start();
				if( gp_remote_plugins && self::HasPermission('Admin_Addons') ){
					echo '<li>'.\gp\tool::Link('Admin/Addons/Remote',$langmessage['Download Plugins']).'</li>';
				}
				if( gp_remote_themes && self::HasPermission('Admin_Theme_Content') ){
					echo '<li>'.\gp\tool::Link('Admin_Theme_Content/Remote',$langmessage['Download Themes']).'</li>';
				}
				echo '<li><a href="'.CMS_DOMAIN.'/Forum">Support Forum</a></li>';
				echo '<li><a href="'.CMS_DOMAIN.'/Services">Service Providers</a></li>';
				echo '<li><a href="'.CMS_DOMAIN.'">Official '.CMS_NAME.' Site</a></li>';
				echo '<li><a href="https://github.com/Typesetter/Typesetter/issues">Report A Bug</a></li>';

				$links = ob_get_clean();
				self::_AdminPanelLinks($in_panel, $links, 'resources', 'fa fa-globe', 'res');


				if( $in_panel ){
					echo '<div class="gpversion">';
					echo CMS_NAME.' '.gpversion;
					echo '</div>';
				}

			}
		}


		/**
		 * Get the appropriate remote browse url if available
		 *
		 */
		public static function RemoteUrl($type){

			if( $type == 'theme' || $type == 'themes' ){
				if( gp_remote_themes ){
					return addon_browse_path.'/Themes';
				}
			}

			if( $type == 'plugin' || $type == 'plugins' ){
				if( gp_remote_plugins ){
					return addon_browse_path.'/Plugins';
				}
			}

			return false;
		}


		/**
		 * Helper function for outputing link groups in AdminPanelLinks()
		 *
		 */
		private static function _AdminPanelLinks($in_panel, $links, $lang_key, $icon_class, $panel_arg){
			global $langmessage;

			if( empty($links) ){
				return;
			}

			$label = isset($langmessage[$lang_key]) ? $langmessage[$lang_key] : $lang_key;

			echo '<div class="panelgroup">';
			self::PanelHeading($in_panel, $label, $icon_class, $panel_arg );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$label.'</a></li>';
			echo $links;
			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		public static function PanelHeading( $in_panel, $label, $icon, $arg ){
			global $gpAdmin;

			if( !$in_panel ){
				echo '<span>';
				echo '<i class="'.$icon.'"></i> ';
				echo '<span>'.$label.'</span>';
				echo '</span>';
				echo '<div class="panelgroup2">';
				return;
			}

			echo '<a class="toplink" data-cmd="toplink" data-arg="'.$arg.'">';
			echo '<i class="'.$icon.'"></i>';
			echo '<span>'.$label.'</span>';
			echo '</a>';

			if( $gpAdmin['gpui_vis'] == $arg ){
				echo '<div class="panelgroup2 in_window">';
			}else{
				echo '<div class="panelgroup2 in_window nodisplay">';
			}

		}


		/**
		 * Get the links for the Frequently Used section of the admin toolbar
		 *
		 */
		public static function GetFrequentlyUsed($in_panel){
			global $langmessage, $gpAdmin;

			$expand_class = 'expand_child';
			if( !$in_panel ){
				$expand_class = 'expand_child_click';
			}

			//frequently used
			echo '<li class="'.$expand_class.'">';
				echo '<a>';
				echo $langmessage['frequently_used'];
				echo '</a>';
				if( $in_panel ){
					echo '<ul class="in_window">';
				}else{
					echo '<ul>';
				}
				$scripts = self::AdminScripts();
				$add_one = true;
				if( isset($gpAdmin['freq_scripts']) ){
					foreach($gpAdmin['freq_scripts'] as $link => $hits ){
						if( isset($scripts[$link]) && isset($scripts[$link]['label']) ){
							echo '<li>';
							echo \gp\tool::Link($link,$scripts[$link]['label']);
							echo '</li>';
							if( $link === 'Admin/Menu' ){
								$add_one = false;
							}
						}
					}
					if( $add_one && count($gpAdmin['freq_scripts']) >= 5 ){
						$add_one = false;
					}
				}
				if( $add_one ){
					echo '<li>';
					echo \gp\tool::Link('Admin/Menu',$scripts['Admin/Menu']['label']);
					echo '</li>';
				}
				echo '</ul>';
			echo '</li>';
		}


		//uses $status from update codes to execute some cleanup code on a regular interval (7 days)
		public static function ScheduledTasks(){
			global $dataDir;

			switch(self::$update_status){
				case 'embedcheck':
				case 'checkincompat':
					//these will continue
				break;

				case 'checklater':
				default:
				return;
			}

			self::CleanCache();

		}


		/**
		 * Delete all files older than 2 weeks
		 * If there are more than 200 files older than one week
		 *
		 */
		public static function CleanCache(){
			global $dataDir;
			$dir = $dataDir.'/data/_cache';

			if( !file_exists($dir) ){
				return;
			}

			$files = scandir($dir);
			$times = array();
			foreach($files as $file){
				if( $file == '.' || $file == '..' || strpos($file,'.php') !== false ){
					continue;
				}
				$full_path	= $dir.'/'.$file;
				$time		= filemtime($full_path);
				$diff		= time() - $time;

				//if relatively new ( < 3 days), don't delete it
				if( $diff < 259200 ){
					continue;
				}

				//if old ( > 14 days ), delete it
				if( $diff > 1209600 ){
					\gp\tool\Files::RmAll($full_path);
					continue;
				}
				$times[$file] = $time;
			}

			//reduce further if needed till we have less than 200 files
			arsort($times);
			$times = array_keys($times);
			while( count($times) > 200 ){
				$full_path = $dir.'/'.array_pop($times);
				\gp\tool\Files::RmAll($full_path);
			}
		}


		public static function AdminHtml(){
			global $page, $gp_admin_html;

			ob_start();

			echo '<div class="nodisplay" id="gp_hidden"></div>';

			if( isset($page->admin_html) ){
				echo $page->admin_html;
			}

			self::GetAdminPanel();


			self::CheckStatus();
			self::ScheduledTasks();
			$gp_admin_html = ob_get_clean() . $gp_admin_html;

		}

		public static function CheckStatus(){

			switch(self::$update_status){
				case 'embedcheck':
					$img_path = \gp\tool::GetUrl('Admin','cmd=embededcheck');
					\gp\tool::IdReq($img_path);
				break;
				case 'checkincompat':
					$img_path = \gp\tool::IdUrl('ci'); //check in
					\gp\tool::IdReq($img_path);
				break;
			}
		}



		public static function GetAdminGroup($grouping){
			global $langmessage,$page;

			$scripts = self::AdminScripts();

			ob_start();
			foreach($scripts as $script => $info){

				if( !isset($info['group']) || $info['group'] !== $grouping ){
					continue;
				}

				if( !self::HasPermission($script) ){
					continue;
				}
				echo '<li>';

				if( isset($info['popup']) && $info['popup'] == true ){
					echo \gp\tool::Link($script,$info['label'],'',array('data-cmd'=>'gpabox'));
				}else{
					echo \gp\tool::Link($script,$info['label']);
				}


				echo '</li>';

				switch($script){
					case 'Admin/Menu':
						echo '<li>';
						echo \gp\tool::Link('Admin/Menu/Ajax','+ '.$langmessage['create_new_file'],'cmd=AddHidden&redir=redir',array('title'=>$langmessage['create_new_file'],'data-cmd'=>'gpabox'));
						echo '</li>';
					break;
				}

			}


			$result = ob_get_clean();
			if( !empty($result) ){
				return $result;
			}
			return false;
		}

		public static function GetAppearanceGroup($in_panel){
			global $page, $langmessage, $gpLayouts, $config;

			if( !self::HasPermission('Admin_Theme_Content') ){
				return false;
			}

			ob_start();

			echo '<li>';
			echo \gp\tool::Link('Admin_Theme_Content',$langmessage['manage']);
			echo '</li>';

			if( !empty($page->gpLayout) ){
				echo '<li>';
				echo \gp\tool::Link('Admin_Theme_Content/Edit/'.urlencode($page->gpLayout),$langmessage['edit_this_layout']);
				echo '</li>';
			}
			echo '<li>';
			echo \gp\tool::Link('Admin_Theme_Content/Available',$langmessage['available_themes']);
			echo '</li>';
			if( gp_remote_themes ){
				echo '<li>';
				echo \gp\tool::Link('Admin_Theme_Content/Remote',$langmessage['Download Themes']);
				echo '</li>';
			}

			//list of layouts
			$expand_class = 'expand_child';
			if( !$in_panel ){
				$expand_class = 'expand_child_click';
			}

			echo '<li class="'.$expand_class.'">';
			echo '<a>'.$langmessage['layouts'].'</a>';
			if( $in_panel ){
				echo '<ul class="in_window">';
			}else{
				echo '<ul>';
			}


			if( !empty($page->gpLayout) ){
				$to_hightlight = $page->gpLayout;
			}else{
				$to_hightlight = $config['gpLayout'];
			}

			foreach($gpLayouts as $layout => $info){
				if( $to_hightlight == $layout ){
					echo '<li class="selected">';
				}else{
					echo '<li>';
				}

				$display = '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span>&nbsp; '.$info['label'];
				echo \gp\tool::Link('Admin_Theme_Content/Edit/'.rawurlencode($layout),$display);
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';

			return ob_get_clean();
		}



		/**
		 * Clean a string for use in a page label
		 * Some tags will be allowed
		 *
		 */
		public static function PostedLabel($string){

			// Remove control characters
			$string = preg_replace( '#[[:cntrl:]]#u', '', $string ) ; //[\x00-\x1F\x7F]

			//change known entities to their character equivalent
			$string = \gp\tool\Strings::entity_unescape($string);

			return self::LabelHtml($string);
		}

		/**
		 * Convert a label to a slug
		 * Does not use PostedSlug() so entity_unescape isn't called twice
		 * @since 2.5b1
		 *
		 */
		public static function LabelToSlug($string){
			return self::PostedSlug( $string, true);
		}


		/**
		 * Clean a slug posted by the user
		 * @param string $slug The slug provided by the user
		 * @return string
		 * @since 2.4b5
		 */
		public static function PostedSlug($string, $from_label = false){
			global $config;

			$orig_string	= $string;

			$string			= \gp\tool\Editing::Sanitize($string);

			//illegal characters
			$string = str_replace( array('?','*',':','|'), array('','','',''), $string);

			//change known entities to their character equivalent
			$string = \gp\tool\Strings::entity_unescape($string);


			//if it's from a label, remove any html
			if( $from_label ){
				$string = self::LabelHtml($string);
				$string = strip_tags($string);

				//after removing tags, unescape special characters
				$string = str_replace( array('&lt;','&gt;','&quot;','&#39;','&amp;'), array('<','>','"',"'",'&'), $string);
			}

			// # character after unescape for entities and unescape of special chacters when $from_label is true
			$string = str_replace('#','',$string);

			//slashes
			$string = self::SlugSlashes($string);

			$string = str_replace(' ',$config['space_char'],$string);

			return \gp\tool\Plugins::Filter('PostedSlug',array($string, $orig_string, $from_label));
		}

		/**
		 * Fix the html for page labels
		 *
		 */
		public static function LabelHtml($string){

			//prepend with space for preg_split(), space will be trimmed at the end
			$string = ' '.$string;

			//change non html entity uses of & to &amp; (not exact but should be sufficient)
			$pieces = preg_split('#(&(?:\#[0-9]{2,4}|[a-zA-Z0-9]{2,8});)#',$string,0,PREG_SPLIT_DELIM_CAPTURE);
			$string = '';
			for($i=0;$i<count($pieces);$i++){
				if( $i%2 ){
					$string .= $pieces[$i];
				}else{
					$string .= str_replace('&','&amp;',$pieces[$i]);
				}
			}

			//change non html tag < and > into &lt; and &gt;
			$pieces = preg_split('#(<(?:/?)[a-zA-Z0-9][^<>]*>)#',$string,0,PREG_SPLIT_DELIM_CAPTURE);
			$string = '';
			for($i=0;$i< count($pieces);$i++){
				if( $i%2 ){
					$string .= $pieces[$i];
				}else{
					$string .= \gp\tool::LabelSpecialChars($pieces[$i]);
				}
			}

			//only allow tags that are legal to be inside <a> except for <script>.Per http://www.w3.org/TR/xhtml1/dtds.html#dtdentry_xhtml1-strict.dtd_a.content
			$string = strip_tags($string,'<abbr><acronym><b><big><bdo><br><button><cite><code><del><dfn><em><kbd><i><img><input><ins><label><map><object><q><samp><select><small><span><sub><sup><strong><textarea><tt><var>');

			return trim($string);
		}


		/**
		 * Remove slashes and dots from a slug that could cause navigation problems
		 *
		 */
		public static function SlugSlashes($string){

			$string = str_replace('\\','/',$string);

			//remove leading "./"
			$string = preg_replace('#^\.+[\\\\/]#','/',$string);

			//remove trailing "/."
			$string = preg_replace('#[\\\\/]\.+$#','/',$string);

			//remove any "/./"
			$string = preg_replace('#[\\\\/]\.+[\\\\/]#','/',$string);

			//remove consecutive slashes
			$string = preg_replace('#[\\\\/]+#','/',$string);

			if( $string == '.' ){
				return '';
			}

			return ltrim($string,'/');
		}



		/**
		 * Case insenstively check the title against all other titles
		 *
		 * @param string $title The title to be checked
		 * @return mixed false or the data index of the matched title
		 * @since 2.4b5
		 */
		public static function CheckTitleCase($title){
			global $gp_index;

			$titles_lower = array_change_key_case($gp_index,CASE_LOWER);
			$title_lower = strtolower($title);
			if( isset($titles_lower[$title_lower]) ){
				return $titles_lower[$title_lower];
			}

			return false;
		}

		/**
		 * Check a title against existing titles, special pages and reserved unique string
		 *
		 * @param string $title The title to be checked
		 * @return mixed false if the title doesn't exist, string if a conflict is found
		 * @since 2.4b5
		 */
		public static function CheckTitle($title,&$message){
			global $gp_index, $config, $langmessage;

			if( empty($title) ){
				$message = $langmessage['TITLE_REQUIRED'];
				return false;
			}

			if( isset($gp_index[$title]) ){
				$message = $langmessage['TITLE_EXISTS'];
				return false;
			}

			$type = \gp\tool::SpecialOrAdmin($title);
			if( $type !== false ){
				$message = $langmessage['TITLE_RESERVED'];
				return false;
			}

			$prefix = substr($config['gpuniq'],0,7).'_';
			if( strpos($title,$prefix) !== false ){
				$message = $langmessage['TITLE_RESERVED'].' (2)';
				return false;
			}

			if( strlen($title) > 100 ){
				$message = $langmessage['LONG_TITLE'];
				return false;
			}

			return true;
		}

		/**
		 * Check a title against existing titles and special pages
		 *
		 */
		public static function CheckPostedNewPage($title,&$message){
			global $langmessage,$gp_index, $config;

			$title = self::LabelToSlug($title);

			if( !self::CheckTitle($title,$message) ){
				return false;
			}

			if( self::CheckTitleCase($title) ){
				$message = $langmessage['TITLE_EXISTS'];
				return false;
			}

			return $title;
		}


		/**
		 * Save config.php and pages.php
		 *
		 */
		public static function SaveAllConfig(){
			if( !self::SaveConfig() ){
				return false;
			}

			if( !self::SavePagesPHP() ){
				return false;
			}
			return true;
		}

		/**
		 * Save CMS page info
		 * @return bool
		 *
		 */
		public static function SavePagesPHP($notify_fail = false, $notify_save = false){
			global $gp_index, $gp_titles, $gp_menu, $gpLayouts, $dataDir, $langmessage;

			$saved = false;
			if( is_array($gp_menu) && is_array($gp_index) && is_array($gp_titles) && is_array($gpLayouts) ){

				$pages					= array();
				$pages['gp_menu']		= $gp_menu;
				$pages['gp_index']		= $gp_index;
				$pages['gp_titles']		= $gp_titles;
				$pages['gpLayouts']		= $gpLayouts;

				$saved = \gp\tool\Files::SaveData($dataDir.'/data/_site/pages.php','pages',$pages);
			}

			return self::SaveNotify($saved, $notify_fail, $notify_save, ' (Page info not saved)');
		}


		/**
		 * Save the CMS configuration
		 * @return bool
		 *
		 */
		public static function SaveConfig($notify_fail = false, $notify_save = false){
			global $config, $langmessage;

			$saved = is_array($config) && \gp\tool\Files::SaveData('_site/config','config',$config);

			return self::SaveNotify($saved, $notify_fail, $notify_save, ' (Config not saved)');
		}

		/**
		 * Return the save result and notify the user if needed
		 *
		 * @param bool $result
		 * @param bool $notify_fail
		 * @param bool $noltify_save
		 * @param string $append
		 */
		public static function SaveNotify($result, $notify_fail, $notify_save, $append = '' ){
			global $langmessage;

			if( $result && $notify_save ){
				msg($langmessage['SAVED']);

			}elseif( !$result && $notify_fail ){
				msg($langmessage['OOPS'].' '.$append);
			}

			return $result;
		}


		/**
		 * @deprecated
		 * used by simpleblog1
		 */
		public static function tidyFix(&$text){
			trigger_error('tidyFix should be called using gp_edit::tidyFix() instead of admin_tools:tidyFix()');
			return false;
		}



		/**
		 * Returns an array 
		 * 	0 => html of the addon section of the admin panel
		 * 	1 => boolean indicating if the current user has any addon admin permissions or there are special links
		 * @return array
		 */
		public static function GetAddonLinks($in_panel){
			global $langmessage, $config;

			$any_permissions = false;

			$expand_class = 'expand_child';
			if( !$in_panel ){
				$expand_class = 'expand_child_click';
			}

			ob_start();

			$addon_permissions = self::HasPermission('Admin_Addons');

			if( $addon_permissions ){
				$any_permissions = true;
				echo '<li>';
				echo \gp\tool::Link('Admin/Addons',$langmessage['manage']);
				echo '</li>';
				if( gp_remote_plugins ){
					echo '<li class="separator">';
					echo \gp\tool::Link('Admin/Addons/Remote',$langmessage['Download Plugins']);
					echo '</li>';
				}
			}


			$show =& $config['addons'];
			if( is_array($show) ){

				foreach($show as $addon => $info){

					//backwards compat
					if( is_string($info) ){
						$addonName = $info;
					}elseif( isset($info['name']) ){
						$addonName = $info['name'];
					}else{
						$addonName = $addon;
					}

					$addon_sublinks = self::GetAddonSubLinks($addon);
					$sublinks = $addon_sublinks[0];
					$addon_permissions = $addon_sublinks[1];
					$any_permissions = $addon_permissions ? true : $any_permissions;

					if( $addon_permissions ){
						if( !empty($sublinks) ){
							echo '<li class="'.$expand_class.'">';
							if( $in_panel ){
								$sublinks = '<ul class="in_window">'.$sublinks.'</ul>';
							}else{
								$sublinks = '<ul>'.$sublinks.'</ul>';
							}
						}else{
							echo '<li>';
						}

						echo \gp\tool::Link('Admin/Addons/'.self::encode64($addon),$addonName);

						echo $sublinks;

						echo '</li>';
					}
				}
			}


			$links = ob_get_clean();
			$any_permissions = true;
			return array($links, $any_permissions);

		}

		/**
		* Determine if the installation should be allowed to process remote installations
		*
		*/
		public static function CanRemoteInstall(){
			static $bit;

			if( isset($bit) ){
				return $bit;
			}

			if( !gp_remote_themes && !gp_remote_plugins ){
				return $bit = 0;
			}

			if( !function_exists('gzinflate') ){
				return $bit = 0;
			}

			if( \gp\tool\RemoteGet::Test() === false ){
				return $bit = 0;
			}

			if( gp_remote_themes ){
				$bit = 1;
			}
			if( gp_remote_plugins ){
				$bit += 2;
			}

			return $bit;
		}



		/**
		 * Returns an array 
		 * 	0 => formatted list of links associated with $addon
		 * 	1 => boolean indicating if the current user has addon admin permissions or if special pages exist
		 * @return array
		 */
		public static function GetAddonSubLinks($addon=false){
			global $config;
			$any_permissions = false;

			$special_links	= self::GetAddonTitles( $addon);
			$admin_links	= self::GetAddonComponents( $config['admin_links'], $addon);


			$result = '';
			foreach($special_links as $linkName => $linkInfo){
				$any_permissions = true;
				$result .= '<li>';
				$result .= \gp\tool::Link($linkName,$linkInfo['label']);
				$result .= '</li>';
			}

			foreach($admin_links as $linkName => $linkInfo){
				if( self::HasPermission($linkName) ){
					$any_permissions = true;
					$result .= '<li>';
					$result .= \gp\tool::Link($linkName,$linkInfo['label']);
					$result .= '</li>';
				}
			}
			return array($result, $any_permissions);
		}




		/**
		 * Get the titles associate with $addon
		 * Similar to GetAddonComponents(), but built for $gp_titles
		 * @return array List of addon links
		 *
		 */
		public static function GetAddonTitles($addon){
			global $gp_index, $gp_titles;

			$sublinks = array();
			foreach($gp_index as $slug => $id){
				$info = $gp_titles[$id];
				if( !is_array($info) ){
					continue;
				}
				if( !isset($info['addon']) ){
					continue;
				}
				if( $info['addon'] !== $addon ){
					continue;
				}
				$sublinks[$slug] = $info;
			}
			return $sublinks;
		}

		/**
		 * Get the admin titles associate with $addon
		 * @return array List of addon links
		 *
		 */
		public static function GetAddonComponents($from,$addon){
			$result = array();

			if( !is_array($from) ){
				return $result;
			}

			foreach($from as $name => $value){
				if( !is_array($value) ){
					continue;
				}
				if( !isset($value['addon']) ){
					continue;
				}
				if( $value['addon'] !== $addon ){
					continue;
				}
				$result[$name] = $value;
			}

			return $result;
		}


		public static function FormatBytes($size, $precision = 2){
			$base = log($size) / log(1024);
			$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
			$floor = max(0,floor($base));
			return round(pow(1024, $base - $floor), $precision) .' '. $suffixes[$floor];
		}

		/**
		 * Base convert that handles large numbers
		 *
		 */
		public static function base_convert($str, $frombase=10, $tobase=36) {
			$str = trim($str);
			if (intval($frombase) != 10) {
				$len = strlen($str);
				$q = 0;
				for ($i=0; $i<$len; $i++) {
					$r = base_convert($str[$i], $frombase, 10);
					$q = bcadd(bcmul($q, $frombase), $r);
				}
			}
			else $q = $str;

			if (intval($tobase) != 10) {
				$s = '';
				while (bccomp($q, '0', 0) > 0) {
					$r = intval(bcmod($q, $tobase));
					$s = base_convert($r, 10, $tobase) . $s;
					$q = bcdiv($q, $tobase, 0);
				}
			}
			else $s = $q;

			return $s;
		}


		/**
		 * Return the size in bytes of the /data directory
		 *
		 */
		public static function DiskUsage(){
			global $dataDir;

			$dir = $dataDir.'/data';
			return self::DirSize($dir);
		}

		public static function DirSize($dir){
			$size = 0;
			$files = scandir($dir);
			$len = count($files);
			for($i=0;$i<$len;$i++){
				$file = $files[$i];
				if( $file == '.' || $file == '..' ){
					continue;
				}
				$full_path = $dir.'/'.$file;
				if( is_link($full_path) ){
					continue;
				}
				if( is_dir($full_path) ){
					$size += self::DirSize($full_path);
					continue;
				}

				$size += filesize($full_path);
			}
			return $size;
		}

		public static function encode64( $input ){
			$encoded	= base64_encode($input);
			$encoded	= rtrim($encoded,'=');
			return strtr($encoded, '+/', '-_');
		}

		public static function decode64( $input ){
			$mod = strlen($input) % 4;
			if( $mod !== 0 ){
				$append_len	= 4 - $mod;
				$input		.= substr('===',0,$append_len);
			}
			return base64_decode(strtr($input, '-_', '+/'));
		}


		/**
		 * Return the time in a human readable string
		 *
		 */
		public static function Elapsed($difference){
			$periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
			$lengths = array('60','60','24','7','4.35','12','10');

			for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			   $difference /= $lengths[$j];
			}

			$difference = round($difference);

			if($difference != 1) {
			   $periods[$j].= 's';
			}

			return $difference.' '.$periods[$j];
		}

		//deprecated v4.4
		public static function AdminContentPanel(){}
		public static function AdminContainer(){}
	}
}

namespace{
	class admin_tools extends \gp\admin\Tools{}
}

