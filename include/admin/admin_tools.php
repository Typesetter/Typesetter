<?php
defined('is_running') or die('Not an entry point...');

class admin_tools{


	static function AdminScripts(){
		global $langmessage, $config;
		$scripts = array();


		/**
		 * Settings
		 *
		 */
		$scripts['Admin_Menu']['script'] = '/include/admin/admin_menu_new.php';
		$scripts['Admin_Menu']['class'] = 'admin_menu_new';
		$scripts['Admin_Menu']['label'] = $langmessage['file_manager'];
		$scripts['Admin_Menu']['group'] = 'content';


		$scripts['Admin_Uploaded']['script'] = '/include/admin/admin_uploaded.php';
		$scripts['Admin_Uploaded']['class'] = 'admin_uploaded';
		$scripts['Admin_Uploaded']['label'] = $langmessage['uploaded_files'];
		$scripts['Admin_Uploaded']['group'] = 'content';


		$scripts['Admin_Extra']['script'] = '/include/admin/admin_extra.php';
		$scripts['Admin_Extra']['class'] = 'admin_extra';
		$scripts['Admin_Extra']['label'] = $langmessage['theme_content'];
		$scripts['Admin_Extra']['group'] = 'content';


		$scripts['Admin_Trash']['script'] = '/include/admin/admin_trash.php';
		$scripts['Admin_Trash']['class'] = 'admin_trash';
		$scripts['Admin_Trash']['label'] = $langmessage['trash'];
		$scripts['Admin_Trash']['group'] = 'content';


		/**
		 * Appearance
		 *
		 */
		$scripts['Admin_Theme_Content']['script'] = '/include/admin/admin_theme_content.php';
		$scripts['Admin_Theme_Content']['class'] = 'admin_theme_content';
		$scripts['Admin_Theme_Content']['label'] = $langmessage['Manage Layouts'];
		$scripts['Admin_Theme_Content']['group'] = 'appearance';



		/**
		 * Settings
		 *
		 */
		$scripts['Admin_Configuration']['script'] = '/include/admin/admin_configuration.php';
		$scripts['Admin_Configuration']['class'] = 'admin_configuration';
		$scripts['Admin_Configuration']['label'] = $langmessage['configuration'];
		$scripts['Admin_Configuration']['group'] = 'settings';
		$scripts['Admin_Configuration']['popup'] = true;


		$scripts['Admin_Users']['script'] = '/include/admin/admin_users.php';
		$scripts['Admin_Users']['class'] = 'admin_users';
		$scripts['Admin_Users']['label'] = $langmessage['user_permissions'];
		$scripts['Admin_Users']['group'] = 'settings';


		$scripts['Admin_CKEditor']['script'] = '/include/admin/admin_ckeditor.php';
		$scripts['Admin_CKEditor']['class'] = 'admin_ckeditor';
		$scripts['Admin_CKEditor']['label'] = 'CKEditor';
		$scripts['Admin_CKEditor']['group'] = 'settings';



		$scripts['Admin_Permalinks']['script'] = '/include/admin/admin_permalinks.php';
		$scripts['Admin_Permalinks']['class'] = 'admin_permalinks';
		$scripts['Admin_Permalinks']['label'] = $langmessage['permalinks'];
		$scripts['Admin_Permalinks']['group'] = 'settings';


		$scripts['Admin_Missing']['script'] = '/include/admin/admin_missing.php';
		$scripts['Admin_Missing']['class'] = 'admin_missing';
		$scripts['Admin_Missing']['label'] = $langmessage['Link Errors'];
		$scripts['Admin_Missing']['group'] = 'settings';



		if( isset($config['admin_links']) && is_array($config['admin_links']) ){
			$scripts += $config['admin_links'];
		}

		$scripts['Admin_Port']['script'] = '/include/admin/admin_port.php';
		$scripts['Admin_Port']['class'] = 'admin_port';
		//$scripts['Admin_Port']['label'] = $langmessage['Import/Export'];
		$scripts['Admin_Port']['label'] = $langmessage['Export'];
		$scripts['Admin_Port']['group'] = 'settings';


		$scripts['Admin_Status']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Status']['class'] = 'admin_status';
		$scripts['Admin_Status']['label'] = $langmessage['Site Status'];
		$scripts['Admin_Status']['group'] = 'settings';


		$scripts['Admin_Uninstall']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Uninstall']['class'] = 'admin_rm';
		$scripts['Admin_Uninstall']['label'] = $langmessage['uninstall_prep'];
		$scripts['Admin_Uninstall']['group'] = 'settings';



		/*
		 * 	Unlisted
		 */


		$scripts['Admin_Addons']['script'] = '/include/admin/admin_addons.php';
		$scripts['Admin_Addons']['class'] = 'admin_addons';
		$scripts['Admin_Addons']['label'] = $langmessage['plugins'];
		$scripts['Admin_Addons']['list'] = false;


		$scripts['Admin_Errors']['script'] = '/include/admin/admin_errors.php';
		$scripts['Admin_Errors']['class'] = 'admin_errors';
		$scripts['Admin_Errors']['label'] = 'Errors';
		$scripts['Admin_Errors']['group'] = false;


/*
		$scripts['Admin_Addon_Themes']['script'] = '/include/admin/admin_addon_themes.php';
		$scripts['Admin_Addon_Themes']['class'] = 'admin_addon_themes';
		$scripts['Admin_Addon_Themes']['label'] = $langmessage['addon_themes'];
		$scripts['Admin_Addon_Themes']['list'] = false;
*/

		return $scripts;
	}


