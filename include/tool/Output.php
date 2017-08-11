<?php

namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	global $GP_ARRANGE, $gpOutConf;

	$GP_ARRANGE = true;
	$gpOutConf = array();


	//named menus should just be shortcuts to the numbers in custom menu
	//	custom menu format: $top_level,$bottom_level,$expand_level

	//custom menu: 0,0,0,0
	$gpOutConf['FullMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetFullMenu',
								'link'			=> 'all_links',
								);


	//custom menu: 0,0,1,1
	$gpOutConf['ExpandMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetExpandMenu',
								'link'			=> 'expanding_links',
								);


	//custom menu: 0,0,2,1
	$gpOutConf['ExpandLastMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetExpandLastMenu',
								'link'			=> 'expanding_bottom_links',
								);

	//custom menu: 0,1,0,0
	$gpOutConf['Menu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetMenu',
								'link'			=> 'top_level_links',
								);

	//custom menu: 1,0,0,0
	$gpOutConf['SubMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetSubMenu',
								'link'			=> 'subgroup_links',
								);

	//custom menu: 0,2,0,0
	$gpOutConf['TopTwoMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetTopTwoMenu',
								'link'			=> 'top_two_links',
								);

	//custom menu: does not translate, this pays no attention to grouping
	$gpOutConf['BottomTwoMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetBottomTwoMenu',
								'link'			=> 'bottom_two_links',
								);

	//custom menu: 1,2,0,0
	$gpOutConf['MiddleSubMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetSecondSubMenu',
								'link'			=> 'second_sub_links',
								);

	//custom menu: 2,3,0,0
	$gpOutConf['BottomSubMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'GetThirdSubMenu',
								'link'			=> 'third_sub_links',
								);

	//custom menu
	$gpOutConf['CustomMenu'] = array(
								'class'			=> '\\gp\\tool\\Output\\Menu',
								'method'		=> 'CustomMenu',
								);


	$gpOutConf['Extra']['method']			= array('\\gp\\tool\\Output','GetExtra');
	//$gpOutConf['Text']['method']			= array('\\gp\\tool\\Output','GetText'); //use Area() and GetArea() instead

	//$gpOutConf['Image']['method']			= array('\\gp\\tool\\Output','GetImage');

	/* The following methods should be used with \gp\tool\Output'::Fetch() */
	$gpOutConf['Gadget']['method']			= array('\\gp\\tool\\Output','GetGadget');


	$gpOutConf['Breadcrumbs']['method']		= array('\\gp\\tool\\Output','BreadcrumbNav');
	$gpOutConf['Breadcrumbs']['link']		= 'Breadcrumb Links';


	class Output{

		public static $components			= '';
		public static $editlinks			= '';
		public static $template_included	= false;

		private static $out_started			= false;
		private static $gadget_cache		= array();

		public static $edit_area_id			= '';

		private static $catchable			= array();

		public static $lang_values			= array();
		public static $inline_vars			= array();
		public static $nested_edit			= false;

		private static $edit_index			= 0;

		private static $head_css			= '';
		private static $head_content		= '';
		private static $head_js				= '';


		/**
		 * Backwards compat for functions moved to \gp\tool\Output\Menu
		 *
		 */
		public static function __callStatic($name,$args){

			if( method_exists('\\gp\\tool\\Output\\Menu',$name) ){
				$menu = new \gp\tool\Output\Menu();
				return call_user_func_array( array($menu,$name), $args);
			}

			throw new \Exception('Call to undefined method gp\\tool\\Output::'.$name);
		}


		/*
		 *
		 * Request Type Functions
		 * functions used in conjuction with $_REQUEST['gpreq']
		 *
		 */

		public static function Prep(){
			global $page;
			if( !isset($page->rewrite_urls) ){
				return;
			}

			ini_set('arg_separator.output', '&amp;');
			foreach($page->rewrite_urls as $key => $value){
				output_add_rewrite_var($key,$value);
			}
		}

		/**
		 * Send only messages and the content buffer to the client
		 * @static
		 */
		public static function Flush(){
			global $page;
			self::StandardHeaders();
			echo GetMessages();
			echo $page->contentBuffer;
		}

		public static function Content(){
			global $page;
			self::StandardHeaders();
			echo GetMessages();
			$page->GetGpxContent();
		}

		public static function StandardHeaders(){
			header('Content-Type: text/html; charset=utf-8');
			Header('Vary: Accept,Accept-Encoding');// for proxies
		}

		/**
		 * Send only the messages and content as a simple html document
		 * @static
		 */
		public static function BodyAsHTML(){
			global $page;

			self::$inline_vars['gp_bodyashtml']	= true;

			self::TemplateSettings();

			self::StandardHeaders();

			echo '<!DOCTYPE html><html><head><meta charset="UTF-8" />';
			self::getHead();
			echo '</head>';

			echo '<body class="gpbody">';
			echo GetMessages();

			$page->GetGpxContent();

			echo '</body>';
			echo '</html>';

			self::HeadContent();
		}

		public static function AdminHtml(){
			global $page;

			//\gp\tool\Output::$inline_vars['gp_bodyashtml']	= true;

			self::StandardHeaders();

			echo '<!DOCTYPE html><html class="admin_body"><head><meta charset="UTF-8" />';
			self::getHead();
			echo '</head>';

			echo '<body class="gpbody">';
			echo GetMessages();

			$page->GetGpxContent();

			echo '</body>';
			echo '</html>';

			self::HeadContent();
		}


		/**
		 * Send all content according to the current layout
		 * @static
		 *
		 */
		public static function Template(){
			global $page, $GP_ARRANGE, $GP_STYLES, $get_all_gadgets_called, $addon_current_id, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES, $GP_MENU_ELEMENTS;
			$get_all_gadgets_called = false;
			self::$template_included = true;

			if( isset($page->theme_addon_id) ){
				$addon_current_id = $page->theme_addon_id;
			}
			self::TemplateSettings();

			self::StandardHeaders();

			$path = $page->theme_dir.'/template.php';
			$return = IncludeScript($path,'require',array('page','GP_ARRANGE','GP_MENU_LINKS','GP_MENU_CLASS','GP_MENU_CLASSES','GP_MENU_ELEMENTS'));

			//return will be false if there's a fatal error with the template.php file
			if( $return === false ){
				self::BodyAsHtml();
			}
			\gp\tool\Plugins::ClearDataFolder();

			self::HeadContent();
		}


		/**
		 * Get the settings for the current theme if settings.php exists
		 * @static
		 */
		public static function TemplateSettings(){
			global $page;

			$path = $page->theme_dir.'/settings.php';
			IncludeScript($path,'require_if',array('page','GP_GETALLGADGETS'));
		}


		/**
		 * Add a Header to the response
		 * The header will be discarded if it's an ajax request or similar
		 *
		 * @param string $header
		 * @param bool $replace
		 * @param int $code
		 * @return bool
		 */
		public static function AddHeader($header, $replace = true, $code = null){
			if( !empty($_REQUEST['gpreq']) ){
				return false;
			}
			if( !is_null($code) ){
				\gp\tool::status_header($code,$header);
			}else{
				header($header,$replace);
			}
			return true;
		}


		/*
		 *
		 * Content Area Functions
		 *
		 */


		public static function GetContainerID($name,$arg=false){
			static $indices;

			$name = str_replace(array('+','/','='),array('','',''),base64_encode($name));
			if( !isset($indices[$name]) ){
				$indices[$name] = 0;
			}else{
				$indices[$name]++;
			}
			return $name.'_'.$indices[$name];
		}


		/**
		 * Fetch the output and return as a string
		 *
		 */
		public static function Fetch($default,$arg=''){
			ob_start();
			self::Get($default,$arg);
			return ob_get_clean();
		}


		public static function Get($default='',$arg=''){
			global $page,$gpLayouts,$gpOutConf;

			$outSet = false;
			$outKeys = false;

			$layout_info =& $gpLayouts[$page->gpLayout];

			//container id
			$container_id = $default.':'.substr($arg,0,10);
			$container_id = self::GetContainerID($container_id);


			if( isset($layout_info) && isset($layout_info['handlers']) ){
				$handlers =& $layout_info['handlers'];
				if( isset($handlers[$container_id]) ){
					$outKeys = $handlers[$container_id];
					$outSet = true;
				}
			}

			//default values
			if( !$outSet && isset($gpOutConf[$default]) ){
				$outKeys[] = trim($default.':'.$arg,':');
			}

			self::ForEachOutput($outKeys,$container_id);

		}

		public static function ForEachOutput($outKeys,$container_id){

			if( !is_array($outKeys) || (count($outKeys) == 0) ){

				$info = array();
				$info['gpOutCmd'] = '';
				self::CallOutput($info,$container_id);
				return;
			}

			foreach($outKeys as $gpOutCmd){

				$info = self::GetgpOutInfo($gpOutCmd);
				if( $info === false ){
					trigger_error('gpOutCmd <i>'.$gpOutCmd.'</i> not set');
					continue;
				}
				$info['gpOutCmd'] = $gpOutCmd;
				self::CallOutput($info,$container_id);
			}
		}

		/* static */
		public static function GetgpOutInfo($gpOutCmd){
			global $gpOutConf,$config;

			$key = $gpOutCmd = trim($gpOutCmd,':');
			$info = false;
			$arg = '';
			$pos = mb_strpos($key,':');
			if( $pos > 0 ){
				$arg = mb_substr($key,$pos+1);
				$key = mb_substr($key,0,$pos);
			}


			if( isset($gpOutConf[$key]) ){
				$info = $gpOutConf[$key];
			}elseif( isset($config['gadgets'][$key]) ){
				$info = $config['gadgets'][$key];
				$info['is_gadget'] = true;
			}else{
				return false;
			}
			$info['key'] = $key;
			$info['arg'] = $arg;
			$info['gpOutCmd'] = $gpOutCmd;

			return $info;
		}


		public static function GpOutLabel($info){
			global $langmessage;

			$label = $info['arg'];
			if( empty($label) ){
				$label = $info['gpOutCmd'];
			}

			if( isset($info['link']) && isset($langmessage[$info['link']]) ){
				$label = $langmessage[$info['link']];
			}
			return str_replace(array(' ','_',':'),array('&nbsp;','&nbsp;',':&nbsp;'),$label);
		}


		public static function CallOutput($info,$container_id){
			global $GP_ARRANGE, $page, $langmessage, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES, $gp_current_container;
			$gp_current_container = $container_id;
			self::$out_started = true;
			self::$edit_area_id = '';


			if( isset($info['disabled']) ){
				return;
			}

			//gpOutCmd identifies the output function used, there can only be one
			if( !isset($info['gpOutCmd']) ){
				trigger_error('gpOutCmd not set for $info in CallOutput()');
				return;
			}


			//generate a class based on the area $info
			if( isset($info['html']) ){
				$class = $info['key'];
				$class = preg_replace('#\[.*\]#','',$class);
			}else{
				$class = $info['gpOutCmd'];
			}

			$class			= 'gpArea_'.str_replace(array(':',','),array('_',''),trim($class,':'));
			$param			= $container_id.'|'.$info['gpOutCmd'];
			$permission		= self::ShowEditLink('Admin_Theme_Content');




			ob_start();

			//for theme content arrangement
			if( $GP_ARRANGE && $permission && isset($GLOBALS['GP_ARRANGE_CONTENT'])  ){
				$empty_container = empty($info['gpOutCmd']); //empty containers can't be removed and don't have labels
				$class .= ' gp_output_area';

				echo '<div class="gp_inner_links nodisplay"><div>';
				echo \gp\tool::Link('Admin_Theme_Content/Edit/'.$page->gpLayout,$param,'cmd=DragArea&dragging='.urlencode($param).'&to=%s',array('data-cmd'=>'creq','class'=>'dragdroplink nodisplay')); //drag-drop link

				echo '<div class="output_area_label">';
				if( $empty_container ){
					echo 'Empty Container';
				}else{
					echo self::GpOutLabel($info);
				}
				echo '</div>';

				echo '<div class="output_area_link">';
				echo ' '.\gp\tool::Link('Admin_Theme_Content/Edit/'.$page->gpLayout,'<i class="fa fa-plus"></i> '.$langmessage['insert'],'cmd=SelectContent&param='.$param,array('data-cmd'=>'gpabox'));
				if( !$empty_container ){
					echo ' '.\gp\tool::Link('Admin_Theme_Content/Edit/'.$page->gpLayout,'<i class="fa fa-times"></i> '.$langmessage['remove'],'cmd=RemoveArea&param='.$param,array('data-cmd'=>'creq'));
				}
				echo '</div>';

				echo '</div></div>';

			}

			//editable links only .. other editable_areas are handled by their output functions
			if( $permission ){
				if( isset($info['link']) ){
					$label = $langmessage[$info['link']];

					$edit_link = self::EditAreaLink($edit_index,'Admin_Theme_Content/Edit/'.urlencode($page->gpLayout),$langmessage['edit'],'cmd=LayoutMenu&handle='.$param,' data-cmd="gpabox" title="'.$label.'" ');
					echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
					echo $edit_link;
					echo \gp\tool::Link('Admin/Menu',$langmessage['file_manager'],'',' class="nodisplay"');
					echo '</span>';

					self::$edit_area_id = 'ExtraEditArea'.$edit_index;

				}elseif( isset($info['key']) && ($info['key'] == 'CustomMenu') ){

					$edit_link = self::EditAreaLink($edit_index,'Admin_Theme_Content/Edit/'.urlencode($page->gpLayout),$langmessage['edit'],'cmd=LayoutMenu&handle='.$param,' data-cmd="gpabox" title="'.$langmessage['Links'].'" ');
					echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
					echo $edit_link;
					echo \gp\tool::Link('Admin/Menu',$langmessage['file_manager'],'',' class="nodisplay"');
					echo '</span>';

					self::$edit_area_id = 'ExtraEditArea'.$edit_index;
				}
			}
			self::$editlinks .= ob_get_clean();


			echo '<div class="'.$class.' GPAREA">';
			self::ExecArea($info);
			echo '</div>';

			$GP_ARRANGE = true;
			$gp_current_container = false;
		}

		public static function ExecArea($info){
			//retreive from gadget cache if set
			if( isset($info['gpOutCmd']) ){
				$gadget = $info['gpOutCmd'];
				if( substr($gadget,0,7) == 'Gadget:' ){
					$gadget = substr($gadget,7);
				}
				if( isset(self::$gadget_cache[$gadget]) ){
					echo self::$gadget_cache[$gadget];
					return;
				}
			}


			$info += array('arg'=>'');
			$args = array( $info['arg'],$info);
			self::ExecInfo($info,$args);
		}

		/**
		 * Execute a set of directives for theme areas, hooks and special pages
		 *
		 */
		public static function ExecInfo($info,$args=array()){
			global $addonFolderName, $installed_addon, $page;

			$args += array('page' => $page);


			//addonDir is deprecated as of 2.0b3
			$addon = false;
			if( isset($info['addonDir']) ){
				$addon = $info['addonDir'];
			}elseif( isset($info['addon']) ){
				$addon = $info['addon'];
			}

			if( $addon !== false ){
				if( gp_safe_mode ){
					return $args;
				}
				\gp\tool\Plugins::SetDataFolder($addon);
			}

			//if addon was just installed
			if( $installed_addon && $installed_addon === $addonFolderName){
				\gp\tool\Plugins::ClearDataFolder();
				return $args;
			}

			// check for fatal errors
			if( self::FatalNotice( 'exec', $info ) ){
				return $args;
			}

			$args = self::_ExecInfo($info,$args);

			if( $addon !== false ){
				\gp\tool\Plugins::ClearDataFolder();
			}

			self::PopCatchable();

			return $args;

		}

		public static function _ExecInfo($info,$args=array()){
			global $dataDir, $gp_overwrite_scripts;

			// get data
			if( !empty($info['data']) ){
				IncludeScript($dataDir.$info['data'],'include_if',array('page','dataDir','langmessage'));
			}

			// get script
			$has_script = false;
			if( !empty($info['script']) ){

				if( is_array($gp_overwrite_scripts) && isset($gp_overwrite_scripts[$info['script']]) ){
					$full_path = $gp_overwrite_scripts[$info['script']];
				}else{
					$full_path = $dataDir.$info['script'];
				}

				if( !file_exists($full_path) ){
					self::ExecError(CMS_NAME.' Error: Addon hook script doesn\'t exist.',$info,'script');
					return $args;
				}

				if( IncludeScript($full_path,'include_once',array('page','dataDir','langmessage')) ){
					$has_script = true;
				}
			}


			//class & method execution
			if( !empty($info['class_admin']) && \gp\tool::LoggedIn() ){
				return self::ExecClass($has_script, $info['class_admin'], $info, $args);

			}elseif( !empty($info['class']) ){
				return self::ExecClass($has_script, $info['class'], $info, $args);

			}


			//method execution
			if( !empty($info['method']) ){
				return self::ExecMethod($has_script, $info, $args);
			}

			return $args;
		}


		/**
		 * Execute hooks that have a ['class'] defined
		 *
		 */
		private static function ExecClass($has_script, $exec_class, $info, $args){

			if( !class_exists($exec_class) ){
				self::ExecError(CMS_NAME.' Error: Addon class doesn\'t exist.',$info,'class');
				return $args;
			}

			$object = new $exec_class($args);

			if( !empty($info['method']) ){
				if( method_exists($object, $info['method']) ){
					$args[0] = call_user_func_array(array($object, $info['method']), $args );
				}elseif( $has_script ){
					self::ExecError(CMS_NAME.' Error: Addon hook method doesn\'t exist (1).',$info,'method');
				}
			}
			return $args;
		}

		/**
		 * Execute hooks that have a ['method'] defined
		 *
		 */
		private static function ExecMethod($has_script, $info, $args){

			$callback = $info['method'];

			//object callbacks since 3.0
			if( is_string($callback) && strpos($callback,'->') !== false ){
				$has_script = true;
				list($object,$method) = explode('->',$callback);
				if( isset($GLOBALS[$object]) && is_object($GLOBALS[$object]) && method_exists($GLOBALS[$object],$method) ){
					$callback = array($GLOBALS[$object],$method);
				}
			}

			if( is_callable($callback) ){
				$args[0] = call_user_func_array($callback,$args);

			}elseif( $has_script ){
				self::ExecError(CMS_NAME.' Error: Addon hook method doesn\'t exist (2).',$info,'method');
			}

			return $args;
		}


		/**
		 * Trigger an error
		 *
		 */
		public static function ExecError( $msg, $exec_info, $error_info ){
			global $config, $addonFolderName;

			// append addon name
			if( !empty($addonFolderName) && isset($config['addons'][$addonFolderName]) ){
				$msg	.= ' Addon: '.$config['addons'][$addonFolderName]['name'].'. ';
			}

			// which piece of $exec_info is the problem
			if( !isset($exec_info[$error_info]) ){
				$msg	.= $error_info;
			}elseif( is_array($exec_info[$error_info]) ){
				$msg	.= $error_info.': '.implode('::',$exec_info[$error_info]);
			}else{
				$msg	.= $error_info.': '.$exec_info[$error_info];
			}

			trigger_error($msg);
		}


		/**
		 * Check for fatal errors corresponing to $hash
		 * Notify administrators of disabled components
		 *
		 */
		public static function FatalNotice( $type, $info ){
			global $dataDir, $page;
			static $notified = false;

			$info = (array)$info;
			$info['catchable_type'] = $type;

			$hash = $type.'_'.\gp\tool::ArrayHash($info);
			self::$catchable[$hash] = $info;

			//no file = no fatal error
			$file = $dataDir.'/data/_site/fatal_'.$hash;
			if( !file_exists($file) ){
				return false;
			}


			$error_info = $error_text = file_get_contents($file);

			// if the file that caused the fatal error has been modified, treat as fixed
			if( $error_text[0] == '{' && $error_info = json_decode($error_text,true) ){


				if( !empty($error_info['file']) && file_exists($error_info['file']) ){

					//compare modified time
					if( array_key_exists('file_modified',$error_info) && filemtime($error_info['file']) != $error_info['file_modified'] ){
						unlink($file);
						return false;
					}

					//compare file size
					if( array_key_exists('file_size',$error_info) && filesize($error_info['file']) != $error_info['file_size'] ){
						unlink($file);
						return false;
					}

				}
			}

			if( !$notified ){
				error_log( 'Warning: A component of this page has been disabled because it caused fatal errors' );
				$notified = true;
			}

			self::PopCatchable();

			return true;
		}

		public static function PopCatchable(){
			array_pop(self::$catchable);
		}


		/**
		 * Determine if an inline edit link should be shown for the current user
		 *
		 * @param string $permission
		 * @return bool
		 */
		public static function ShowEditLink($permission=null){

			if( !is_null($permission) ){
				return !self::$nested_edit && \gp\tool::LoggedIn() && \gp\admin\Tools::HasPermission($permission);
			}
			return !self::$nested_edit && \gp\tool::LoggedIn();
		}


		/**
		 * @param int $index
		 * @param string $href
		 * @param string $label
		 * @param string $query
		 * @param string|array $attr
		 *
		 */
		public static function EditAreaLink(&$index,$href,$label,$query='',$attr=''){
			self::$edit_index++;
			$index = self::$edit_index; //since &$index is passed by reference

			if( is_array($attr) ){
				$attr += array('class'=>'ExtraEditLink nodisplay','id'=>'ExtraEditLink'.$index,'data-gp-area-id'=>$index);
			}else{
				$attr .= ' class="ExtraEditLink nodisplay" id="ExtraEditLink'.$index.'" data-gp-area-id="'.$index.'"';
			}
			return \gp\tool::Link($href,$label,$query,$attr);
		}


		/**
		 * Unless the gadget area is customized by the user, this function will output all active gadgets
		 * If the area has been reorganized, it will output the customized areas
		 * This function is not called from \gp\tool\Output::Get('GetAllGadgets') so that each individual gadget area can be used as a drag area
		 *
		 */
		public static function GetAllGadgets(){
			global $config, $page, $gpLayouts, $get_all_gadgets_called;
			$get_all_gadgets_called = true;

			//if we have handler info
			if( isset($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets']) ){
				self::ForEachOutput($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets'],'GetAllGadgets');
				return;
			}

			//show all gadgets if no changes have been made
			if( !empty($config['gadgets']) ){
				$count = 0;
				foreach($config['gadgets'] as $gadget => $info){
					if( isset($info['addon']) ){
						$info['gpOutCmd'] = $info['key'] = $gadget;
						self::CallOutput($info,'GetAllGadgets');
						$count++;
					}
				}
				if( $count ){
					return;
				}
			}

			//Show the area as editable if there isn't anything to show
			$info = array();
			$info['gpOutCmd'] = '';
			self::CallOutput($info,'GetAllGadgets');
		}


		/**
		 * Get a Single Gadget
		 * This method should be called using \gp\tool\Output::Fetch('Gadget',$gadget_name)
		 *
		 */
		public static function GetGadget($id){
			global $config;

			if( !isset($config['gadgets'][$id]) ){
				return;
			}

			self::ExecArea($config['gadgets'][$id]);
		}

		/**
		 * Prepare the gadget content before getting template.php so that gadget functions can add css and js to the head
		 * @return null
		 */
		public static function PrepGadgetContent(){
			global $page;

			//not needed for admin pages
			if( $page->pagetype == 'admin_display' ){
				return;
			}

			$gadget_info = self::WhichGadgets($page->gpLayout);

			foreach($gadget_info as $gpOutCmd => $info){
				if( !isset(self::$gadget_cache[$gpOutCmd]) ){
					ob_start();
					self::ExecArea($info);
					self::$gadget_cache[$gpOutCmd] = ob_get_clean();
				}
			}
		}

		/**
		 * Return information about the gadgets being used in the current layout
		 * @return array
		 */
		public static function WhichGadgets($layout){
			global $config,$gpLayouts;

			$gadget_info = $temp_info = array();
			if( !isset($config['gadgets']) ){
				return $gadget_info;
			}

			$layout_info = & $gpLayouts[$layout];


			$GetAllGadgets = true;
			if( isset($layout_info['all_gadgets']) && !$layout_info['all_gadgets'] ){
				$GetAllGadgets = false;
			}

			if( isset($layout_info['handlers']) ){
				foreach($layout_info['handlers'] as $handler => $out_cmds){

					//don't prep even if GetAllGadgets is set in the layout's config
					if( $handler == 'GetAllGadgets' && !$GetAllGadgets ){
						continue;
					}
					foreach($out_cmds as $gpOutCmd){
						$temp_info[$gpOutCmd] = self::GetgpOutInfo($gpOutCmd);
					}
				}
			}

			//add all gadgets if $GetAllGadgets is true and the GetAllGadgets handler isn't overwritten
			if( $GetAllGadgets && !isset($layout_info['handlers']['GetAllGadgets']) ){
				foreach($config['gadgets'] as $gadget => $temp){
					if( isset($temp['addon']) ){
						$temp_info[$gadget] = self::GetgpOutInfo($gadget);
					}
				}
			}

			foreach($temp_info as $gpOutCmd => $info){
				if( isset($info['is_gadget'])
					&& $info['is_gadget']
					&& !isset($info['disabled'])
					){
						$gadget_info[$gpOutCmd] = $info;
				}
			}

			return $gadget_info;
		}


		public static function GetExtra($name='Side_Menu',$info=array()){
			global $dataDir,$langmessage;


			$attrs			= array();
			$name			= str_replace(' ','_',$name);
			$file_stats		= array();
			$is_draft		= false;
			$extra_content	= self::ExtraContent( $name, $file_stats, $is_draft );
			$wrap			= self::ShowEditLink('Admin_Extra');

			if( !$wrap ){
				echo '<div'.\gp\tool\Output\Sections::SectionAttributes($attrs,$extra_content[0]['type']).'>';
				echo \gp\tool\Output\Sections::RenderSection($extra_content[0],0,'',$file_stats);
				echo '</div>';
				return;
			}


			$edit_link = self::EditAreaLink($edit_index,'Admin/Extra',$langmessage['edit'],'cmd=edit&file='.$name,array('title'=>$name,'data-cmd'=>'inline_edit_generic'));

			$include_link = '';
			if( $extra_content[0]['type'] == 'include' && $extra_content[0]['include_type'] == false ){
				$include_link = \gp\tool::Link($extra_content[0]['content'], $langmessage['view/edit_page']);
			}

			ob_start();
			echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
			echo $edit_link;
			echo $include_link;
			echo \gp\tool::Link('Admin/Extra',$langmessage['theme_content'],'',' class="nodisplay"');
			echo '</span>';
			self::$editlinks .= ob_get_clean();


			$attrs['data-gp_label']		= str_replace('_',' ',$name);
			$attrs['class']				= 'editable_area';
			$attrs['id']				= 'ExtraEditArea'.$edit_index;

			if( $is_draft ){
				$attrs['data-draft']	= 1;
			}else{
				$attrs['data-draft']	= 0;
			}

			echo '<div'.\gp\tool\Output\Sections::SectionAttributes($attrs,$extra_content[0]['type']).'>';
			echo \gp\tool\Output\Sections::RenderSection($extra_content[0],0,'',$file_stats);
			echo '</div>';
		}


		/**
		 * Get and return the extra content specified by $title
		 *
		 */
		public static function ExtraContent( $title, &$file_stats = array(), &$is_draft = false ){

			//draft?
			$draft_file = '_extra/'.$title.'/draft';
			if( \gp\tool::LoggedIn() && \gp\tool\Files::Exists($draft_file) ){
				$is_draft = true;
				return \gp\tool\Files::Get($draft_file,'file_sections');
			}

			//new location
			$file = '_extra/'.$title.'/page';
			if( \gp\tool\Files::Exists($file) ){
				return \gp\tool\Files::Get($file,'file_sections');
			}

			$file = '_extra/'.$title;
			$extra_section = array();
			if( \gp\tool\Files::Exists($file) ){

				ob_start();
				$extra_section = \gp\tool\Files::Get($file,'extra_content');
				$extra_section_string = ob_get_clean();

				if( !count($extra_section) ){
					$extra_section['content'] = $extra_section_string;
				}
			}

			$extra_section 	+= array('type'=>'text','content'=>'');
			return array($extra_section);
		}

		public static function GetImage($src,$attributes = array()){
			global $page,$dataDir,$langmessage,$gpLayouts;

			//$width,$height,$attributes = ''
			$attributes = (array)$attributes;
			$attributes += array('class'=>'');
			unset($attributes['id']);


			//default image information
			$img_rel = dirname($page->theme_rel).'/'.ltrim($src,'/');


			//container id
			$container_id = 'Image:'.$src;
			$container_id = self::GetContainerID($container_id);

			//select custom image
			$image = false;
			if( isset($gpLayouts[$page->gpLayout])
				&& isset($gpLayouts[$page->gpLayout]['images'])
				&& isset($gpLayouts[$page->gpLayout]['images'][$container_id])
				&& is_array($gpLayouts[$page->gpLayout]['images'][$container_id])
				){
					//shuffle($gpLayouts[$page->gpLayout]['images'][$container_id]); //Does not make sense ? There will always be only 1 entry in for this container as it is per img element
					$image = $gpLayouts[$page->gpLayout]['images'][$container_id][0]; //call to current also not needed, there will only be 1 entry
					$img_full = $dataDir.$image['img_rel'];
					if( file_exists($img_full) ){
						$img_rel = $image['img_rel'];
						$attributes['width'] = $image['width'];
						$attributes['height'] = $image['height'];
					}
			}

			//attributes
			if( !isset($attributes['alt']) ){
				$attributes['alt'] = '';
			}


			//edit options
			$editable = self::ShowEditLink('Admin_Theme_Content');
			if( $editable ){
				$edit_link = self::EditAreaLink($edit_index,'Admin_Theme_Content/Image/'.$page->gpLayout,$langmessage['edit'],'file='.rawurlencode($img_rel).'&container='.$container_id.'&time='.time(),'title="Edit Image" data-cmd="inline_edit_generic"');
				self::$editlinks .= '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">'.$edit_link.'</span>';
				$attributes['class'] .= ' editable_area';
				$attributes['id'] = 'ExtraEditArea'.$edit_index;
			}

			//remove class if empty
			$attributes['class'] = trim($attributes['class']);
			if( empty($attributes['class']) ){
				unset($attributes['class']);
			}

			//convert attributes to string
			$str = '';
			foreach($attributes as $key => $value){
				$str .= ' '.$key.'="'.htmlspecialchars($value,ENT_COMPAT,'UTF-8',false).'"';
			}
			echo '<img src="'.\gp\tool::GetDir($img_rel,true).'"'.$str.'/>';
		}


		/*
		 *
		 * Output Additional Areas
		 *
		 */

		/* draggable html and editable text */
		public static function Area($name,$html){
			global $gpOutConf;
			if( self::$out_started ){
				trigger_error('\gp\tool\Output::Area() must be called before all other output functions');
				return;
			}
			$name = '[text]'.$name;
			$gpOutConf[$name] = array();
			$gpOutConf[$name]['method'] = array('\\gp\\tool\\Output','GetAreaOut');
			$gpOutConf[$name]['html'] = $html;
		}

		public static function GetArea($name,$text){
			$name = '[text]'.$name;
			self::Get($name,$text);
		}

		public static function GetAreaOut($text,$info){
			global $config,$langmessage,$page;

			$html =& $info['html'];

			$wrap = self::ShowEditLink('Admin_Theme_Content');
			if( $wrap ){
				self::$editlinks .= self::EditAreaLink($edit_index,'Admin_Theme_Content/Text',$langmessage['edit'],'cmd=EditText&key='.urlencode($text).'&return='.urlencode($page->title),' title="'.htmlspecialchars($text).'" data-cmd="gpabox" ');
				echo '<div class="editable_area inner_size" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
			}

			if( isset($config['customlang'][$text]) ){
				$text = $config['customlang'][$text];

			}elseif( isset($langmessage[$text]) ){
				$text =  $langmessage[$text];
			}

			echo str_replace('%s',$text,$html); //in case there's more than one %s

			if( $wrap ){
				echo '</div>';
			}
		}

		/*
		 *
		 * editable text, not draggable
		 *
		 */

		/* similar to ReturnText() but links to script for editing all addon texts */
		// the $html parameter should primarily be used when the text is to be placed inside of a link or other element that cannot have a link and/or span as a child node
		public static function GetAddonText($key,$html='%s', $wrapper_class = ''){
			global $addonFolderName;

			if( !$addonFolderName ){
				return self::ReturnText($key, $html, $wrapper_class);
			}

			$query = 'cmd=AddonTextForm&addon='.urlencode($addonFolderName).'&key='.urlencode($key);
			return self::ReturnTextWorker($key,$html,$query, $wrapper_class);
		}

		public static function ReturnText($key,$html='%s', $wrapper_class = ''){
			$query = 'cmd=EditText&key='.urlencode($key);
			return self::ReturnTextWorker($key,$html,$query, $wrapper_class);
		}

		public static function ReturnTextWorker($key,$html,$query, $wrapper_class=''){
			global $langmessage;

			$text		= self::SelectText($key);
			$result		= str_replace('%s',$text,$html); //in case there's more than one %s


			$editable	= self::ShowEditLink('Admin_Theme_Content');
			if( $editable ){

				$title = htmlspecialchars(strip_tags($key));
				if( strlen($title) > 20 ){
					$title = substr($title,0,20).'...'; //javscript may shorten it as well
				}

				self::$editlinks .= self::EditAreaLink($edit_index,'Admin_Theme_Content/Text',$langmessage['edit'],$query,' title="'.$title.'" data-cmd="gpabox" ');
				return '<span class="editable_area '.$wrapper_class.'" id="ExtraEditArea'.$edit_index.'">'.$result.'</span>';
			}

			if( $wrapper_class ){
				return '<span class="'.$wrapper_class.'">'.$result.'</span>';
			}

			return $result;

		}



		/**
		 * Returns the user translated string if it exists or $key (the untranslated string) if a translation doesn't exist
		 *
		 */
		public static function SelectText($key){
			global $config,$langmessage;

			$text = $key;
			if( isset($config['customlang'][$key]) ){
				$text = $config['customlang'][$key];

			}elseif( isset($langmessage[$key]) ){
				$text = $langmessage[$key];
			}
			return $text;
		}


		/**
		 * Generate and output the <head> portion of the html document
		 *
		 */
		public static function GetHead(){
			\gp\tool\Plugins::Action('GetHead');
			self::PrepGadgetContent();
			echo '<!-- get_head_placeholder '.gp_random.' -->';
		}

		public static function HeadContent(){
			global $config, $page, $wbMessageBuffer;

			//before ob_start() so plugins can get buffer content
			\gp\tool\Plugins::Action('HeadContent');

			ob_start();

			if( \gp\tool::LoggedIn() ){
				\gp\tool::AddColorBox();
			}


			//always include javascript when there are messages
			if( $page->admin_js || !empty($page->jQueryCode) || !empty($wbMessageBuffer) || isset($_COOKIE['cookie_cmd']) ){
				\gp\tool::LoadComponents('gp-main');
			}
			//defaults
			\gp\tool::LoadComponents('jquery,gp-additional');

			//get css and js info
			$scripts = \gp\tool\Output\Combine::ScriptInfo( self::$components );

			self::GetHead_TKD();

			ob_start();
			self::GetHead_CSS($scripts['css']); //css before js so it's available to scripts
			self::$head_css = ob_get_clean();

			self::$head_content = ob_get_clean();

			//javascript
			ob_start();
			self::GetHead_Lang();
			self::GetHead_JS($scripts['js']);
			self::GetHead_InlineJS();
			self::$head_js = ob_get_clean();



			//gadget info
			if( isset($config['addons']) ){
				foreach($config['addons'] as $addon_info){
					if( !empty($addon_info['html_head']) ){
						self::MoveScript($addon_info['html_head']);
					}
				}
			}

			if( !empty($page->head) ){
				self::MoveScript($page->head);
			}
		}


		/**
		 * Move <script>..</script> to self::$head_js
		 *
		 */
		public static function MoveScript($string){

			//conditional comments with script tags
			$patt = '#'.preg_quote('<!--[if','#').'.*?'.preg_quote('<![endif]-->','#').'#s';
			if( preg_match_all($patt,$string, $matches) ){
				foreach($matches[0] as $match){
					if( strpos($match,'<script') !== false ){
						$string = str_replace($match, '', $string);
						self::$head_js .= "\n".$match;
					}
				}
			}


			//script tags
			if( preg_match_all('#<script.*?</script>#i',$string,$matches) ){
				foreach($matches[0] as $match){
					$string = str_replace($match, '', $string);
					self::$head_js .= "\n".$match;
				}
			}

			//add the rest to the head_content
			self::$head_content .= "\n".$string;
		}


		/**
		 * Output the title, keywords, description and other meta for the current html document
		 * @static
		 */
		public static function GetHead_TKD(){
			global $config, $page, $gpLayouts;

			//charset
			if( $page->gpLayout && isset($gpLayouts[$page->gpLayout]) && isset($gpLayouts[$page->gpLayout]['doctype']) ){
				echo $gpLayouts[$page->gpLayout]['doctype'];
			}


			//title, keyords & description
			$page_title = self::MetaTitle();
			self::MetaKeywords($page_title);
			self::MetaDescription($page_title);

			if( !empty($page->TitleInfo['rel']) ){
				echo "\n".'<meta name="robots" content="'.$page->TitleInfo['rel'].'" />';
			}

			echo "\n<meta name=\"generator\" content=\"Typesetter CMS\" />";
		}


		/**
		 * Add the <title> tag to the page
		 * return the value
		 *
		 */
		public static function MetaTitle(){
			global $page, $config;

			$meta_title = '';
			$page_title = '';
			if( !empty($page->TitleInfo['browser_title']) ){
				$page_title = $page->TitleInfo['browser_title'];
			}elseif( !empty($page->label) ){
				$page_title = strip_tags($page->label);
			}elseif( isset($page->title) ){
				$page_title = \gp\tool::GetBrowserTitle($page->title);
			}
			$meta_title .= $page_title;
			if( !empty($page_title) && !empty($config['title']) ){
				$meta_title .=  ' - ';
			}
			$meta_title .= $config['title'];

			$meta_title = \gp\tool\Plugins::Filter('MetaTitle', array($meta_title, $page_title, $config['title']) );

			echo "\n" . '<title>' . $meta_title . '</title>';
			return $page_title;
		}


		/**
		 * Add the <meta name="keywords"> tag to the page
		 *
		 */
		public static function MetaKeywords($page_title){
			global $page, $config;

			if( count($page->meta_keywords) ){
				$keywords = $page->meta_keywords;
			}elseif( !empty($page->TitleInfo['keywords']) ){
				$keywords = explode(',',$page->TitleInfo['keywords']);
			}

			$keywords[]		= strip_tags($page_title);
			$keywords[]		= strip_tags($page->label);

			$site_keywords	= explode(',',$config['keywords']);
			$keywords		= array_merge($keywords,$site_keywords);
			$keywords		= array_unique($keywords);
			$keywords		= array_filter($keywords);

			echo "\n<meta name=\"keywords\" content=\"".implode(', ',$keywords)."\" />";
		}

		/**
		 * Add the <meta name="dscription"> tag to the page
		 *
		 */
		public static function MetaDescription($page_title){
			global $page, $config;

			$description = '';
			if( !empty($page->meta_description) ){
				$description .= $page->meta_description;
			}elseif( !empty($page->TitleInfo['description']) ){
				$description .= $page->TitleInfo['description'];
			}else{
				$description .= $page_title;
			}
			$description = self::EndPhrase($description);

			if( !empty($config['desc']) ){
				$description .= htmlspecialchars($config['desc']);
			}
			$description = trim($description);

			if( !empty($description) ){
				echo "\n<meta name=\"description\" content=\"".$description."\" />";
			}
		}


		/**
		 * Prepare and output any inline Javascript for the current page
		 * @static
		 */
		public static function GetHead_InlineJS(){
			global $page, $linkPrefix;

			ob_start();
			echo $page->head_script;

			if( !empty($page->jQueryCode) ){
				echo '$(function(){';
				echo $page->jQueryCode;
				echo '});';
			}

			$inline = ob_get_clean();
			$inline = ltrim($inline);
			if( !empty($inline) ){
				echo "\n<script>\n".$inline."\n</script>\n";
			}
		}


		/**
		 * Add language values to the current page
		 * @static
		 */
		public static function GetHead_Lang(){
			global $langmessage;

			if( !count(self::$lang_values) ){
				return;
			}

			echo "\n<script type=\"text/javascript\">";
			echo 'var gplang = {';
			$comma = '';
			foreach(self::$lang_values as $from_key => $to_key){
				echo $comma;
				echo $to_key.':"'.str_replace(array('\\','"'),array('\\\\','\"'),$langmessage[$from_key]).'"';
				$comma = ',';
			}
			echo "}; </script>";
		}


		/**
		 * Prepare and output the Javascript for the current page
		 * @static
		 */
		public static function GetHead_JS($scripts){
			global $page, $config;

			$combine		= $config['combinejs'] && !\gp\tool::loggedIn() && ($page->pagetype !== 'admin_display');
			$scripts		= self::GetHead_CDN('js',$scripts);


			//just local jquery
			if( !count($page->head_js) && count($scripts) === 1 && isset($scripts['jquery']) ){
				echo '<!-- jquery_placeholder '.gp_random.' -->';
				return;
			}


			if( !$combine || $page->head_force_inline ){
				echo "\n<script type=\"text/javascript\">\n";
				\gp\tool::jsStart();
				echo "\n</script>";
			}

			if( is_array($page->head_js) ){
				$scripts += $page->head_js; //other js files
			}else{
				trigger_error('$page->head_js is not an array');
			}

			self::CombineFiles($scripts,'js',$combine );
		}


		/**
		 * Prepare and output the css for the current page
		 * @static
		 */
		public static function GetHead_CSS($scripts){
			global $page, $config, $dataDir;

			$scripts = self::GetHead_CDN('css',$scripts);


			if( isset($page->css_user) && is_array($page->css_user) ){
				$scripts = array_merge($scripts,$page->css_user);
			}


			// add theme css
			if( !empty($page->theme_name) && $page->get_theme_css === true ){
				$scripts = array_merge( $scripts, self::LayoutStyleFiles() );
			}

			//styles that need to override admin.css should be added to $page->css_admin;
			if( isset($page->css_admin) && is_array($page->css_admin) ){
				$scripts = array_merge($scripts,$page->css_admin);
			}


			//convert .scss & .less files to .css
			foreach($scripts as $key => $script){

				// allow arrays of scripts
				$files = array();

				if( is_array($script) ){
					// array of scripts
					if( isset($script['file']) ){
						// single script
						$file = $script['file'];
						$ext = \gp\tool::Ext($file);
						$files[$ext] = array($dataDir.$file);
					}else{
						// multiple scripts
						foreach( $script as $file ){
							$file = is_array($file) ? $file['file'] : $file;
							$ext = \gp\tool::Ext($file);
							//$files[$ext] += array();
							$files[$ext][] = $dataDir.$file;
						}
					}
				}else{
					$file = $script;
					$ext = \gp\tool::Ext($file);
					$files[$ext] = array($dataDir.$file);
				}

				foreach( $files as $ext => $files_same_ext ){
					//less and scss
					if( $ext == 'less' || $ext == 'scss' ){ // msg("from GetHead_CSS");
						$scripts[$key] = \gp\tool\Output\Css::Cache($files_same_ext,$ext);
					}
				}

			}

			self::CombineFiles($scripts,'css',$config['combinecss']);
		}


		/**
		 * Add CDN hosted resources to the page
		 *
		 */
		public static function GetHead_CDN($type,$scripts){
			global $config;

			if( empty($config['cdn']) ){
				return $scripts;
			}

			$cdn		= $config['cdn'];
			$packages	= array();

			foreach($scripts as $key => $script_info){

				if( !isset($script_info['cdn']) || !isset($script_info['cdn'][$cdn]) ){
					continue;
				}

				$cdn_url					= $script_info['cdn'][$cdn];

				//remove packages
				if( isset($script_info['package']) ){
					foreach($scripts as $_key => $_info){
						if( isset($_info['package']) && $_info['package'] == $script_info['package'] ){
							unset($scripts[$_key]);
						}
					}
				}
				unset($scripts[$key]);

				if( $type == 'css' ){
					echo "\n".'<link rel="stylesheet" type="text/css" href="'.$cdn_url.'" />';
				}else{
					echo "\n".'<script type="text/javascript" src="'.$cdn_url.'"></script>';
				}
			}

			return $scripts;
		}


		/**
		 * Return a list of css files used by the current layout
		 *
		 */
		public static function LayoutStyleFiles(){
			global $page, $dataDir;


			$files			= array();
			$dir			= $page->theme_dir . '/' . $page->theme_color;
			$style_type		= self::StyleType($dir);
			$custom_file	= self::CustomStyleFile($page->gpLayout, $style_type);

			//css file
			if( $style_type == 'css' ){

				$files[] = rawurldecode($page->theme_path).'/style.css';

				if( $page->gpLayout && file_exists($custom_file) ){
					$files[] = \gp\tool\Output\Css::Cache( $custom_file, 'less' );
				}

				return $files;
			}


			//less or scss file
			$var_file	= $dir.'/variables.'.$style_type;
			if( file_exists($var_file) ){
				$files[] = $var_file;
			}


			if( $page->gpLayout && file_exists($custom_file) ){
				$files[] = $custom_file;
			}


			if( $style_type == 'scss' ){

				$files[]		= $dir . '/style.scss';
				return array( \gp\tool\Output\Css::Cache($files) );
			}

			array_unshift($files, $dir.'/style.less');

			return array( \gp\tool\Output\Css::Cache($files,'less') );
		}


		/**
		 * Get the path for the custom css/scss/less file
		 *
		 */
		public static function CustomStyleFile($layout, $style_type){
			global $dataDir;

			if( $style_type == 'scss' ){
				return $dataDir.'/data/_layouts/'.$layout.'/custom.scss';
			}

			return $dataDir.'/data/_layouts/'.$layout.'/custom.css';
		}


		/**
		 * Get the filetype of the style.* file
		 *
		 * @return string|false
		 */
		public static function StyleType($dir){
			$css_path	= $dir.'/style.css';
			$less_path	= $dir.'/style.less';
			$scss_path	= $dir.'/style.scss';

			if( file_exists($css_path) ){
				return 'css';
			}

			if( file_exists($less_path) ){
				return 'less';
			}

			if( file_exists($scss_path) ){
				return 'scss';
			}

			return false;
		}


		/**
		 * Combine the files in $files into a combine.php request
		 * If $page->head_force_inline is true, resources will be included inline in the document
		 *
		 * @param array $files Array of files relative to $dataDir
		 * @param string $type The type of resource being combined
		 *
		 */
		public static function CombineFiles($files,$type,$combine){
			global $page;

			//msg("files=" . pre($files));

			// allow arrays of scripts
			$files_flat = array();

			//only need file paths
			foreach($files as $key => $val){
				if( is_array($val) ){
					// array of scripts
					if( isset($val['file']) ){
						// single script
						$files_flat[$key] = $val['file'];
					}else{
						// multiple scripts
						foreach( $val as $subkey => $file ){
							$files_flat[$key.'-'.$subkey] = is_array($file) ? $file['file'] : $file;
						}
					}
				}else{
					$files_flat[$key] = $val;
				}
			}

			$files_flat = array_unique($files_flat);
			$files_flat = array_filter($files_flat);//remove empty elements

			// Force resources to be included inline
			// CheckFile will fix the $file path if needed
			if( $page->head_force_inline ){
				if( $type == 'css' ){
					echo '<style type="text/css">';
				}else{
					echo '<script type="text/javascript">';
				}
				foreach($files_flat as $file_key => $file){
					$full_path = \gp\tool\Output\Combine::CheckFile($file);
					if( $full_path === false ) continue;
					readfile($full_path);
					echo ";\n";
				}
				if( $type == 'css' ){
					echo '</style>';
				}else{
					echo '</script>';
				}
				return;
			}


			//files not combined except for script components
			if( !$combine || (isset($_REQUEST['no_combine']) && \gp\tool::LoggedIn()) ){
				foreach($files_flat as $file_key => $file){

					$html = "\n".'<script type="text/javascript" src="%s"></script>';
					if( $type == 'css' ){
						$html = "\n".'<link type="text/css" href="%s" rel="stylesheet"/>';
					}

					\gp\tool\Output\Combine::CheckFile($file);
					if( \gp\tool::LoggedIn() ){
						$file .= '?v='.rawurlencode(gpversion);
					}
					echo sprintf($html,\gp\tool::GetDir($file,true));
				}
				return;
			}


			$html = "\n".'<script type="text/javascript" src="%s"></script>';
			if( $type == 'css' ){
				$html = "\n".'<link rel="stylesheet" type="text/css" href="%s"/>';
			}

			//create combine request
			$combined_file = \gp\tool\Output\Combine::GenerateFile($files_flat,$type);
			if( $combined_file === false ){
				return;
			}


			echo sprintf($html,\gp\tool::GetDir($combined_file,true));
		}


		/**
		 * Complete the response by adding final content to the <head> of the document
		 * @static
		 * @since 2.4.1
		 * @param string $buffer html content
		 * @return string finalized response
		 */
		public static function BufferOut($buffer){
			global $config;


			//add error notice if there was a fatal error
			if( !ini_get('display_errors') ){
				$last_error	= self::LastFatal();
				if( $last_error ){
					self::RecordFatal( $last_error );
					$buffer .= self::FatalMessage( $last_error );
				}
			}


			//remove lock
			if( defined('gp_has_lock') && gp_has_lock ){
				\gp\tool\Files::Unlock('write',gp_random);
			}


			//make sure whe have a complete html request
			$placeholder = '<!-- get_head_placeholder '.gp_random.' -->';
			if( strpos($buffer,$placeholder) === false ){
				return $buffer;
			}

			//add css to bottom of <body>
			if( defined('load_css_in_body') && load_css_in_body == true ){
				$buffer = self::AddToBody($buffer, self::$head_css );
			}

			//add js to bottom of <body>
			$buffer = self::AddToBody($buffer, self::$head_js );


			$replacements			= array();

			//performace stats
			if( class_exists('admin_tools') ){
				$replacements		= self::PerformanceStats();
			}

			//head content
			if( defined('load_css_in_body') && load_css_in_body == true  ){
				$replacements[$placeholder]	= self::$head_content;
			}else{
				$replacements[$placeholder]	= self::$head_css . self::$head_content;
			}


			//add jquery if needed
			$placeholder = '<!-- jquery_placeholder '.gp_random.' -->';
			$replacement = '';
			if( !empty(self::$head_js) || stripos($buffer,'<script') !== false ){
				$replacement = "\n<script type=\"text/javascript\" src=\"".\gp\tool::GetDir('/include/thirdparty/js/jquery.js')."\"></script>";
			}

			$replacements[$placeholder]	= $replacement;


			//messages
			$pos = strpos($buffer,'<!-- message_start '.gp_random.' -->');
			$len = strpos($buffer,'<!-- message_end -->') - $pos;
			if( $pos && $len ){
				$replacement = GetMessages(false);
				$buffer = substr_replace($buffer,$replacement,$pos,$len+20);
			}


			return str_replace( array_keys($replacements), array_values($replacements), $buffer);
		}


		/**
		 * Add content to the html document before the </body> tag
		 *
		 */
		public static function AddToBody($buffer, $add_string){

			if( empty($add_string) ){
				return $buffer;
			}

			$pos_body = stripos($buffer,'</body');
			if( $pos_body !== false ){
				return substr_replace($buffer,"\n".$add_string."\n",$pos_body,0);
			}

			return $buffer;
		}


		/**
		 * Return the message displayed when a fatal error has been caught
		 *
		 */
		public static function FatalMessage( $error_details ){

			$message = '<p>Oops, an error occurred while generating this page.<p>';

			if( !\gp\tool::LoggedIn() ){

				//reload non-logged in users automatically if there were catchable errors
				if( !empty(self::$catchable) ){
					$message .= 'Reloading... <script type="text/javascript">window.setTimeout(function(){window.location.href = window.location.href},1000);</script>';
				}else{
					$message .= '<p>If you are the site administrator, you can troubleshoot the problem by changing php\'s display_errors setting to 1 in the gpconfig.php file.</p>'
							.'<p>If the problem is being caused by an addon, you may also be able to bypass the error by enabling '.CMS_NAME.'\'s safe mode in the gpconfig.php file.</p>'
							.'<p>More information is available in the <a href="'.CMS_DOMAIN.'/Docs/Main/Troubleshooting">Documentation</a>.</p>'
							.'<p><a href="">Reload this page to continue</a>.</p>';
				}

				return $message;
			}


			$message .= '<h3>Error Details</h3>'
					.pre($error_details)
					.'<p><a href="">Reload this page</a></p>'
					.'<p style="font-size:90%">Note: Error details are only displayed for logged in administrators</p>'
					.\gp\tool::ErrorBuffer(true,false);

			return $message;
		}


		/**
		 * Determine if a fatal error has been fired
		 * @return array
		 */
		public static function LastFatal(){

			if( !function_exists('error_get_last') ){
				return;
			}


			$fatal_errors	= array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR );
			$last_error		= error_get_last();
			if( is_array($last_error) && in_array($last_error['type'],$fatal_errors) ){
				return $last_error;
			}

		}


		/**
		 * Record fatal errors in /data/_site/ so we can prevent subsequent requests from having the same issue
		 *
		 */
		static function RecordFatal($last_error){
			global $dataDir, $config, $addon_current_id, $addonFolderName;

			$last_error['request'] = $_SERVER['REQUEST_URI'];
			if( $addon_current_id ){
				$last_error['addon_name'] = $config['addons'][$addonFolderName]['name'];
				$last_error['addon_id'] = $addon_current_id;
			}

			$last_error['file'] = realpath($last_error['file']);//may be redundant
			showError($last_error['type'], $last_error['message'],  $last_error['file'],  $last_error['line'], false); //send error to logger

			if( empty(self::$catchable) ){
				return;
			}

			$last_error['time'] = time();
			$last_error['request_method'] = $_SERVER['REQUEST_METHOD'];
			if( !empty($last_error['file']) ){
				$last_error['file_modified'] = filemtime($last_error['file']);
				$last_error['file_size'] = filesize($last_error['file']);
			}

			$content	= json_encode($last_error);
			$temp		= array_reverse(self::$catchable);

			foreach($temp as $error_hash => $info){

				$file = $dataDir.'/data/_site/fatal_'.$error_hash;
				\gp\tool\Files::Save($file,$content);

				if( $info['catchable_type'] == 'exec' ){
					break;
				}
			}

		}


		/**
		 * Return Performance Stats about the current request
		 *
		 * @return array
		 */
		public static function PerformanceStats(){

			$stats = array();

			if( function_exists('memory_get_peak_usage') ){
				$stats['<span cms-memory-usage>?</span>']	= \gp\admin\Tools::FormatBytes(memory_get_usage());
				$stats['<span cms-memory-max>?</span>']		= \gp\admin\Tools::FormatBytes(memory_get_peak_usage());
			}

			if( isset($_SERVER['REQUEST_TIME_FLOAT']) ){
				$time	= microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
			}else{
				$time	= microtime(true) - gp_start_time;
			}

			$stats['<span cms-seconds>?</span>']		= round($time,3);
			$stats['<span cms-ms>?</span>']			= round($time*1000);


			return $stats;
		}


		/**
		 * Return true if the user agent is a search engine bot
		 * Detection is rudimentary and shouldn't be relied on
		 * @return bool
		 */
		public static function DetectBot(){
			$user_agent =& $_SERVER['HTTP_USER_AGENT'];
			return preg_match('#bot|yahoo\! slurp|ask jeeves|ia_archiver|spider#i',$user_agent);
		}

		/**
		 * Return true if the current page is the home page
		 */
		public static function is_front_page(){
			global $config, $page;
			return $page->gp_index == $config['homepath_key'];
		}


		/**
		 * Outputs the sitemap link, admin login/logout link, powered by link, admin html and messages
		 * @static
		 */
		public static function GetAdminLink(){
			global $config, $langmessage, $page;

			if( !isset($config['showsitemap']) || $config['showsitemap'] ){
				echo ' <span class="sitemap_link">';
				echo \gp\tool::Link('Special_Site_Map',$langmessage['site_map']);
				echo '</span>';
			}

			if( !isset($config['showlogin']) || $config['showlogin'] ){
				echo ' <span class="login_link">';
					if( \gp\tool::LoggedIn() ){
						echo \gp\tool::Link($page->title,$langmessage['logout'],'cmd=logout',array('data-cmd'=>'creq','rel'=>'nofollow'));
					}else{
						echo \gp\tool::Link('Admin',$langmessage['login'],'file='.rawurlencode($page->title),' rel="nofollow" data-cmd="login"');
					}
				echo '</span>';
			}


			if( !isset($config['showgplink']) || $config['showgplink'] ){
				if( self::is_front_page() ){
					echo ' <span id="powered_by_link">';
					echo 'Powered by <a href="'.CMS_DOMAIN.'" target="_blank">'.CMS_NAME.'</a>';
					echo '</span>';
				}
			}


			\gp\tool\Plugins::Action('GetAdminLink');


			echo GetMessages();
		}


		/**
		 * Add punctuation to the end of a string if it isn't already punctuated. Looks for !?.,;: characters
		 * @static
		 * @since 2.4RC1
		 */
		public static function EndPhrase($string){
			$string = trim($string);
			if( empty($string) ){
				return $string;
			}
			$len = strspn($string,'!?.,;:',-1);
			if( $len == 0 ){
				$string .= '.';
			}
			return $string.' ';
		}



		public static function RunOut(){
			global $page;

			$page->RunScript();

			//prepare the admin content
			if( \gp\tool::LoggedIn() ){
				\gp\admin\Tools::AdminHtml();
			}


			//decide how to send the content
			self::Prep();
			switch(\gp\tool::RequestType()){

				// <a data-cmd="admin_box">
				case 'flush':
					self::Flush();
				break;

				// remote request
				// file browser
				case 'body':
					\gp\tool::CheckTheme();
					self::BodyAsHTML();
				break;

				case 'admin':
					self::AdminHtml();
				break;

				// <a data-cmd="gpajax">
				// <a data-cmd="gpabox">
				// <input data-cmd="gpabox">
				case 'json':
					\gp\tool::CheckTheme();
					\gp\tool\Output\Ajax::Response();
				break;

				case 'content':
					self::Content();
				break;

				default:
					\gp\tool::CheckTheme();
					self::Template();
				break;
			}



			// if logged in, don't send 304 response
			if( \gp\tool::LoggedIn() ){

				//empty edit links if there isn't a layout
				if( !$page->gpLayout ){
					self::$editlinks = '';
				}

				return;
			}

			// attempt to send 304 response
			if( $page->fileModTime > 0 ){
				global $wbMessageBuffer;
				$len	= ob_get_length();
				$etag	= \gp\tool::GenEtag( $page->fileModTime, $len, json_encode($wbMessageBuffer), self::$head_content, self::$head_js );
				\gp\tool::Send304( $etag );
			}
		}


		/**
		 * Add one or more components to the page. Output the <script> and/or <style> immediately
		 * @param string $names comma separated list of components
		 *
		 */
		public static function GetComponents($names = ''){
			$scripts = \gp\tool\Output\Combine::ScriptInfo( $names );

			$scripts['css'] = self::GetHead_CDN('css',$scripts['css']);
			self::CombineFiles($scripts['css'], 'css', false );

			$scripts['js'] = self::GetHead_CDN('js',$scripts['js']);
			self::CombineFiles($scripts['js'], 'js', false );
		}

	}
}

namespace{
	class gpOutput extends gp\tool\Output{}
}
