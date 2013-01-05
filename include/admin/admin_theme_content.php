<?php
defined('is_running') or die('Not an entry point...');

/*
what can be moved?
	* .editable_area

How do we position elements?
	* above, below in relation to another editable_area

How do we do locate them programatically
	* We need to know the calling functions that output the areas
		then be able to organize a list of output functions within each of the calling functions
		!each area is represented by a list, either a default value if an override hasn't been defined, or the custom list created by the user

How To Identify the Output Functions for the Output Lists?
	* Gadgets have:
		$info['script']
		$info['data']
		$info['class']


$gpOutConf = array() of output functions/classes.. to use with the theme content
	==potential values==
	$gpOutConf[-ident-]['script'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['data'] = -path relative to datadir-
	$gpOutConf[-ident-]['class'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['method'] = string or array: string=name of function, array(class,method)


	$gpLayout['Loyout_Name']['handlers'][-ident-] = array(0=>-ident-,1=>-ident-)
	$gpLayout['Loyout_Name']['color'] = '#123456'
	$gpLayout['Loyout_Name']['theme'] = 'One_Point_5/Blue'

*/

//includeFile('admin/admin_menu_tools.php');
includeFile('admin/admin_addon_install.php');

class admin_theme_content extends admin_addon_install{

	var $curr_layout = false;
	var $LayoutArray;


	//remote install variables
	var $config_index = 'themes';
	var $addon_folder_name = '_themes';
	var $path_root = 'Admin_Theme_Content';
	var $path_remote = 'Admin_Theme_Content/Remote';
	var $can_install_links = false;


	function admin_theme_content(){
		global $page,$config,$gpLayouts, $langmessage;

		$this->find_label = $langmessage['Find Themes'];
		$this->manage_label = $langmessage['Manage Layouts'];


		//message('request: '.showArray($_REQUEST));

		$page->head_js[] = '/include/js/theme_content.js';
		$page->head_js[] = '/include/js/dragdrop.js';

		$page->css_admin[] = '/include/css/theme_content.css';
		$page->css_admin[] = '/include/css/addons.css';

		$cmd = common::GetCommand();

		//layout requests
		if( strpos($page->requested,'/') ){
			$parts = explode('/',$page->requested);
			$layout_part = $parts[1];
			switch( strtolower($layout_part) ){
				case 'remote':
					$this->RemoteBrowse();
				return;
			}

			if( isset($gpLayouts[$layout_part]) ){
				$this->EditLayout($layout_part,$cmd);
				return;
			}
		}

		if( isset($_REQUEST['layout']) && isset($gpLayouts[$_REQUEST['layout']]) ){
			$this->curr_layout = $_REQUEST['layout'];
		}else{
			$this->curr_layout = $config['gpLayout'];
		}
		$this->SetLayoutArray();


		switch($cmd){

			//remote themes
			case 'remote_install':
			case 'remote_install2':
			case 'remote_install3':
				$this->RemoteInstallMain($cmd);
			return;

			case 'deletetheme':
				$this->DeleteTheme();
			return;

			case 'delete_theme_confirmed':
				$this->DeleteThemeConfirmed();
			break;


			//adminlayout
			case 'adminlayout':
				$this->AdminLayout();
			return;

			//theme ratings
			case 'Update Review';
			case 'Send Review':
			case 'rate':
				includeFile('admin/admin_addons_tool.php');
				$rating = new admin_addons_tool();
				$rating->admin_addon_rating('theme','Admin_Theme_Content');
				if( $rating->ShowRatingText ){
					return;
				}
			break;

			//new layouts
			case 'preview':
				if( $this->PreviewTheme() ){
					return;
				}
			break;
			case 'newlayout':
				$this->NewLayoutPrompt();
			return;
			case 'addlayout':
				$this->NewLayout();
			break;
			//copy
			case 'copy':
				$this->CopyLayoutPrompt();
			return;
			case 'copylayout';
				$this->CopyLayout();
			break;



			//editing layouts without a layout id as part of slug
			case 'editlayout'://linked from install page without a layout id
			case 'details':
				$this->EditLayout($this->curr_layout,$cmd);
			return;

			case 'rmgadget':
				$this->RmGadget($_REQUEST['layout']);
			break;

			case 'restore':
				$this->Restore($_REQUEST['layout']);
			break;

			case 'change_layout_color':
				$this->ChangeLayoutColor($_REQUEST['layout'],false);
			break;

			case 'css_preferences':
				$this->CSSPreferences($_REQUEST['layout'],false);
			break;


			//layout options
			case 'makedefault':
				$this->MakeDefault($_GET['layout_id']);
			break;
			case 'deletelayout':
				$this->DeleteLayoutConfirmed();
			break;

			case 'layout_details':
				$this->LayoutDetails();
			break;


			//links
			case 'editlinks':
			case 'editcustom':
				$this->SelectLinks();
			return;
			case 'savelinks':
				$this->SaveLinks();
			break;

			//text
			case 'edittext':
				$this->EditText();
			return;
			case 'savetext':
				$this->SaveText();
			break;


			case 'saveaddontext':
				$this->SaveAddonText();
			break;
			case 'addontext':
				$this->AddonText();
			return;


		}

		//message(showArray($_GET));
		$this->Show();
	}


	function AdminLayout(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';

		$admin_layout = $langmessage['default'];
		echo '<h2>'.'Admin Layout'.'</h2>';

		echo '<select name="">';
			echo '<option value="">'.$langmessage['default'].'</option>';
		echo '</select>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Edit layout properties
	 * 		Layout Identification
	 * 		Content Arrangement
	 * 		Gadget Visibility
	 *
	 */
	function EditLayout($layout,$cmd){
		global $page,$gpLayouts,$langmessage,$config;



		$GLOBALS['GP_ARRANGE_CONTENT'] = true;
		$page->head_js[] = '/include/js/inline_edit/inline_editing.js';

		$this->curr_layout = $layout;
		$this->SetLayoutArray();
		$page->SetTheme($layout);

		$this->LoremIpsum();

		gpOutput::TemplateSettings();

		gpPlugin::Action('edit_layout_cmd',array($layout));

		switch($cmd){

			/**
			 * Inline image editing
			 *
			 */
			case 'inlineedit':
				$this->InlineEdit();
			return;
			case 'gallery_folder':
			case 'gallery_images':
				$this->GalleryImages();
			break;
			case 'image_editor':
				$this->ImageEditor();
			return;
			case 'save_image':
				$this->SaveHeaderImage();
			return;

			case 'theme_images':
				$this->ShowThemeImages();
			return;



			/**
			 * CSS editing
			 *
			 */
			case 'save_css':
				$this->SaveCSS();
			break;
			case 'css':
				$this->EditCSS();
			break;

			case 'makedefault':
				$this->MakeDefault($layout);
			break;
			case 'change_layout_color':
				$this->ChangeLayoutColor($layout);
			break;
			case 'css_preferences':
				$this->CSSPreferences($layout);
			break;

			case 'rmgadget':
				$this->RmGadget($layout);
			break;

			case 'layout_details':
				$this->LayoutDetails();
			break;

			case 'restore':
				$this->Restore($layout);
			break;
			case 'drag_area':
				$this->Drag();
			break;

			//insert
			case 'insert':
				$this->SelectContent();
			return;

			case 'addcontent':
				$this->AddContent();
			break;

			//remove
			case 'rm_area':
				$this->RemoveArea();
			break;

		}

		$layout_info = common::LayoutInfo($layout,false);
		$handlers_count = 0;
		if( isset($layout_info['handlers']) && is_array($layout_info['handlers']) ){
			foreach($layout_info['handlers'] as $val){
				$int = count($val);
				if( $int === 0){
					$handlers_count++;
				}
				$handlers_count += $int;
			}
		}

		$page->label = $langmessage['layouts'] . ' » '.$layout_info['label'];



		//display options
		switch($cmd){
			case 'makedefault':
			case 'details':
				$this->ShowDetails($layout, $layout_info, $handlers_count );
			return;
		}

		$page->show_admin_content = false;
		$page->head .= "\n".'<script type="text/javascript">var gpLayouts=true;</script>';

		$this->PrepareCSS();
		$this->Toolbar($layout, $layout_info );
	}

	/**
	 * Show details about the selected layout
	 *
	 */
	function ShowDetails( $layout, $layout_info, $handlers_count){
		global $langmessage, $config;

		echo '<h3>'.$langmessage['details'].'</h3>';

		//layout options
		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">';
		echo $langmessage['layout'];
		echo '</th></tr>';

		echo '<tr><td style="width:40%">';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<a data-cmd="layout_id" title="'.$layout_info['color'].'" data-arg="'.$layout_info['color'].'">';
		echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
		echo '<input type="hidden" name="layout_label" value="'.$layout_info['label'].'"  /> ';
		echo '<span class="layout_color_id" style="background-color:'.$layout_info['color'].';"></span>';
		echo '&nbsp;';
		echo $layout_info['label'];
		echo '</a>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['theme'];
		echo '</td><td>';
		echo $layout_info['theme_name'];
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['usage'];
		echo '</td><td>';
			if( $config['gpLayout'] == $layout ){
				echo $langmessage['default'];
			}elseif( !isset($_GET['show']) ){
				echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),str_replace(' ','&nbsp;',$langmessage['make_default']),'cmd=makedefault',array('data-cmd'=>'gpabox','title'=>$langmessage['make_default']));
			}else{
				echo common::Link('Admin_Theme_Content',str_replace(' ','&nbsp;',$langmessage['default']),'cmd=makedefault&layout_id='.rawurlencode($layout),array('data-cmd'=>'creq','title'=>htmlspecialchars($langmessage['make_default'])));
			}

			echo ' &nbsp; ';
			$titles_count = $this->TitlesCount($layout);
			echo sprintf($langmessage['%s Pages'],$titles_count);
		echo '</td></tr>';


