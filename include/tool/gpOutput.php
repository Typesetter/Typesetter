<?php
defined('is_running') or die('Not an entry point...');

//for output handlers, see admin_theme_content.php for more info
global $GP_ARRANGE, $gpOutConf, $GP_LANG_VALUES, $GP_INLINE_VARS, $GP_EXEC_STACK;

$GP_ARRANGE = true;
$GP_NESTED_EDIT = false;
$gpOutConf = $GP_LANG_VALUES = $GP_INLINE_VARS = $GP_EXEC_STACK = array();


//named menus should just be shortcuts to the numbers in custom menu
//	custom menu format: $top_level,$bottom_level,$expand_level

//custom menu: 0,0,0,0
$gpOutConf['FullMenu']['method']		= array('gpOutput','GetFullMenu');
$gpOutConf['FullMenu']['link']			= 'all_links';

//custom menu: 0,0,1,1
$gpOutConf['ExpandMenu']['method']		= array('gpOutput','GetExpandMenu');
$gpOutConf['ExpandMenu']['link']		= 'expanding_links';

//custom menu: 0,0,2,1
$gpOutConf['ExpandLastMenu']['method']	= array('gpOutput','GetExpandLastMenu');
$gpOutConf['ExpandLastMenu']['link']	= 'expanding_bottom_links';

//custom menu: 0,1,0,0
$gpOutConf['Menu']['method']			= array('gpOutput','GetMenu');
$gpOutConf['Menu']['link']				= 'top_level_links';

//custom menu: 1,0,0,0
$gpOutConf['SubMenu']['method']			= array('gpOutput','GetSubMenu');
$gpOutConf['SubMenu']['link']			= 'subgroup_links';

//custom menu: 0,2,0,0
$gpOutConf['TopTwoMenu']['method']		= array('gpOutput','GetTopTwoMenu');
$gpOutConf['TopTwoMenu']['link']		= 'top_two_links';

//custom menu: does not translate, this pays no attention to grouping
$gpOutConf['BottomTwoMenu']['method']	= array('gpOutput','GetBottomTwoMenu');
$gpOutConf['BottomTwoMenu']['link']		= 'bottom_two_links';

//custom menu: 1,2,0,0
$gpOutConf['MiddleSubMenu']['method']	= array('gpOutput','GetSecondSubMenu');
$gpOutConf['MiddleSubMenu']['link']		= 'second_sub_links';

//custom menu: 2,3,0,0
$gpOutConf['BottomSubMenu']['method']	= array('gpOutput','GetThirdSubMenu');
$gpOutConf['BottomSubMenu']['link']		= 'third_sub_links';

$gpOutConf['CustomMenu']['method']		= array('gpOutput','CustomMenu');

$gpOutConf['Extra']['method']			= array('gpOutput','GetExtra');
//$gpOutConf['Text']['method']			= array('gpOutput','GetText'); //use Area() and GetArea() instead

//$gpOutConf['Image']['method']			= array('gpOutput','GetImage');

/* The following methods should be used with gpOutput::Fetch() */
$gpOutConf['Gadget']['method']			= array('gpOutput','GetGadget');


class gpOutput{

	public static $components = '';
	public static $editlinks = '';
	public static $template_included = false;

	private static $out_started = false;
	private static $gadget_cache = array();

	public static $edit_area_id = '';

	public static $fatal_notices = array();


	/*
	 *
	 * Request Type Functions
	 * functions used in conjuction with $_REQUEST['gpreq']
	 *
	 */

