<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Edit extends \gp\admin\Layout{

	protected $layout_request = true;
	protected $layout_slug;


	public function RunScript(){
		global $gpLayouts, $config, $gpAdmin;

		//layout request
		$parts		= explode('/', $this->page->requested);

		// prevent opening layout editor by locked users
		if( isset($gpAdmin['locked']) && $gpAdmin['locked'] ){
			$url = \gp\tool::GetUrl(isset($_REQUEST['redir']) ? $_REQUEST['redir'] : 'Admin');
			\gp\tool::Redirect($url);
		}

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
		$url = \gp\tool::GetUrl('Admin_Theme_Content', '', false);
		\gp\tool::Redirect($url, 302);
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
			msg($langmessage['OOPS'] . ' (Theme Not Found)');
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
		$this->layout_slug = 'Admin_Theme_Content/Edit/' . rawurlencode($this->curr_layout);

		$this->cmds['ShowThemeImages']	= '';
		$this->cmds['SelectContent']	= '';

		$this->cmds['LayoutMenu']		= '';
		$this->cmds['LayoutMenuSave']	= 'ReturnHeader';

		//show the layout (displayed within an iframe)
		$this->cmds['SaveChanges']		= 'ShowInIframe';
		$this->cmds['PreviewChanges']	= 'ShowInIframe';
		$this->cmds['addcontent']		= 'ShowInIframe';
		$this->cmds['RemoveArea']		= 'ShowInIframe';
		$this->cmds['DragArea']			= 'ShowInIframe';
		$this->cmds['in_iframe']		= 'ShowInIframe';

		\gp\tool\Plugins::Action('edit_layout_cmd', [$this->curr_layout]);

		$cmd = \gp\tool::GetCommand();

		$this->LayoutCommands();
		$this->RunCommands($cmd);
	}


	public function DefaultDisplay(){
		global $langmessage;

		$layout_info		= \gp\tool::LayoutInfo($this->curr_layout, false);
		$this->page->label	= $langmessage['layouts'] . ' » ' . $layout_info['label'];

		$this->LayoutEditor($this->curr_layout, $layout_info);
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
		$this->page->head .= '<script type="text/javascript">' .
			'if( typeof(parent.$gp) == "object" && typeof(parent.$gp.iframeloaded()) == "function" ){ ' .
			'parent.$gp.iframeloaded(); ' .
			'}</script>';
		if( $cmd != 'PreviewCSS' ){
			$this->page->head .= '<script type="text/javascript">var gpLayouts=true;</script>';
		}
	}


	/**
	 * Display the toolbar for layout editing
	 *
	 */
	public function LayoutEditor($layout, $layout_info){
		global $langmessage, $gpAdmin;

		$_REQUEST						+= ['gpreq' => 'body']; //force showing only the body as a complete html document
		$this->page->get_theme_css		= false;
		\gp\admin\Tools::$show_toolbar	= false;

		\gp\tool::LoadComponents('colorpicker');

		$this->page->css_user[]			= '/include/thirdparty/codemirror/lib/codemirror.css';
		$this->page->head_js[]			= '/include/thirdparty/codemirror/lib/codemirror.js';
		$this->page->head_js[]			= '/include/thirdparty/codemirror/mode/css/css.js';
		$this->page->head_js[]			= '/include/thirdparty/codemirror/addon/display/placeholder.js';

		$this->page->css_admin[]		= '/include/css/theme_content_outer.scss';
		$this->page->head_js[]			= '/include/js/theme_content_outer.js';
		$this->page->head_js[]			= '/include/js/layout_editor.js';

		//custom css
		$css							= $this->GetLayoutCSS($this->curr_layout);
		$dir							= $layout_info['dir'] . '/' . $layout_info['theme_color'];
		$style_type						= \gp\tool\Output::StyleType($dir);

		$style_type_info = [];
		switch($style_type){
			case 'scss':
				$style_type_info['name'] = 'Scss';
				$style_type_info['link'] = 'https://sass-lang.com/';
				break;

			case 'less':
				$style_type_info['name'] = 'Less';
				$style_type_info['link'] = 'http://lesscss.org/';
				break;

			case 'css':
			default:
				$style_type_info['name'] = 'CSS';
				$style_type_info['link'] = 'https://developer.mozilla.org/docs/Web/CSS';
		}

		//Iframe
		echo '<div id="gp_iframe_wrap">';
		$url = \gp\tool::GetUrl('Admin_Theme_Content/Edit/' . rawurlencode($layout), 'cmd=in_iframe');
		echo '<iframe src="' . $url . '" id="gp_layout_iframe" name="gp_layout_iframe"></iframe>';
		echo '</div>';

		//CSS Editing
		ob_start();
		$form_action = \gp\tool::GetUrl(
			'Admin_Theme_Content/Edit/' . $this->curr_layout, 'cmd=in_iframe'
		);
		echo '<div id="theme_editor">';
		echo '<form id="layout_editor_form" action="' . $form_action . '" method="post" ';
		echo	'class="gp_scroll_area full_height" target="gp_layout_iframe">';

		//selects
		echo '<div>';

		//layout
		echo	'<div class="layout_select">';
		$this->LayoutSelect($layout, $layout_info);
		echo	'</div>';

		//options
		echo	'<div>';
		echo		'<div class="dd_menu">';
		echo			'<a data-cmd="dd_menu">' . $langmessage['Layout Options'] . '</a>';
		echo			'<div class="dd_list">';
		echo				'<ul>';
		$this->LayoutOptions($layout, $layout_info);
		echo				'</ul>';
		echo			'</div>';
		echo		'</div>';
		echo	'</div>';

		echo '</div>';

		//editor/customizer tabs
		echo '<div class="layout_editor_tabs no_padding_y">';

		$active_class = ' active'; // first tab is active


		//customizer tab
		$this->customizer_data	= $this->GetLayoutCustomizer($layout);
		$this->layout_config	= $this->GetLayoutConfig($layout);

		if( !empty($this->customizer_data) ){
			echo	'<span data-rel="customizer_area" class="tab_switch' . $active_class . '">';
			echo		'<span class="is_dirty_indicator"';
			echo			' title="' . $langmessage['Modified'] . '"></span>';
			echo		$langmessage['Customizer'];
			echo	'</span>';
			$active_class = '';
		}

		//css editor tab
		echo	'<span data-rel="css_editor_area" class="tab_switch' . $active_class . '">';
		echo		'<span class="is_dirty_indicator"></span>';
		echo		$style_type_info['name'];
		echo	'</span>';
		$active_class = '';

		echo '</div>';


		//editor/customizer area (flex-grows)
		echo '<div class="full_height no_padding_top">';

		if( !empty($this->customizer_data) ){
			echo '<div class="customizer_area active">';
			echo	'<div>';
			$this->Customizer();
			echo	'</div>';
			echo '</div>';// /.customizer_area
		}

		//editor area (flex-grows)
		echo '<div class="css_editor_area">';
		if( empty($css) ){
			$var_file = $dir . '/variables.' . $style_type;
			if( file_exists($var_file) ){
				$css = file_get_contents($var_file);
			}
		}

		$style_type_hint = $style_type != 'css' ?
			strtoupper($style_type) . ' / CSS' :
			strtoupper($style_type);

		$placeholder = sprintf($langmessage['Add X here'], $style_type_hint);

		echo	'<textarea name="css" id="gp_layout_css" class="gptextarea" ';
		echo		'placeholder="' . htmlspecialchars($placeholder) . '" ';
		echo		'wrap="off" data-mode="' . htmlspecialchars($style_type) . '">';
		echo		htmlspecialchars($css);
		echo	'</textarea>';
		// info link
		echo	'<a class="css_editor_langinfo"';
		echo		' title="' . $langmessage['about'] . ': ' . $style_type_info['name'] . '"';
		echo		' href="' . $style_type_info['link'] . '" target="_blank">';
		echo		'<i class="fa fa-question-circle">&zwnj;</i>';
		echo	'</a>';

		echo '</div>';// /.css_editor_area

		echo '</div>';// /.full_height


		//hidden inputs to indicate which parts should be saved
		echo '<input id="gp_layout_save_css" type="hidden" name="save_css" value="" />';
		echo '<input id="gp_layout_save_customizer" type="hidden" name="save_customizer" value="" />';

		//buttons
		echo '<div class="css_buttons">';

		//preview
		echo	'<button name="cmd" type="submit" value="PreviewChanges"';
		echo		' class="gpvalidate gpsubmit gpdisabled" disabled="disabled"';
		echo		' data-cmd="preview_changes">';
		echo		$langmessage['preview'];
		echo	'</button>';

		//save
		echo	'<button name="cmd" type="submit" value="SaveChanges"';
		echo		' class="gpvalidate gpsubmit gpdisabled" disabled="disabled"';
		echo		' data-cmd="save_changes">';
		echo		$langmessage['save'];
		echo	'</button>';

		//reset
		echo	'<button';
		echo 		' class="gpcancel gpdisabled" disabled="disabled"';
		echo 		' data-cmd="reset_changes">';
		echo 		$langmessage['Reset'];
		echo 	'</button>';

		//cancel
		$cancel_url = !empty($_REQUEST['redir']) ? $_REQUEST['redir'] : 'Admin_Theme_Content';
		echo \gp\tool::Link(
				$cancel_url,
				$langmessage['Close'],
				'',
				'class="gpcancel"'
			);

		echo '</div>'; // /.css_buttons

		echo '</form>';

		echo '</div>'; // /#theme_editor

		$this->page->admin_html = ob_get_clean();
	}


	/**
	 * Display all the layouts available in a <select>
	 *
	 */
	public function LayoutSelect($curr_layout=false, $curr_info=false){
		global $gpLayouts, $langmessage, $config;

		$display = $langmessage['available_layouts'];
		if( $curr_layout ){
			$display = '<span class="layout_color_id" ' .
				'style="background-color:' . $curr_info['color'] . ';">' .
				'</span> &nbsp; ' . $curr_info['label'];
		}

		echo '<div><div class="dd_menu">';
		echo '<a data-cmd="dd_menu">' . $display . '</a>';

		echo '<div class="dd_list"><ul>';
		foreach($gpLayouts as $layout => $info){
			$attr = '';
			if( $layout == $curr_layout){
				$attr = ' class="selected"';
			}
			echo '<li' . $attr . '>';

			$display = '<span class="layout_color_id" ' .
				'style="background-color:' . $info['color'] . ';">' .
				'</span> &nbsp; ' . $info['label'];
			if( $config['gpLayout'] == $layout ){
				$display .= ' <span class="layout_default"> (' . $langmessage['default'] . ')</span>';
			}
			echo \gp\tool::Link('Admin_Theme_Content/Edit/' . rawurlencode($layout), $display);
			echo '</li>';
		}
		echo '</ul></div>';
		echo '</div></div>';
	}


	/**
	 * Output the Customizer
	 * @since 5.2
	 * @return bool customizer available
	 *
	 */
	public function Customizer(){
		global $page, $langmessage;

		\gp\tool::LoadComponents('colorpicker');

		// debug('$layout config = ' . pre($this->layout_config));
		// debug('$customizer_data = ' . pre($this->customizer_data));

		if( empty($this->layout_config) ){
			echo '<div css="gp_warning">Error: Layout config not defined</div>';
			return false;
		}

		if( empty($this->customizer_data) ){
			echo '<div css="gp_warning">Error: Customizer data not defined</div>';
			return false;
		}

		// echo '<form id="customizer_form">';

		foreach($this->customizer_data as $section => $section_data){

			$collapsed_class = !empty($section_data['collapsed']) ? ' collapsed' : '';

			echo '<div class="customizer_section' . $collapsed_class . '">';
			echo	'<a class="customizer_section_label" data-cmd="toggle_customizer_section">';
			echo		htmlspecialchars($section_data['label']);
			echo	'</a>';

			echo '<div class="customizer_controls">';
			foreach($section_data['items'] as $item_name => $item_data){

				if( empty($item_data['control']) ){
					echo '<div class="gp_warning">Error: Control definition not found for value ';
					echo	'<em>' . htmlspecialchars($item_name) . '</em>';
					echo '</div>';
					continue;
				}

				if( !isset($item_data['control']['type']) ){
					echo '<div class="gp_warning">Error: Can not create control of type ';
					echo	'<em>' . htmlspecialchars($control['type']) . '</em>';
					echo '</div>';
					continue;
				}

				$this->CustomizerControl($item_name, $item_data['control']);

			}
			echo	'</div>'; // .customizer_controls

			echo '</div>'; // .customizer_section
		}

		// echo '</form>';
		return true;
	}


	/**
	 * Output a Customizer Control
	 * @since 5.2
	 * @param string value name
	 *
	 */
	public function CustomizerControl($value_name, $control){
		global $page, $langmessage;

		echo '<div class="customizer_control">';

		echo	'<div class="customizer_control_label">';
		echo		htmlspecialchars($control['label']);
		echo	'</div>';

		$value_name		= htmlspecialchars($value_name);
		$current_value	= !empty($this->layout_config[$value_name]['value']) ?
			htmlspecialchars($this->layout_config[$value_name]['value']) :
			false;
		$current_units	= !empty($this->layout_config[$value_name]['units']) ?
			htmlspecialchars($this->layout_config[$value_name]['units']) :
			'';
		$name_attr		= 'customizer[' . htmlspecialchars($value_name) . ']';
		$placeholder	= !empty($control['placeholder']) ?
			htmlspecialchars($control['placeholder']) :
			$langmessage['default'];
		$minmax_attrs	= '';

		switch($control['type']){
			case 'number':
				if( isset($control['min']) && $control['min'] !== false ){
					$minmax_attrs .= ' min="' . $control['min'] . '"';
				}
				if( isset($control['max']) && $control['max'] !== false ){
					$minmax_attrs .= ' max="' . $control['max'] . '"';
				}
				if( !empty($control['step']) ){
					$minmax_attrs .= ' step="' . $control['step'] . '"';
				}
			case 'text':
			case 'url':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}
				echo '<div class="customizer_input_group">';
				echo '<input';
				echo	' name="' . $name_attr . '[value]"';
				echo	' type="' . htmlspecialchars($control['type']) . '"';
				echo	' value="' . $current_value . '"';
				echo	$minmax_attrs;
				echo	' placeholder="' . $placeholder . '"';
				echo ' />';
				if( !empty($control['units']) && is_array($control['units']) ){
					echo '<select class="units"';
					echo ' name="' . $name_attr . '[units]"';
					echo '>';
					foreach( $control['units'] as $key => $units ){
						$option_text = !is_numeric($key) ? $key : $units;
						$selected = $current_units == $units ? ' selected="selected"' : '';
						echo '<option value="' . $units . '"' . $selected . '>';
						echo	$option_text;
						echo '</option>';
					}
					echo '</select>';
				}
				echo '</div>'; // /.customizer_input_group
				break;

			case 'select':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}
				echo '<div class="customizer_input_group">';
				echo '<select';
				echo	' name="' . $name_attr . '[value]"';
				echo '>';
				foreach( $control['possible_values'] as $key => $value ){
					$option_text = !is_numeric($key) ? $key : $value;
					$selected = $current_value == $value ? ' selected="selected"' : '';
					echo '<option value="' . $value . '"' . $selected . '>';
					echo	$option_text;
					echo '</option>';
				}
				echo '</select>';
				echo '</div>'; // /.customizer_input_group
				break;

			case 'checkbox':
				echo '<div class="customizer_checkbox_group">';

				$checked	= $current_value ? ' checked="checked"' : '';
				$on_off		= $current_value ? 'on' : 'off';
				$id			= 'checkbox_' . $value_name;

				echo '<input type="hidden"';
				echo	' class="customizer_checkbox_alias"';
				echo	' name="' . $name_attr . '[value]"';
				echo	' value="' . $on_off . '"';
				echo ' />';
				echo '<input type="checkbox"';
				echo 	' data-cmd="toggle_customizer_checkbox"';
				echo 	' id="' . $id . '"';
				echo $checked;
				echo ' />';
				echo '<label for="' . $id . '">';
				if( !empty($control['description']) ){
					echo	'<span class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo 	'</span>';
				}
				echo '</label>';
				echo '</div>'; // /.customizer_checkbox_group
				break;

			case 'radio':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}
				echo '<div class="customizer_radio_group">';
				$i = 0;
				foreach( $control['possible_values'] as $key => $value ){
					$radio_label = !is_numeric($key) ? $key : $value;
					$checked = $current_value == $value ? ' checked="checked"' : '';
					$id = 'radio_' . $value_name . '_' . $i;
					echo '<input id="' . $id . '" name="' . $name_attr . '[value]"';
					echo	' type="radio" value="' . $value . '"' . $checked . '/>';
					echo '<label for="' . $id . '">';
					echo	'<span class="customizer_control_desc">';
					echo		$radio_label;
					echo 	'</span>';
					echo '</label>';
					$i++;
				}
				echo '</div>'; // /.customizer_radio_group
				break;

			case 'colorpicker':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}
				echo '<div class="customizer_colorpicker_group">';
				echo '<input';
				echo ' name="' . $name_attr . '[value]"';
				echo ' type="text"';
				echo ' value="' . $current_value . '"';
				echo ' style="background-color:' . $current_value . ';"';
				echo ' placeholder="' . $placeholder . '"';
				echo '/>';
				echo '</div>'; // /.customizer_input_group
				break;

			case 'colors':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}

				echo '<div class="customizer_colors_group">';
				$i = 0;
				foreach( $control['possible_values'] as $key => $value ){
					$color_title = !is_numeric($key) ? $key : $value;
					$checked = $current_value == $value ? ' checked="checked"' : '';
					$id = 'color_' . $value_name . '_' . $i;

					echo '<span class="customizer_color_swatch">';
					echo '<input id="' . $id . '" name="' . $name_attr . '[value]"';
					echo	' type="radio" value="' . $value . '"' . $checked . '/>';
					echo '<label for="' . $id . '"';
					echo	' style="background-color:' . $value . '"';
					echo	' title="' . $color_title . '"';
					echo '>';
					echo '</label>';
					echo '</span>';
					$i++;
				}
				// add unset swatch
				$checked = $current_value == '' ? ' checked="checked"' : '';
				$id = 'color_disabled_' . $value_name;
				echo '<span class="customizer_color_swatch">';
				echo '<input id="' . $id . '" name="' . $name_attr . '[value]"';
				echo	' type="radio" value=""' . $checked . '/>';
				echo '<label for="' . $id . '"';
				echo	' style="background-color:transparent;"';
				echo	' title="' . $langmessage['disabled'] . '"';
				echo '>';
				echo '</label>';
				echo '</span>';
				echo '</div>'; // /.customizer_colors_group
				break;

			case 'file':
			case 'image':
				if( !empty($control['description']) ){
					echo	'<div class="customizer_control_desc">';
					echo		htmlspecialchars($control['description']);
					echo	'</div>';
				}
				echo '<div class="customizer_file_group">';

				$is_image = preg_match(
					'/\.(jpg|jpeg|png|apng|gif|webp|svg|bmp|ico)$/i',
					$current_value
				);
				$display_class = $is_image ? '' : ' nodisplay';
				$img_src = $is_image ? ' src="' . $current_value . '"' : '';

				echo '<a class="customizer_image_preview' . $display_class . '"';
				echo 	' data-cmd="customizer_select_file"';
				echo 	' title="' . $langmessage['Select Image'] . '">';
				echo	'<img' . $img_src . '/>';
				echo '</a>'; // /.customizer_image_preview

				$title			= $langmessage['uploaded_files'];
				$icon			= 'fa-file-o';
				if( $control['type'] == 'image' ){
					$title			= $langmessage['Select Image'];
					$fa_icon		= 'fa-image';
				}

				echo '<div class="customizer_input_group">';
				echo '<input class="customizer_file_url" name="' . $name_attr . '[value]"';
				echo	' type="text" value="' . $current_value . '"';
				echo	' placeholder="' . htmlspecialchars($placeholder) . '"';
				echo '/>';
				echo '<a class="customizer_button"';
				echo 	' title="' . $title . '"';
				echo 	' data-cmd="customizer_select_file">';
				echo 	'<i class="fa ' . $fa_icon . '">&zwnj;</i>';
				echo '</a>';
				echo '</div>'; // /.customizer_input_group

				echo '</div>'; // /.customizer_file_group
				break;
		}

		echo '</div>'; // /.customizer_control
	}


	/**
	 * Save changes made in the Layout Editor
	 *
	 */
	public function SaveChanges(){

		// only if dirty
		if( !empty($_POST['save_customizer']) ){
			$this->SaveCustomizer();
		}

		// only if dirty
		if( !empty($_POST['save_css']) ){
			$this->SaveCSS();
		}
	}


	/**
	 * Save Customizer values
	 *
	 */
	public function SaveCustomizer(){
		global $gpLayouts;

		$this->CustomizerResults();
		// msg('CustomizerResults() &rarr; $this->customizer_results = ' . pre($this->customizer_results));

		// if( !empty($this->customizer_results['js_vars']) ){
		//	$this->page->head_script .= $this->customizer_results['js_vars'];
		// }

		if( !$this->SaveCustomizerResults($this->curr_layout, $this->customizer_results) ){
			return false;
		}

		$gpLayouts[$this->curr_layout]['customizer_css'] = true;

		// js vars
		if( empty($this->customizer_results['js_vars']) ){
			unset($gpLayouts[$this->curr_layout]['js_vars']);
		}else{
			$gpLayouts[$this->curr_layout]['js_vars'] = $this->customizer_results['js_vars'];
		}

		// php vars
		if( empty($this->customizer_results['php_vars']) ){
			unset($gpLayouts[$this->curr_layout]['config']);
		}else{
			$gpLayouts[$this->curr_layout]['config'] = $this->customizer_results['php_vars'];
		}

		// save
		if( !$this->SaveLayouts() ){
			return false;
		}

		return true;
	}


	/**
	 * Save edits to the layout css
	 *
	 */
	public function SaveCSS(){
		global $gpLayouts;

		$css =& $_POST['css'];

		if( !$this->SaveCustomCSS($this->curr_layout, $css) ){
			return false;
		}

		$gpLayouts[$this->curr_layout]['css'] = true;
		if( !$this->SaveLayouts() ){
			return false;
		}
		$this->page->SetTheme($this->curr_layout);
	}


	/**
	 * Preview changes made in the Layout Editor
	 *
	 */
	public function PreviewChanges(){
		$this->PreviewCustomizer();
		$this->PreviewCSS();
	}


	/**
	 * set current theme/laout values
	 *
	 */
	public function SetLayoutValues(){
		$this->page->layout_info		= \gp\tool::LayoutInfo($this->curr_layout, false);
		$this->page->theme_color		= $this->page->layout_info['theme_color'];
		$this->page->theme_rel			= dirname($this->page->theme_rel) . '/' . $this->page->theme_color;
		$this->page->theme_path			= dirname($this->page->theme_path) . '/' . $this->page->theme_color;
		$this->page->layout_dir			= $this->page->theme_dir . '/' . $this->page->theme_color;
		$this->page->layout_style_type	= \gp\tool\Output::StyleType($this->page->layout_dir);
	}


	/**
	 * Preview changes from the Customizer
	 *
	 */
	public function PreviewCustomizer(){
		$this->CustomizerResults();
		// msg('CustomizerResults() &rarr; $this->customizer_results = ' . pre($this->customizer_results));

		if( !empty($this->customizer_results['php_vars']) ){
			$this->page->preview_layout_config = $this->customizer_results['php_vars'];
			// msg('$this->page->preview_layout_config = ' . pre($this->page->preview_layout_config));
		}

		if( !empty($this->customizer_results['js_vars']) ){
			$this->page->head_script .= $this->customizer_results['js_vars'];
		}
	}


	/**
	 * Process the post values from the customizer
	 *
	 */
	public function CustomizerResults(){

		if( !isset($_POST['customizer']) ){
			$this->customizer_results = [];
			return false;
		}

		$this->SetLayoutValues();

		$style_type				= $this->page->layout_style_type;
		$customizer_data		= $this->GetLayoutCustomizer($this->curr_layout);
		$messages				= [];

		$customizer_vars		= [];
		foreach($customizer_data as $section => $section_data){
			foreach($section_data['items'] as $item_name => $item_data){
				$vars = [];

				$vars['default_value'] = $item_data['default_value'];

				if( !empty($item_data['default_units']) ){
					$vars['default_units'] = $item_data['default_units'];
				}

				$vars['control_type'] = $item_data['control']['type'];

				if( isset($item_data['control']['min']) ){
					$vars['min'] = (float)$item_data['control']['min'];
				}

				if( isset($item_data['control']['max']) ){
					$vars['max'] = (float)$item_data['control']['max'];
				}

				if( !empty($item_data['control']['possible_values']) ){
					$vars['possible_values'] = array_values($item_data['control']['possible_values']);
				}

				if( !empty($item_data['control']['units']) ){
					$vars['units'] = array_values($item_data['control']['units']);
				}

				$vars['used_in'] = [];
				if( !empty($item_data['control']['used_in']) ){
					$vars['used_in'] = $item_data['control']['used_in'];
				}

				if( !empty($item_data['control']['pattern']) ){
					$vars['pattern'] = $item_data['control']['pattern'];
				}

				$customizer_vars[$item_name] = $vars;
			}
		}

		$customizer_post_values	=& $_POST['customizer'];

		$patterns				= [
			'color'		=>	'/(#(?:[0-9a-f]{2}){2,4}$|(#[0-9a-f]{3}$)|' .
							'(rgb|hsl)a?\((-?\d+%?[,\s]+){2,3}\s*[\d\.]+%?\)$|' .
							'black$|silver$|gray$|whitesmoke$|maroon$|red$|purple$|' .
							'fuchsia$|green$|lime$|olivedrab$|yellow$|navy$|blue$|teal$|' .
							'aquamarine$|orange$|aliceblue$|antiquewhite$|aqua$|azure$|' .
							'beige$|bisque$|blanchedalmond$|blueviolet$|brown$|burlywood$|' .
							'cadetblue$|chartreuse$|chocolate$|coral$|cornflowerblue$|' .
							'cornsilk$|crimson$|currentcolor$|darkblue$|darkcyan$|darkgoldenrod$|' .
							'darkgray$|darkgreen$|darkgrey$|darkkhaki$|darkmagenta$|darkolivegreen$|' .
							'darkorange$|darkorchid$|darkred$|darksalmon$|darkseagreen$|darkslateblue$|' .
							'darkslategray$|darkslategrey$|darkturquoise$|darkviolet$|deeppink$|' .
							'deepskyblue$|dimgray$|dimgrey$|dodgerblue$|firebrick$|floralwhite$|' .
							'forestgreen$|gainsboro$|ghostwhite$|goldenrod$|gold$|greenyellow$|grey$|' .
							'honeydew$|hotpink$|indianred$|indigo$|ivory$|khaki$|lavenderblush$|lavender$|' .
							'lawngreen$|lemonchiffon$|lightblue$|lightcoral$|lightcyan$|lightgoldenrodyellow$|' .
							'lightgray$|lightgreen$|lightgrey$|lightpink$|lightsalmon$|lightseagreen$|' .
							'lightskyblue$|lightslategray$|lightslategrey$|lightsteelblue$|lightyellow$|' .
							'limegreen$|linen$|mediumaquamarine$|mediumblue$|mediumorchid$|mediumpurple$|' .
							'mediumseagreen$|mediumslateblue$|mediumspringgreen$|mediumturquoise$|' .
							'mediumvioletred$|midnightblue$|mintcream$|mistyrose$|moccasin$|navajowhite$|' .
							'oldlace$|olive$|orangered$|orchid$|palegoldenrod$|palegreen$|paleturquoise$|' .
							'palevioletred$|papayawhip$|peachpuff$|peru$|pink$|plum$|powderblue$|rosybrown$|' .
							'royalblue$|saddlebrown$|salmon$|sandybrown$|seagreen$|seashell$|sienna$|' .
							'skyblue$|slateblue$|slategray$|slategrey$|snow$|springgreen$|steelblue$|' .
							'tan$|thistle$|tomato$|transparent$|turquoise$|violet$|wheat$|white$|' .
							'yellowgreen$|rebeccapurple$)/i',

			'url'		=>	'%^(?:(?:https?://|ftp://|/|//))(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})' .
							'(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})' .
							'(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])' .
							'(?:\.?(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.?(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))' .
							'|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.' .
							'(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.?' .
							'(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$%iuS',

			'onoff'		=>	'/(on|off)/',

			'number'	=> '/^[0-9]+(\.[0-9]+)?$/',

			'integer'	=> '/^[0-9]+$/',
		];

		$ctl_types			= [
			// name			   use named regex pattern
			'text'			=> false,
			'select'		=> false,
			'radio'			=> false,
			'number'		=> 'number',
			'checkbox'		=> 'onoff',
			'colorpicker'	=> 'color',
			'colors'		=> 'color',
			'url'			=> 'url',
			'image'			=> 'url',
			'file'			=> 'url',
		];

		$layout_config			= [];
		$php_vars				= [];
		$scssless_vars			= '';
		$css_vars				= '';
		$js_vars				= [];

		foreach($customizer_post_values as $var_name => $values){
			if( !array_key_exists($var_name, $customizer_vars) ){
				$messages[] = 'Undefined variable name ' . htmlspecialchars($var_name);
				continue;
			}

			// validate value
			$current_val	= $values['value'];
			$current_units	= isset($values['units']) ? $values['units'] : '';

			$ctl_type	= $customizer_vars[$var_name]['control_type'];
			if( !array_key_exists($ctl_type, $ctl_types) ){
				$messages[] = 'Unregistered control type ' . htmlspecialchars($ctl_type);
				continue;
			}

			// validate via regex
			$pattern	= false;
			if( !empty($customizer_vars[$var_name]['pattern']) ){
				// pattern is defined in customizer.php
				$pattern = $customizer_vars[$var_name]['pattern'];
			}else{
				// use pattern defined for control type
				$pattern = $ctl_types[$ctl_type];
			}
			
			if( !empty($currrent_val) && $pattern ){
				if( array_key_exists($pattern, $patterns) ){
					// using named regex pattern
					$pattern = $patterns[$pattern];
				}
				if( !preg_match($pattern, $current_val) ){
					$messages[] = 'Invalid value ' . htmlspecialchars($current_val) .
						' for variable ' . htmlspecialchars($var_name) .
						' (pattern does not match)';
					continue;
				}
			}

			// validate possible values
			if( !empty($customizer_vars[$var_name]['possible_values']) ){
				if( $current_val && !in_array($current_val, $customizer_vars[$var_name]['possible_values']) ){
					$messages[] = 'Invalid value ' . htmlspecialchars($current_val) .
						' for variable ' . htmlspecialchars($var_name) .
						' (not in possible values)';
					continue;
				}
			}

			// validate possible units
			if( !empty($customizer_vars[$var_name]['units']) ){
				if( !in_array($current_units, $customizer_vars[$var_name]['units']) ){
					$messages[] = 'Invalid units ' . htmlspecialchars($current_units) .
						' for variable ' . htmlspecialchars($var_name);
					continue;
				}
			}

			// add to used envoronments
			foreach($customizer_vars[$var_name]['used_in'] as $used_in){

				$current_val_bool = $current_val;
				if( $current_val == 'on' ){
					$current_val_bool = true;
				}
				if( $current_val == 'off' ){
					$current_val_bool = false;
				}

				$layout_config[$var_name] = [ 'value' => $current_val_bool ];
				if( !empty($current_units) ){
					$layout_config[$var_name]['units'] = $current_units;
				}

				$used_in = strtolower($used_in);
				switch($used_in){
					case 'php':
						$php_vars[$var_name] = $layout_config[$var_name];
						break;

					case 'js':
						$js_vars[$var_name] = $layout_config[$var_name];
						break;

					case 'scssless':
						$prefix = $style_type == 'scss' ? '$' : '@';
						if( !empty($current_val) ){
							$scssless_vars .= $prefix . $var_name . ': ' . $current_val . $current_units . ";\n";
						}
						break;

					case 'css':
						if( !empty($current_val) ){
							$css_vars .= '  --' . $var_name . ': ' . $current_val . $current_units . ";\n";
						}
						break;
				}
			}
		}

		if( !empty($css_vars) ){
			// css vars will use the :root scope which has a higher specificity than html
			$css_vars = "\n" . ':root {' . "\n" . $css_vars . '}' . "\n";
		}

		$js_vars = "\n" . 'var layout_config = ' . json_encode($js_vars) . ';' . "\n";

		if(!empty($messages) ){
			msg(implode('<br/>' , $messages));
		}

		$customizer_results = [
			'layout_config'		=> $layout_config,
			'php_vars'			=> $php_vars,
			'js_vars'			=> $js_vars,
			'scssless_vars'		=> $scssless_vars,
			'css_vars'			=> $css_vars,
		];

		$this->customizer_results = $customizer_results;
	}


	/**
	 * Preview changes to the custom css/scss/less
	 *
	 */
	public function PreviewCSS(){
		global $langmessage;

		$this->SetLayoutValues();

		$style_files				= [];

		switch($this->page->layout_style_type){
			case 'scss':
				$this->PreviewScss($this->page->layout_dir);
				return;

			case 'less':
				$this->PreviewLess($this->page->layout_dir);
				return;

			case 'css':
			default:
				$this->page->css_user[]	= rawurldecode($this->page->theme_path) . '/style.css';

				$css = '';

				// customizer css vars
				if( !empty($this->customizer_results['css_vars']) ){
					$css .= $this->customizer_results['css_vars'];
				}

				// custom css
				$css .= $_REQUEST['css'];

				if( !empty(trim($css)) ){
					//make sure this is seen as code and not a filename
					$style_files[]	= $css . "\n";
					// let's use the Scss Compiler to validate the user css
					$parsed_data	= \gp\tool\Output\Css::ParseScss($style_files);
					$compiled		= $parsed_data[0];
			
					if( $compiled === false ){
						msg($langmessage['OOPS'] . ' (Invalid CSS)');
						return false;
					}
					$this->page->head .= '<style>' . $compiled . '</style>';
				}
				break;
		}

		$this->page->get_theme_css	= false;
	}


	/**
	 * Order of files for LESS
	 *  variables.less
	 *  custom.less (posted)
	 *  customizer.scss (posted)
	 *  Bootstrap.less
	 */
	protected function PreviewLess($dir){
		global $langmessage;

		$style_files			= [];

		// variables.less
		$var_file = $dir . '/variables.less';
		if( file_exists($var_file) ){
			$style_files[]			= $var_file;
		}

		// custom less
		$temp_less = trim($_REQUEST['css']);
		if( !empty($temp_less) ){
			//make sure this is seen as code and not a filename
			$style_files[]			= $_REQUEST['css'] . "\n";
		}

		// customizer less vars
		if( !empty($this->customizer_results['scssless_vars']) ){
			//make sure this is seen as code and not a filename
			$style_files[]	 	= $this->customizer_results['scssless_vars'] . "\n";
		}

		// customizer css vars
		if( !empty($this->customizer_results['css_vars']) ){
			//make sure this is seen as code and not a filename
			$style_files[]	 	= $this->customizer_results['css_vars'] . "\n";
		}

		// layout style file
		$style_files[]			= $dir . '/style.less';

		$parsed_data			= \gp\tool\Output\Css::ParseLess($style_files);
		$compiled				= $parsed_data[0];

		if( $compiled === false ){
			msg($langmessage['OOPS'] . ' (Invalid LESS)');
			return false;
		}

		$this->page->head .= '<style>' . $compiled . '</style>';
		$this->page->get_theme_css	= false;
	}


	/**
	 * Order of files for SCSS
	 *  variables.scss
	 *  custom.scss (posted)
	 *  customizer.scss (posted)
	 *  Bootstrap.scss
	 */
	protected function PreviewScss($dir){
		global $langmessage;

		$style_files			= [];

		// variables.scss
		$var_file = $dir . '/variables.scss';
		if( file_exists($var_file) ){
			$style_files[]		= $var_file;
		}

		// custom scss
		$temp_scss = trim($_REQUEST['css']);
		if( !empty($temp_scss) ){
			//make sure this is seen as code and not a filename
			$style_files[]		= $_REQUEST['css'] . "\n";
		}

		// customizer scss vars
		if( !empty($this->customizer_results['scssless_vars']) ){
			//make sure this is seen as code and not a filename
			$style_files[]		= $this->customizer_results['scssless_vars'] . "\n";
		}

		// customizer css vars
		if( !empty($this->customizer_results['css_vars']) ){
			//make sure this is seen as code and not a filename
			$style_files[]		= $this->customizer_results['css_vars'] . "\n";
		}

		// layout style file
		$style_files[]			= $dir . '/style.scss';

		$parsed_data			= \gp\tool\Output\Css::ParseScss($style_files);
		$compiled				= $parsed_data[0];

		if( $compiled === false ){
			msg($langmessage['OOPS'] . ' (Invalid SCSS)');
			return false;
		}

		$this->page->head .= '<style>' . $compiled . '</style>';
		$this->page->get_theme_css	= false;
	}


	public function DragArea(){
		global $langmessage;

		if( !$this->GetValues($_GET['dragging'], $from_container, $from_gpOutCmd) ){
			return;
		}
		if( !$this->GetValues($_GET['to'], $to_container, $to_gpOutCmd) ){
			return;
		}


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers, $from_container, $from_gpOutCmd);
		$this->PrepContainerHandlers($handlers, $to_container, $to_gpOutCmd);


		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			msg($langmessage['OOPS'] . ' (2)');
			return;
		}

		$where	= $this->ContainerWhere($from_gpOutCmd, $handlers[$from_container]);
		$to		= $this->ContainerWhere($to_gpOutCmd, $handlers[$from_container], false);

		if( $where === false ){
			return;
		}

		array_splice($handlers[$from_container], $where, 1);

		/**
		 * for moving down
		 * if target is the same container
		 * and target is below dragged element
		 * then $offset = 1
		 *
		 */
		$offset = 0;
		if( ($from_container == $to_container) &&
			($to !== false) &&
			($to > $where)
		){
			$offset = 1;
		}

		if( !$this->AddToContainer($handlers[$to_container], $to_gpOutCmd, $from_gpOutCmd, false, $offset) ){
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
			msg($langmessage['OOPS'] . ' (Param not set)');
			return;
		}
		$param = $_GET['param'];

		//counts
		$count_gadgets = 
			(isset($config['gadgets']) && is_array($config['gadgets'])) ?
			count($config['gadgets']) :
			false;

		echo '<div class="inline_box">';

		echo	'<div class="gp_tabs">';

		echo		'<a href="#layout_extra_content" class="selected"';
		echo			' data-cmd="tabs">';
		echo			$langmessage['theme_content'] . '</a>';
		if( $count_gadgets > 0 ){
			echo	' <a href="#layout_gadgets" data-cmd="tabs">';
			echo		$langmessage['gadgets'];
			echo	'</a>';
		}
		echo		' <a href="#layout_menus" data-cmd="tabs">';
		echo			$langmessage['Link_Menus'];
		echo		'</a>';

		echo		' <a href="#layout_custom" data-cmd="tabs">';
		echo			$langmessage['Custom Menu'];
		echo		'</a>';

		echo	'</div>'; // /.gp_tabs

		$this->SelectContent_Areas($param, $count_gadgets);

		echo '</div>'; // /.inline_box
	}


	public function SelectContent_Areas($param, $count_gadgets){
		global $dataDir, $langmessage, $config;

		$addQuery = 'cmd=addcontent&where=' . rawurlencode($param);
		echo '<div id="area_lists">';

		//extra content
		echo '<div id="layout_extra_content">';
		echo '<table class="bordered">';
		echo	'<tr>';
		echo		'<th colspan="2">&nbsp;</th>';
		echo	'</tr>';

		$extrasFolder	= $dataDir . '/data/_extra';
		$files			= scandir($extrasFolder) or [];

		asort($files);
		foreach($files as $file){

			$extraName	= \gp\admin\Content\Extra::AreaExists($file);
			if( $extraName === false ){
				continue;
			}

			echo	'<tr>';
			echo		'<td>';
			echo			str_replace('_', ' ', $extraName);
			echo		'</td>';
			echo		'<td class="add">';
			echo			\gp\tool::Link(
								$this->layout_slug,
								$langmessage['add'],
								$addQuery . '&insert=Extra:' . $extraName,
								['data-cmd' => 'creq']
							);
			echo		'</td>';
			echo	'</tr>';
		}

		//new extra area
		echo	'<tr>';
		echo		'<td colspan="2">';
		echo			'<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" method="post">';
		echo				'<input type="hidden" name="cmd"';
		echo					' value="addcontent" />';
		echo				'<input type="hidden" name="addtype"';
		echo					' value="new_extra" />';
		echo				'<input type="hidden" name="where"';
		echo					' value="' . htmlspecialchars($param) . '" />';
		echo				'<input type="hidden" name="where"';
		echo					' value="' . htmlspecialchars($param) . '" />';
		echo				'<input type="hidden" name="where"';
		echo					' value="' . htmlspecialchars($param) . '" />';
		echo				'<input type="hidden" name="where"';
		echo					' value="' . htmlspecialchars($param) . '" />';
		echo				'<input type="text" name="extra_area"';
		echo					' value="" size="15" class="gpinput"';
		echo					' required="required"';
		echo					' placeholder="' . htmlspecialchars($langmessage['name']) . '" />';

		$types = \gp\tool\Output\Sections::GetTypes();
		echo				'<select name="type" class="gpselect">';
		foreach($types as $type => $info){
			echo				'<option value="' . $type . '">' . $info['label'] . '</option>';
		}
		echo				'</select> ';

		echo				'<input type="submit" name=""';
		echo					' value="' . $langmessage['Add New Area'] . '"';
		echo					' class="gpbutton gpvalidate" />';
		echo			'</form>';
		echo		'</td>';
		echo	'</tr>';
		echo '</table>';

		echo '<p>';
		echo	'<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" ';
		echo		'method="post" style="text-align:right"> ';
		echo		'<input type="submit" name="cmd"';
		echo			' value="' . $langmessage['cancel'] . '"';
		echo			' class="admin_box_close gpcancel" />';
		echo	'</form>';
		echo '</p>';
		echo '</div>'; // /#layout_extra_content


		//gadgets
		if( $count_gadgets > 0 ){
			echo '<div id="layout_gadgets" class="nodisplay">';
			echo '<table class="bordered">';
			echo	'<tr>';
			echo		'<th colspan="2">&nbsp;</th>';
			echo	'</tr>';

			foreach($config['gadgets'] as $gadget => $info){
				echo	'<tr>';
				echo		'<td>';
				echo			str_replace('_', ' ', $gadget);
				echo		'</td>';
				echo		'<td class="add">';
				echo \gp\tool::Link(
						$this->layout_slug,
						$langmessage['add'],
						$addQuery . '&insert=' . $gadget,
						['data-cmd'=>'creq']
					);
				echo		'</td>';
				echo	'</tr>';
			}

			echo 	'<tr>';
			echo		'<td colspan="2" class="add">';
			echo			'<input type="submit" name="cmd"';
			echo				' value="' . $langmessage['cancel'] . '"';
			echo				' class="admin_box_close gpcancel" />';
			echo		'</td>';
			echo	'</tr>';

			echo '</table>';
			echo '</div>'; // /#layout_gadgets
		}

		//menus
		echo '<div id="layout_menus" class="nodisplay">';
		echo '<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" method="post">';
		echo	'<input type="hidden" name="cmd" value="addcontent" />';
		echo	'<input type="hidden" name="addtype" value="preset_menu" />';
		echo	'<input type="hidden" name="where"';
		echo		' value="' . htmlspecialchars($param) . '" />';

		echo	'<table class="bordered">';
		$this->PresetMenuForm();

		echo		'<tr>';
		echo			'<td colspan="2" class="add">';
		echo				'<input type="submit" name=""';
		echo					' value="' . $langmessage['Add New Menu'] . '"';
		echo					' class="gpsubmit" />';
		echo				'<input type="submit" name="cmd"';
		echo					' value="' . $langmessage['cancel'] . '"';
		echo					' class="admin_box_close gpcancel" />';
		echo			'</td>';
		echo		'</tr>';
		echo	'</table>';
		echo '</form>';
		echo '</div>'; // /#layout_menus

		//custom area
		echo '<div id="layout_custom" class="nodisplay">';
		echo '<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" method="post">';
		echo	'<input type="hidden" name="cmd" value="addcontent" />';
		echo	'<input type="hidden" name="addtype" value="custom_menu" />';
		echo	'<input type="hidden" name="where" value="' . htmlspecialchars($param) . '" />';

		echo	'<table class="bordered">';
		$this->CustomMenuForm();

		echo		'<tr>';
		echo			'<td colspan="2" class="add">';
		echo				'<input type="submit" name=""';
		echo					' value="' . $langmessage['Add New Menu'] . '"';
		echo					' class="gpsubmit" />';
		echo				' <input type="submit" name="cmd"';
		echo					' value="' . $langmessage['cancel'] . '" ';
		echo					' class="admin_box_close gpcancel" />';
		echo			'</td>';
		echo		'</tr>';
		echo 	'</table>';
		echo '</form>';
		echo '</div>'; // /#layout_custom

		echo '</div>';
	}


	/**
	 * Insert new content into a layout
	 *
	 */
	public function AddContent(){
		global $langmessage;

		//for ajax responses
		$this->page->ajaxReplace = [];

		if( !isset($_REQUEST['where']) ){
			msg($langmessage['OOPS']);
			return false;
		}

		//prep destination
		if( !$this->GetValues($_REQUEST['where'], $to_container, $to_gpOutCmd) ){
			return false;
		}
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers, $to_container, $to_gpOutCmd);

		//figure out what we're inserting
		$addtype =& $_REQUEST['addtype'];
		switch($addtype){
			case 'new_extra':
				$extra_name = $this->NewExtraArea();
				if( $extra_name === false ){
					msg($langmessage['OOPS'] . ' (2)');
					return false;
				}
				$insert = 'Extra:' . $extra_name;
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
			msg($langmessage['OOPS'] . ' (Nothing to insert)');
			return false;
		}

		//new info
		$new_gpOutInfo = \gp\tool\Output::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			msg($langmessage['OOPS'] . ' (Nothing to insert)');
			return false;
		}
		$new_gpOutCmd = rtrim($new_gpOutInfo['key'] . ':' . $new_gpOutInfo['arg'], ':');

		if( !$this->AddToContainer($handlers[$to_container], $to_gpOutCmd, $new_gpOutCmd, false) ){
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
			msg($langmessage['OOPS']);
			return false;
		}

		$data	= \gp\tool\Editing::DefaultContent($_POST['type']);
		$file	= $dataDir . '/data/_extra/' . $title . '/page.php';

		if( \gp\admin\Content\Extra::AreaExists($title) !== false ){
			return $title;
		}

		if( !\gp\tool\Files::SaveData($file, 'file_sections', [$data]) ){
			msg($langmessage['OOPS']);
			return false;
		}

		return $title;
	}


	public function RemoveArea(){
		global $langmessage;

		//for ajax responses
		$this->page->ajaxReplace = [];

		if( !$this->ParseHandlerInfo($_GET['param'], $curr_info) ){
			msg($langmessage['OOPS'] . ' (0)');
			return;
		}
		$gpOutCmd	= $curr_info['gpOutCmd'];
		$container	= $curr_info['container'];

		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers, $container, $gpOutCmd);

		//remove from $handlers[$container]
		$where = $this->ContainerWhere($gpOutCmd, $handlers[$container]);
		if( $where === false ){
			return;
		}

		array_splice($handlers[$container], $where, 1);

		$this->SaveHandlersNew($handlers);
	}


	/**
	 * Get the position of $gpOutCmd in $container_info
	 * @return int|false
	 *
	 */
	public function ContainerWhere($gpOutCmd, &$container_info, $warn=true){
		global $langmessage;

		$where = array_search($gpOutCmd, $container_info);

		if( !is_int($where) ){
			if( $warn ){
				msg($langmessage['OOPS'] . ' (Not found in container)');
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

		if( !$this->ParseHandlerInfo($_GET['handle'], $curr_info) ){
			msg($langmessage['00PS']);
			return;
		}

		$showCustom			= false;
		$menu_args			= $this->MenuArgs($curr_info);

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}

		echo '<div class="inline_box">';

		echo '<div class="gp_tabs">';
		if( $showCustom ){
			echo	' <a href="#layout_menus" data-cmd="tabs">';
			echo		$langmessage['Link_Menus'];
			echo	'</a>';
			echo	' <a href="#layout_custom" data-cmd="tabs" class="selected">';
			echo		$langmessage['Custom Menu'];
			echo	'</a>';
		}else{
			echo	' <a href="#layout_menus" data-cmd="tabs" class="selected">';
			echo		$langmessage['Link_Menus'];
			echo	'</a>';
			echo	' <a href="#layout_custom" data-cmd="tabs">';
			echo		$langmessage['Custom Menu'];
			echo	'</a>';
		}
		echo '</div>'; // /.gp_tabs

		echo '<br/>';

		echo '<div id="area_lists">';

		//preset menus
		$style = $showCustom ? ' class="nodisplay"' : '';
		echo '<div id="layout_menus"' . $style . '>';
		echo	'<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" method="post">';
		echo		'<input type="hidden" name="handle"';
		echo			' value="' . htmlspecialchars($_GET['handle']) . '" />';

		echo		'<table class="bordered">';
		$this->PresetMenuForm($menu_args);

		echo			'<tr>';
		echo				'<td class="add" colspan="2">';
		echo					'<button type="submit" name="cmd" ';
		echo						'value="LayoutMenuSave" ';
		echo						'data-cmd="gpajax" class="gpsubmit">';
		echo						$langmessage['save'];
		echo					'</button> ';
		echo					'<input type="submit" name="cmd"';
		echo						' value="' . $langmessage['cancel'] . '"';
		echo						' class="admin_box_close gpcancel" />';
		echo				'</td>';
		echo			'</tr>';
		echo		'</table>';
		echo	'</form>';
		echo '</div>'; // /#layout_menus

		//custom menus
		$style = $showCustom ? '' : ' class="nodisplay"';
		echo '<div id="layout_custom"' . $style . '>';
		echo	'<form action="' . \gp\tool::GetUrl($this->layout_slug) . '" method="post">';
		echo		'<input type="hidden" name="handle"';
		echo			' value="' . htmlspecialchars($_GET['handle']) . '" />';

		echo		'<table class="bordered">';
		$this->CustomMenuForm($menu_args);

		echo			'<tr>';
		echo				'<td class="add" colspan="2">';
		echo					'<button type="submit" name="cmd"';
		echo						' value="LayoutMenuSave"';
		echo						' data-cmd="gpajax" class="gpsubmit">';
		echo						$langmessage['save'];
		echo					'</button>';
		echo					' <input type="submit" name="cmd"';
		echo						' value="' . $langmessage['cancel'] . '"';
		echo						' class="admin_box_close gpcancel" />';
		echo				'</td>';
		echo			'</tr>';
		echo		'</table>';
		echo	'</form>';
		echo '</div>'; // /#layout_custom

		echo '<p class="admin_note">';
		echo	$langmessage['see_also'];
		echo	' ';
		echo	\gp\tool::Link(
					'Admin/Menu',
					$langmessage['file_manager']
				);
		echo	', ';
		echo	\gp\tool::Link(
					'Admin_Theme_Content',
					$langmessage['content_arrangement']
				);
		echo '</p>';

		echo '</div>'; // /#area_lists

		echo '</div>'; // /.inline_box
	}


	/**
	 * Save the posted layout menu settings
	 *
	 */
	public function LayoutMenuSave(){
		global $langmessage, $gpLayouts;

		if( !$this->ParseHandlerInfo($_POST['handle'], $curr_info) ){
			msg($langmessage['OOPS'] . ' (0)');
			return;
		}

		if( isset($_POST['new_handle']) ){
			$new_gpOutCmd = $this->NewPresetMenu();
		}else{
			$new_gpOutCmd = $this->NewCustomMenu();
		}

		if( $new_gpOutCmd === false ){
			msg($langmessage['OOPS'] . ' (1)');
			return;
		}

		//prep
		$handlers = $this->GetAllHandlers($this->curr_layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers, $container, $curr_info['gpOutCmd']);

		//unchanged?
		if( $curr_info['gpOutCmd'] == $new_gpOutCmd ){
			return;
		}

		if( !$this->AddToContainer($handlers[$container], $curr_info['gpOutCmd'], $new_gpOutCmd, true) ){
			return;
		}

		$this->SaveHandlersNew($handlers, $this->curr_layout);
	}


	public function ParseHandlerInfo($str, &$info){
		global $config, $gpOutConf;

		if( substr_count($str, '|') !== 1 ){
			return false;
		}

		list($container, $fullKey) = explode('|', $str);

		$arg		= '';
		$pos		= strpos($fullKey, ':');
		$key		= $fullKey;
		if( $pos > 0 ){
			$arg	= substr($fullKey, $pos + 1);
			$key	= substr($fullKey, 0, $pos);
		}

		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}

		$info				= [];
		$info['gpOutCmd']	= trim($fullKey, ':');
		$info['container']	= $container;
		$info['key']		= $key;
		$info['arg']		= $arg;

		return true;
	}


	/**
	 * Get the container and gpOutCmd from the $arg
	 *
	 */
	public function GetValues($arg, &$container, &$gpOutCmd){
		global $langmessage;

		if( substr_count($arg, '|') !== 1 ){
			msg($langmessage['OOPS'] . ' (Invalid argument)');
			return false;
		}

		list($container, $gpOutCmd) = explode('|', $arg);
		return true;
	}


	public function AddToContainer(&$container_info, $to_gpOutCmd, $new_gpOutCmd, $replace=true, $offset=0){
		global $langmessage;

		//add to to_container in front of $to_gpOutCmd
		if( !is_array($container_info) ){
			msg($langmessage['OOPS'] . ' (a1)');
			return false;
		}


		//can't have two identical outputs in the same container
		$check = $this->ContainerWhere($new_gpOutCmd, $container_info, false);
		if( $check !== false ){
			msg($langmessage['OOPS'] . ' (Area already in container)');
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

		array_splice($container_info, $where, $length, $new_gpOutCmd);

		return true;
	}


	public function NewCustomMenu(){

		$upper_bound		=& $_POST['upper_bound'];
		$lower_bound		=& $_POST['lower_bound'];
		$expand_bound		=& $_POST['expand_bound'];
		$expand_all			=& $_POST['expand_all'];
		$source_menu		=& $_POST['source_menu'];

		$this->CleanBounds($upper_bound, $lower_bound, $expand_bound, $expand_all, $source_menu);

		$arg =	$upper_bound	. ',' .
				$lower_bound	. ',' .
				$expand_bound	. ',' .
				$expand_all		. ',' .
				$source_menu;

		return 'CustomMenu:' . $arg;
	}


	public function NewPresetMenu(){
		global $gpOutConf;

		$new_gpOutCmd =& $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutCmd]) || !isset($gpOutConf[$new_gpOutCmd]['link']) ){
			return false;
		}

		return rtrim($new_gpOutCmd . ':' . $this->CleanMenu($_POST['source_menu']), ':');
	}


	public function PresetMenuForm($args=[]){
		global $gpOutConf, $langmessage;

		$current_function	=& $args['current_function'];
		$current_menu		=& $args['source_menu'];

		$this->MenuSelect($current_menu);

		echo '<tr>';
		echo 	'<th colspan="2">';
		echo		$langmessage['Menu Output'];
		echo	'</th>';
		echo '</tr>';

		$i = 0;
		foreach($gpOutConf as $outKey => $info){

			if( !isset($info['link']) ){
				continue;
			}
			echo '<tr>';
			echo	'<td>';
			echo		'<label for="new_handle_' . $i . '">';
			if( isset($langmessage[$info['link']]) ){
				echo		str_replace(' ', '&nbsp;', $langmessage[$info['link']]);
			}else{
				echo		str_replace(' ', '&nbsp;', $info['link']);
			}
			echo		'</label>';
			echo	'</td>';
			echo	'<td class="add">';

			if( $current_function == $outKey ){
				echo	'<input id="new_handle_' . $i . '" type="radio"';
				echo		' name="new_handle" value="' . $outKey . '"';
				echo		' checked="checked"/>';
			}else{
				echo	'<input id="new_handle_' . $i . '" type="radio"';
				echo		' name="new_handle" value="' . $outKey . '" />';
			}
			echo	'</td>';
			echo '</tr>';

			$i++;
		}
	}


	public function MenuArgs($curr_info){

		$menu_args = [];

		if( $curr_info['key'] == 'CustomMenu' ){

			$args		= explode(',', $curr_info['arg']);
			$args		+= [0 => 0, 1 => -1, 2 => -1, 3 => 0, 4 => '']; //defaults
			list($upper_bound, $lower_bound, $expand_bound, $expand_all, $source_menu) = $args;

			$this->CleanBounds($upper_bound, $lower_bound, $expand_bound, $expand_all, $source_menu);

			$menu_args['upper_bound']	= $upper_bound;
			$menu_args['lower_bound']	= $lower_bound;
			$menu_args['expand_bound']	= $expand_bound;
			$menu_args['expand_all']	= $expand_all;
			$menu_args['source_menu']	= $source_menu;

		}else{

			$menu_args['current_function']	= $curr_info['key'];
			$menu_args['source_menu']		= $this->CleanMenu($curr_info['arg']);

		}

		return $menu_args;
	}


	/**
	 * Output form elements for setting custom menu settings
	 * @param array $menu_args
	 *
	 */
	public function CustomMenuForm($menu_args=[]){
		global $langmessage;

		$upper_bound	=& $menu_args['upper_bound'];
		$lower_bound	=& $menu_args['lower_bound'];
		$expand_bound	=& $menu_args['expand_bound'];
		$expand_all		=& $menu_args['expand_all'];
		$source_menu	=& $menu_args['source_menu'];

		$this->MenuSelect($source_menu);

		echo '<tr>';
		echo	'<th colspan="2">';
		echo		$langmessage['Show Titles...'];
		echo	'</th>';
		echo '</tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'upper_bound', $upper_bound);
		$this->CustomMenuSection($langmessage['... At And Above Level'], 'lower_bound', $lower_bound);

		echo '<tr>';
		echo	'<th colspan="2">';
		echo		$langmessage['Expand Menu...'];
		echo	'</th>';
		echo '</tr>';

		$this->CustomMenuSection($langmessage['... Below Level'], 'expand_bound', $expand_bound);

		echo '<tr>';
		echo	'<td>';
		echo		$langmessage['... Expand All'];
		echo	'</td>';
		echo	'<td class="add">';
		$attr = $expand_all ? ' checked' : '';
		echo		'<input type="checkbox" name="expand_all"' . $attr . ' />';
		echo	'</td>';
		echo '</tr>';
	}


	public function CleanBounds(&$upper_bound, &$lower_bound, &$expand_bound, &$expand_all, &$source_menu){

		$upper_bound	= (int)$upper_bound;
		$upper_bound	= max(0, $upper_bound);
		$upper_bound	= min(4, $upper_bound);

		$lower_bound	= (int)$lower_bound;
		$lower_bound	= max(-1, $lower_bound);
		$lower_bound	= min(4, $lower_bound);

		$expand_bound	= (int)$expand_bound;
		$expand_bound	= max(-1, $expand_bound);
		$expand_bound	= min(4, $expand_bound);

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
	 * @param string $label
	 * @param string $name
	 * @param int $value
	 *
	 */
	public function CustomMenuSection($label, $name, $value){
		echo '<tr>';
		echo	'<td>';
		echo		$label;
		echo	'</td>';
		echo	'<td class="add">';
		echo		'<select name="' . $name . '" class="gpselect">';
		for($i = 0; $i <= 4; $i++){

			$label		= ($i === 0) ? '' : $i;
			$selected	= ($i === $value) ? ' selected' : '';

			echo '<option value="' . $i . '"' . $selected . '>' . $label . '</option>';
		}

		echo		'</select>';
		echo	'</td>';
		echo '</tr>';
	}


	public function MenuSelect($source_menu){
		global $config, $langmessage;

		echo '<tr>';
		echo	'<th colspan="2">';
		echo		$langmessage['Source Menu'];
		echo	'</th>';
		echo '</tr>';
		echo '<tr>';
		echo	'<td>';
		echo		$langmessage['Menu'];
		echo	'</td>';
		echo	'<td class="add">';
		echo		'<select name="source_menu" class="gpselect">';
		echo			'<option value="">' . $langmessage['Main Menu'] . '</option>';
		if( isset($config['menus']) && count($config['menus']) > 0 ){
			foreach($config['menus'] as $id => $menu ){
				$attr = '';
				if( $source_menu == $id ){
					$attr = ' selected="selected"';
				}
				echo '<option value="' . htmlspecialchars($id) . '"' . $attr . '>';
				echo	htmlspecialchars($menu);
				echo '</option>';
			}
		}
		echo		'</select>';
		echo	'</td>';
		echo '</tr>';
	}

}