		$theme_colors = $this->GetThemeColors($layout_info['dir']);
		echo '<tr><td>';
		echo $langmessage['style'];
		echo '</td><td>';
		if( !isset($_GET['show']) ){
			echo '<form action="'.common::GetUrl('Admin_Theme_Content/'.rawurlencode($layout)).'" method="post">';
		}else{
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="layout" value="'.$layout.'" />';
		}
		echo '<select name="color" class="gpselect">';
		foreach($theme_colors as $color){
			if( $color == $layout_info['theme_color'] ){
				echo '<option value="'.htmlspecialchars($color).'" selected="selected">';
			}else{
				echo '<option value="'.htmlspecialchars($color).'">';
			}
			echo $color;
			echo '</option>';
		}
		echo '</select>';
		echo ' <input type="hidden" name="cmd" value="change_layout_color" />';
		echo ' <input type="submit" name="" value="'.htmlspecialchars($langmessage['save']).'" class="gpbutton" />';
		echo '</form>';
		echo '</td></tr>';


		echo '<tr><td>';
		echo $langmessage['content_arrangement'];
		echo '</td><td>';
		if( $handlers_count > 0 ){
			if( !isset($_GET['show']) ){
				echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$langmessage['restore_defaults'],'cmd=restore','data-cmd="creq"');
			}else{
				echo common::Link('Admin_Theme_Content',$langmessage['restore_defaults'],'cmd=restore&layout='.rawurlencode($layout),'data-cmd="creq"');
			}
		}else{
			echo $langmessage['default'];
		}
		echo '</td></tr>';
		echo '</table>';


		// gadgets
		echo '<br/>';
		echo '<table class="bordered full_width">';
		$gadget_info = gpOutput::WhichGadgets($this->curr_layout);
		echo '<tr><th style="width:40%">';
		echo $langmessage['gadgets'];
		echo '</th><th>&nbsp;</th></tr>';
		if( !isset($config['gadgets']) || count($config['gadgets']) == 0 ){
			echo '<tr><td colspan="2">';
			echo $langmessage['Empty'];
			echo '</td></tr>';
		}else{
			foreach($config['gadgets'] as $gadget => $temp){
				echo '<tr><td>';
				echo str_replace('_',' ',$gadget);
				echo '</td><td>';
				if( isset($gadget_info[$gadget]) ){
					if( !isset($_GET['show']) ){
						echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$langmessage['remove'],'cmd=rmgadget&gadget='.urlencode($gadget),'data-cmd="creq"');
					}else{
						echo common::Link('Admin_Theme_Content',$langmessage['remove'],'cmd=rmgadget&gadget='.urlencode($gadget).'&layout='.rawurlencode($layout),'data-cmd="creq"');
					}
				}else{
					echo $langmessage['disabled'];
				}
				echo '</td></tr>';
			}
		}
		echo '</table>';


		//CSS options
		echo '<br/>';
		if( !isset($_GET['show']) ){
			echo '<form action="'.common::GetUrl('Admin_Theme_Content/'.rawurlencode($layout)).'" method="post">';
		}else{
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="layout" value="'.$layout.'" />';
		}
		echo '<input type="hidden" name="cmd" value="css_preferences" />';
		echo '<table class="bordered full_width">';
		echo '<tr><th style="width:40%">CSS</th><th>&nbsp;</th></tr>';

		echo '<tr><td>';
		echo 'Name Based Menu Classes';
		echo '</td><td>';
		$checked = '';
		if( !isset($layout_info['menu_css_ordered']) ){
			$checked = 'checked="checked"';
		}
		echo '<input type="checkbox" name="menu_css_ordered" value="on" '.$checked.' />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'Ordered Menu Classes';
		echo '</td><td>';
		$checked = '';
		if( !isset($layout_info['menu_css_indexed']) ){
			$checked = 'checked="checked"';
		}
		echo '<input type="checkbox" name="menu_css_indexed" value="on" '.$checked.' />';
		echo '</td></tr>';


		echo '<tr><td>';
		echo '&nbsp;';
		echo '</td><td>';
		echo ' <input type="submit" name="" value="'.htmlspecialchars($langmessage['save']).'" class="gpbutton" />';
		echo '</td></tr>';

		echo '</table>';
		echo '</form>';

		//affected titles
		$titles_count = $this->TitlesCount($layout);

		echo '<br/>';
		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">';
		echo $langmessage['titles_using_layout'];
		echo ': '.$titles_count;
		echo '</th></tr>';

		echo '<tr><td colspan="2">';
		if( $titles_count > 0 ){
			echo '<ul class="titles_using">';

			foreach( $this->LayoutArray as $index => $layout_comparison ){
				if( $layout == $layout_comparison ){

					$title = common::IndexToTitle($index);
					if( empty($title) ){
						continue; //may be external link
					}

					echo "\n<li>";
					$label = common::GetLabel($title);
					$label = common::LabelSpecialChars($label);
					echo common::Link($title,$label);
					echo '</li>';
				}
			}

			echo '</ul>';
			echo '<div class="clear"></div>';
		}
		echo '</td></tr>';
		echo '</table>';

	}

	/**
	 * Add CSS to the page to add space for the editing toolbar
	 *
	 */
	function ToolbarCSS(){
		global $page;
		$page->head .= '<style type="text/css" media="screen">'
					. 'html { margin-top: 36px !important; } * html body { margin-top: 36px !important; }'
					. '.messages{top:37px !important;}'
					. '</style>';
	}

	/**
	 * Display the toolbar for layout editing
	 *
	 */
	function Toolbar($layout, $layout_info ){
		global $page,$gpLayouts,$langmessage,$config;
		$page->show_admin_content = false;
		$this->ToolbarCSS();


		ob_start();

		echo '<div id="theme_toolbar">';


		//theme_right
		echo '<div id="theme_right">';

		echo '<div>';
		$this->ThemeSelect();
		echo '</div>';

		echo '</div>';


		//theme_left
		echo '<div id="theme_left">';
		echo '<div class="step"><b>'. common::Link('Admin_Theme_Content',$langmessage['Manage Layouts']).'</b></div>';

		echo '<div class="step">';
		$this->LayoutSelect($layout,$layout_info);
		echo '</div>';

		$this->LayoutOptions($layout,$layout_info);


		echo '</div>';//theme_left

		echo '</div>'; //theme_toolbar
		$this->ColorSelector($layout);
		$page->admin_html = ob_get_clean();
	}

	/**
	 * Create a drop-down menu for the layout options
	 *
	 */
	function LayoutOptions($layout,$info){
		global $langmessage;
		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$langmessage['options'].'</a>';
		echo '<div class="dd_list"><ul>';
		echo '<li>'.common::Link('Admin_Theme_Content/'.rawurlencode($layout),$langmessage['details'],'cmd=details','data-cmd="gpabox"').'</li>';
		echo '<li>'.common::Link('Admin_Theme_Content/'.rawurlencode($layout),'CSS','cmd=css','data-cmd="gpabox"').'</li>';
		echo '<li>'.common::Link('Admin_Theme_Content',$langmessage['Copy'],'cmd=copy&layout='.rawurlencode($layout),'data-cmd="gpabox"').'</li>';

		$attr = array('data-cmd'=>'cnreq', 'class'=>'gpconfirm','title'=>sprintf($langmessage['generic_delete_confirm'],$info['label']));
		echo '<li>'.common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletelayout&layout_id='.rawurlencode($layout),$attr).'</li>';

		echo '</ul></div>';
		echo '</div></div>';
	}

	/**
	 * Display all the layouts available in a <select>
	 *
	 */
	function LayoutSelect($curr_layout=false,$curr_info=false){
		global $gpLayouts, $langmessage;

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

			$display = '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span> &nbsp; '
					. $info['label'];
			echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$display);
			echo '</li>';
		}
		echo '</ul></div>';
		echo '</div></div>';
	}


	/**
	 * Display all the available theme in a <select>
	 *
	 */
	function ThemeSelect($curr_theme_id = false, $curr_color = false){
		global $langmessage;
		$themes = $this->GetPossible();

		$display = $langmessage['available_themes'];
		if( $curr_theme_id ){
			$display = htmlspecialchars($curr_theme_id.' / '.$curr_color);
			$display = str_replace(array('_','(remote)','(package)'),array(' ','',''),$display);
		}

		echo '<div class="dd_menu">';
		echo '<a data-cmd="dd_menu">'.$display.'</a>';

		echo '<div class="dd_list"><ul>';
		foreach($themes as $theme_id => $info){
			echo '<li><span class="list_heading">'.htmlspecialchars(str_replace('_',' ',$info['name'])).'</span>';
			echo '<ul>';
			foreach($info['colors'] as $color){
				$attr = '';
				if( $theme_id == $curr_theme_id && $color == $curr_color ){
					$attr = ' class="selected"';
				}
				echo '<li'.$attr.'>';

				echo common::Link('Admin_Theme_Content',htmlspecialchars(str_replace('_',' ',$color)),'cmd=preview&theme='.rawurlencode($theme_id.'/'.$color));
				echo '</li>';
			}
			echo '</li>';
			echo '</ul>';
		}
		echo '</ul></div>';
		echo '</div>';
	}



	/*
	 * Get the content of the drag and drop window
	 * @deprecated
	function DragDropNote($layout, $layout_info, $handlers_count ){
		ob_start();
		echo '<div id="gp_drag_n_drop" class="gp_floating_area nodisplay"><div><div>';
		echo $langmessage['DRAG-N-DROP-DESC'];
		$page->non_admin_content .= ob_get_clean();
	}
	 */



	/**
	 * Output textarea for adding css to a layout
	 *
	 */
	function EditCSS(){
		global $langmessage, $page,$gpLayouts;

		$css = $this->layoutCSS($this->curr_layout);
		if( empty($css) ){
			//$css = '/* Add your CSS here. Changes will be applied as you edit. */';
		}

		echo '<form action="'.common::GetUrl('Admin_Theme_Content/'.$this->curr_layout).'" method="post">';
		echo '<h3>CSS</h3>';
		echo '<textarea name="css" id="gp_layout_css" class="layout_css gptextarea" rows="10" cols="50">';
		echo htmlspecialchars($css);
		echo '</textarea>';
		echo '<p>';
		echo ' <input type="hidden" name="cmd" value="save_css" />';
		echo ' <input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="button" name="" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';

		$page->ajaxReplace[] = array('EditCSS','','');
	}

	/**
	 * Prepare the page for css editing
	 *
	 */
	function PrepareCSS(){
		global $page,$gpLayouts;

		$css = $this->layoutCSS($this->curr_layout);
		$page->head .= '<style id="gp_layout_style" type="text/css">'.$css.'</style>';
		$page->layout_css = false;
	}

	/**
	 * Get the custom css for a layout if it exists
	 *
	 */
	function LayoutCSS($layout){
		global $dataDir, $gpLayouts;

		$layout_info = $gpLayouts[$layout];
		if( !isset($layout_info['css']) || !$layout_info['css'] ){
			return '';
		}

		$path = $dataDir.'/data/_layouts/'.$layout.'/custom.css';
		if( file_exists($path) ){
			return file_get_contents($path);
		}

		return '';
	}

	/**
	 * Save edits to the layout css
	 *
	 */
	function SaveCSS(){
		global $langmessage, $dataDir, $gpLayouts;

		$path = $dataDir.'/data/_layouts/'.$this->curr_layout.'/custom.css';
		$css =& $_POST['css'];


		//delete if empty
		if( empty($css) ){
			unset($gpLayouts[$this->curr_layout]['css']);
			if( !admin_tools::SavePagesPHP() ){
				message($langmessage['OOPS'].' (Data not saved)');
				return false;
			}
			$this->RemoveCSS($this->curr_layout);
		}


		//save if not empty
		if( !gpFiles::Save($path,$css) ){
			message($langmessage['OOPS'].' (CSS not saved)');
			return false;
		}

		$gpLayouts[$this->curr_layout]['css'] = true;
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (Data not saved)');
			return false;
		}
		message($langmessage['SAVED']);
	}

	/**
	 * Remove the custom css file for a layout
	 *
	 */
	function RemoveCSS($layout){
		global $dataDir;
		$dir = $dataDir.'/data/_layouts/'.$layout;
		$path = $dir.'/custom.css';
		if( file_exists($path) ){
			unlink($path);
		}

		$path = $dir.'/index.html';
		if( file_exists($path) ){
			unlink($path);
		}

		if( file_exists($dir) ){
			gpFiles::RmDir($dir);
		}
	}

	/**
	 * Save changes to the css settings for a layout
	 *
	 */
	function CSSPreferences($layout, $set_theme = true){
		global $langmessage, $gpLayouts, $page;
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (Invalid Layout)');
			return false;
		}

		$old_info = $new_info = $gpLayouts[$layout];
		if( !isset($_POST['menu_css_ordered']) ){
			$new_info['menu_css_ordered'] = false;
		}else{
			unset($new_info['menu_css_ordered']);
		}

		if( !isset($_POST['menu_css_indexed']) ){
			$new_info['menu_css_indexed'] = false;
		}else{
			unset($new_info['menu_css_indexed']);
		}

		$gpLayouts[$layout] = $new_info;

		if( !admin_tools::SavePagesPHP() ){
			$gpLayouts[$layout] = $old_info;
			message($langmessage['OOPS'].' (Not Saved)');
			return;
		}

		if( $set_theme || $page->gpLayout == $layout ){
			$page->SetTheme($layout);
		}
	}

	/**
	 * Change the color variant for $layout
	 *
	 */
	function ChangeLayoutColor($layout,$set_theme=true){
		global $langmessage,$gpLayouts,$page;

		$color =& $_REQUEST['color'];
		$layout_info = common::LayoutInfo($layout,false);
		$theme_colors = $this->GetThemeColors($layout_info['dir']);

		if( !isset($theme_colors[$color]) ){
			message($langmessage['OOPS'].' (Invalid Color)');
			return false;
		}

		$old_info = $new_info = $gpLayouts[$layout];
		$theme_name = dirname($new_info['theme']);
		$new_info['theme'] = $theme_name.'/'.$color;
		$gpLayouts[$layout] = $new_info;

		if( !admin_tools::SavePagesPHP() ){
			$gpLayouts[$layout] = $old_info;
			message($langmessage['OOPS'].' (Not Saved)');
			return;
		}

		if( $set_theme || $page->gpLayout == $layout ){
			$page->SetTheme($layout);
		}

	}


	/**
	 * Remove a gadget from a layout
	 * @return null
	 *
	 */
	function RmGadget($layout){
		global $page,$langmessage,$gpLayouts;

		$gadget =& $_REQUEST['gadget'];

		$handlers = $this->GetAllHandlers($layout);
		$this->PrepContainerHandlers($handlers,'GetAllGadgets','GetAllGadgets'); //make sure GetAllGadgets is set

		$changed = false;
		foreach($handlers as $container => $container_info){
			foreach($container_info as $key => $gpOutCmd){
				if( $gpOutCmd == $gadget ){
					$changed = true;
					unset($handlers[$container][$key]);
				}
			}
		}

		if( !$changed ){
			message($langmessage['OOPS'].' (Not Changed)');
			return;
		}

		$this->SaveHandlersNew($handlers,$layout);
	}


	function GetRandColor(){
		$colors = $this->GetColors();
		$color_key = array_rand($colors);
		return $colors[$color_key];
	}

	function GetColors(){

		//color/layout_id changing
		$colors = array();

		$colors[] = '#ff0000';
		$colors[] = '#ff9900';
		$colors[] = '#ffff00';
		$colors[] = '#00ff00';
		$colors[] = '#00ffff';
		$colors[] = '#0000ff';
		$colors[] = '#9900ff';
		$colors[] = '#ff00ff';

		$colors[] = '#f4cccc';
		$colors[] = '#fce5cd';
		$colors[] = '#fff2cc';
		$colors[] = '#d9ead3';
		$colors[] = '#d0e0e3';
		$colors[] = '#cfe2f3';
		$colors[] = '#d9d2e9';
		$colors[] = '#ead1dc';


		$colors[] = '#ea9999';
		$colors[] = '#f9cb9c';
		$colors[] = '#ffe599';
		$colors[] = '#b6d7a8';
		$colors[] = '#a2c4c9';
		$colors[] = '#9fc5e8';
		$colors[] = '#b4a7d6';
		$colors[] = '#d5a6bd';

		$colors[] = '#e06666';
		$colors[] = '#f6b26b';
		$colors[] = '#ffd966';
		$colors[] = '#93c47d';
		$colors[] = '#76a5af';
		$colors[] = '#6fa8dc';
		$colors[] = '#8e7cc3';
		$colors[] = '#c27ba0';


		$colors[] = '#cc0000';
		$colors[] = '#e69138';
		$colors[] = '#f1c232';
		$colors[] = '#6aa84f';
		$colors[] = '#45818e';
		$colors[] = '#3d85c6';
		$colors[] = '#674ea7';
		$colors[] = '#a64d79';


		$colors[] = '#990000';
		$colors[] = '#b45f06';
		$colors[] = '#bf9000';
		$colors[] = '#38761d';
		$colors[] = '#134f5c';
		$colors[] = '#0b5394';
		$colors[] = '#351c75';
		$colors[] = '#741b47';


/*		Too dark
		$colors[] = '#660000';
		$colors[] = '#783f04';
		$colors[] = '#7f6000';
		$colors[] = '#274e13';
		$colors[] = '#0c343d';
		$colors[] = '#073763';
		$colors[] = '#20124d';
		$colors[] = '#4c1130';
*/

		return $colors;
	}



	/**
	 * Give users a few options before creating the new layout
	 *
	 */
	function NewLayoutPrompt(){
		global $langmessage;

		$theme =& $_REQUEST['theme'];
		$theme_info = $this->ThemeInfo($theme);
		if( $theme_info === false ){
			message($langmessage['OOPS'].' (Invalid Theme)');
			return false;
		}

		$label = substr($theme_info['name'].'/'.$theme_info['color'],0,25);

		echo '<h3>'.$langmessage['new_layout'].'</h3>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<table class="bordered full_width">';

		echo '<tr><th colspan="2">';
		echo $langmessage['options'];
		echo '</th></tr>';

		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<input type="text" name="label" value="'.htmlspecialchars($label).'" class="gpinput" />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['make_default'];
		echo '</td><td>';
		echo '<input type="checkbox" name="default" value="default" />';
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo ' <input type="hidden" name="theme" value="'.htmlspecialchars($theme).'" />';
		echo ' <input type="hidden" name="cmd" value="addlayout" />';
		echo ' <input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="button" name="" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';
	}

	/**
	 * Add a new layout to the installation
	 *
	 */
	function NewLayout(){
		global $gpLayouts,$langmessage,$config,$page;

		$theme =& $_POST['theme'];
		$theme_info = $this->ThemeInfo($theme);
		if( $theme_info === false ){
			message($langmessage['OOPS'].' (Invalid Theme)');
			return false;
		}

		$newLayout = array();
		$newLayout['theme'] = $theme_info['folder'].'/'.$theme_info['color'];
		$newLayout['color'] = $this->GetRandColor();
		$newLayout['label'] = htmlspecialchars($_POST['label']);
		if( $theme_info['is_addon'] ){
			$newLayout['is_addon'] = true;
			$newLayout['theme_label'] = $theme_info['name'].'/'.$theme_info['color'];
		}
		if( isset($theme_info['id']) && is_numeric($theme_info['id']) ){
			$newLayout['addon_id'] = $theme_info['id'];
		}


		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );

		$gpLayoutsBefore = $gpLayouts;
		$gpLayouts[$layout_id] = $newLayout;
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS']);
		}


		if( !empty($_POST['default']) && $_POST['default'] != 'false' ){
			$config['gpLayout'] = $layout_id;
			admin_tools::SaveConfig();
			$page->SetTheme();
			$this->SetLayoutArray();
		}
	}


	/**
	 * Display some options before copying a layout
	 *
	 */
	function CopyLayoutPrompt(){
		global $langmessage, $gpLayouts;

		$layout = $_REQUEST['layout'];
		if( empty($layout) || !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].'(Invalid Request)');
			return;
		}

		$label = admin_theme_content::NewLabel($gpLayouts[$layout]['label']);

		echo '<h3>'.$langmessage['new_layout'].'</h3>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<table class="bordered full_width">';

		echo '<tr><th colspan="2">';
		echo $langmessage['options'];
		echo '</th></tr>';

		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<input type="text" name="label" value="'.htmlspecialchars($label).'" class="gpinput" />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['make_default'];
		echo '</td><td>';
		echo '<input type="checkbox" name="default" value="default" />';
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo ' <input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
		echo ' <input type="hidden" name="cmd" value="copylayout" />';
		echo ' <input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="button" name="" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';

	}

	/**
	 * Copy a layout
	 *
	 */
	function CopyLayout(){
		global $gpLayouts,$langmessage,$config,$page,$dataDir;

		$copy_id =& $_REQUEST['layout'];

		if( empty($copy_id) || !isset($gpLayouts[$copy_id]) ){
			message($langmessage['OOPS'].'(Invalid Request)');
			return;
		}
		if( empty($_POST['label']) ){
			message($langmessage['OOPS'].'(Empty Label)');
			return;
		}

		$newLayout = $gpLayouts[$copy_id];
		$newLayout['color'] = $this->GetRandColor();
		$newLayout['label'] = htmlspecialchars($_POST['label']);

		//get new unique layout id
		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );


		$gpLayoutsBefore = $gpLayouts;
		$gpLayouts[$layout_id] = $newLayout;

		if( !gpFiles::ArrayInsert($copy_id,$layout_id,$newLayout,$gpLayouts,1) ){
			message($langmessage['OOPS'].'(Not Inserted)');
			return;
		}


		//copy any css
		$css = $this->layoutCSS($copy_id);
		if( !empty($css) ){
			$path = $dataDir.'/data/_layouts/'.$layout_id.'/custom.css';
			if( !gpFiles::Save($path,$css) ){
				message($langmessage['OOPS'].' (CSS not saved)');
				return false;
			}
		}


		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].'(Not Saved)');
		}

	}


	/**
	 * Create a new unique layout label
	 * @static
	 */
	function NewLabel($label){
		global $gpLayouts;
		$labels = array();

		foreach($gpLayouts as $info){
			$labels[$info['label']] = true;
		}

		$len = strlen($label);
		if( $len > 15 ){
			$label = substr($label,0,$len-2);
		}
		if( substr($label,$len-2,1) === '_' && is_numeric(substr($label,$len-1,1)) ){
			$int = substr($label,$len-1,1);
			$label = substr($label,0,$len-2);
		}


		$int = 1;
		do{
			$new_label = $label.'_'.$int;
			$int++;
		}while( isset($labels[$new_label]) );

		return $new_label;
	}

	/**
	 * Preview a theme and give users the option of creating a new layout
	 *
	 */
	function PreviewTheme(){
		global $langmessage,$config,$page;

		$theme =& $_GET['theme'];
		$theme_info = $this->ThemeInfo($theme);
		if( $theme_info === false ){
			message($langmessage['OOPS']);
			return false;
		}

		$theme_id = dirname($theme);
		$template = $theme_info['folder'];
		$color = $theme_info['color'];
		$display = htmlspecialchars($theme_info['name'].' / '.$theme_info['color']);
		$display = str_replace('_',' ',$display);
		$this->LoremIpsum();
		$page->gpLayout = false;
		$page->theme_name = $template;
		$page->theme_color = $color;
		$page->theme_dir = $theme_info['full_dir'];
		$page->layout_css = false;
		$page->theme_rel = $theme_info['rel'].'/'.$color;

		if( isset($theme_info['id']) ){
			$page->theme_addon_id = $theme_info['id'];
		}

		$path = '/themes/';
		if( $theme_info['is_addon'] ){
			$path = '/data/_themes/';
		}
		$page->theme_path = common::GetDir($path.$page->theme_name.'/'.$page->theme_color);

		$page->show_admin_content = false;

		$this->ToolbarCSS();


		ob_start();
		echo '<div id="theme_toolbar"><div>';

		//theme_right
		echo '<div id="theme_right">';
		$this->LayoutSelect();
		echo '</div>';

		echo '<div id="theme_left">';
		echo '<div class="step"><b>';
		echo common::Link('Admin_Theme_Content',$langmessage['available_themes']);
		echo '</b></div>';

		echo '<div class="step">';
		$this->ThemeSelect($theme_id,$color);
		echo '</div>';

		echo '<div class="add_layout">';
		echo common::Link('Admin_Theme_Content',$langmessage['use_this_theme'],'cmd=newlayout&theme='.rawurlencode($theme),'data-cmd="gpabox"');
		echo '</div>';

		echo '</div>';

		echo '</div></div>';
		$page->admin_html = ob_get_clean();
		return true;
	}

	function LoremIpsum(){
		global $page, $langmessage, $gp_titles, $gp_menu;
		ob_start();
		echo '<h2>Lorem Ipsum H2</h2>';
		echo '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
		quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
		Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. </p>';
		echo '<h3>Lorem Ipsum H3</h3>';
		echo '<p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>';


		echo '<table>';
		echo '<tr><th>Lorem Ipsum Table Heading</th></tr>';
		echo '<tr><td>Lorem Ipsum Table Cell</td></tr>';
		echo '</table>';

		echo '<h4>Lorem Ipsum H4</h4>';
		echo '<blockquote>';
		echo 'Lorem Ipsum Blockquote';
		echo '</blockquote>';


		$page->non_admin_content = ob_get_clean();
	}




	function ThemeInfo($theme){

		$template = dirname($theme);
		$color = basename($theme);

		$themes = $this->GetPossible();
		if( !isset($themes[$template]) || !isset($themes[$template]['colors'][$color]) ){
			return false;
		}

		$theme_info = $themes[$template];
		$theme_info['color'] = $color;

		return $theme_info;
	}

	/**
	 * Return an array of available themes
	 * @return array
	 */
	function GetPossible(){
		global $dataDir,$dirPrefix;
		$themes = array();

		//packaged themes
		$dir = $dataDir.'/themes';
		$layouts = gpFiles::readDir($dir,1);
		foreach($layouts as $name){
			$full_dir = $dir.'/'.$name;
			$templateFile = $full_dir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}


			$ini_info = $this->GetAvailInstall($full_dir);

			$index = $name.'(package)';

			$themes[$index]['name'] = $name;
			$themes[$index]['colors'] = $this->GetThemeColors($full_dir);
			$themes[$index]['folder'] = $name;
			$themes[$index]['is_addon'] = false;
			$themes[$index]['full_dir'] = $full_dir;
			$themes[$index]['rel'] = '/themes/'.$name;
			if( isset($ini_info['Addon_Unique_ID']) ){
				$themes[$index]['id'] = $ini_info['Addon_Unique_ID'];
			}

		}

		//downloaded themes
		$dir = $dataDir.'/data/_themes';
		$layouts = gpFiles::readDir($dir,1);
		asort($layouts);
		foreach($layouts as $folder){
			$full_dir = $dir.'/'.$folder;
			$templateFile = $full_dir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}

			$ini_info = $this->GetAvailInstall($full_dir);

			$index = $ini_info['Addon_Name'].'(remote)';
			$themes[$index]['name'] = $ini_info['Addon_Name'];
			$themes[$index]['colors'] = $this->GetThemeColors($full_dir);
			$themes[$index]['folder'] = $folder;
			$themes[$index]['is_addon'] = true;
			$themes[$index]['full_dir'] = $full_dir;
			$themes[$index]['id'] = $ini_info['Addon_Unique_ID'];
			$themes[$index]['rel'] = '/data/_themes/'.$folder;
		}

		uksort($themes,'strnatcasecmp');

		return $themes;
	}

	function GetThemeColors($dir){
		$subdirs = gpFiles::readDir($dir,1);
		$colors = array();
		asort($subdirs);
		foreach($subdirs as $subdir){
			if( $subdir == 'images'){
				continue;
			}
			$colors[$subdir] = $subdir;
		}
		return $colors;
	}


	/**
	 * Save $layout as the default layout for the site
	 * @param string $layout
	 *
	 */
	function MakeDefault($layout){
		global $config,$langmessage,$gpLayouts,$page;

		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$oldConfig = $config;
		$config['gpLayout'] = $layout;

		if( admin_tools::SaveConfig() ){

			$page->SetTheme();
			$this->SetLayoutArray();

			message($langmessage['SAVED']);
		}else{
			$config = $oldConfig;
			message($langmessage['OOPS']);
		}
	}

	/**
	 * Remote a layout
	 *
	 */
	function DeleteLayoutConfirmed(){
		global $gpLayouts,$langmessage, $gp_titles;

		$gpLayoutsBefore = $gpLayouts;

		$layout =& $_POST['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		//remove from $gp_titles
		$this->RmLayout($layout);

		//save
		if( !admin_tools::SavePagesPHP() ){
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
			return false;
		}
		message($langmessage['SAVED']);

		//remove custom css
		$this->RemoveCSS($layout);
	}

	/**
	 * Save the color and label of a layout
	 *
	 */
	function LayoutDetails(){
		global $gpLayouts,$langmessage;

		$gpLayoutsBefore = $gpLayouts;

		$layout =& $_POST['layout'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !empty($_POST['color']) && (strlen($_POST['color']) == 7) && $_POST['color']{0} == '#' ){
			$gpLayouts[$layout]['color'] = $_POST['color'];
		}

		$gpLayouts[$layout]['label'] = htmlspecialchars($_POST['layout_label']);


		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}


	/**
	 * Show all layouts and themes
	 *
	 */
	function Show(){
		global $config,$page,$langmessage,$gpLayouts;


		$this->FindForm();

		echo '<h2 class="hmargin">';
		echo $langmessage['Manage Layouts'];
		echo ' <span>|</span> ';
		echo common::Link($this->path_remote,$this->find_label);
		echo '</h2>';

		echo '<table class="bordered full_width">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['usage'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '/';
			echo $langmessage['style'];
			echo '</th>';
			echo '</tr>';

		foreach($gpLayouts as $layout => $info){
			$this->LayoutRow($layout,$info);
		}

		echo '</table>';

		echo '<br/>';

		$this->ShowAvailable();


		echo '<p class="admin_note">';
		echo $langmessage['see_also'].' '.common::Link('Admin_Menu',$langmessage['file_manager']);
		echo '</p>';

		$this->ColorSelector();

	}

	/**
	 * Display the color selector for
	 * @param string $layout The layout being edited
	 *
	 */
	function ColorSelector($layout = false){

		$colors = $this->GetColors();
		echo '<div id="layout_ident" class="gp_floating_area">';
		echo '<div>';

		if( $layout ){
			echo '<form action="'.common::GetUrl('Admin_Theme_Content/'.$layout).'" method="post">';
		}else{
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		}
		echo '<input type="hidden" name="layout" value="" />';
		echo '<input type="hidden" name="color" value="" />';
		echo '<input type="hidden" name="cmd" value="layout_details" />';

		echo '<table>';


		echo '<tr>';
			echo '<td>';
			echo ' <a class="layout_color_id" id="current_color"></a> ';
			echo '<input type="text" name="layout_label" value="" maxlength="15"/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo '<div class="colors">';
			foreach($colors as $color){
				echo '<a class="color" style="background-color:'.$color.'" title="'.$color.'" data-arg="'.$color.'"></a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo ' <input type="submit" name="" value="Ok" class="gpsubmit"/>';
			echo ' <input type="button" class="cancel gpcancel" name="" value="Cancel" />';
			echo '</td>';
			echo '</tr>';

		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';

	}

	function ShowAvailable($show=true){
		global $langmessage,$config;
		$themes = $this->GetPossible();


		//versions available online
		includeFile('tool/update.php');
		update_class::VersionsAndCheckTime($new_versions);

		$class = $style = '';
		if( !$show ){
			$class = ' hidden';
			$style = ';display:none';
		}
		$avail_count = 0;
		foreach($themes as $theme_id => $info){
			$avail_count += count($info['colors']);
		}

		echo '<table class="bordered" style="width:100%'.$style.'">';
		echo '<tr><th colspan="3">'.$langmessage['available_themes'].': '.$avail_count.'</th>';
		$i=0;
		foreach($themes as $theme_id => $info){
			echo '<tr class="'.($i++ % 2 ? ' even' : '').'">';
			echo '<td>';
			echo str_replace('_',' ',$info['name']);
			echo '</td>';
			echo '<td>';
			$comma = '';
			foreach($info['colors'] as $color){
				echo $comma;
				echo common::Link('Admin_Theme_Content',$color,'cmd=preview&theme='.rawurlencode($theme_id.'/'.$color)); //,' data-cmd="creq" ');
				$comma = ', ';
			}

			echo '</td>';
			echo '<td>';
			if( isset($info['id']) ){
				echo common::Link('Admin_Theme_Content',$langmessage['rate'],'cmd=rate&arg='.rawurlencode($info['full_dir']));
				echo ' &nbsp; ';
			}else{
				echo '<span class="unavail">'.$langmessage['rate'].'</span>';
			}

			if( $info['is_addon'] ){

				if( isset($info['id']) ){
					$forum_id = 1000 + $info['id'];
					echo '<a href="'.addon_browse_path.'/Forum?show=f'.$forum_id.'" target="_blank">'.$langmessage['Support'].'</a>';
					echo ' &nbsp; ';
				}

				if( isset($info['id']) && isset($new_versions[$info['id']]) ){
					echo '<a href="'.addon_browse_path.'/Themes?id='.$info['id'].'" data-cmd="remote">';
					echo $langmessage['upgrade'].' (gpEasy.com)';
					echo '</a>';
					echo ' &nbsp; ';
				}
				$folder = $info['folder'];
				echo common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletetheme&folder='.rawurlencode($folder).'&label='.rawurlencode($theme_id),'data-cmd="gpabox"');

				if( isset($config['themes'][$folder]['order']) ){
					echo ' &nbsp; <span>Order: '.$config['themes'][$folder]['order'].'</span>';
				}
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}



	/**
	 * Show
	 */
	function LayoutRow($layout,$info){
		global $page, $langmessage, $config;
		static $i = 0;

		echo '<tr class="expand_row'.($i++ % 2 ? ' even' : '').'">';

		//label
			echo '<td class="nowrap">';
			echo '<a data-cmd="layout_id" title="'.$info['color'].'" data-arg="'.$info['color'].'">';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
			echo '<input type="hidden" name="layout_label" value="'.$info['label'].'"  /> ';
			echo '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span>';
			echo '&nbsp;';
			echo $info['label'];
			echo '</a>';


			//options
			echo '<div class="gp_options">';

			echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$langmessage['edit'],'',' title="'.htmlspecialchars($langmessage['Arrange Content']).'" ');
			echo ' &nbsp; ';

			echo common::Link('Admin_Theme_Content/'.rawurlencode($layout),$langmessage['details'],'cmd=details&show=main','data-cmd="gpabox"');
			echo ' &nbsp; ';

			echo common::Link('Admin_Theme_Content',$langmessage['Copy'],'cmd=copy&layout='.rawurlencode($layout),'data-cmd="gpabox"');
			echo ' &nbsp; ';

			if( $config['gpLayout'] == $layout ){
				echo '<span>'.$langmessage['delete'].'</span>';
			}else{
				$attr = array( 'data-cmd'=>'creq','class'=>'gpconfirm','title'=>sprintf($langmessage['generic_delete_confirm'],$info['label']) );
				echo common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletelayout&layout_id='.rawurlencode($layout),$attr);
			}

			echo '</div>';
			echo '</td>';

		//usage
			echo '<td class="nowrap">';
			if( $config['gpLayout'] == $layout ){
				echo '<b>'.$langmessage['default'].'</b>';
			}else{
				echo common::Link('Admin_Theme_Content',str_replace(' ','&nbsp;',$langmessage['default']),'cmd=makedefault&layout_id='.rawurlencode($layout),array('data-cmd'=>'creq','title'=>$langmessage['make_default']));
			}
			echo ' &nbsp; ';

			$titles_count = $this->TitlesCount($layout);
			echo sprintf($langmessage['%s Pages'],$titles_count);

			echo '</td>';

		//theme
			echo '<td class="nowrap">';
			if( isset($info['is_addon']) && $info['is_addon'] ){
				echo htmlspecialchars($info['theme_label']);
			}else{
				echo $info['theme'];
			}

			echo '</td>';


		echo '</tr>';

	}

	function TitlesCount($layout){
		$titles_count = 0;
		foreach( $this->LayoutArray as $layout_comparison ){
			if( $layout == $layout_comparison ){
				$titles_count++;
			}
		}
		return $titles_count;
	}

	/**
	 * Restore a layout to it's default content arrangement
	 */
	function Restore($layout){
		$this->SaveHandlersNew(array(),$layout);
	}

	function SaveHandlersNew($handlers,$layout=false){
		global $config,$page,$langmessage,$gpLayouts;

		//make sure the keys are sequential
		foreach($handlers as $container => $container_info){
			if( is_array($container_info) ){
				$handlers[$container] = array_values($container_info);
			}
		}

		if( $layout == false ){
			$layout = $this->curr_layout;
		}

		if( !isset( $gpLayouts[$layout] )  ){
			message($langmessage['OOPS']);
			return false;
		}

		$gpLayoutsBefore = $gpLayouts;
		if( count($handlers) === 0 ){
			unset($gpLayouts[$layout]['handlers']);
		}else{
			$gpLayouts[$layout]['handlers'] = $handlers;
		}

		if( admin_tools::SavePagesPHP() ){

			message($langmessage['SAVED']);

		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}


	function ParseHandlerInfo($str,&$info){
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


	function GetAllHandlers($layout=false){
		global $page,$gpLayouts, $config;

		if( $layout === false ){
			$layout = $this->curr_layout;
		}

		$handlers =& $gpLayouts[$layout]['handlers'];

		if( !is_array($handlers) || count($handlers) < 1 ){
			$gpLayouts[$layout]['hander_v'] = '2';
			$handlers = array();
		}

		//clean : characters for backwards compat
		foreach($handlers as $container => $container_info){
			if( is_string($container_info) ){
				$handlers[$container] = trim($container_info,':');
				continue;
			}
			if( !is_array($container_info) ){
				continue;
			}
			foreach($container_info as $key => $gpOutCmd){
				$handlers[$container][$key] = trim($gpOutCmd,':');
			}
		}

		return $handlers;
	}


	//set default values if not set
	function PrepContainerHandlers(&$handlers,$container,$gpOutCmd){
		if( isset($handlers[$container]) && is_array($handlers[$container]) ){
			return;
		}
		$handlers[$container] = $this->GetDefaultList($container,$gpOutCmd);
	}



	function GetDefaultList($container,$gpOutCmd){
		global $config, $gpOutConf;

		if( $container !== 'GetAllGadgets' ){

			//Just a container that doesn't have content by default
			// ex: 		gpOutput::Get('AfterContent');
			if( empty($gpOutCmd) ){
				return array();
			}

			return array($gpOutCmd);
		}

		$result = array();
		if( isset($config['gadgets']) && is_array($config['gadgets']) ){
			foreach($config['gadgets'] as $gadget => $info){
				if( isset($info['addon']) ){
					$result[] = $gadget;
				}
			}
		}
		return $result;
	}

	function GetValues($a,&$container,&$gpOutCmd){
		if( substr_count($a,'|') !== 1 ){
			return false;
		}

		list($container,$gpOutCmd) = explode('|',$a);
		return true;
	}

	function AddToContainer(&$container,$to_gpOutCmd,$new_gpOutCmd,$replace=true,$offset=0){
		global $langmessage;

		//unchanged?
		if( $replace && ($to_gpOutCmd == $new_gpOutCmd) ){
			return true;
		}


		//add to to_container in front of $to_gpOutCmd
		if( !isset($container) || !is_array($container) ){
			message($langmessage['OOPS'].' (a1)');
			return false;
		}

		//can't have two identical outputs in the same container
		$check = array_search($new_gpOutCmd,$container);
		if( ($check !== null) && ($check !== false) ){
			message($langmessage['OOPS']. ' (Area already in container)');
			return false;
		}

		//if empty, just add
		if( count($container) === 0 ){
			$container[] = $new_gpOutCmd;
			return true;
		}

		$length = 1;
		if( $replace === false ){
			$length = 0;
		}

		//insert
		$where = array_search($to_gpOutCmd,$container);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. ' (Destination Container Not Found)');
			return false;
		}
		$where += $offset;

		array_splice($container,$where,$length,$new_gpOutCmd);

		return true;
	}



	function SelectContent(){
		global $langmessage,$config,$gpOutConf;

		if( !isset($_GET['param']) ){
			message($langmessage['OOPS'].' (0)');
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

	function SelectContent_Areas($param,$count_gadgets){
		global $dataDir,$langmessage,$config,$gpOutConf;


		$slug = 'Admin_Theme_Content/'.rawurlencode($this->curr_layout);
		$addQuery = 'cmd=addcontent&where='.rawurlencode($param);
		echo '<div id="area_lists">';

			//extra content
			echo '<div id="layout_extra_content">';
			echo '<table class="bordered">';

				echo '<tr><th colspan="2">&nbsp;</th></tr>';

				$extrasFolder = $dataDir.'/data/_extra';
				$files = gpFiles::ReadDir($extrasFolder);
				asort($files);
				foreach($files as $file){
					$extraName = $file;
					echo '<tr>';
					echo '<td>';
					echo str_replace('_',' ',$extraName);
					echo '</td>';
					echo '<td class="add">';
					echo common::Link($slug,$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,'data-cmd="creq"');
					echo '</td>';
					echo '</tr>';
				}


				//new extra area
				echo '<tr><td>';
				echo '<form action="'.common::GetUrl($slug).'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="new_extra" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				echo '<input type="text" name="extra_area" value="" size="15" class="gpinput"/>';
				echo ' <input type="submit" name="" value="'.$langmessage['Add New Area'].'" class="gpbutton"/>';
				echo '</form>';
				echo '</td><td colspan="2" class="add">';
				echo '<form action="'.common::GetUrl($slug).'" method="post">';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
				echo '</form>';
				echo '</td></tr>';
				echo '</table>';

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
							echo common::Link($slug,$langmessage['add'],$addQuery.'&insert='.$gadget,'data-cmd="creq"');
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


				echo '<form action="'.common::GetUrl($slug).'" method="post">';
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
				echo '<form action="'.common::GetUrl($slug).'" method="post">';
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

	function NewCustomMenu(){

		$upper_bound =& $_POST['upper_bound'];
		$lower_bound =& $_POST['lower_bound'];
		$expand_bound =& $_POST['expand_bound'];
		$expand_all =& $_POST['expand_all'];
		$source_menu =& $_POST['source_menu'];

		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);

		$arg = $upper_bound.','.$lower_bound.','.$expand_bound.','.$expand_all.','.$source_menu;
		return 'CustomMenu:'.$arg;
	}

	function NewPresetMenu(){
		global $gpOutConf;

		$new_gpOutCmd =& $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutCmd]) || !isset($gpOutConf[$new_gpOutCmd]['link']) ){
			return false;
		}

		return rtrim($new_gpOutCmd.':'.$this->CleanMenu($_POST['source_menu']),':');
	}

	function CleanBounds(&$upper_bound,&$lower_bound,&$expand_bound,&$expand_all,&$source_menu){

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
	function CleanMenu($menu){
		global $config;

		if( empty($menu) ){
			return '';
		}
		if( !isset($config['menus'][$menu]) ){
			return '';
		}
		return $menu;
	}

	function PresetMenuForm($args = array()){
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


	function MenuArgs($curr_info){

		$menu_args = array();

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;

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


	function CustomMenuForm($arg = '',$menu_args = array()){
		global $langmessage;

		$upper_bound =& $menu_args['upper_bound'];
		$lower_bound =& $menu_args['lower_bound'];
		$expand_bound =& $menu_args['expand_bound'];
		$expand_all =& $menu_args['expand_all'];
		$source_menu =& $menu_args['source_menu'];


		echo '<table class="bordered">';

		$this->MenuSelect($source_menu);

		echo '<tr><th colspan="2">';
			echo $langmessage['Show Titles...'];
		echo '</th></tr>';

		echo '<tr><td>';
			echo $langmessage['... Below Level'];
			echo '</td><td class="add">';
			echo '<select name="upper_bound" class="gpselect">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $upper_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}
			echo '</select>';
			echo '</td></tr>';

		echo '<tr><td>';
			echo $langmessage['... At And Above Level'];
			echo '</td><td class="add">';
			echo '<select name="lower_bound" class="gpselect">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $lower_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}


			echo '</select>';
			echo '</td></tr>';

		echo '<tr><th colspan="2">';
			echo $langmessage['Expand Menu...'];
			echo '</th></tr>';

		echo '<tr><td>';
			echo $langmessage['... Below Level'];
			echo '</td><td class="add">';
			echo '<select name="expand_bound" class="gpselect">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $expand_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}

			echo '</select>';
			echo '</td></tr>';

		echo '<tr><td>';
			echo $langmessage['... Expand All'];
			echo '</td><td class="add">';
			$attr = '';
			if( $expand_all ){
				$attr = ' checked="checked"';
			}
			echo '<input type="checkbox" name="expand_all" '.$attr.'>';
			echo '</td></tr>';

	}

	function MenuSelect($source_menu){
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


	function AddContent(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

		if( !isset($_REQUEST['where']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//prep destination
		if( !$this->GetValues($_REQUEST['where'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].' (0)');
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
					message($langmessage['OOPS'].'(2)');
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
			message($langmessage['OOPS'].' (1)');
			return false;
		}

		//new info
		$new_gpOutInfo = gpOutput::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}
		$new_gpOutCmd = rtrim($new_gpOutInfo['key'].':'.$new_gpOutInfo['arg'],':');

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$new_gpOutCmd,false) ){
			return false;
		}

		$this->SaveHandlersNew($handlers);

		return true;
	}

	//return the name of the cleansed extra area name, create file if it doesn't already exist
	function NewExtraArea(){
		global $dataDir,$langmessage;

		if( empty($_POST['extra_area']) ){
			return false;
		}

		$extra_name = gp_edit::CleanTitle($_POST['extra_area']);
		$extra_file = $dataDir.'/data/_extra/'.$extra_name.'.php';

		if( file_exists($extra_file) ){
			return $extra_name;
		}

		$text = '<div>'.htmlspecialchars($_POST['extra_area']).'</div>';
		if( !gpFiles::SaveFile($extra_file,$text) ){
			return false;
		}

		return $extra_name;
	}


	function Drag(){
		global $page,$langmessage;

		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutCmd) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].'(1)');
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


		$where = array_search($from_gpOutCmd,$handlers[$from_container]);
		$to = array_search($to_gpOutCmd,$handlers[$from_container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(3)');
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
			&& ($to !== null)
			&& ($to !== false)
			&& $to > $where ){
				$offset = 1;
		}

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$from_gpOutCmd,false,$offset) ){
			return;
		}

		$this->SaveHandlersNew($handlers);

	}


	function RemoveArea(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

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
		$where = array_search($gpOutCmd,$handlers[$container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}

		array_splice($handlers[$container],$where,1);
		$this->SaveHandlersNew($handlers);

	}


	function SelectLinks(){
		global $langmessage,$gpLayouts,$gpOutConf;

		$layout =& $_REQUEST['layout'];

		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['00PS']);
			return;
		}


		$showCustom = false;
		$current_function = false;
		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}else{
			$current_function = $curr_info['key'];
		}

		$menu_args = $this->MenuArgs($curr_info);


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
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
			echo '<input type="hidden" name="cmd" value="savelinks" />';

			echo '<table class="bordered">';
			$this->PresetMenuForm($menu_args);

			echo '<tr><td class="add" colspan="2">';
				echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
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
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
			echo '<input type="hidden" name="cmd" value="savelinks" />';

			$this->CustomMenuForm($curr_info['arg'],$menu_args);

			echo '<tr><td class="add" colspan="2">';
				echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
				echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

			echo '<p class="admin_note">';
			echo $langmessage['see_also'];
			echo ' ';
			echo common::Link('Admin_Menu',$langmessage['file_manager']);
			echo ', ';
			echo common::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
			echo '</p>';

		echo '</div>';
		echo '</div>';

	}


	function SaveLinks(){
		global $config,$langmessage,$gpOutConf,$gpLayouts;


		$layout =& $_POST['layout'];

		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}


		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}



		if( isset($_POST['new_handle']) ){
			$new_gpOutCmd = $this->NewPresetMenu();
		}else{
			$new_gpOutCmd = $this->NewCustomMenu();
		}

		if( !$new_gpOutCmd ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}


		//prep
		$handlers = $this->GetAllHandlers($layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers,$container,$curr_info['gpOutCmd']);


		if( !$this->AddToContainer($handlers[$container],$curr_info['gpOutCmd'],$new_gpOutCmd,true) ){
			return;
		}

		$this->SaveHandlersNew($handlers,$layout);


		//message('not forwarding');
		$this->ReturnHeader();
	}


	function ReturnHeader(){

		if( empty($_POST['return']) ){
			return;
		}


		$return = trim($_POST['return']);
		if( strpos($return,'http') !== 0 ){
			$return = common::GetUrl($return,'',false);
		}
		common::Redirect($return,302);
	}



	function GetAddonTexts($addon){
		global $dataDir,$langmessage,$config;

		$addonDir = $dataDir.'/data/_addoncode/'.$addon;
		if( !is_dir($addonDir) ){
			return false;
		}

		//not set up correctly
		if( !isset($config['addons'][$addon]['editable_text']) ){
			return false;
		}

		$file = $addonDir.'/'.$config['addons'][$addon]['editable_text'];
		if( !file_exists($file) ){
			return false;
		}

		include($file);
		if( !isset($texts) || !is_array($texts) || (count($texts) == 0 ) ){
			return false;
		}

		return $texts;
	}


	function SaveAddonText(){
		global $dataDir,$langmessage,$config;

		$addon = gp_edit::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);
		//not set up correctly
		if( $texts === false ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		$configBefore = $config;
		foreach($texts as $text){
			if( !isset($_POST['values'][$text]) ){
				continue;
			}


			$default = $text;
			if( isset($langmessage[$text]) ){
				$default = $langmessage[$text];
			}

			$value = htmlspecialchars($_POST['values'][$text]);

			if( ($value === $default) || (htmlspecialchars($default) == $value) ){
				unset($config['customlang'][$text]);
			}else{
				$config['customlang'][$text] = $value;
			}
		}

		if( !admin_tools::SaveConfig() ){
			//these two lines are fairly useless when the ReturnHeader() is used
			$config = $configBefore;
			message($langmessage['OOPS'].' (1)');
		}else{

			$this->UpdateAddon($addon);

			message($langmessage['SAVED']);

		}

		$this->ReturnHeader();
	}

	function UpdateAddon($addon){
		if( !function_exists('OnTextChange') ){
			return;
		}

		gpPlugin::SetDataFolder($addon);

		OnTextChange();

		gpPlugin::ClearDataFolder();
	}

	function AddonText(){
		global $dataDir,$langmessage,$config;

		$addon = gp_edit::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);

		//not set up correctly
		if( $texts === false ){
			$this->EditText();
			return;
		}


		echo '<div class="inline_box" style="text-align:right">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="saveaddontext" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />'; //will be populated by javascript


		$this->AddonTextFields($texts);
		echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';


		echo '</form>';
		echo '</div>';

	}

	function AddonTextFields($array){
		global $langmessage,$config;
		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';

		$key =& $_GET['key'];
		foreach($array as $text){

			$default = $value = $text;
			if( isset($langmessage[$text]) ){
				$default = $value = $langmessage[$text];
			}
			if( isset($config['customlang'][$text]) ){
				$value = $config['customlang'][$text];
			}

			$style = '';
			if( $text == $key ){
				$style = ' style="background-color:#f5f5f5"';
			}

			echo '<tr'.$style.'>';
			echo '<td>';
			echo $text;
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="values['.htmlspecialchars($text).']" value="'.$value.'" class="gpinput"/>'; //value has already been escaped with htmlspecialchars()
			echo '</td>';
			echo '</tr>';

		}
		echo '</table>';
	}





	function EditText(){
		global $config, $langmessage,$page;

		if( !isset($_GET['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		$default = $value = $key = $_GET['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];

		}
		if( isset($config['customlang'][$key]) ){
			$value = $config['customlang'][$key];
		}


		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="savetext" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($key).'" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript

		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>';
			echo $default;
			echo '</td>';
			echo '<td>';
			//$value is already escaped using htmlspecialchars()
			echo '<input type="text" name="value" value="'.$value.'" class="gpinput"/>';
			echo '<p>';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit"/>';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</p>';
			echo '</td>';
			echo '</tr>';
		echo '</table>';

		echo '</form>';
		echo '</div>';
	}



	function SaveText(){
		global $config, $langmessage,$page;

		if( !isset($_POST['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !isset($_POST['value']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}

		$default = $key = $_POST['key'];
		if( isset($langmessage[$key]) ){
			$default = $langmessage[$key];
		}

		$config['customlang'][$key] = $value = htmlspecialchars($_POST['value']);
		if( ($value === $default) || (htmlspecialchars($default) == $value) ){
			unset($config['customlang'][$key]);
		}

		if( admin_tools::SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS'].' (s1)');
		}
		$this->ReturnHeader();

	}

	function SetLayoutArray(){
		global $gp_menu, $gp_titles, $gp_index, $config;


		$titleThemes = array();
		$customThemes = array();
		$customThemeLevel = 0;
		$max_level = 5;


		foreach($gp_menu as $id => $info){

			$level = $info['level'];

			//reset theme inheritance
			$max_level = max($max_level,$level);
			for( $i = $level; $i <= $max_level; $i++){
				if( isset($customThemes[$i]) ){
					$customThemes[$i] = false;
				}
			}

			if( !empty($gp_titles[$id]['gpLayout']) ){
				$titleThemes[$id] = $gp_titles[$id]['gpLayout'];
			}else{

				$parent_layout = false;
				$temp_level = $level;
				while( $temp_level >= 0 ){
					if( isset($customThemes[$temp_level]) && ($customThemes[$temp_level] !== false) ){
						$titleThemes[$id] = $parent_layout = $customThemes[$temp_level];
						break;
					}
					$temp_level--;
				}

				if( $parent_layout === false ){
					$titleThemes[$id] = $config['gpLayout'];
				}
			}

			$customThemes[$level] = $titleThemes[$id];
		}


		foreach($gp_index as $title => $id){
			$titleInfo = $gp_titles[$id];

			if( isset($titleThemes[$id]) ){
				continue;
			}

			if( !empty($titleInfo['gpLayout']) ){
				$titleThemes[$id] = $titleInfo['gpLayout'];
			}else{
				$titleThemes[$id] = $config['gpLayout'];
			}

		}


		$this->LayoutArray = $titleThemes;

	}


	/*
	 * Remote Themes
	 *
	 *
	 */


	function DeleteTheme(){
		global $langmessage, $dataDir;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';

		$can_delete = true;
		$theme_folder_name =& $_GET['folder'];
		$theme_folder = $dataDir.'/data/_themes/'.$theme_folder_name;
		if( empty($theme_folder_name) || !ctype_alnum($theme_folder_name) || empty($_GET['label']) ){
			echo $langmessage['OOPS'];
			$can_delete = false;
		}

		if( !$this->CanDeleteTheme($theme_folder_name,$message) ){
			echo $message;
			$can_delete = false;
		}

		if( $can_delete ){
			$label = htmlspecialchars($_GET['label']);
			echo '<input type="hidden" name="cmd" value="delete_theme_confirmed" />';
			echo '<input type="hidden" name="folder" value="'.htmlspecialchars($theme_folder_name).'" />';
			echo sprintf($langmessage['generic_delete_confirm'], '<i>'.$label.'</i>');
		}

		echo '<p>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Finalize theme removal
	 *
	 */
	function DeleteThemeConfirmed(){
		global $langmessage, $dataDir, $gpLayouts, $config;

		$gpLayoutsBefore = $gpLayouts;
		$can_delete = true;
		$theme_folder_name =& $_POST['folder'];
		$theme_folder = $dataDir.'/data/_themes/'.$theme_folder_name;
		if( empty($theme_folder_name) || !ctype_alnum($theme_folder_name) || !isset($config['themes'][$theme_folder_name]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$order = false;
		if( isset($config['themes'][$theme_folder_name]['order']) ){
			$order = $config['themes'][$theme_folder_name]['order'];
		}

		if( !$this->CanDeleteTheme($theme_folder_name,$message) ){
			message($message);
			return false;
		}


		//remove layouts
		foreach($gpLayouts as $layout_id => $layout_info){

			if( !isset($layout_info['is_addon']) || !$layout_info['is_addon'] ){
				continue;
			}
			$layout_folder = dirname($layout_info['theme']);
			if( $layout_folder == $theme_folder_name ){
				$this->RmLayout($layout_id);
			}
		}


		//delete the folder
		$dir = $dataDir.'/data/_themes/'.$theme_folder_name;
		gpFiles::RmAll($dir);

		//remove from settings
		unset($config['themes'][$theme_folder_name]);

		if( admin_tools::SaveAllConfig() ){
			message($langmessage['SAVED']);
			if( $order ){
				$img_path = common::IdUrl('ci');
				common::IdReq($img_path);
			}
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}

	}

	function RmLayout($layout){
		global $gp_titles,$gpLayouts;

		foreach($gp_titles as $title => $titleInfo){
			if( isset($titleThemes[$title]) ){
				continue;
			}
			if( empty($titleInfo['gpLayout']) ){
				continue;
			}

			if( $titleInfo['gpLayout'] == $layout ){
				unset($gp_titles[$title]['gpLayout']);
			}
		}
		unset($gpLayouts[$layout]);
	}


	function CanDeleteTheme($folder,&$message){
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


	/**
	 * Show images available in themes
	 *
	 */
	function ShowThemeImages(){
		global $page,$langmessage,$dirPrefix;
		$page->ajaxReplace = array();
		$themes = $this->GetPossible();
		$current_theme = false;

		//which theme folder
		if( isset($_REQUEST['theme']) && isset($themes[$_REQUEST['theme']]) ){
			$current_theme = $_REQUEST['theme'];
			$current_info = $themes[$current_theme];
			$current_label = $current_info['name'];
			$current_dir = $current_info['full_dir'];
			$current_url = common::GetDir($current_info['rel']);

		//current layout
		}else{
			$layout_info = common::LayoutInfo($this->curr_layout,false);
			$current_label = $layout_info['theme_name'];
			$current_dir = $layout_info['dir'];
			$current_url = common::GetDir(dirname($layout_info['path']));
		}


		//list of themes
		ob_start();
		echo '<div class="gp_edit_select ckeditor_control">';
		echo '<a class="gp_selected_folder"><span class="folder"></span>';
		echo $current_label;
		echo '</a>';

		echo '<div class="gp_edit_select_options">';

		foreach($themes as $theme_id => $info){
			echo common::Link('Admin_Theme_Content/'.rawurlencode($this->curr_layout),'<span class="folder"></span>'.$info['name'],'cmd=theme_images&theme='.rawurlencode($theme_id),' data-cmd="gpajax" class="gp_gallery_folder" ');
		}
		echo '</div>';
		echo '</div>';

		$gp_option_area = ob_get_clean();


		//images in theme
		includeFile('tool/Images.php');
		self::GetAvailThemeImages( $current_dir, $current_url, $images );
		ob_start();
		foreach($images as $image ){
			echo '<span class="expand_child">'
				. '<a href="'.$image['url'].'" data-cmd="gp_gallery_add" data-width="'.$image['width'].'" data-height="'.$image['height'].'">'
				. '<img src="'.$image['url'].'" alt=""/>'
				. '</a></span>';
		}
		$gp_gallery_avail_imgs = ob_get_clean();


		if( $current_theme ){
			$page->ajaxReplace[] = array('inner','#gp_option_area',$gp_option_area);
			$page->ajaxReplace[] = array('inner','#gp_gallery_avail_imgs',$gp_gallery_avail_imgs);
		}else{
			$content = '<div id="gp_option_area">'.$gp_option_area.'</div>'
						.'<div id="gp_gallery_avail_imgs">'.$gp_gallery_avail_imgs.'</div>';
			$page->ajaxReplace[] = array('inner','#gp_image_area',$content);
		}

		$page->ajaxReplace[] = array('inner','#gp_folder_options',''); //remove upload button
	}

	/**
	 * Load the inline editor for a theme image
	 *
	 */
	function InlineEdit(){

		$section = array();
		$section['type'] = 'image';
		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($section);
		die();
	}

	/**
	 * Output content for use with the inline image editor
	 *
	 */
	function ImageEditor(){
		global $page,$dataDir,$langmessage;
		$page->ajaxReplace = array();

		//image options
		ob_start();

		echo '<div id="gp_current_image">';
		echo '<input type="hidden" name="orig_height">';
		echo '<input type="hidden" name="orig_width">';
		echo '<span id="gp_image_wrap"><img/></span>';
		echo '<table>';
		echo '<tr><td>'.$langmessage['Width'].'</td><td><input type="text" name="width" class="ck_input"/></td>';
		echo '<td>'.$langmessage['Height'].'</td><td><input type="text" name="height" class="ck_input"/></td>';
		echo '<td><a data-cmd="deafult_sizes" class="ckeditor_control ck_reset_size" title="'.$langmessage['Theme_default_sizes'].'">&#10226;</a></td>';
		echo '</tr>';
		echo '<tr><td>'.$langmessage['Left'].'</td><td><input type="text" name="left" class="ck_input" value="0"/></td>';
		echo '<td>'.$langmessage['Top'].'</td><td><input type="text" name="top" class="ck_input" value="0"/></td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';

		echo '<div id="gp_source_options">';
		echo '<b>'.$langmessage['Select Image'].'</b>';
		echo common::Link('Admin_Theme_Content/'.rawurlencode($this->curr_layout),$langmessage['Theme Images'],'cmd=theme_images',' data-cmd="gpajax" class="ckeditor_control half_width" ');
		echo '<a class="ckeditor_control half_width" data-cmd="show_uploaded_images">'.$langmessage['uploaded_files'].'</a>';
		echo '</div>';

		echo '<div id="gp_image_area"></div><div id="gp_upload_queue"></div>';

		$content = ob_get_clean();

		$page->ajaxReplace[] = array('inner','#ckeditor_top',$content);
		$page->ajaxReplace[] = array('image_options_loaded','',''); //tell the script the images have been loaded
	}


	function GalleryImages(){
		$_GET += array('dir'=>'/headers');
		includeFile('admin/admin_uploaded.php');
		admin_uploaded::InlineList($_GET['dir'],false);
	}

	function SaveHeaderImage(){
		global $page, $dataDir, $dirPrefix, $langmessage;
		includeFile('tool/Images.php');
		includeFile('tool/editing.php');
		$page->ajaxReplace = array();
		//$dest_dir = $dataDir.'/data/_layouts/'.$this->curr_layout; //Not used anywhere.


		//source file
		$source_file_rel = $_REQUEST['file'];
		if( !empty($_REQUEST['src']) ){
			$source_file_rel = rawurldecode($_REQUEST['src']);
			if( !empty($dirPrefix) ){
				$len = strlen($dirPrefix);
				$source_file_rel = substr($source_file_rel,$len);
			}
		}
		$source_file_rel = '/'.ltrim($source_file_rel,'/');
		$source_file_full = $dataDir.$source_file_rel;
		if( !file_exists($source_file_full) ){
			message($langmessage['OOPS'].' (Source file not found)');
			return;
		}
		$src_img = thumbnail::getSrcImg($source_file_full);
		if( !$src_img ){
			message($langmessage['OOPS'].' (Couldn\'t create image [1])');
			return;
		}


		//size and position variables
		$orig_w = $width = imagesx($src_img);
		$orig_h = $height = imagesy($src_img);
		$posx = $posy = 0;
		if( isset($_REQUEST['posx']) && is_numeric($_REQUEST['posx']) ){
			$posx = $_REQUEST['posx'];
		}
		if( isset($_REQUEST['posy']) && is_numeric($_REQUEST['posy']) ){
			$posy = $_REQUEST['posy'];
		}
		if( isset($_REQUEST['width']) && is_numeric($_REQUEST['width']) ){
			$width = $_REQUEST['width'];
		}
		if( isset($_REQUEST['height']) && is_numeric($_REQUEST['height']) ){
			$height = $_REQUEST['height'];
		}


		//check to see if the image needs to be resized
		if( $posx == 0
			&& $posy == 0
			&& $width == $orig_w
			&& $height == $orig_h
			){
				$this->SetImage($source_file_rel,$width,$height);
				return;
		}

		//destination file
		$name = basename($source_file_rel);
		$parts = explode('.',$name);
		$type = array_pop($parts);
		if( count($parts) > 1 ){
			$time_part = array_pop($parts);
			if( !ctype_digit($time_part) ){
				$parts[] = $time_part;
			}
		}
		$name = implode('.',$parts);
		$time = time();
		if( isset($_REQUEST['time']) && ctype_digit($_REQUEST['time']) ){
			$time = $_REQUEST['time'];
		}
		//$dest_img_rel = '/data/_uploaded/headers/'.$name.'.'.$time.'.'.$type;
		$dest_img_rel = '/data/_uploaded/headers/'.$name.'.'.$time.'.png';
		$dest_img_full = $dataDir.$dest_img_rel;

		//make sure the folder exists
		if( !gpFiles::CheckDir( dirname($dest_img_full) ) ){
			message($langmessage['OOPS'].' (Couldn\'t create directory)');
			return false;
		}

		if( !thumbnail::createImg($src_img, $dest_img_full, $posx, $posy, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h, $width, $height) ){
			message($langmessage['OOPS'].' (Couldn\'t create image [2])');
			return;
		}

		if( $this->SetImage($dest_img_rel,$width,$height) ){
			includeFile('admin/admin_uploaded.php');
			admin_uploaded::CreateThumbnail($dest_img_full);
		}

	}

	function SetImage($img_rel,$width,$height){
		global $gpLayouts,$langmessage,$page;


		$save_info = array();
		$save_info['img_rel'] = $img_rel;
		$save_info['width'] = $width;
		$save_info['height'] = $height;

		$container = $_REQUEST['container'];
		//$gpLayouts[$this->curr_layout]['images'] = array(); //prevents shuffle - REMOVED to allow images per container to be saved.
		$gpLayouts[$this->curr_layout]['images'][$container] = array(); //prevents shuffle
		$gpLayouts[$this->curr_layout]['images'][$container][] = $save_info;

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (Data not saved)');
			return false;
		}
		$page->ajaxReplace[] = array('ck_saved','','');
		return true;
	}


	/**
	 * Get a list of all the images available within a theme
	 *
	 */
	function GetAvailThemeImages( $dir, $url, &$images ){
		$files = scandir($dir);
		$files = array_diff($files,array('.','..'));
		foreach($files as $file){

			$file_full = $dir.'/'.$file;
			$file_url = $url.'/'.$file;
			if( is_dir($file_full) ){
				self::GetAvailThemeImages( $file_full, $file_url, $images );
				continue;
			}

			if( !self::IsImg( $file ) ){
				continue;
			}

			$size = getimagesize($file_full);

			$temp = array();
			$temp['width'] = $size[0];
			$temp['height'] = $size[1];
			$temp['full'] = $file_full;
			$temp['url'] = $file_url;
			$images[] = $temp;
		}
	}


	/**
	 * Determines if the $file is an image based on the file extension
	 * @static
	 * @return bool
	 */
	static function IsImg($file){
		$img_types = array('bmp','png','jpg','jpeg','gif','tiff','tif');

		$name_parts = explode('.',$file);
		$file_type = array_pop($name_parts);
		$file_type = strtolower($file_type);

		return in_array($file_type,$img_types);
	}

}