	static function Prep(){
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
	static function Flush(){
		global $page;
		header('Content-Type: text/html; charset=utf-8');
		echo GetMessages();
		echo $page->contentBuffer;
	}

	static function Content(){
		global $page;
		header('Content-Type: text/html; charset=utf-8');
		echo GetMessages();
		$page->GetGpxContent();
	}

	/**
	 * Send only the messages and content as a simple html document
	 * @static
	 */
	static function BodyAsHTML(){
		global $page;

		$page->head_script .= 'var gp_bodyashtml = true;';

		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html><html><head><meta charset="UTF-8" />';
		gpOutput::getHead();
		echo '</head>';
		echo '<body class="gpbody">';
		echo GetMessages();

		$page->GetGpxContent();

		echo '</body>';
		echo '</html>';

		gpOutput::HeadContent();
	}

	/**
	 * Send all content according to the current layout
	 * @static
	 */
	static function Template(){
		global $page, $get_all_gadgets_called, $addon_current_id;
		$get_all_gadgets_called = false;

		if( isset($page->theme_addon_id) ){
			$addon_current_id = $page->theme_addon_id;
		}
		gpOutput::TemplateSettings();
		header('Content-Type: text/html; charset=utf-8');

		$path = $page->theme_dir.'/template.php';
		IncludeScript($path,'require',array('page','GP_ARRANGE','GP_MENU_LINKS','GP_MENU_CLASS','GP_MENU_CLASSES','GP_MENU_ELEMENTS'));
		self::$template_included = true;

		gpPlugin::ClearDataFolder();

		gpOutput::HeadContent();
	}

	/**
	 * Get the settings for the current theme if settings.php exists
	 * @static
	 */
	static function TemplateSettings(){
		global $page;

		$path = $page->theme_dir.'/settings.php';
		IncludeScript($path,'require_if',array('page','GP_GETALLGADGETS'));
	}


	/**
	 * Add a Header to the response
	 * The header will be discarded if it's an ajax request or similar
	 * @static
	 */
	static function AddHeader($header, $replace = true, $code = false){
		if( !empty($_REQUEST['gpreq']) ){
			return false;
		}
		if( $code ){
			common::status_header($code,$header);
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


	static function GetContainerID($name,$arg=false){
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
	static function Fetch($default,$arg=''){
		ob_start();
		gpOutput::Get($default,$arg);
		return ob_get_clean();
	}


	static function Get($default='',$arg=''){
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

		gpOutput::ForEachOutput($outKeys,$container_id);

	}

	static function ForEachOutput($outKeys,$container_id){

		if( !is_array($outKeys) || (count($outKeys) == 0) ){

			$info = array();
			$info['gpOutCmd'] = '';
			gpOutput::CallOutput($info,$container_id);
			return;
		}

		foreach($outKeys as $gpOutCmd){

			$info = gpOutput::GetgpOutInfo($gpOutCmd);
			if( $info === false ){
				trigger_error('gpOutCmd <i>'.$gpOutCmd.'</i> not set');
				continue;
			}
			$info['gpOutCmd'] = $gpOutCmd;
			gpOutput::CallOutput($info,$container_id);
		}
	}

	/* static */
	static function GetgpOutInfo($gpOutCmd){
		global $gpOutConf,$config;

		//echo 'here';

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

	/* static */
	static function GpOutLabel($key){
		global $langmessage;

		$info = gpOutput::GetgpOutInfo($key);

		$label = $key;
		if( isset($info['link']) && isset($langmessage[$info['link']]) ){
			$label = $langmessage[$info['link']];
		}
		return str_replace(array(' ','_',':'),array('&nbsp;','&nbsp;',':&nbsp;'),$label);
	}


	static function CallOutput($info,$container_id){
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

		$param = $container_id.'|'.$info['gpOutCmd'];
		$class = 'gpArea_'.str_replace(array(':',','),array('_',''),trim($info['gpOutCmd'],':'));
		$permission = gpOutput::ShowEditLink('Admin_Theme_Content');

		ob_start();

		//for theme content arrangement
		if( $GP_ARRANGE && $permission && isset($GLOBALS['GP_ARRANGE_CONTENT'])  ){
			$empty_container = empty($info['gpOutCmd']); //empty containers can't be removed and don't have labels
			$class .= ' gp_output_area';

			echo '<div class="gp_inner_links nodisplay">';
			echo common::Link('Admin_Theme_Content/'.$page->gpLayout,$param,'cmd=drag_area&dragging='.urlencode($param).'&to=%s','data-cmd="creq" class="dragdroplink nodisplay"'); //drag-drop link
			if( !$empty_container ){
				echo '<div class="output_area_label">';
				echo ' '.gpOutput::GpOutLabel($info['gpOutCmd']);
				echo '</div>';
			}
			echo '<div class="output_area_link">';
			if( !$empty_container ){
				echo ' '.common::Link('Admin_Theme_Content/'.$page->gpLayout,$langmessage['remove'],'cmd=rm_area&param='.$param,'data-cmd="creq"');
			}
			echo ' '.common::Link('Admin_Theme_Content/'.$page->gpLayout,$langmessage['insert'],'cmd=insert&param='.$param,array('data-cmd'=>'gpabox'));
			echo '</div></div>';

		}

		//editable links only .. other editable_areas are handled by their output functions
		if( $permission ){
			$menu_marker = false;
			if( isset($info['link']) ){
				$label = $langmessage[$info['link']];

				$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=editlinks&layout='.urlencode($page->gpLayout).'&handle='.$param,' data-cmd="gpabox" title="'.$label.'" ');
				echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
				echo $edit_link;
				echo common::Link('Admin_Menu',$langmessage['file_manager'],'',' class="nodisplay"');
				echo '</span>';

				self::$edit_area_id = 'ExtraEditArea'.$edit_index;
				$menu_marker = true;

			}elseif( isset($info['key']) && ($info['key'] == 'CustomMenu') ){

				$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=editcustom&layout='.urlencode($page->gpLayout).'&handle='.$param,' data-cmd="gpabox" title="'.$langmessage['Links'].'" ');
				echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
				echo $edit_link;
				echo common::Link('Admin_Menu',$langmessage['file_manager'],'',' class="nodisplay"');
				echo '</span>';

				self::$edit_area_id = 'ExtraEditArea'.$edit_index;
				$menu_marker = true;
			}

			//for menu arrangement, admin_menu_new.js
			if( $menu_marker ){
				echo '<div class="menu_marker nodisplay" data-menuid="'.self::$edit_area_id.'">';
				echo '<input type="hidden" value="'.htmlspecialchars($info['gpOutCmd']).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars($GP_MENU_LINKS).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars(json_encode($GP_MENU_CLASSES)).'" />';
				echo '</div>';
			}
		}
		gpOutput::$editlinks .= ob_get_clean();


		echo '<div class="'.$class.' GPAREA">';
		gpOutput::ExecArea($info);
		echo '</div>';

		$GP_ARRANGE = true;
		$gp_current_container = false;
	}

	static function ExecArea($info){
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
		gpOutput::ExecInfo($info,$args);
	}

	/**
	 * Execute a set of directives for theme areas, hooks and special pages
	 *
	 */
	static function ExecInfo($info,$args=array()){
		global $dataDir, $addonFolderName, $installed_addon, $config, $GP_EXEC_STACK, $page;


		//addonDir is deprecated as of 2.0b3
		if( isset($info['addonDir']) ){
			if( gp_safe_mode ) return;
			gpPlugin::SetDataFolder($info['addonDir']);
		}elseif( isset($info['addon']) ){
			if( gp_safe_mode ) return;
			gpPlugin::SetDataFolder($info['addon']);
		}

		//if addon was just installed
		if( $installed_addon && $installed_addon === $addonFolderName){
			gpPlugin::ClearDataFolder();
			return $args;
		}

		// check for fatal errors
		$curr_hash = 'exec'.common::ArrayHash($info);
		if( self::FatalNotice($curr_hash) ){
			return $args;
		}

		$GP_EXEC_STACK[] = $curr_hash;

		//data
		if( !empty($info['data']) ){
			IncludeScript($dataDir.$info['data'],'include_if',array('page','dataDir','langmessage'));
		}

		//script
		$has_script = false;
		if( isset($info['script']) ){

			$full_path = $dataDir.$info['script'];
			if( !file_exists($full_path) ){
				$name =& $config['addons'][$addonFolderName]['name'];
				trigger_error('gpEasy Error: Addon hook script doesn\'t exist. Script: '.$info['script'].' Addon: '.$name);
			}elseif( IncludeScript($full_path,'include_once',array('page','dataDir','langmessage')) ){
				$has_script = true;
			}
		}


		//class & method
		if( !empty($info['class']) ){
			if( class_exists($info['class']) ){
				$object = new $info['class']($args);

				if( !empty($info['method']) ){
					if( method_exists($object, $info['method']) ){
						$args[0] = call_user_func_array(array($object, $info['method']), $args );
					}elseif( $has_script ){
						trigger_error('gpEasy Error: Addon hook method doesn\'t exist. Script: '.$info['method']);
					}
				}
			}elseif( $has_script ){
				$name =& $config['addons'][$addonFolderName]['name'];
				trigger_error('gpEasy Error: Addon hook class doesn\'t exist. Script: '.$info['class'].' Addon: '.$name);
			}
		}elseif( !empty($info['method']) ){

			$callback = $info['method'];

			//object callbacks since gpEasy 3.0
			if( is_string($callback) && strpos($callback,'->') !== false ){
				$has_script = true;
				list($object,$method) = explode('->',$callback);
				if( isset($GLOBALS[$object]) && is_object($GLOBALS[$object]) && method_exists($GLOBALS[$object],$method) ){
					$callback = array($GLOBALS[$object],$method);
				}
			}

			if( is_callable($callback) ){
				$args[0] = call_user_func_array($callback,$args);
				$method_called = true;

			}elseif( $has_script ){
				$name =& $config['addons'][$addonFolderName]['name'];
				trigger_error('gpEasy Error: Addon hook method doesn\'t exist. Script: '.$info['method'].' Addon: '.$name);
			}
		}

		gpPlugin::ClearDataFolder();
		array_pop($GP_EXEC_STACK);

		return $args;
	}


	/**
	 * Check for fatal errors corresponing to $hash
	 * Notify administrators of disabled components
	 *
	 */
	static function FatalNotice($hash){
		global $dataDir,$page;
		static $notified = array();


		//no file = no fatal error
		$file = $dataDir.'/data/_site/fatal_'.$hash;
		if( !file_exists($file) ){
			return false;
		}


		$error_info = $error_text = file_get_contents($file);
		$info_hash = md5($error_text);

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
			$error_text = pre($error_info);
		}


		//notify admin
		$message = 'Warning: A compenent of this page has been disabled because it caused fatal errors:';
		if( !count(self::$fatal_notices) ){
			error_log( $message );
		}
		if( common::LoggedIn() ){

			if( !in_array($info_hash,self::$fatal_notices) ){
				$message .= ' <br/> '.common::Link($page->title,'Enable Component','cmd=enable_component&hash='.$hash) //cannot be creq
							.' &nbsp; <a href="javascript:void(0)" onclick="var st = this.nextSibling.style; if( st.display==\'block\'){ st.display=\'none\' }else{st.display=\'block\'};return false;">Show Backtrace</a>'
							.'<div class="nodisplay">'
							.$error_text
							.'</div>';
				msg( $message );
				self::$fatal_notices[] = $info_hash;
			}
		}

		return true;
	}


	static function ShowEditLink($permission=false){
		global $GP_NESTED_EDIT;

		if( $permission ){
			return !$GP_NESTED_EDIT && common::LoggedIn() && admin_tools::HasPermission($permission);
		}
		return !$GP_NESTED_EDIT && common::LoggedIn();
	}

	static function EditAreaLink(&$index,$href,$label,$query='',$attr=''){
		static $count = 0;
		$count++;
		$index = $count; //since &$index is passed by reference
		if( is_array($attr) ){
			$attr += array('class'=>'ExtraEditLink nodisplay','id'=>'ExtraEditLink'.$index);
		}else{
			$attr .= ' class="ExtraEditLink nodisplay" id="ExtraEditLink'.$index.'"';
		}
		return common::Link($href,$label,$query,$attr);
	}


	/**
	 * Unless the gadget area is customized by the user, this function will output all active gadgets
	 * If the area has been reorganized, it will output the customized areas
	 * This function is not called from gpOutput::Get('GetAllGadgets') so that each individual gadget area can be used as a drag area
	 *
	 */
	static function GetAllGadgets(){
		global $config, $page, $gpLayouts, $get_all_gadgets_called;
		$get_all_gadgets_called = true;

		//if we have handler info
		if( isset($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets']) ){
			gpOutput::ForEachOutput($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets'],'GetAllGadgets');
			return;
		}

		//show all gadgets if no changes have been made
		if( !empty($config['gadgets']) ){
			$count = 0;
			foreach($config['gadgets'] as $gadget => $info){
				if( isset($info['addon']) ){
					$info['gpOutCmd'] = $info['key'] = $gadget;
					gpOutput::CallOutput($info,'GetAllGadgets');
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
		gpOutput::CallOutput($info,'GetAllGadgets');
	}


	/**
	 * Get a Single Gadget
	 * This method should be called using gpOutput::Fetch('Gadget',$gadget_name)
	 *
	 */
	static function GetGadget($id){
		global $config;

		if( !isset($config['gadgets'][$id]) ){
			return;
		}

		gpOutput::ExecArea($config['gadgets'][$id]);
	}

	/**
	 * Prepare the gadget content before getting template.php so that gadget functions can add css and js to the head
	 * @return null
	 */
	static function PrepGadgetContent(){
		global $page;

		$gadget_info = gpOutput::WhichGadgets($page->gpLayout);

		foreach($gadget_info as $gpOutCmd => $info){
			if( !isset(self::$gadget_cache[$gpOutCmd]) ){
				ob_start();
				gpOutput::ExecArea($info);
				self::$gadget_cache[$gpOutCmd] = ob_get_clean();
			}
		}
	}

	/**
	 * Return information about the gadgets being used in the current layout
	 * @return array
	 */
	static function WhichGadgets($layout){
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
					$temp_info[$gpOutCmd] = gpOutput::GetgpOutInfo($gpOutCmd);
				}
			}
		}

		//add all gadgets if $GetAllGadgets is true and the GetAllGadgets handler isn't overwritten
		if( $GetAllGadgets && !isset($layout_info['handlers']['GetAllGadgets']) ){
			foreach($config['gadgets'] as $gadget => $temp){
				if( isset($temp['addon']) ){
					$temp_info[$gadget] = gpOutput::GetgpOutInfo($gadget);
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


	/**
	 * @param string $arg comma seperated argument list: $top_level, $bottom_level, $options
	 *		$top_level  (int)  The upper level of the menu to show, if deeper (in this case > ) than 0, only the submenu is shown
	 *		$bottom_level  (int)  The lower level of menu to show
	 *		$expand_level (int)  The upper level from where to start expanding sublinks, if -1 no expansion
	 * 		$expand_all (int)	Whether or not to expand all levels below $expand_level (defaults to 0)
	 * 		$source_menu (string)	Which menu to use
	 *
	 */
	static function CustomMenu($arg,$title=false){
		global $page, $gp_index;

		//from output functions
		if( is_array($title) ){
			$title = $page->title;
		}

		$title_index = false;
		if( isset($gp_index[$title]) ){
			$title_index = $gp_index[$title];
		}

		$args = explode(',',$arg);
		$args += array( 0=>0, 1=>3, 2=>-1, 3=>1, 4=>'' ); //defaults
		list($top_level,$bottom_level,$expand_level,$expand_all,$source_menu) = $args;


		//get menu array
		$source_menu_array = gpOutput::GetMenuArray($source_menu);



		//reduce array to $title => $level
		$menu = array();
		foreach($source_menu_array as $temp_key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$menu[$temp_key] = $titleInfo['level'];
		}

		//Reduce for expansion
		//first reduction
		//message('expand level: '.$expand_level);
		if( (int)$expand_level >= 1 ){
			if( $expand_all ){
				$menu = gpOutput::MenuReduce_ExpandAll($menu,$expand_level,$title_index,$top_level);
			}else{
				$menu = gpOutput::MenuReduce_Expand($menu,$expand_level,$title_index,$top_level);
			}
		}


		//Reduce if $top_level >= 0
		//second reduction
		if( (int)$top_level > 0 ){
			//echo 'top level: '.$top_level;
			//message('top: '.$top_level);
			$menu = gpOutput::MenuReduce_Top($menu,$top_level,$title_index);
		}else{
			$top_level = 0;
		}

		//Reduce by trimming off titles below $bottom_level
		// last reduction : in case the selected link is below $bottom_level
		if( $bottom_level > 0 ){
			//message('bottom: '.$bottom_level);
			$menu = gpOutput::MenuReduce_Bottom($menu,$bottom_level);
		}

		gpOutput::OutputMenu($menu,$top_level,$source_menu_array);
	}

	/**
	 * Return the data for the requested menu, return the main menu if the requested menu doesn't exist
	 * @param string $id String identifying the requested menu
	 * @return array menu data
	 */
	static function GetMenuArray($id){
		global $dataDir, $gp_menu, $config;

		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( !file_exists($menu_file) ){
			return gpPlugin::Filter('GetMenuArray',array($gp_menu));
		}


		$menu = array();
		$fileVersion = false;
		require($menu_file);

		if( $fileVersion && version_compare($fileVersion,'3.0b1','<') ){
			$menu = gpOutput::FixMenu($menu);
		}

		return gpPlugin::Filter('GetMenuArray',array($menu));
	}

	/**
	 * Update menu entries to gpEasy 3.0 state
	 * .. htmlspecialchars label for external links
	 * @since 3.0b1
	 */
	static function FixMenu($menu){

		//fix external links, prior to 3.0, escaping was done when the menu was output
		foreach($menu as $key => $value){

			if( !isset($value['url']) ){
				continue;
			}

			//make sure it has a label
			if( empty($value['label']) ){
				$menu[$key]['label'] = $value['url'];
			}

			//make sure the title attr is escaped
			if( !empty($value['title_attr']) ){
				$menu[$key]['title_attr'] = htmlspecialchars($menu[$key]['title_attr']);
			}

			//make sure url and label are escape
			$menu[$key]['url'] = htmlspecialchars($menu[$key]['url']);
			$menu[$key]['label'] = htmlspecialchars($menu[$key]['label']);
		}
		return $menu;
	}


	static function MenuReduce_ExpandAll($menu,$expand_level,$curr_title_key,$top_level){

		$result_menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($menu as $title_key => $level){

			if( $level < $expand_level ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $title_key == $curr_title_key ){
				$foundGroup = true;
				$result_menu = $result_menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$result_menu[$title_key] = $level;
			}elseif( $level < $expand_level ){
				$result_menu[$title_key] = $level;
			}else{
				$submenu[$title_key] = $level;
			}
		}

		return $result_menu;
	}


	//Reduce titles deeper than $expand_level || $current_level
	static function MenuReduce_Expand($menu,$expand_level,$curr_title_key,$top_level){
		$result_menu = array();
		$submenu = array();


		//if $top_level is set, we need to take it into consideration
		$expand_level = max( $expand_level, $top_level);

		//titles higher than the $expand_level
		$good_titles = array();
		foreach($menu as $title_key => $level){
			if( $level < $expand_level ){
				$good_titles[$title_key] = $level;
			}
		}


		if( isset($menu[$curr_title_key]) ){
			$curr_level = $menu[$curr_title_key];
			$good_titles[$curr_title_key] = $menu[$curr_title_key];


			//titles below selected
			// cannot use $submenu because $foundTitle may require titles above the $submenu threshold
			$foundTitle = false;
			foreach($menu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$foundTitle = true;
					continue;
				}

				if( !$foundTitle ){
					continue;
				}

					if( ($curr_level+1) == $level ){
						$good_titles[$title_key] = $level;
					}elseif( $curr_level < $level ){
						continue;
					}else{
						break;
					}
			}



			//$start_time = microtime();
			//reduce the menu to the current group
			$submenu = gpOutput::MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level);
			//message('group: ('.count($submenu).') '.showArray($submenu));


			// titles even-with selected title within group
			$even_temp = array();
			$even_group = false;
			foreach($submenu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$even_group = true;
					$good_titles = $good_titles + $even_temp;
					continue;
				}

				if( $level < $curr_level ){
					if( $even_group ){
						$even_group = false; //done
					}else{
						$even_temp = array(); //reset
					}
				}

				if( $level == $curr_level ){
					if( $even_group ){
						$good_titles[$title_key] = $level;
					}else{
						$even_temp[$title_key] = $level;
					}
				}
			}


			// titles above selected title, deeper than $expand_level, and within the group
			gpOutput::MenuReduce_Sub($good_titles,$submenu,$curr_title_key,$expand_level,$curr_level);
			gpOutput::MenuReduce_Sub($good_titles,array_reverse($submenu),$curr_title_key,$expand_level,$curr_level);

			//message('time: '.microtime_diff($start_time,microtime()));

		}



		//rebuild $good_titles in order
		// array_intersect_assoc() would be useful here, it's php4.3+ and there's no indication if the order of the first argument is preserved
		foreach($menu as $title => $level){
			if( isset($good_titles[$title]) ){
				$result_menu[$title] = $level;
			}
		}

		return $result_menu;

	}

	// reduce the menu to the group
	static function MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level){
		$result = array();
		$group_temp = array();
		$found_title = false;

		foreach($menu as $title_key => $level){

			//back at the top
			if( $level < $expand_level ){
				$group_temp = array();
				$found_title = false;
			}


			if( $title_key == $curr_title_key ){
				$found_title = true;
				$result = $group_temp;
			}

			if( $level >= $expand_level ){
				if( $found_title ){
					$result[$title_key] = $level;
				}else{
					$group_temp[$title_key] = $level;
				}
			}
		}

		return $result;
	}

	// titles above selected title, deeper than $expand_level, and within the group
	static function MenuReduce_Sub(&$good_titles,$menu,$curr_title_key,$expand_level,$curr_level){
		$found_title = false;
		$test_level = $curr_level;
		foreach($menu as $title_key => $level){

			if( $title_key == $curr_title_key ){
				$found_title = true;
				$test_level = $curr_level;
				continue;
			}

			//after the title is found
			if( !$found_title ){
				continue;
			}
			if( $level < $expand_level ){
				break;
			}
			if( ($level >= $expand_level) && ($level < $test_level ) ){
				$test_level = $level+1; //prevent showing an adjacent menu trees
				$good_titles[$title_key] = $level;
			}
		}
	}

	//Reduce the menu to titles deeper than ($show_level-1)
	static function MenuReduce_Top($menu,$show_level,$curr_title_key){
		$result_menu = array();
		$foundGroup = false;

		//current title not in menu, so there won't be a submenu
		if( !isset($menu[$curr_title_key]) ){
			return $result_menu;
		}

		$top_level = $show_level-1;

		foreach($menu as $title_key => $level){

			//no longer in subgroup, we can stop now
			if( $foundGroup && ($level <= $top_level) ){
				//message('no long in subgroup: '.$title_key);
				break;
			}

			if( $title_key == $curr_title_key ){
				//message('found: '.$title_key);
				$foundGroup = true;
			}

			//we're back at the $top_level, start over
			if( $level <= $top_level ){
				$result_menu = array();
				//message('start over: '.$title_key);
				//message('start over: '.showArray($result_menu));
				continue;
			}

			//we're at the correct level, put titles in $result_menu in case $page->title is found
			if( $level > $top_level ){
				$result_menu[$title_key] = $level;
			}
		}

		if( !$foundGroup ){
			return array();
		}

		return $result_menu;
	}


	//Reduce the menu to titles above $bottom_level value
	static function MenuReduce_Bottom($menu,$bottom_level){
		$result_menu = array();

		foreach($menu as $title => $level){
			if( $level < $bottom_level ){
				$result_menu[$title] = $level;
			}
		}
		return $result_menu;
	}


	static function GetExtra($name='Side_Menu',$info=array()){
		global $dataDir,$langmessage;

		includeFile('tool/SectionContent.php');
		$name = str_replace(' ','_',$name);
		$extra_content = self::ExtraContent( $name, $file_stats );

		$wrap = gpOutput::ShowEditLink('Admin_Extra');
		if( $wrap ){

			ob_start();
			$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$name,array('title'=>$name,'data-cmd'=>'inline_edit_generic'));
			echo '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">';
			echo $edit_link;
			echo common::Link('Admin_Extra',$langmessage['theme_content'],'',' class="nodisplay"');
			echo '</span>';
			gpOutput::$editlinks .= ob_get_clean();

			echo '<div class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
			echo section_content::RenderSection($extra_content,0,'',$file_stats);
			echo '</div>';
		}else{
			echo section_content::RenderSection($extra_content,0,'',$file_stats);
		}

	}

	static function ExtraContent( $title, &$file_stats = array() ){
		global $dataDir;
		$file = $dataDir.'/data/_extra/'.$title.'.php';

		$extra_content = array();
		if( file_exists($file) ){
			ob_start();
			include($file);
			$extra_content_string = ob_get_clean();
			if( !count($extra_content) ){
				$extra_content['content'] = $extra_content_string;
			}
		}

		return $extra_content + array('type'=>'text','content'=>'');
	}

	static function GetImage($src,$attributes = array()){
		global $page,$dataDir,$langmessage,$gpLayouts;

		//$width,$height,$attributes = ''
		$attributes = (array)$attributes;
		$attributes += array('class'=>'');
		unset($attributes['id']);


		//default image information
		$img_rel = dirname($page->theme_rel).'/'.ltrim($src,'/');


		//container id
		$container_id = 'Image:'.$src;
		$container_id = gpOutput::GetContainerID($container_id);

		//select custom image
		$image = false;
		if( isset($gpLayouts[$page->gpLayout])
			&& isset($gpLayouts[$page->gpLayout]['images'])
			&& isset($gpLayouts[$page->gpLayout]['images'][$container_id])
			&& is_array($gpLayouts[$page->gpLayout]['images'][$container_id])
			){
				//echo showArray($gpLayouts[$page->gpLayout]['images'][$container_id]);
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
		$editable = gpOutput::ShowEditLink('Admin_Theme_Content');
		if( $editable ){
			$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content/'.$page->gpLayout,$langmessage['edit'],'file='.rawurlencode($img_rel).'&container='.$container_id.'&time='.time(),'title="Edit Image" data-cmd="inline_edit_generic"');
			gpOutput::$editlinks .= '<span class="nodisplay" id="ExtraEditLnks'.$edit_index.'">'.$edit_link.'</span>';
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
		echo '<img src="'.common::GetDir($img_rel,true).'"'.$str.'/>';
	}


	static function GetFullMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);
		gpOutput::OutputMenu($source_menu_array,0,$source_menu_array);
	}

	static function GetMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $info){
			if( (int)$info['level'] !== 0 ){
				continue;
			}
			$sendMenu[$key] = true;
		}

		gpOutput::OutputMenu($sendMenu,0,$source_menu_array);
	}

	static function GetSecondSubMenu($arg,$info){
		gpOutput::GetSubMenu($arg,$info,1);
	}
	static function GetThirdSubMenu($arg,$info){
		gpOutput::GetSubMenu($arg,$info,2);
	}

	static function GetSubMenu($arg='',$info=false,$search_level=false){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$reset_level = 0;
		if( !empty($search_level) ){
			$reset_level = max(0,$search_level-1);
		}


		$menu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$level = $titleInfo['level'];

			if( $foundGroup ){
				if( $level <= $reset_level ){
					break;
				}
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
			}

			if( $level <= $reset_level ){
				$menu = array();
				continue;
			}

			if( empty($search_level) ){
				$menu[$key] = $level;
			}elseif( $level == $search_level ){
				$menu[$key] = $level;
			}

		}

		if( !$foundGroup ){
			gpOutput::OutputMenu(array(),$reset_level+1,$source_menu_array);
		}else{
			gpOutput::OutputMenu($menu,$reset_level+1,$source_menu_array);
		}
	}

	static function GetTopTwoMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			if( $titleInfo['level'] >= 2 ){
				continue;
			}
			$sendMenu[$key] = true;
		}
		gpOutput::OutputMenu($sendMenu,0,$source_menu_array);
	}

