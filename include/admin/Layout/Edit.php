<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Edit extends \gp\admin\Layout{

	protected $layout_request = true;
	protected $layout_slug;


	public function RunScript(){
		global $gpLayouts, $config;

		//layout request
		$parts		= explode('/',$this->page->requested);
		if( !empty($parts[2]) ){

			if( $this->SetCurrLayout($parts[2]) ){
				$this->EditLayout();
				return;
			}

		//default layout
		}elseif( $this->SetCurrLayout($config['gpLayout']) ){
			$this->EditLayout();
			return;
		}

		//redirect
		$url = \gp\tool::GetUrl('Admin_Theme_Content','',false);
		\gp\tool::Redirect($url,302);
	}

	/**
	 * Set the current layout
	 *
	 */
	protected function SetCurrLayout($layout){
		global $langmessage, $gpLayouts;

		if( !isset($gpLayouts[$layout]) ){
			return false;
		}

		$this->curr_layout = $layout;
		$this->SetLayoutArray();
		$this->page->SetTheme($layout);

		if( !$this->page->gpLayout ){
			message($langmessage['OOPS'].' (Theme Not Found)');
			parent::RunScript();
			return false;
		}

		\gp\tool\Output::TemplateSettings();

		return true;
	}


	/**
	 * Edit layout properties
	 * 		Layout Identification
	 * 		Content Arrangement
	 * 		Gadget Visibility
	 *
	 */
	public function EditLayout(){

		$GLOBALS['GP_ARRANGE_CONTENT']	= true;
		$this->layout_slug				= 'Admin_Theme_Content/Edit/'.rawurlencode($this->curr_layout);


		$this->cmds['ShowThemeImages']	= '';
		$this->cmds['SelectContent']	= '';

		$this->cmds['LayoutMenu']		= '';
		$this->cmds['LayoutMenuSave']	= 'ReturnHeader';


		//show the layout (displayed within an iframe)
		$this->cmds['SaveCSS']			= 'ShowInIframe';
		$this->cmds['PreviewCSS']		= 'ShowInIframe';
		$this->cmds['addcontent']		= 'ShowInIframe';
		$this->cmds['RemoveArea']		= 'ShowInIframe';
		$this->cmds['DragArea']			= 'ShowInIframe';
		$this->cmds['in_iframe']		= 'ShowInIframe';


		\gp\tool\Plugins::Action('edit_layout_cmd',array($this->curr_layout));

		$cmd = \gp\tool::GetCommand();

		$this->LayoutCommands();
		$this->RunCommands($cmd);
	}


	public function DefaultDisplay(){
		global $langmessage;

		$layout_info		= \gp\tool::LayoutInfo($this->curr_layout,false);
		$this->page->label	= $langmessage['layouts'] . ' » '.$layout_info['label'];

		$this->LayoutEditor($this->curr_layout, $layout_info );
	}


	/**
	 * Prepare the page for css editing
	 *
	 */
	public function ShowInIframe(){

		$this->LoremIpsum();

		$cmd = \gp\tool::GetCommand();

		$this->page->show_admin_content		= false;
		\gp\admin\Tools::$show_toolbar		= false;

		// <head>
		$this->page->head .= '<script type="text/javascript">parent.$gp.iframeloaded();</script>';
		if( $cmd != 'PreviewCSS' ){
			$this->page->head .= '<script type="text/javascript">var gpLayouts=true;</script>';
		}
	}


	/**
	 * Display the toolbar for layout editing
	 *
	 */
	public function LayoutEditor($layout, $layout_info ){
		global $langmessage, $gpAdmin;


		$_REQUEST					+= array('gpreq' => 'body'); //force showing only the body as a complete html document
		$this->page->get_theme_css		= false;

		$this->page->css_user[]		= '/include/thirdparty/codemirror/lib/codemirror.css';
		$this->page->head_js[]		= '/include/thirdparty/codemirror/lib/codemirror.js';
		$this->page->head_js[]		= '/include/thirdparty/codemirror/mode/css/css.js';

		$this->page->css_admin[]	= '/include/css/theme_content_outer.scss';
		$this->page->head_js[]		= '/include/js/theme_content_outer.js';


		//custom css
		$css			= $this->layoutCSS($this->curr_layout);
		$dir			= $layout_info['dir'].'/'.$layout_info['theme_color'];
		$style_type	= \gp\tool\Output::StyleType($dir);

		$style_type_info = array();
		switch ($style_type) {
			case 'scss':
				$style_type_info['name'] = 'Scss';
				$style_type_info['link'] = 'http://sass-lang.com/';
				break;
			case 'less':
				$style_type_info['name'] = 'Less';
				$style_type_info['link'] = 'http://lesscss.org/';
				break;
			default:
				$style_type_info['name'] = 'CSS';
				$style_type_info['link'] = 'https://developer.mozilla.org/docs/Web/CSS';
		}



		//Iframe
		echo '<div id="gp_iframe_wrap">';
		$url = \gp\tool::GetUrl('Admin_Theme_Content/Edit/'.rawurlencode($layout),'cmd=in_iframe');
		echo '<iframe src="'.$url.'" id="gp_layout_iframe" name="gp_layout_iframe" scrolling="no"></iframe>';
		echo '</div>';


		//CSS Editing
		ob_start();
		echo '<div id="theme_editor">';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content/Edit/'.$this->curr_layout,'cmd=in_iframe').'" method="post" class="gp_scroll_area full_height" target="gp_layout_iframe">';
		echo '<table border="0">';
		echo '<tr><td>';



		echo '<div>';
		echo '<div class="layout_select">';
		$this->LayoutSelect($layout,$layout_info);
		echo '</div>';


		//options
		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$langmessage['Layout Options'].'</a>';
		echo '<div class="dd_list">';
		echo '<ul>';
		$this->LayoutOptions($layout,$layout_info);
		echo '</ul>';
		echo '</div>';
		echo '</div></div>';


		//css textarea
		echo '</div>';
		echo '<div class="separator"></div>';

		//sytax links
		echo '<div style="text-align:right">';
		echo 'Syntax: ';
		echo '<a href="' . $style_type_info['link'] . '" target="_blank">' . $style_type_info['name'] . '</a>';
		echo '</div>';



		echo '</td></tr><tr><td class="full_height">';

		echo '<div class="full_height">';

		if( empty($css) ){
			$var_file 			= $dir.'/variables.'.$style_type;
			if( file_exists($var_file) ){
				$css = file_get_contents($var_file);
			}
		}

		//editor mode
		echo '<textarea name="css" id="gp_layout_css" class="gptextarea" placeholder="'.htmlspecialchars($langmessage['Add your LESS and CSS here']).'" wrap="off" data-mode="'.htmlspecialchars($style_type).'">';
		echo htmlspecialchars($css);
		echo '</textarea>';

		echo '</div></td></tr><tr><td>';

		echo '<div class="css_buttons">';

		// preview
		echo '<button name="cmd" type="submit" value="PreviewCSS" class="gpsubmit gpdisabled" disabled="disabled" data-cmd="preview_css" />'.$langmessage['preview'].'</button>';

		// save
		echo '<button name="cmd" type="submit" value="SaveCSS" class="gpsubmit gpdisabled" disabled="disabled" data-cmd="save_css" />'.$langmessage['save'].'</button>'; 

		// reset
		echo '<input type="reset" class="gpcancel gpdisabled" disabled="disabled" data-cmd="reset_css" />';

		//cancel
		$cancel_url = !empty($_REQUEST['redir']) ? $_REQUEST['redir'] : 'Admin_Theme_Content';
		echo \gp\tool::Link($cancel_url, $langmessage['Close'], '', 'class="gpcancel"');

		echo '</div>'; // /.css_buttons

		echo '</td></tr></table>';
		echo '</form>';


		//show site in iframe

		echo '</div>'; //#theme_editor

		$this->page->admin_html = ob_get_clean();
	}


	/**
	 * Display all the layouts available in a <select>
	 *
	 */
	public function LayoutSelect($curr_layout=false,$curr_info=false){
		global $gpLayouts, $langmessage, $config;

		$display = $langmessage['available_layouts'];
		if( $curr_layout ){
			$display = '<span class="layout_color_id" style="background-color:'.$curr_info['color'].';"></span> &nbsp; '
					. $curr_info['label'];
		}

		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$display.'</a>';

		echo '<div class="dd_list"><ul>';
		foreach($gpLayouts as $layout => $info){
			$attr = '';
			if( $layout == $curr_layout){
				$attr = ' class="selected"';
			}
			echo '<li'.$attr.'>';

			$display = '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span> &nbsp; '. $info['label'];
			if( $config['gpLayout'] == $layout ){
				$display .= ' <span class="layout_default"> ('.$langmessage['default'].')</span>';
			}
			echo \gp\tool::Link('Admin_Theme_Content/Edit/'.rawurlencode($layout),$display);
			echo '</li>';
		}
		echo '</ul></div>';
		echo '</div></div>';
	}



	/**
	 * Save edits to the layout css
	 *
	 */
	public function SaveCSS(){
		global $langmessage, $dataDir, $gpLayouts;

		$css				=& $_POST['css'];

		if( !$this->SaveCustom($this->curr_layout, $css) ){
			return false;
		}


		$gpLayouts[$this->curr_layout]['css'] = true;
		if( !$this->SaveLayouts() ){
			return false;
		}
		$this->page->SetTheme($this->curr_layout);
	}


	/**
	 * Preview changes to the custom css/less
	 *
	 */
	public function PreviewCSS(){
		global $langmessage;

		$layout_info				= \gp\tool::LayoutInfo($this->curr_layout,false);

		$this->page->theme_color	= $layout_info['theme_color'];
		$this->page->theme_rel		= dirname($this->page->theme_rel).'/'.$this->page->theme_color;
		$this->page->theme_path		= dirname($this->page->theme_path).'/'.$this->page->theme_color;
		$dir						= $this->page->theme_dir.'/'.$this->page->theme_color;
		$style_type					= \gp\tool\Output::StyleType($dir);
		$style_files				= array();

		if( $style_type == 'scss' ){
			$this->PreviewScss($dir);
			return;
		}

		// which css files
		if( $style_type == 'css' ){
			$this->page->css_user[]	= rawurldecode($this->page->theme_path).'/style.css';
		}else{
			$style_files[]		= $dir.'/style.less';
		}


		// variables.less
		$var_file = $dir . '/variables.less';
		if( file_exists($var_file) ){
			$style_files[] = $var_file;
		}


		$temp = trim($_REQUEST['css']);
		if( !empty($temp) ){
			$style_files[] = $_REQUEST['css']. "\n"; //make sure this is seen as code and not a filename
		}


		if( count($style_files) ){

			$compiled		= \gp\tool\Output\Css::ParseLess( $style_files );

			if( $compiled === false ){
				message($langmessage['OOPS'].' (Invalid LESS)');
				return false;
			}

			$this->page->head .= '<style>'.$compiled.'</style>';
		}

		$this->page->get_theme_css	= false;
	}


	/**
	 * Order of files for SCSS
	 *  Variables.scss
	 *  custom.scss
	 *  Bootstrap.scss
	 */
	protected function PreviewScss($dir){
		global $langmessage;

		$style_files			= array();

		// variables.scss
		$var_file = $dir . '/variables.scss';
		if( file_exists($var_file) ){
			$style_files[] = $var_file;
		}

		//custom
		$temp = trim($_REQUEST['css']);
		if( !empty($temp) ){
			$style_files[] = $_REQUEST['css']. "\n"; //make sure this is seen as code and not a filename
		}

		$style_files[]		= $dir.'/style.scss';

		$compiled			= \gp\tool\Output\Css::ParseScss($style_files);

		if( $compiled === false ){
			message($langmessage['OOPS'].' (Invalid SCSS)');
			return false;
		}

		$this->page->head .= '<style>'.$compiled.'</style>';
		$this->page->get_theme_css	= false;
	}


	public function DragArea(){
		global $langmessage;

		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutCmd) ){
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutCmd) ){
			return;
		}


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$from_container,$from_gpOutCmd);
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}


		$where	= $this->ContainerWhere($from_gpOutCmd, $handlers[$from_container]);
		$to		= $this->ContainerWhere($to_gpOutCmd, $handlers[$from_container],false);

		if( $where === false ){
			return;
		}

		array_splice($handlers[$from_container],$where,1);


		/**
		 * for moving down
		 * if target is the same container
		 * and target is below dragged element
		 * then $offset = 1
		 *
		 */
		$offset = 0;
		if( ($from_container == $to_container)
			&& ($to !== false)
			&& $to > $where ){
				$offset = 1;
		}

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$from_gpOutCmd,false,$offset) ){
			return;
		}

		$this->SaveHandlersNew($handlers);

	}




	/**
	 * Display dialog for insterting gadgets/menus/etc into layouts
	 *
	 */
	public function SelectContent(){
		global $langmessage, $config;

		if( !isset($_GET['param']) ){
			message($langmessage['OOPS'].' (Param not set)');
			return;
		}
		$param = $_GET['param'];

		//counts
		$count_gadgets = ( isset($config['gadgets']) && is_array($config['gadgets']) ) ? count($config['gadgets']) : false;
		echo '<div class="inline_box">';

		echo '<div class="layout_links">';
		echo '<a href="#layout_extra_content" class="selected" data-cmd="tabs">'. $langmessage['theme_content'] .'</a>';
		if( $count_gadgets > 0 ){
			echo ' <a href="#layout_gadgets" data-cmd="tabs">'. $langmessage['gadgets'] .'</a>';
		}
		echo ' <a href="#layout_menus" data-cmd="tabs">'. $langmessage['Link_Menus'] .'</a>';

		echo ' <a href="#layout_custom" data-cmd="tabs">'. $langmessage['Custom Menu'] .'</a>';

		echo '</div>';

		$this->SelectContent_Areas($param,$count_gadgets);
		echo '</div>';
	}


	public function SelectContent_Areas($param,$count_gadgets){
		global $dataDir, $langmessage, $config;


		$addQuery = 'cmd=addcontent&where='.rawurlencode($param);
		echo '<div id="area_lists">';

			//extra content
			echo '<div id="layout_extra_content">';
			echo '<table class="bordered">';

				echo '<tr><th colspan="2">&nbsp;</th></tr>';

				$extrasFolder	= $dataDir.'/data/_extra';
				$files			= scandir($extrasFolder);
				asort($files);
				foreach($files as $file){

					$extraName	= \gp\admin\Content\Extra::AreaExists($file);
					if( $extraName === false ){
						continue;
					}

					echo '<tr><td>';
					echo str_replace('_',' ',$extraName);
					echo '</td><td class="add">';
					echo \gp\tool::Link($this->layout_slug,$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,array('data-cmd'=>'creq'));
					echo '</td></tr>';
				}


				//new extra area
				echo '<tr><td colspan="2">';
				echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="new_extra" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				echo '<input type="text" name="extra_area" value="" size="15" class="gpinput" required placeholder="'.htmlspecialchars($langmessage['name']).'" />';
				$types = \gp\tool\Output\Sections::GetTypes();
				echo '<select name="type" class="gpselect">';
				foreach($types as $type => $info){
					echo '<option value="'.$type.'">'.$info['label'].'</option>';
				}
				echo '</select> ';
				echo ' <input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gpbutton gpvalidate"/>';
				echo '</form>';
				echo '</td></tr>';
				echo '</table>';

				echo '<p>';
				echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post" style="text-align:right">';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
				echo '</form>';
				echo '</p>';

			echo '</div>';


			//gadgets
			if( $count_gadgets > 0){
				echo '<div id="layout_gadgets" class="nodisplay">';
					echo '<table class="bordered">';
					echo '<tr><th colspan="2">&nbsp;</th></tr>';

					foreach($config['gadgets'] as $gadget => $info){
						echo '<tr>';
							echo '<td>';
							echo str_replace('_',' ',$gadget);
							echo '</td>';
							echo '<td class="add">';
							echo \gp\tool::Link($this->layout_slug,$langmessage['add'],$addQuery.'&insert='.$gadget,array('data-cmd'=>'creq'));
							echo '</td>';
							echo '</tr>';
					}

					echo '<tr><td colspan="2" class="add">';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';

					echo '</table>';
				echo '</div>';
			}

			//menus
			echo '<div id="layout_menus" class="nodisplay">';


				echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="preset_menu" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';


				echo '<table class="bordered">';
					$this->PresetMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" class="gpsubmit" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';
				echo '</table>';
				echo '</form>';


			echo '</div>';


			echo '<div id="layout_custom" class="nodisplay">';

				//custom area
				echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="custom_menu" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				$this->CustomMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" class="gpsubmit" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
					echo '</td></tr>';
				echo '</table>';

				echo '</form>';
			echo '</div>';
		echo '</div>';
	}


	/**
	 * Insert new content into a layout
	 *
	 */
	public function AddContent(){
		global $langmessage;

		//for ajax responses
		$this->page->ajaxReplace = array();

		if( !isset($_REQUEST['where']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//prep destination
		if( !$this->GetValues($_REQUEST['where'],$to_container,$to_gpOutCmd) ){
			return false;
		}
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//figure out what we're inserting
		$addtype =& $_REQUEST['addtype'];
		switch($_REQUEST['addtype']){

			case 'new_extra':
				$extra_name = $this->NewExtraArea();
				if( $extra_name === false ){
					message($langmessage['OOPS'].' (2)');
					return false;
				}
				$insert = 'Extra:'.$extra_name;
			break;

			case 'custom_menu':
				$insert = $this->NewCustomMenu();
			break;

			case 'preset_menu':
				$insert = $this->NewPresetMenu();
			break;


			default:
				$insert = $_REQUEST['insert'];
			break;
		}

		if( !$insert ){
			message($langmessage['OOPS'].' (Nothing to insert)');
			return false;
		}

		//new info
		$new_gpOutInfo = \gp\tool\Output::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			message($langmessage['OOPS'].' (Nothing to insert)');
			return false;
		}
		$new_gpOutCmd = rtrim($new_gpOutInfo['key'].':'.$new_gpOutInfo['arg'],':');

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$new_gpOutCmd,false) ){
			return false;
		}

		$this->SaveHandlersNew($handlers);

		return true;
	}

	/**
	 * Return the name of the cleansed extra area name, create file if it doesn't already exist
	 *
	 */
	public function NewExtraArea(){
		global $langmessage, $dataDir;

		$title = \gp\tool\Editing::CleanTitle($_REQUEST['extra_area']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$data	= \gp\tool\Editing::DefaultContent($_POST['type']);
		$file	= $dataDir.'/data/_extra/'.$title.'/page.php';

		if( \gp\admin\Content\Extra::AreaExists($title) !== false ){
			return $title;
		}

		if( !\gp\tool\Files::SaveData($file,'file_sections',array($data) ) ){
			message($langmessage['OOPS']);
			return false;
		}

		return $title;
	}



	public function RemoveArea(){
		global $langmessage;

		//for ajax responses
		$this->page->ajaxReplace = array();

		if( !$this->ParseHandlerInfo($_GET['param'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$gpOutCmd = $curr_info['gpOutCmd'];
		$container = $curr_info['container'];


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$container,$gpOutCmd);


		//remove from $handlers[$container]
		$where = $this->ContainerWhere($gpOutCmd, $handlers[$container]);
		if( $where === false ){
			return;
		}

		array_splice($handlers[$container],$where,1);

		$this->SaveHandlersNew($handlers);
	}


	/**
	 * Get the position of $gpOutCmd in $container_info
	 *
	 */
	public function ContainerWhere( $gpOutCmd, &$container_info, $warn = true){
		global $langmessage;

		$where = array_search($gpOutCmd,$container_info);

		if( ($where === null) || ($where === false) ){
			if( $warn ){
				message($langmessage['OOPS'].' (Not found in container)');
			}
			return false;
		}

		return $where;
	}


	/**
	 * Display popup dialog for editing layout menus
	 *
	 */
	public function LayoutMenu(){
		global $langmessage, $gpLayouts;


		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['00PS']);
			return;
		}


		$showCustom			= false;
		$menu_args			= $this->MenuArgs($curr_info);

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}



		echo '<div class="inline_box" style="width:30em">';

		echo '<div class="layout_links">';
		if( $showCustom ){
			echo ' <a href="#layout_menus" data-cmd="tabs">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" data-cmd="tabs" class="selected">'. $langmessage['Custom Menu'] .'</a>';
		}else{
			echo ' <a href="#layout_menus" data-cmd="tabs" class="selected">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" data-cmd="tabs">'. $langmessage['Custom Menu'] .'</a>';
		}
		echo '</div>';

		echo '<br/>';
		echo '<div id="area_lists">';

		//preset menus
			$style = '';
			if( $showCustom ){
				$style = ' class="nodisplay"';
			}
			echo '<div id="layout_menus" '.$style.'>';
			echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';


			echo '<table class="bordered">';
			$this->PresetMenuForm($menu_args);

			echo '<tr><td class="add" colspan="2">';
			echo '<button type="submit" name="cmd" value="LayoutMenuSave" class="gpajax gpsubmit">'.$langmessage['save'].'</button>';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

		//custom menus
			$style = ' class="nodisplay"';
			if( $showCustom ){
				$style = '';
			}
			echo '<div id="layout_custom" '.$style.'>';
			echo '<form action="'.\gp\tool::GetUrl($this->layout_slug).'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';

			$this->CustomMenuForm($menu_args);

			echo '<tr><td class="add" colspan="2">';
			echo '<button type="submit" name="cmd" value="LayoutMenuSave" class="gpsubmit">'.$langmessage['save'].'</button>';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

			echo '<p class="admin_note">';
			echo $langmessage['see_also'];
			echo ' ';
			echo \gp\tool::Link('Admin/Menu',$langmessage['file_manager']);
			echo ', ';
			echo \gp\tool::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
			echo '</p>';

		echo '</div>';
		echo '</div>';

	}

	/**
	 * Save the posted layout menu settings
	 *
	 */
	public function LayoutMenuSave(){
		global $langmessage, $gpLayouts;

		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}



		if( isset($_POST['new_handle']) ){
			$new_gpOutCmd = $this->NewPresetMenu();
		}else{
			$new_gpOutCmd = $this->NewCustomMenu();
		}

		if( $new_gpOutCmd === false ){
			message($langmessage['OOPS'].' (1)');
			return;
		}


		//prep
		$handlers = $this->GetAllHandlers($this->curr_layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers,$container,$curr_info['gpOutCmd']);


		//unchanged?
		if( $curr_info['gpOutCmd'] == $new_gpOutCmd ){
			return;
		}

		if( !$this->AddToContainer($handlers[$container],$curr_info['gpOutCmd'],$new_gpOutCmd,true) ){
			return;
		}

		$this->SaveHandlersNew($handlers,$this->curr_layout);
	}


	public function ParseHandlerInfo($str,&$info){
		global $config,$gpOutConf;

		if( substr_count($str,'|') !== 1 ){
			return false;
		}


		list($container,$fullKey) = explode('|',$str);

		$arg = '';
		$pos = strpos($fullKey,':');
		$key = $fullKey;
		if( $pos > 0 ){
			$arg = substr($fullKey,$pos+1);
			$key = substr($fullKey,0,$pos);
		}

		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}

		$info = array();
		$info['gpOutCmd'] = trim($fullKey,':');
		$info['container'] = $container;
		$info['key'] = $key;
		$info['arg'] = $arg;

		return true;

	}


	/**
	 * Get the container and gpOutCmd from the $arg
	 *
	 */
	public function GetValues($arg,&$container,&$gpOutCmd){
		global $langmessage;

		if( substr_count($arg,'|') !== 1 ){
			message($langmessage['OOPS'].' (Invalid argument)');
			return false;
		}

		list($container,$gpOutCmd) = explode('|',$arg);
		return true;
	}


	public function AddToContainer(&$container_info,$to_gpOutCmd,$new_gpOutCmd,$replace=true,$offset=0){
		global $langmessage;


		//add to to_container in front of $to_gpOutCmd
		if( !is_array($container_info) ){
			message($langmessage['OOPS'].' (a1)');
			return false;
		}


		//can't have two identical outputs in the same container
		$check = $this->ContainerWhere($new_gpOutCmd, $container_info, false);
		if( $check !== false ){
			message($langmessage['OOPS']. ' (Area already in container)');
			return false;
		}

		//if empty, just add
		if( count($container_info) === 0 ){
			$container_info[] = $new_gpOutCmd;
			return true;
		}

		//insert
		$where	= $this->ContainerWhere($to_gpOutCmd, $container_info);
		if( $where === false ){
			return false;
		}

		$length = 1;
		if( $replace === false ){
			$length	= 0;
			$where	+= $offset;
		}

		array_splice($container_info,$where,$length,$new_gpOutCmd);

		return true;
	}


	public function NewCustomMenu(){

		$upper_bound =& $_POST['upper_bound'];
		$lower_bound =& $_POST['lower_bound'];
		$expand_bound =& $_POST['expand_bound'];
		$expand_all =& $_POST['expand_all'];
		$source_menu =& $_POST['source_menu'];

		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);

		$arg = $upper_bound.','.$lower_bound.','.$expand_bound.','.$expand_all.','.$source_menu;
		return 'CustomMenu:'.$arg;
	}


	public function NewPresetMenu(){
		global $gpOutConf;

		$new_gpOutCmd =& $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutCmd]) || !isset($gpOutConf[$new_gpOutCmd]['link']) ){
			return false;
		}

		return rtrim($new_gpOutCmd.':'.$this->CleanMenu($_POST['source_menu']),':');
	}


	public function PresetMenuForm($args = array()){
		global $gpOutConf,$langmessage;

		$current_function =& $args['current_function'];
		$current_menu =& $args['source_menu'];

		$this->MenuSelect($current_menu);


		echo '<tr><th colspan="2">';
			echo $langmessage['Menu Output'];
		echo '</th></tr>';


		$i = 0;
		foreach($gpOutConf as $outKey => $info){

			if( !isset($info['link']) ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<label for="new_handle_'.$i.'">';
			if( isset($langmessage[$info['link']]) ){
				echo str_replace(' ','&nbsp;',$langmessage[$info['link']]);
			}else{
				echo str_replace(' ','&nbsp;',$info['link']);
			}
			echo '</label>';
			echo '</td>';
			echo '<td class="add">';

			if( $current_function == $outKey ){
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" checked="checked"/>';
			}else{
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" />';
			}
			echo '</td>';
			echo '</tr>';
			$i++;
		}
	}


	public function MenuArgs($curr_info){

		$menu_args = array();

		if( $curr_info['key'] == 'CustomMenu' ){

			$args = explode(',',$curr_info['arg']);
			$args += array( 0=>0, 1=>-1, 2=>-1, 3=>0, 4=>'' ); //defaults
			list($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu) = $args;

			$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);


			$menu_args['upper_bound'] = $upper_bound;
			$menu_args['lower_bound'] = $lower_bound;
			$menu_args['expand_bound'] = $expand_bound;
			$menu_args['expand_all'] = $expand_all;
			$menu_args['source_menu'] = $source_menu;


		}else{

			$menu_args['current_function'] = $curr_info['key'];
			$menu_args['source_menu'] = $this->CleanMenu($curr_info['arg']);
		}


		return $menu_args;

	}



	/**
	 * Output form elements for setting custom menu settings
	 *
	 * @param array $menu_args
	 */
	public function CustomMenuForm($menu_args = array()){
		global $langmessage;


		$upper_bound	=& $menu_args['upper_bound'];
		$lower_bound	=& $menu_args['lower_bound'];
		$expand_bound	=& $menu_args['expand_bound'];
		$expand_all		=& $menu_args['expand_all'];
		$source_menu	=& $menu_args['source_menu'];


		echo '<table class="bordered">';

		$this->MenuSelect($source_menu);

		echo '<tr><th colspan="2">';
		echo $langmessage['Show Titles...'];
		echo '</th></tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'upper_bound', $upper_bound);
		$this->CustomMenuSection($langmessage['... At And Above Level'], 'lower_bound', $lower_bound);


		echo '<tr><th colspan="2">';
		echo $langmessage['Expand Menu...'];
		echo '</th></tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'expand_bound', $expand_bound);


		echo '<tr><td>';
		echo $langmessage['... Expand All'];
		echo '</td><td class="add">';
		$attr = $expand_all ? 'checked' : '';
		echo '<input type="checkbox" name="expand_all" '.$attr.'>';
		echo '</td></tr>';

	}




	public function CleanBounds(&$upper_bound,&$lower_bound,&$expand_bound,&$expand_all,&$source_menu){

		$upper_bound = (int)$upper_bound;
		$upper_bound = max(0,$upper_bound);
		$upper_bound = min(4,$upper_bound);

		$lower_bound = (int)$lower_bound;
		$lower_bound = max(-1,$lower_bound);
		$lower_bound = min(4,$lower_bound);

		$expand_bound = (int)$expand_bound;
		$expand_bound = max(-1,$expand_bound);
		$expand_bound = min(4,$expand_bound);

		if( $expand_all ){
			$expand_all = 1;
		}else{
			$expand_all = 0;
		}

		$source_menu = $this->CleanMenu($source_menu);
	}

	public function CleanMenu($menu){
		global $config;

		if( empty($menu) ){
			return '';
		}
		if( !isset($config['menus'][$menu]) ){
			return '';
		}
		return $menu;
	}


	/**
	 * Output section for custom menu form
	 *
	 * @param string $label
	 * @param string $name
	 * @param int $value
	 */
	public function CustomMenuSection($label, $name, $value){
		echo '<tr><td>';
		echo $label;
		echo '</td><td class="add">';
		echo '<select name="'.$name.'" class="gpselect">';
		for($i=0;$i<=4;$i++){

			$label		= ($i === 0) ? '' : $i;
			$selected	= ($i === $value) ? 'selected' : '';

			echo '<option value="'.$i.'" '.$selected.'>'.$label.'</option>';
		}

		echo '</select>';
		echo '</td></tr>';
	}


	public function MenuSelect($source_menu){
		global $config, $langmessage;

		echo '<tr><th colspan="2">';
			echo $langmessage['Source Menu'];
		echo '</th>';
		echo '</tr>';
		echo '<tr><td>';
		echo $langmessage['Menu'];
		echo '</td><td class="add">';
		echo '<select name="source_menu" class="gpselect">';
		echo '<option value="">'.$langmessage['Main Menu'].'</option>';
		if( isset($config['menus']) && count($config['menus']) > 0 ){
			foreach($config['menus'] as $id => $menu ){
				$attr = '';
				if( $source_menu == $id ){
					$attr = ' selected="selected"';
				}
				echo '<option value="'.htmlspecialchars($id).'" '.$attr.'>'.htmlspecialchars($menu).'</option>';
			}
		}
		echo '</select>';
		echo '</td></tr>';
	}

}