	/**
	 * Determine if the current user has permissions for the $script
	 * @static
	 * @return bool
	 */
	static function HasPermission($script){
		global $gpAdmin;
		if( is_array($gpAdmin) ){
			$gpAdmin += array('granted'=>'');
			return admin_tools::CheckPermission($gpAdmin['granted'],$script);
		}
		return false;
	}

	/**
	 * Determine if a user has permissions for the $script
	 * @static
	 * @since 3.0b2
	 * @return bool
	 */
	static function CheckPermission($granted,$script){

		if( $granted == 'all' ){
			return true;
		}

		$granted = ','.$granted.',';
		if( strpos($granted,','.$script.',') !== false ){
			return true;
		}

		return false;

	}

	/**
	 * Determine if a user can edit a specific page
	 * @static
	 * @since 3.0b2
	 * @param string $index The data index of the page
	 * @return bool
	 */
	static function CanEdit($index){
		global $gpAdmin;

		//pre 3.0 check
		if( !isset($gpAdmin['editing']) ){
			return admin_tools::HasPermission('file_editing');
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
	static function EditingValue(&$user_info){
		if( isset($user_info['editing']) ){
			return;
		}
		if( admin_tools::CheckPermission($user_info['granted'],'file_editing') ){
			$user_info['editing'] = 'all';
			return 'all';
		}
		$user_info['editing'] = '';
	}



	/**
	 * Output the main admin toolbar
	 * @param array $new_versions Data about newly available versions of gpEasy and addons
	 * @static
	 */
	static function GetAdminPanel($new_versions){
		global $page, $gpAdmin, $config;

		//don't send the panel when it's a gpreq=json request
		if( !empty($_REQUEST['gpreq']) ){
			return;
		}

		$class = '';
		if( isset($gpAdmin['gpui_cmpct']) && $gpAdmin['gpui_cmpct'] ){
			$class = ' compact';
			if( $gpAdmin['gpui_cmpct'] === 2 ){
				$class = ' compact min';
			}elseif( $gpAdmin['gpui_cmpct'] === 3 ){
				$class = ' minb';
			}
		}
		$class = ' class="keep_viewable'.$class.'"';

		$position = ' style="top:'.max(-10,$gpAdmin['gpui_ty']).'px;left:'.max(-10,$gpAdmin['gpui_tx']).'px"';

		echo "\n\n";
		echo '<div id="simplepanel"'.$class.$position.'><div>';

			//toolbar
			echo '<div class="toolbar cf">';
				echo '<a class="toggle_panel" data-cmd="toggle_panel"></a>';
				echo common::Link('Admin','','','class="icon_admin_home"');
				echo common::Link('special_gpsearch','','',array('class'=>'icon_admin_search','data-cmd'=>'gpabox'));
				echo '<span class="extra admin_arrow_out"></span>';
			echo '</div>';


			admin_tools::AdminPanelLinks(true,$new_versions);

		echo '</div></div>'; //end simplepanel

		echo "\n\n";
	}

	/**
	 * Output the link areas that are displayed in the main admin toolbar and admin_main
	 * @param bool $in_panel Whether or not the links will be displayed in the toolbar
	 * @param array $new_versions Data about newly available versions of gpEasy and addons
	 * @static
	 */
	static function AdminPanelLinks($in_panel=true,$new_versions=array()){
		global $langmessage, $page, $gpAdmin, $config;

		$expand_class = 'expand_child';
		$id_piece = '';
		if( !$in_panel ){
			$expand_class = 'expand_child_click';
			$id_piece = '_click';
		}


		//current page
		if( $in_panel && !isset($GLOBALS['GP_ARRANGE_CONTENT']) ){
			echo '<div class="panelgroup" id="current_page_panel">';

			self::PanelHeading($in_panel, $langmessage['Current Page'], 'icon_page_gear', 'cur' );

			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Current Page'].'</a></li>';
			foreach($page->admin_links as $label => $link){
				echo '<li>';

					if( is_array($link) ){
						echo call_user_func_array(array('common','Link'),$link); /* preferred */

					}elseif( is_numeric($label) ){
						echo $link; //just a text label

					}elseif( empty($link) ){
						echo '<span>';
						echo $label;
						echo '</span>';

					}else{
						echo '<a href="'.$link.'">';
						echo $label;
						echo '</a>';
					}

				echo '</li>';
			}

			echo '<li class="'.$expand_class.'"><a>'.$langmessage['Editable Areas'].'</a>';
			echo '<ul class="in_window" id="editable_areas_list">';
			echo '<li><a>empty</a></li>';
			echo '</ul>';
			echo '</li>';


			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//content
		if( $links = admin_tools::GetAdminGroup('content') ){
			echo '<div class="panelgroup" id="panelgroup_content'.$id_piece.'">';
			self::PanelHeading($in_panel, $langmessage['Content'], 'icon_page', 'con' );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Content'].'</a></li>';
			echo $links;
			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//appearance
		if( $links = self::GetAppearanceGroup($in_panel) ){
			echo '<div class="panelgroup" id="panelgroup_appearance'.$id_piece.'">';
			self::PanelHeading($in_panel, $langmessage['Appearance'], 'icon_app', 'app' );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Appearance'].'</a></li>';
			echo $links;
			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//add-ons
		$links = admin_tools::GetAddonLinks($in_panel);
		if( !empty($links) ){
			echo '<div class="panelgroup" id="panelgroup_addons'.$id_piece.'">';
			self::PanelHeading($in_panel, $langmessage['plugins'], 'icon_plug', 'add' );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['plugins'].'</a></li>';
			echo $links;
			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//settings
		if( $links = admin_tools::GetAdminGroup('settings') ){
			echo '<div class="panelgroup" id="panelgroup_settings'.$id_piece.'">';
			self::PanelHeading($in_panel, $langmessage['Settings'], 'icon_cog', 'set' );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Settings'].'</a></li>';
			echo $links;
			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//updates
		if( count($new_versions) > 0 ){
			echo '<div class="panelgroup" id="panelgroup_versions'.$id_piece.'">';
			self::PanelHeading($in_panel, $langmessage['updates'], 'icon_rfrsh', 'upd' );
			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['updates'].'</a></li>';

			if( isset($new_versions['core']) ){
				echo '<li>';
				echo '<a href="'.common::GetDir('/include/install/update.php').'">gpEasy '.$new_versions['core'].'</a>';
				echo '</li>';
			}

			foreach($new_versions as $addon_id => $new_addon_info){
				if( !is_numeric($addon_id) ){
					continue;
				}

				$label = $new_addon_info['name'].':  '.$new_addon_info['version'];
				if( $new_addon_info['type'] == 'theme' ){
					echo '<li>'.common::Link('Admin_Theme_Content/Remote',$label).'</li>';

				}elseif( $new_addon_info['type'] == 'plugin' ){
					echo '<li>'.common::Link('Admin_Addons/Remote',$label).'</li>';
				}else{
					continue;
				}
			}

			echo '</ul>';
			echo '</div>';
			echo '</div>';
		}


		//username
		echo '<div class="panelgroup" id="panelgroup_user'.$id_piece.'">';

			self::PanelHeading($in_panel, $gpAdmin['username'], 'icon_user', 'use' );

			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$gpAdmin['username'].'</a></li>';
			admin_tools::GetFrequentlyUsed($in_panel);

			echo '<li>';
			echo common::Link('Admin_Preferences',$langmessage['Preferences'],'',array('data-cmd'=>'gpabox'));
			echo '</li>';

			echo '<li>';
			echo common::Link($page->title,$langmessage['logout'],'cmd=logout','data-cmd="creq"');
			echo '</li>';

			echo '<li>';
			echo common::Link('Admin_About','About gpEasy','',array('data-cmd'=>'gpabox'));
			echo '</li>';
			echo '</ul>';
			echo '</div>';

		echo '</div>';
	}

	static function PanelHeading( $in_panel, $label, $icon, $arg ){
		global $gpAdmin;

		if( !$in_panel ){
			echo '<span class="'.$icon.'"><span>'.$label.'</span></span>';
			echo '<div class="panelgroup2">';
			return;
		}

		echo '<a class="toplink '.$icon.'" data-cmd="toplink" data-arg="'.$arg.'"><span>';
		echo $label;
		echo '</span></a>';

		if( $gpAdmin['gpui_vis'] == $arg ){
			echo '<div class="panelgroup2 in_window">';
		}else{
			echo '<div class="panelgroup2 in_window nodisplay">';
		}

	}

	/**
	 * Output the html used for inline editor toolbars
	 * @static
	 */
	static function InlineEditArea(){
		global $langmessage;

		//inline editor html
		echo '<div id="ckeditor_wrap" class="nodisplay">';
		echo '<div id="ckeditor_area" class="gp_floating_area">';
		echo '<div class="cf">';
			echo '<div class="toolbar">';
				echo '<div class="gp_right">';
				echo '<span class="admin_arrow_out"></span>';
				echo '<a class="docklink" data-cmd="ck_docklink"></a>';
				echo '</div>';
			echo '</div>';

			echo '<div class="tools">';

			echo '<div id="ckeditor_top"></div>';

			echo '<div id="ckeditor_controls"><div id="ckeditor_save">';
			echo '<a data-cmd="ck_save" class="ckeditor_control">'.$langmessage['save'].'</a>';
			echo '<a data-cmd="ck_close" class="ckeditor_control">'.$langmessage['Close'].'</a>';
			echo '<a data-cmd="ck_save" data-arg="ck_close" class="ckeditor_control">'.$langmessage['Save & Close'].'</a>';
			echo '</div></div>';

			echo '<div id="ckeditor_bottom"></div>';

			echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';


	}

	/**
	 * Get the links for the Frequently Used section of the admin toolbar
	 *
	 */
	static function GetFrequentlyUsed($in_panel){
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
			$scripts = admin_tools::AdminScripts();
			$add_one = true;
			if( isset($gpAdmin['freq_scripts']) ){
				foreach($gpAdmin['freq_scripts'] as $link => $hits ){
					if( isset($scripts[$link]) ){
						echo '<li>';
						echo common::Link($link,$scripts[$link]['label']);
						echo '</li>';
						if( $link === 'Admin_Menu' ){
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
				echo common::Link('Admin_Menu',$scripts['Admin_Menu']['label']);
				echo '</li>';
			}
			echo '</ul>';
		echo '</li>';
	}

	static function AdminContentPanel(){
		global $page, $config, $langmessage, $gp_menu;

		//the login form does not need the panel
		if( !common::LoggedIn() ){
			return;
		}

		echo '<div id="admincontent_panel" class="toolbar">';
		echo '<div class="gp_right">';
		echo '<span class="admin_arrow_out"></span>';
		echo common::Link('','','','class="close_home"');
		echo '</div>';

		reset($gp_menu);
		$homepath = common::IndexToTitle(key($gp_menu));
		echo common::Link_Page($homepath);
		echo ' &#187; ';
		echo common::Link('Admin',$langmessage['administration']);
		if( !empty($page->title) && !empty($page->label) && $page->title != 'Admin' ){
			echo ' &#187; ';
			echo common::Link($page->title,$page->label);
		}
		echo '</div>';

	}

	static function AdminContainer(){
		global $gpAdmin;

		// if the admin window doesn't have a position
		if( !isset($gpAdmin['gpui_pposx']) || !isset($gpAdmin['gpui_pposy']) || ($gpAdmin['gpui_pposx'] == 0 && $gpAdmin['gpui_pposy'] == 0) ){
			$gpAdmin['gpui_pposx'] = $gpAdmin['gpui_pposy'] = 70;
		}
		if( !isset($gpAdmin['gpui_pw']) || $gpAdmin['gpui_pw'] == 0 ){
			$gpAdmin['gpui_pw'] = 960;
		}

		return '<div id="admincontainer" class="gp_floating_area" style="left:'.$gpAdmin['gpui_pposx'].'px;top:'.$gpAdmin['gpui_pposy'].'px;width:'.Max($gpAdmin['gpui_pw'],300).'px;">';
	}



	//uses $status from update codes to execute some cleanup code on a regular interval (7 days)
	static function ScheduledTasks($status){
		global $dataDir;


		switch($status){
			case 'embedcheck':
			case 'checkincompat':
				//these will continue
			break;

			case 'checklater':
			default:
			return;
		}

		//clean cache
		//delete files older than 2 weeks, they will be regenerated if needed
		$cache_dir = $dataDir.'/data/_cache';
		$cache_files = gpFiles::ReadDir($cache_dir,'css');
		$time = time();
		foreach($cache_files as $file){
			$full_path = $cache_dir.'/'.$file.'.css';
			if( $time - filemtime($full_path) > 1209600 ){
				@unlink($full_path);
			}
		}

	}


	//admin_tools::AdminHtml();
	static function AdminHtml(){
		global $page, $gp_admin_html;

		ob_start();
		includeFile('tool/update.php');
		$update_status = update_class::VersionsAndCheckTime($new_versions);

		echo '<div id="loading1" class="nodisplay"></div>';
		echo '<div id="loading2" class="nodisplay"></div>';

		admin_tools::GetAdminPanel($new_versions);
		admin_tools::InlineEditArea();
		echo '<div class="nodisplay" id="gp_hidden"></div>';

		if( isset($page->admin_html) ){
			echo $page->admin_html;
		}

		admin_tools::CheckStatus($update_status);
		admin_tools::ScheduledTasks($update_status);
		$gp_admin_html .= ob_get_clean();
	}

	static function CheckStatus($status){

		switch($status){
			case 'embedcheck':
				$img_path = common::GetUrl('Admin','cmd=embededcheck');
				common::IdReq($img_path);
			break;
			case 'checkincompat':
				$img_path = common::IdUrl('ci'); //check in
				common::IdReq($img_path);
			break;
		}
	}



	static function GetAdminGroup($grouping){
		global $langmessage,$page;

		$scripts = admin_tools::AdminScripts();

		ob_start();
		foreach($scripts as $script => $info){
			if( isset($info['list']) && ($info['list'] === false) ){
				continue;
			}

			if( !isset($info['group']) || (strpos($info['group'],$grouping) === false) ){
				continue;
			}

			if( !admin_tools::HasPermission($script) ){
				continue;
			}
			echo '<li>';

			if( isset($info['popup']) && $info['popup'] == true ){
				echo common::Link($script,$info['label'],'',array('data-cmd'=>'gpabox'));
			}else{
				echo common::Link($script,$info['label']);
			}

			echo '</li>';

			switch($script){
				case 'Admin_Menu':
					echo '<li>';
					echo common::Link('Admin_Menu','+ '.$langmessage['create_new_file'],'cmd=add_hidden&redir=redir',array('title'=>$langmessage['create_new_file'],'data-cmd'=>'gpajax'));
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

	static function GetAppearanceGroup($in_panel){
		global $page, $langmessage, $gpLayouts, $config;

		if( !admin_tools::HasPermission('Admin_Theme_Content') ){
			return false;
		}

		ob_start();
		echo admin_tools::GetAdminGroup('appearance');


		if( !empty($page->gpLayout) ){
			echo '<li>';
			echo common::Link('Admin_Theme_Content/'.urlencode($page->gpLayout),$langmessage['edit_this_layout']);
			echo '</li>';
		}
		echo '<li>';
		echo common::Link('Admin_Theme_Content/Remote',$langmessage['Download Themes']);
		echo '</li>';

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
		foreach($gpLayouts as $layout => $info){
			echo '<li>';
			$display = $info['label'];
			if( $config['gpLayout'] == $layout ){
				$display = '<b>'.$display.'</b>';
			}
			$display = '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span>&nbsp; '.$display;
			echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$display);
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
	static function PostedLabel($string){
		includeFile('tool/strings.php');

		// Remove control characters
		$string = preg_replace( '#[[:cntrl:]]#u', '', $string ) ; //[\x00-\x1F\x7F]

		//change known entities to their character equivalent
		$string = gp_strings::entity_unescape($string);

		return admin_tools::LabelHtml($string);
	}

	/**
	 * Convert a label to a slug
	 * Does not use PostedSlug() so entity_unescape isn't called twice
	 * @since 2.5b1
	 *
	 */
	static function LabelToSlug($string){
		return admin_tools::PostedSlug( $string, true);
	}


	/**
	 * Clean a slug posted by the user
	 * @param string $slug The slug provided by the user
	 * @return string
	 * @since 2.4b5
	 */
	static function PostedSlug($string,$from_label = false){
		includeFile('tool/strings.php');

		// Remove control characters
		$string = preg_replace( '#[[:cntrl:]]#u', '', $string ) ; // 	[\x00-\x1F\x7F]

		//illegal characters
		$string = str_replace( array('?','*',':','|'), array('','','',''), $string);

		//change known entities to their character equivalent
		$string = gp_strings::entity_unescape($string);


		//if it's from a label, remove any html
		if( $from_label ){
			$string = admin_tools::LabelHtml($string);
			$string = strip_tags($string);

			//after removing tags, unescape special characters
			$string = str_replace( array('&lt;','&gt;','&quot;','&#39;','&amp;'), array('<','>','"',"'",'&'), $string);
		}

		// # character after unescape for entities and unescape of special chacters when $from_label is true
		$string = str_replace('#','',$string);

		//slashes
		$string = admin_tools::SlugSlashes($string);

		return str_replace(' ','_',$string);
	}

	/**
	 * Fix the html for page labels
	 *
	 */
	static function LabelHtml($string){

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
				$string .= common::LabelSpecialChars($pieces[$i]);
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
	static function SlugSlashes($string){

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
	static function CheckTitleCase($title){
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
	static function CheckTitle($title,&$message){
		global $gp_index, $config, $langmessage;

		if( empty($title) ){
			$message = $langmessage['TITLE_REQUIRED'];
			return false;
		}

		if( isset($gp_index[$title]) ){
			$message = $langmessage['TITLE_EXISTS'];
			return false;
		}

		$type = common::SpecialOrAdmin($title);
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
	static function CheckPostedNewPage($title,&$message){
		global $langmessage,$gp_index, $config;

		$title = admin_tools::LabelToSlug($title);

		if( !admin_tools::CheckTitle($title,$message) ){
			return false;
		}

		if( admin_tools::CheckTitleCase($title) ){
			$message = $langmessage['TITLE_EXISTS'];
			return false;
		}

		return $title;
	}


	//
	//	functions for gp_menu, gp_titles
	//

	//admin_tools::SaveAllConfig();
	static function SaveAllConfig(){
		if( !admin_tools::SaveConfig() ){
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			return false;
		}
		return true;
	}

	/**
	 * Save the gpEasy configuration
	 * @return bool
	 *
	 */
	static function SavePagesPHP(){
		global $gp_index, $gp_titles, $gp_menu, $gpLayouts, $dataDir;

		if( !is_array($gp_menu) || !is_array($gp_index) || !is_array($gp_titles) || !is_array($gpLayouts) ){
			return false;
		}

		$pages = array();
		$pages['gp_menu'] = $gp_menu;
		$pages['gp_index'] = $gp_index;
		$pages['gp_titles'] = $gp_titles;
		$pages['gpLayouts'] = $gpLayouts;

		if( !gpFiles::SaveArray($dataDir.'/data/_site/pages.php','pages',$pages) ){
			return false;
		}
		return true;

	}

	/**
	 * Save the gpEasy configuration
	 * @return bool
	 *
	 */
	static function SaveConfig(){
		global $config, $dataDir;

		if( !is_array($config) ) return false;

		if( !isset($config['gpuniq']) ) $config['gpuniq'] = common::RandomString(20);

		return gpFiles::SaveArray($dataDir.'/data/_site/config.php','config',$config);
	}


	/**
	 * @deprecated
	 * used by simpleblog1
	 */
	static function tidyFix(&$text){
		trigger_error('tidyFix should be called using gp_edit::tidyFix() instead of admin_tools:tidyFix()');
		return false;
	}



	/**
	 * Return the addon section of the admin panel
	 *
	 */
	static function GetAddonLinks($in_panel){
		global $langmessage, $config;

		$expand_class = 'expand_child';
		if( !$in_panel ){
			$expand_class = 'expand_child_click';
		}

		ob_start();

		$addon_permissions = admin_tools::HasPermission('Admin_Addons');

		if( $addon_permissions ){
			echo '<li>';
			echo common::Link('Admin_Addons',$langmessage['manage']);
			echo '</li>';
			echo '<li class="seperator">';
			echo common::Link('Admin_Addons/Remote',$langmessage['Download Plugins']);
			echo '</li>';
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

				$sublinks = admin_tools::GetAddonSubLinks($addon);

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

				if( $addon_permissions ){
					echo common::Link('Admin_Addons',$addonName,'cmd=show&addon='.rawurlencode($addon));
				}else{
					echo '<a>'.$addonName.'</a>';
				}
				echo $sublinks;

				echo '</li>';
			}
		}


		return ob_get_clean();

	}



	/**
	 * Determine if the installation should be allowed to process remote installations
	 *
	 */
	static function CanRemoteInstall(){
		static $bool;

		if( isset($bool) ){
			return $bool;
		}

		includeFile('tool/RemoteGet.php');

		$bool = true;

		if( !gpRemoteGet::Test() ){
			$bool = false;
		}

		//used by pclzip
		if( !function_exists('gzinflate') ){
			$bool = false;
		}

		if( defined('gp_remote_addons') && gp_remote_addons === false ){
			$bool = false;
		}

		return $bool;
	}


	/**
	 * Return a formatted list of links associated with $addon
	 * @return string
	 */
	static function GetAddonSubLinks($addon=false){
		global $config;

		$special_links = admin_tools::GetAddonTitles( $addon);
		$admin_links = admin_tools::GetAddonComponents( $config['admin_links'], $addon);


		$result = '';
		foreach($special_links as $linkName => $linkInfo){
			$result .= '<li>';
			$result .= common::Link($linkName,$linkInfo['label']);
			$result .= '</li>';
		}

		foreach($admin_links as $linkName => $linkInfo){
			if( admin_tools::HasPermission($linkName) ){
				$result .= '<li>';
				$result .= common::Link($linkName,$linkInfo['label']);
				$result .= '</li>';
			}
		}
		return $result;
	}




	/**
	 * Get the titles associate with $addon
	 * Similar to GetAddonComponents(), but built for $gp_titles
	 * @return array List of addon links
	 *
	 */
	static function GetAddonTitles($addon){
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
	static function GetAddonComponents($from,$addon){
		if( !is_array($from) ){
			return;
		}

		$result = array();
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


	static function FormatBytes($size, $precision = 2){
		$base = log($size) / log(1024);
		$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
		$floor = max(0,floor($base));
		return round(pow(1024, $base - $floor), $precision) .' '. $suffixes[$floor];
	}

	/**
	 * Base convert that handles large numbers
	 *
	 */
	static function base_convert($str, $frombase=10, $tobase=36) {
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

}