	static function GetBottomTwoMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 1) || ($level == 2) ){
				$sendMenu[$key] = true;
			}
		}
		gpOutput::OutputMenu($sendMenu,1,$source_menu_array);
	}

	static function GetExpandLastMenu($arg=''){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 0) || ($level == 1) ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( ($level == 0) || ($level == 1) ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}
		}

		gpOutput::OutputMenu($menu,0,$source_menu_array);
	}


	static function GetExpandMenu($arg=''){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $info){
			$level = $info['level'];

			if( $level == 0 ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}

			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( $level == 0 ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}

		}
		gpOutput::OutputMenu($menu,0,$source_menu_array);

	}

	/**
	 * Output a navigation menu
	 * @static
	 */
	static function OutputMenu( $menu, $start_level, $source_menu=false ){
		global $page, $gp_menu, $gp_titles, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES;

		//source menu
		if( $source_menu === false ){
			$source_menu =& $gp_menu;
		}


		//menu classes
		if( !is_array($GP_MENU_CLASSES) ){
			$GP_MENU_CLASSES = array();
		}
		if( empty($GP_MENU_CLASS) ){
			$GP_MENU_CLASS = 'menu_top';
		}
		$GP_MENU_CLASSES += array(
							'menu_top'			=> $GP_MENU_CLASS,
							'selected'			=> 'selected',
							'selected_li'		=> 'selected_li',
							'childselected'		=> 'childselected',
							'childselected_li'	=> 'childselected_li',
							'li_'				=> 'li_',
							'li_title'			=> 'li_title',
							'haschildren'		=> 'haschildren',
							'haschildren_li'	=> '',
							'child_ul'			=> '',
							);

		if( empty($GP_MENU_LINKS) ){
			$GP_MENU_LINKS = '<a href="{$href_text}" title="{$title}"{$attr}>{$label}</a>';
		}

		$clean_attributes = array( 'attr'=>'', 'class'=>array(), 'id'=>'' );
		$clean_attributes_a = array('href' => '', 'attr' => '', 'value' => '', 'title' => '', 'class' =>array() );



		// opening ul
		$attributes_ul = $clean_attributes;
		$attributes_ul['class']['menu_top'] = $GP_MENU_CLASSES['menu_top'];
		if( self::$edit_area_id ){
			$attributes_ul['id'] = self::$edit_area_id;
			$attributes_ul['class']['editable_area'] = 'editable_area';
		}

		if( !count($menu) ){
			$attributes_ul['class']['empty_menu'] = 'empty_menu';
			$result[] = self::FormatMenuElement('div',$attributes_ul).'</div>'; //an empty <ul> is not valid xhtml
			return;
		}

		$result = array();
		$prev_level = $start_level;
		$page_title_full = common::GetUrl($page->title);
		$open = false;
		$li_count = array();

		//get parent page
		$parent_page = false;
		$parents = common::Parents($page->gp_index,$source_menu);
		if( count($parents) ){
			$parent_page = $parents[0];
		}


		//output
		$result[] = self::FormatMenuElement('ul',$attributes_ul);


		$menu = array_keys($menu);
		foreach($menu as $menu_index => $menu_key){
			$menu_info = $source_menu[$menu_key];
			$this_level = $menu_info['level'];

			//the next entry
			$next_info = false;
			$next_index = $menu_index+1;
			if( array_key_exists($next_index,$menu) ){
				$next_index = $menu[$next_index];
				$next_info = $source_menu[$next_index];
			}

			$attributes_a = $clean_attributes_a;
			$attributes_li = $attributes_ul = $clean_attributes;


			//ordered or "indexed" classes
			if( $page->menu_css_ordered ){
				for($i = $prev_level;$i > $this_level; $i--){
					unset($li_count[$i]);
				}
				if( !isset($li_count[$this_level]) ){
					$li_count[$this_level] = 0;
				}else{
					$li_count[$this_level]++;
				}
				if( !empty($GP_MENU_CLASSES['li_']) ){
					$attributes_li['class']['li_'] = $GP_MENU_CLASSES['li_'].$li_count[$this_level];
				}
			}

			if( $page->menu_css_indexed && !empty($GP_MENU_CLASSES['li_title_']) ){
				$attributes_li['class']['li_title_'] = $GP_MENU_CLASSES['li_title_'].$menu_key;
			}


			//selected classes
			if( $this_level < $next_info['level'] ){
				$attributes_a['class']['haschildren'] = $GP_MENU_CLASSES['haschildren'];
				$attributes_li['class']['haschildren_li'] = $GP_MENU_CLASSES['haschildren_li'];
			}

			if( isset($menu_info['url']) && ($menu_info['url'] == $page->title || $menu_info['url'] == $page_title_full) ){
				$attributes_a['class']['selected'] = $GP_MENU_CLASSES['selected'];
				$attributes_li['class']['selected_li'] = $GP_MENU_CLASSES['selected_li'];

			}elseif( $menu_key == $page->gp_index ){
				$attributes_a['class']['selected'] = $GP_MENU_CLASSES['selected'];
				$attributes_li['class']['selected_li'] = $GP_MENU_CLASSES['selected_li'];

			}elseif( in_array($menu_key,$parents) ){
				$attributes_a['class']['childselected'] = $GP_MENU_CLASSES['childselected'];
				$attributes_li['class']['childselected_li'] = $GP_MENU_CLASSES['childselected_li'];

			}


			//current is a child of the previous
			if( $this_level > $prev_level ){

				if( $menu_index === 0 ){ //only needed if the menu starts below the start_level
					$result[] = self::FormatMenuElement('li',$attributes_li);
				}

				if( !empty($GP_MENU_CLASSES['child_ul']) ){
					$attributes_ul['class'][] = $GP_MENU_CLASSES['child_ul'];
				}

				while( $this_level > $prev_level){
					$result[] = self::FormatMenuElement('ul',$attributes_ul);
					$result[] = '<li>';
					$prev_level++;
					$attributes_ul = $clean_attributes;
				}
				array_pop($result);//remove the last <li>

			//current is higher than the previous
			}elseif( $this_level < $prev_level ){
				while( $this_level < $prev_level){
					$result[] = '</li>';
					$result[] = '</ul>';

					$prev_level--;
				}

				if( $open ){
					$result[] = '</li>';
				}

			}elseif( $open ){
				$result[] = '</li>';
			}



			//external
			if( isset($menu_info['url']) ){
				if( empty($menu_info['title_attr']) ){
					$menu_info['title_attr'] = strip_tags($menu_info['label']);
				}

				$attributes_a['href'] = $menu_info['url'];
				$attributes_a['value'] = $menu_info['label'];
				$attributes_a['title'] = $menu_info['title_attr'];
				if( isset($menu_info['new_win']) ){
					$attributes_a['target'] = '_blank';
				}

			//internal link
			}else{

				$title = common::IndexToTitle($menu_key);
				$attributes_a['href'] = common::GetUrl($title);
				$attributes_a['value'] = common::GetLabel($title);
				$attributes_a['title'] = common::GetBrowserTitle($title);

				if( !empty($gp_titles[$menu_key]['rel']) ){
					$attributes_a['rel'] = $gp_titles[$menu_key]['rel'];
				}
			}

			$result[] = self::FormatMenuElement('li',$attributes_li);
			$result[] = self::FormatMenuElement('a',$attributes_a);



			$prev_level = $this_level;
			$open = true;
		}

		while( $start_level <= $prev_level){
			$result[] = '</li>';
			$result[] = '</ul>';
			$prev_level--;
		}

		echo implode('',$result); //don't separate by spaces so css inline can be more functional
	}

	static function FormatMenuElement( $node, $attributes){
		global $GP_MENU_LINKS, $GP_MENU_ELEMENTS;


		// build attr
		foreach($attributes as $key => $value){
			if( $key == 'title' || $key == 'href' || $key == 'value' ){
				continue;
			}
			if( is_array($value) ){
				$value = array_filter($value);
				$value = implode(' ',$value);
			}
			if( empty($value) ){
				continue;
			}
			$attributes['attr'] .= ' '.$key.'="'.$value.'"';
		}


		// call template defined function
		if( !empty($GP_MENU_ELEMENTS) && is_callable($GP_MENU_ELEMENTS) ){
			$return = call_user_func($GP_MENU_ELEMENTS, $node, $attributes);
			if( is_string($return) ){
				return $return;
			}
		}

		switch($node){

			case 'ul';
			case 'li':
			case 'div';
			return '<'.$node.$attributes['attr'].'>';


			//links
			case 'a';
			$search = array('{$href_text}','{$attr}','{$label}','{$title}');
			return str_replace( $search, $attributes, $GP_MENU_LINKS );
		}
	}


	/*
	 *
	 * Output Additional Areas
	 *
	 */

	/* draggable html and editable text */
	static function Area($name,$html){
		global $gpOutConf;
		if( self::$out_started ){
			trigger_error('gpOutput::Area() must be called before all other output functions');
			return;
		}
		$name = '[text]'.$name;
		$gpOutConf[$name] = array();
		$gpOutConf[$name]['method'] = array('gpOutput','GetAreaOut');
		$gpOutConf[$name]['html'] = $html;
	}

	static function GetArea($name,$text){
		$name = '[text]'.$name;
		gpOutput::Get($name,$text);
	}

	static function GetAreaOut($text,$info){
		global $config,$langmessage,$page;

		$html =& $info['html'];

		$wrap = gpOutput::ShowEditLink('Admin_Theme_Content');
		if( $wrap ){
			gpOutput::$editlinks .= gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=edittext&key='.urlencode($text).'&return='.urlencode($page->title),' title="'.htmlspecialchars($text).'" data-cmd="gpabox" ');
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
	static function GetAddonText($key,$html='%s'){
		global $addonFolderName;

		if( !$addonFolderName ){
			return gpOutput::ReturnText($key,$html);
		}

		$query = 'cmd=addontext&addon='.urlencode($addonFolderName).'&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}

	static function ReturnText($key,$html='%s'){
		$query = 'cmd=edittext&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}

	static function ReturnTextWorker($key,$html,$query){
		global $langmessage;

		$result = '';
		$wrap = gpOutput::ShowEditLink('Admin_Theme_Content');
		if( $wrap ){

			$title = htmlspecialchars(strip_tags($key));
			if( strlen($title) > 20 ){
				$title = substr($title,0,20).'...'; //javscript may shorten it as well
			}

			gpOutput::$editlinks .= gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],$query,' title="'.$title.'" data-cmd="gpabox" ');
			$result .= '<span class="editable_area" id="ExtraEditArea'.$edit_index.'">';
		}

		$text = gpOutput::SelectText($key);
		$result .= str_replace('%s',$text,$html); //in case there's more than one %s

		if( $wrap ){
			$result .= '</span>';
		}

		return $result;

	}



	/**
	 * Returns the user translated string if it exists or $key (the untranslated string) if a translation doesn't exist
	 *
	 */
	static function SelectText($key){
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
	static function GetHead(){
		gpPlugin::Action('GetHead');
		gpOutput::PrepGadgetContent();
		echo '<!-- get_head_placeholder '.gp_random.' -->';
	}

	static function HeadContent(){
		global $config, $page, $gp_head_content, $wbMessageBuffer;
		$gp_head_content = '';

		//before ob_start() so plugins can get buffer content
		gpPlugin::Action('HeadContent');

		ob_start();


		if( common::LoggedIn() ){
			common::AddColorBox();
		}


		//always include javascript when there are messages
		if( $page->admin_js || !empty($page->jQueryCode) || !empty($wbMessageBuffer) || isset($_COOKIE['cookie_cmd']) ){
			common::LoadComponents('gp-main');
		}
		//defaults
		common::LoadComponents('jquery,gp-additional');

		//get css and js info
		includeFile('combine.php');
		$scripts = gp_combine::ScriptInfo( gpOutput::$components );

		gpOutput::GetHead_TKD();
		gpOutput::GetHead_CSS($scripts['css']); //css before js so it's available to scripts
		gpOutput::GetHead_Lang();
		gpOutput::GetHead_JS($scripts['js']);
		gpOutput::GetHead_InlineJS();

		//gadget info
		if( !empty($config['addons']) ){
			foreach($config['addons'] as $addon_info){
				if( !empty($addon_info['html_head']) ){
					echo "\n";
					echo $addon_info['html_head'];
				}
			}
		}

		if( !empty($page->head) ){
			echo $page->head;
		}

		$gp_head_content = ob_get_clean();
	}

	/**
	 * Output the title, keywords, description and other meta for the current html document
	 * @static
	 */
	static function GetHead_TKD(){
		global $config, $page, $gpLayouts;

		//charset
		if( $page->gpLayout && isset($gpLayouts[$page->gpLayout]) && isset($gpLayouts[$page->gpLayout]['doctype']) ){
			echo $gpLayouts[$page->gpLayout]['doctype'];
		}


		//start keywords;
		$keywords = array();
		if( count($page->meta_keywords) ){
			$keywords = $page->meta_keywords;
		}elseif( !empty($page->TitleInfo['keywords']) ){
			$keywords = explode(',',$page->TitleInfo['keywords']);
		}

		//title
		echo "\n<title>";
		$page_title = '';
		if( !empty($page->TitleInfo['browser_title']) ){
			$page_title = $page->TitleInfo['browser_title'];
			$keywords[] = $page->TitleInfo['browser_title'];
		}elseif( !empty($page->label) ){
			$page_title = strip_tags($page->label);
		}elseif( isset($page->title) ){
			$page_title = common::GetBrowserTitle($page->title);
		}
		echo $page_title;
		if( !empty($page_title) && !empty($config['title']) ){
			echo ' - ';
		}
		echo $config['title'].'</title>';

		if( !empty($page->TitleInfo['rel']) ){
			echo "\n".'<meta name="robots" content="'.$page->TitleInfo['rel'].'" />';
		}


		//keywords
		$keywords[] = strip_tags($page->label);
		$site_keywords = explode(',',$config['keywords']);
		$keywords = array_merge($keywords,$site_keywords);
		$keywords = array_unique($keywords);
		$keywords = array_diff($keywords,array(''));
		echo "\n<meta name=\"keywords\" content=\"".implode(', ',$keywords)."\" />";


		//description
		$description = '';
		if( !empty($page->meta_description) ){
			$description .= $page->meta_description;
		}elseif( !empty($page->TitleInfo['description']) ){
			$description .= $page->TitleInfo['description'];
		}else{
			$description .= $page_title;
		}
		$description = gpOutput::EndPhrase($description);

		if( !empty($config['desc']) ){
			$description .= htmlspecialchars($config['desc']);
		}
		$description = trim($description);
		if( !empty($description) ){
			echo "\n<meta name=\"description\" content=\"".$description."\" />";
		}
		echo "\n<meta name=\"generator\" content=\"gpEasy CMS\" />";

	}


	/**
	 * Prepare and output any inline Javascript for the current page
	 * @static
	 */
	static function GetHead_InlineJS(){
		global $page, $linkPrefix, $GP_INLINE_VARS;

		ob_start();

		if( gpdebugjs ){
			$GP_INLINE_VARS['debugjs'] = gpdebugjs;
		}

		if( common::LoggedIn() ){
			$GP_INLINE_VARS += array(
				'isadmin' => true,
				'gpBLink' => common::HrefEncode($linkPrefix,false),
				'post_nonce' => common::new_nonce('post',true),
				'gpRem' => admin_tools::CanRemoteInstall(),
				'admin_resizable' => true,
				);
			gpsession::GPUIVars();
		}

		if( count($GP_INLINE_VARS) > 0 ){
			echo 'var ';
			$comma = '';
			foreach($GP_INLINE_VARS as $key => $value){
				echo $comma.$key.'='.json_encode($value);
				$comma = ',';
			}
			echo ';';
		}

		echo $page->head_script;

		if( !empty($page->jQueryCode) ){
			echo '$(function(){';
			echo $page->jQueryCode;
			echo '});';
		}

		$inline = ob_get_clean();


		if( !empty($inline) ){
			echo "\n<script type=\"text/javascript\">\n";
			echo $inline;
			echo "\n</script>";
		}
	}


	/**
	 * Add language values to the current page
	 * @static
	 */
	static function GetHead_Lang(){
		global $langmessage, $GP_LANG_VALUES;

		if( !count($GP_LANG_VALUES) ){
			return;
		}

		echo "\n<script type=\"text/javascript\">";
		echo 'var gplang = {';
		$comma = '';
		foreach($GP_LANG_VALUES as $from_key => $to_key){
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
	static function GetHead_JS($scripts){
		global $page, $config;

		$placeholder = '<!-- jquery_placeholder '.gp_random.' -->';

		$keys_before = array_keys($scripts);
		$combine = $config['combinejs'] && !common::loggedIn() && ($page->pagetype !== 'admin_display');

		//just jQuery
		if( !count($page->head_js) && count($scripts) < 2 ){
			echo $placeholder;
			return;
		}

		//remote jquery
		if( $config['jquery'] != 'local' ){
			echo $placeholder;
			unset($scripts['jquery']);
		}

		//jquery ui
		if( $config['jquery'] == 'jquery_ui' ){
			$has_jquery_ui = false;
			foreach($scripts as $key => $script){
				if( isset($script['package']) && $script['package'] == 'jquery_ui' ){
					$has_jquery_ui = true;
					unset($scripts[$key]);
				}
			}
			if( $has_jquery_ui ){
				echo "\n<script type=\"text/javascript\" src=\"//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js\"></script>";
			}
		}

		if( !$combine || $page->head_force_inline ){
			echo "\n<script type=\"text/javascript\">";
			common::jsStart();
			echo '</script>';
		}

		if( is_array($page->head_js) ){
			$scripts += $page->head_js; //other js files
		}else{
			trigger_error('$page->head_js is not an array');
		}

		gpOutput::CombineFiles($scripts,'js',$combine );
	}


	/**
	 * Prepare and output the css for the current page
	 * @static
	 */
	static function GetHead_CSS($scripts){
		global $page, $config;

		//remote jquery ui
		if( $config['jquery'] == 'jquery_ui' && isset($scripts['ui-theme']) ){
			echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css\" />";
			unset($scripts['ui-theme']);
		}

		if( isset($page->css_user) && is_array($page->css_user) ){
			$scripts = array_merge($scripts,$page->css_user);
		}

		//after other styles, so themes can overwrite defaults
		if( !empty($page->theme_name) && $page->get_theme_css === true ){
			$scripts[] = rawurldecode($page->theme_path).'/style.css';
		}

		//layout css
		if( isset($page->layout_css) && $page->layout_css ){
			$scripts[] = '/data/_layouts/'.$page->gpLayout.'/custom.css';
		}

		//styles that need to override admin.css should be added to $page->css_admin;
		if( isset($page->css_admin) && is_array($page->css_admin) ){
			$scripts = array_merge($scripts,$page->css_admin);
		}

		gpOutput::CombineFiles($scripts,'css',$config['combinecss']);
	}


	/**
	 * Combine the files in $files into a combine.php request
	 * If $page->head_force_inline is true, resources will be included inline in the document
	 *
	 * @param array $files Array of files relative to $dataDir
	 * @param string $type The type of resource being combined
	 *
	 */
	static function CombineFiles($files,$type,$combine){
		global $page;

		//only need file paths
		foreach($files as $key => $script){
			if( is_array($script) ){
				$files[$key] = $script['file'];
			}
		}
		$files = array_unique($files);
		$files = array_filter($files);//remove empty elements

		// Force resources to be included inline
		// CheckFile will fix the $file path if needed
		if( $page->head_force_inline ){
			if( $type == 'css' ){
				echo '<style type="text/css">';
			}else{
				echo '<script type="text/javascript">';
			}
			foreach($files as $file_key => $file){
				$full_path = gp_combine::CheckFile($file);
				if( !$full_path ) continue;
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

		$html = "\n".'<script type="text/javascript" src="%s"></script>';
		if( $type == 'css' ){
			$html = "\n".'<link rel="stylesheet" type="text/css" href="%s"/>';
		}

		//files not combined except for script components
		if( !$combine || (isset($_REQUEST['no_combine']) && common::LoggedIn()) ){
			foreach($files as $file_key => $file){
				gp_combine::CheckFile($file);
				echo sprintf($html,common::GetDir($file,true));
			}
			return;
		}

		//create combine request
		$combined_file = gp_combine::GenerateFile($files,$type);
		echo sprintf($html,common::GetDir($combined_file,true));
	}


	/**
	 * Complete the response by adding final content to the <head> of the document
	 * @static
	 * @since 2.4.1
	 * @param string $buffer html content
	 * @return string finalized response
	 */
	static function BufferOut($buffer){
		global $config,	$gp_head_content, $addonFolderName, $dataDir, $GP_EXEC_STACK, $addon_current_id, $wbErrorBuffer;


		//add error notice if there was a fatal error
		if( !ini_get('display_errors') && function_exists('error_get_last') ){

			//check for fatal error
			$fatal_errors = array(E_ERROR,E_PARSE);
			$last_error = error_get_last();
			if( is_array($last_error) && in_array($last_error['type'],$fatal_errors) ){

				$last_error['request'] = $_SERVER['REQUEST_URI'];
				if( $addon_current_id ){
					$last_error['addon_name'] = $config['addons'][$addonFolderName]['name'];
					$last_error['addon_id'] = $addon_current_id;
				}

				$last_error['file'] = realpath($last_error['file']);//may be redundant
				showError($last_error['type'], $last_error['message'],  $last_error['file'],  $last_error['line'], false); //send error to logger
				$reload = false;

				//disable execution
				if( count($GP_EXEC_STACK) ){

					$last_error['time'] = time();
					$last_error['request_method'] = $_SERVER['REQUEST_METHOD'];
					if( !empty($last_error['file']) ){
						$last_error['file_modified'] = filemtime($last_error['file']);
						$last_error['file_size'] = filesize($last_error['file']);
					}
					$content = json_encode($last_error);

					$error_hash = array_pop($GP_EXEC_STACK);
					foreach($GP_EXEC_STACK as $error_hash){
						$file = $dataDir.'/data/_site/fatal_'.$error_hash;
						gpFiles::Save($file,$content);
						$reload = true;
					}
				}


				//reload non-logged in users automatically, display message to admins
				$buffer .= '<p>Oops, an error occurred while generating this page.<p>';
				if( !common::LoggedIn() ){
					if( $reload ){
						$buffer .= 'Reloading... <script type="text/javascript">window.setTimeout(function(){window.location.href = window.location.href},1000);</script>';
					}else{
						$buffer .= '<p>If you are the site administrator, you can troubleshoot the problem by changing php\'s display_errors setting to 1 in the gpconfig.php file.</p>'
								.'<p>If the problem is being caused by an addon, you may also be able to bypass the error by enabling gpEasy\'s safe mode in the gpconfig.php file.</p>'
								.'<p>More information is available in the <a href="http://docs.gpeasy.com/Main/Troubleshooting">gpEasy documentation</a>.</p>'
								.'<p><a href="">Reload this page to continue</a>.</p>'
								;
					}
				}else{
					$buffer .= '<h3>Error Details</h3>'
							.showArray($last_error)
							.'<p><a href="">Reload this page</a></p>';
					if( $reload ){
						$buffer .= '<p><a href="">Reload this page with the faulty component disabled</a></p>'
								. '<p><a href="?cmd=enable_component&hash='.$error_hash.'">Reload this page with the faulty component enabled</a></p>';
					}
					$buffer .= '<p style="font-size:90%">Note: Error details are only displayed for logged in administrators</p>'
							.common::ErrorBuffer(true,false);

				}

			}
		}


		//remove lock
		if( defined('gp_has_lock') && gp_has_lock ){
			gpFiles::Unlock('write',gp_random);
		}


		//replace the <head> placeholder with header content
		$placeholder = '<!-- get_head_placeholder '.gp_random.' -->';
		$pos = strpos($buffer,$placeholder);
		if( $pos === false ){
			return $buffer;
		}

		$buffer = substr_replace($buffer,$gp_head_content,$pos,strlen($placeholder));


		//add jquery if needed
		$placeholder = '<!-- jquery_placeholder '.gp_random.' -->';
		$pos = strpos($buffer,$placeholder);
		if( $pos !== false ){
			$replacement = '';
			if( strpos($buffer,'<script') !== false ){
				if( $config['jquery'] != 'local' ){
					$replacement = "\n<script type=\"text/javascript\" src=\"//ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js\"></script>";
				}else{
					$replacement = "\n<script type=\"text/javascript\" src=\"".common::GetDir('/include/thirdparty/js/jquery.js')."\"></script>";
				}
			}
			$buffer = substr_replace($buffer,$replacement,$pos,strlen($placeholder));
		}

		//messages
		$pos = strpos($buffer,'<!-- message_start '.gp_random.' -->');
		$len = strpos($buffer,'<!-- message_end -->') - $pos;
		if( $pos && $len ){
			$replacement = GetMessages(false);
			$buffer = substr_replace($buffer,$replacement,$pos,$len+20);
		}


		if( gpdebug_tools && function_exists('memory_get_peak_usage') && ($pos = strpos($buffer,'<body')) ){
			$pos = strpos($buffer,'>',$pos);
			$max_used = memory_get_peak_usage();
			//$limit = @ini_get('memory_limit'); //need to convert to byte value
			//$percentage = round($max_used/$limit,2);
			$replacement = "\n".'<div style="position:absolute;top:-1px;right:0;z-index:10000;padding:5px 10px;background:rgba(255,255,255,0.95);border:1px solid rgba(0,0,0,0.2);font-size:12px">'
					.'<b>Debug Tools</b>'
					.'<table>'
					//.'<tr><td>Memory Usage:</td><td> '.number_format(memory_get_usage()).'</td></tr>'
					.'<tr><td>Memory:</td><td> '.number_format($max_used).'</td></tr>'
					//.'<tr><td>% of Limit:</td><td> '.$percentage.'%</td></tr>'
					.'<tr><td>Time:</td><td> '.microtime_diff($_SERVER['REQUEST_TIME'],microtime()).'</td></tr>'
					.'</table>'
					.'</div>';
			$buffer = substr_replace($buffer,$replacement,$pos+1,0);
		}

		return $buffer;
	}

	/**
	 * Return true if the user agent is a search engine bot
	 * Detection is rudimentary and shouldn't be relied on
	 * @return bool
	 */
	static function DetectBot(){
		$user_agent =& $_SERVER['HTTP_USER_AGENT'];
		return preg_match('#bot|yahoo\! slurp|ask jeeves|ia_archiver|spider#i',$user_agent);
	}

	/**
	 * Return true if the current page is the home page
	 */
	static function is_front_page(){
		global $gp_menu, $page;
		reset($gp_menu);
		return $page->gp_index == key($gp_menu);
	}


	/**
	 * Outputs the sitemap link, admin login/logout link, powered by link, admin html and messages
	 * @static
	 */
	static function GetAdminLink(){
		global $config, $langmessage, $page;

		if( !isset($config['showsitemap']) || $config['showsitemap'] ){
			echo ' <span class="sitemap_link">';
			echo common::Link('Special_Site_Map',$langmessage['site_map']);
			echo '</span>';
		}

		if( !isset($config['showlogin']) || $config['showlogin'] ){
			echo ' <span class="login_link">';
				if( common::LoggedIn() ){
					echo common::Link($page->title,$langmessage['logout'],'cmd=logout','data-cmd="creq" rel="nofollow" ');
				}else{
					echo common::Link('Admin',$langmessage['login'],'file='.rawurlencode($page->title),' rel="nofollow" data-cmd="login"');
				}
			echo '</span>';
		}


		if( !isset($config['showgplink']) || $config['showgplink'] ){
			echo ' <span id="powered_by_link">';
			echo 'Powered by <a href="http://gpEasy.com" title="A Free and Easy CMS in PHP" target="_blank">gp|Easy CMS</a>';
			echo '</span>';
		}

		echo GetMessages();
	}


	/**
	 * Add punctuation to the end of a string if it isn't already punctuated. Looks for !?.,;: characters
	 * @static
	 * @since 2.4RC1
	 */
	static function EndPhrase($string){
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



	static function RunOut(){
		global $page;

		$page->RunScript();

		//decide how to send the content
		self::Prep();
		switch(common::RequestType()){

			// <a data-cmd="admin_box">
			case 'flush':
				self::Flush();
			break;

			// remote request
			// file browser
			case 'body':
				common::CheckTheme();
				self::BodyAsHTML();
			break;

			// <a data-cmd="gpajax">
			// <a data-cmd="gpabox">
			// <input data-cmd="gpabox">
			case 'json':
				common::CheckTheme();
				includeFile('tool/ajax.php');
				gpAjax::Response();
			break;

			case 'content':
				self::Content();
			break;

			default:
				common::CheckTheme();
				self::Template();
			break;
		}



		//if logged in, prepare the admin content and don't send 304 response
		if( common::LoggedIn() ){
			admin_tools::AdminHtml();

			//empty edit links if there isn't a layout
			if( !$page->gpLayout ){
				self::$editlinks = '';
			}

			return;
		}

		/* attempt to send 304 response  */
		if( $page->fileModTime > 0 ){
			global $wbMessageBuffer, $gp_head_content;
			$len = strlen($gp_head_content) + ob_get_length();
			if( count($wbMessageBuffer) ){
				$len += strlen( serialize($wbMessageBuffer) );
			}
			common::Send304( common::GenEtag( $page->fileModTime, $len ) );
		}
	}

	/**
	 * Add one or more components to the page. Output the <script> and/or <style> immediately
	 * @param string $names comma separated list of components
	 *
	 */
	function GetComponents($names = ''){
		includeFile('combine.php');
		$scripts = gp_combine::ScriptInfo( $names );
		gpOutput::CombineFiles($scripts['css'], 'css', false );
		gpOutput::CombineFiles($scripts['js'], 'js', false );
	}


}
