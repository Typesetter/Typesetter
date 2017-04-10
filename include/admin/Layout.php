<?php

namespace gp\admin;

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


class Layout extends \gp\admin\Addon\Install{

	public $curr_layout;
	protected $layout_request		= false;
	protected $LayoutArray;
	protected $scriptUrl			= 'Admin_Theme_Content';
	protected $versions				= array();


	//remote install variables
	public $config_index			= 'themes';
	public $code_folder_name		= '_themes';
	public $path_remote				= 'Admin_Theme_Content/Remote';

	private $gpLayouts_before;
	private $config_before;


	public function __construct($args){
		global $gpLayouts, $config;

		parent::__construct($args);

		$this->gpLayouts_before		= $gpLayouts;
		$this->config_before		= $config;

		$this->page->head_js[]			= '/include/js/theme_content.js';
		$this->page->head_js[]			= '/include/js/dragdrop.js';
		$this->page->css_admin[]		= '/include/css/theme_content.scss';


		\gp\tool::LoadComponents('resizable');


		$this->GetPossible();

	}


	public function RunScript(){
		global $config, $gpLayouts, $langmessage;

		$cmd = \gp\tool::GetCommand();

		//set current layout
		$this->curr_layout = $config['gpLayout'];
		if( isset($_REQUEST['layout']) ){
			$this->curr_layout = $_REQUEST['layout'];
		}
		if( !array_key_exists($this->curr_layout,$gpLayouts) ){
			message($langmessage['OOPS'].' (Invalid Layout)');
			$cmd = '';
		}

		$this->SetLayoutArray();


		//Installation
		$this->cmds['remote_install']			= 'RemoteInstall';
		$this->cmds['RemoteInstall']			= '';
		$this->cmds['RemoteInstallConfirmed']	= 'DefaultDisplay';
		$this->cmds['UpgradeTheme']				= 'DefaultDisplay';

		//Copy, Delete
		$this->cmds['CopyLayoutPrompt']			= '';
		$this->cmds['CopyLayout']				= 'DefaultDisplay';
		$this->cmds['DeleteLayout']				= 'DefaultDisplay';

		//Reviews
		$this->cmds['SendAddonReview']			= '';
		$this->cmds['ReviewAddonForm']			= '';

		$this->cmds['addontext']				= 'RedirectText';



		$this->LayoutCommands();
		$this->RunCommands($cmd);
	}

	/**
	 * Redirect addontext requests to correct path for TS 5.0+
	 *
	 */
	protected function RedirectText(){
		$params = $_GET;
		$params['cmd'] = 'AddonTextForm';

		$url = \gp\tool::GetUrl('Admin_Theme_Content/Text',http_build_query($params,'','&'),false);
		\gp\tool::Redirect($url);
	}



	/**
	 * Perform various layout commands
	 *
	 */
	public function LayoutCommands(){

		$this->cmds['ShowTitles']		= '';
		$this->cmds['ShowGadgets']		= '';
		$this->cmds['LayoutLabel']		= '';
		$this->cmds['MakeDefault']		= 'DefaultDisplay';
		$this->cmds['CSSPreferences']	= '';
		$this->cmds['RestoreLayout']	= 'DefaultDisplay';
		$this->cmds['RmGadget']			= 'ShowGadgets';

	}


	/**
	 * Show all layouts and themes
	 *
	 */
	public function DefaultDisplay(){
		global $config, $langmessage, $gpLayouts;

		$this->page->head_js[] = '/include/js/auto_width.js';

		$this->ShowHeader();

		echo '<div id="adminlinks2">';

		//all other layouts
		foreach($gpLayouts as $layout => $info){
			$this->LayoutDiv($layout,$info);
		}
		echo '</div>';

		echo '<hr/>';
		echo '<p class="admin_note">';
		echo $langmessage['see_also'].' '.\gp\tool::Link('Admin/Menu',$langmessage['file_manager']);
		echo '</p>';


		$this->ColorSelector();
	}


	/**
	 * Display a list of all the titles using the current layout
	 *
	 */
	public function ShowTitles(){
		global $langmessage;

		//affected titles
		$titles_count = $this->TitlesCount($this->curr_layout);

		echo '<h2>'.$langmessage['titles_using_layout'];
		echo ': '.$titles_count;
		echo '</h2>';

		if( $titles_count > 0 ){
			echo '<ul class="titles_using">';

			foreach( $this->LayoutArray as $index => $layout_comparison ){
				if( $this->curr_layout == $layout_comparison ){

					$title = \gp\tool::IndexToTitle($index);
					if( empty($title) ){
						continue; //may be external link
					}

					echo "\n<li>";
					$label = \gp\tool::GetLabel($title);
					$label = \gp\tool::LabelSpecialChars($label);
					echo \gp\tool::Link($title,$label);
					echo '</li>';
				}
			}

			echo '</ul>';
			echo '<div class="clear"></div>';
		}

	}


	/**
	 * Display gadgets and their status for the current layout
	 *
	 */
	public function ShowGadgets(){
		global $langmessage, $config;

		$gadget_info = \gp\tool\Output::WhichGadgets($this->curr_layout);

		echo '<h2>'.$langmessage['gadgets'].'</h2>';
		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">&nbsp;</th></tr>';

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
					echo $this->LayoutLink( $this->curr_layout, $langmessage['remove'], 'cmd=RmGadget&gadget='.urlencode($gadget), array('data-cmd'=>'gpabox') );
				}else{
					echo $langmessage['disabled'];
				}
				echo '</td></tr>';
			}
		}
		echo '</table>';
	}



	/**
	 * Create a drop-down menu for the layout options
	 *
	 */
	public function LayoutOptions($layout,$info){
		global $langmessage, $config;


		//theme name
		echo '<li>';
		echo '<span>'.$langmessage['theme'].': '.$this->ThemeLabel($info['theme_name']).'</span>';
		echo '</li>';


		//default
		echo '<li>';
		if( $config['gpLayout'] == $layout ){
			echo '<span><b>'.$langmessage['default'].'</b></span>';
		}else{
			echo \gp\tool::Link('Admin_Theme_Content',$langmessage['make_default'],'cmd=MakeDefault&layout='.rawurlencode($layout),array('data-cmd'=>'creq','title'=>$langmessage['make_default']));
		}
		echo '</li>';


		//gadgets
		echo '<li>';
		echo $this->LayoutLink( $layout, $langmessage['gadgets'], 'cmd=ShowGadgets', 'data-cmd="gpabox"' );
		echo '</li>';


		//titles using layout
		echo '<li>';
		$titles_count	= $this->TitlesCount($layout);
		$label			= sprintf($langmessage['%s Pages'],$titles_count);
		if( $titles_count ){
			echo $this->LayoutLink( $layout, $label, 'cmd=ShowTitles', 'data-cmd="gpabox"' );
		}else{
			echo '<span>'.$label.'</span>';
		}
		echo '</li>';


		//content arrangement
		$handlers_count = $this->HandlersCount($info);
		echo '<li>';
		if( $handlers_count ){
			echo $this->LayoutLink( $layout, $langmessage['restore_defaults'], 'cmd=RestoreLayout', array('data-cmd'=>'creq') );
		}else{
			echo '<span>'.$langmessage['content_arrangement'].': '.$langmessage['default'].'</span>';
		}
		echo '</li>';


		//copy
		echo '<li>';
		$query = 'cmd=CopyLayoutPrompt&layout='.$layout;
		echo \gp\tool::Link('Admin_Theme_Content',$langmessage['Copy'],$query,'data-cmd="gpabox"');
		echo '</li>';

		//delete
		if( $config['gpLayout'] != $layout ){
			echo '<li>';
			$attr = array( 'data-cmd'=>'creq','class'=>'gpconfirm','title'=>sprintf($langmessage['generic_delete_confirm'],$info['label']) );
			echo \gp\tool::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletelayout&layout='.$layout,$attr);
			echo '</li>';
		}
	}


	/**
	 * Get number of handlers
	 *
	 */
	public function HandlersCount($layout_info){

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

		return $handlers_count;
	}


	/**
	 * Get the custom css for a layout if it exists
	 *
	 */
	public function LayoutCSS($layout){

		$custom_file		= $this->LayoutCSSFile($layout);

		if( file_exists($custom_file) ){
			return file_get_contents($custom_file);
		}

		return '';
	}


	/**
	 * Save the custom.css or custom.scss file
	 *
	 */
	public function SaveCustom($layout, $css){
		global $langmessage;

		//delete css file if empty
		if( empty($css) ){
			return $this->RemoveCSS($layout);
		}

		//save if not empt
		$custom_file		= $this->LayoutCSSFile($layout);

		if( !\gp\tool\Files::Save($custom_file,$css) ){
			message($langmessage['OOPS'].' (CSS not saved)');
			return false;
		}

		return true;
	}


	/**
	 * Get the path of the custom css file
	 *
	 */
	public function LayoutCSSFile($layout){

		$layout_info		= \gp\tool::LayoutInfo($layout,false);
		$dir				= $layout_info['dir'].'/'.$layout_info['theme_color'];
		$style_type			= \gp\tool\Output::StyleType($dir);

		return \gp\tool\Output::CustomStyleFile($layout, $style_type);
	}


	/**
	 * Remove the custom css file for a layout
	 *
	 */
	public function RemoveCSS($layout){
		global $gpLayouts;

		$path = $this->LayoutCSSFile($layout);
		if( file_exists($path) ){
			unlink($path);
		}

		$dir	= dirname($path);
		$path	= $dir.'/index.html';

		if( file_exists($path) ){
			unlink($path);
		}

		if( file_exists($dir) ){
			\gp\tool\Files::RmDir($dir);
		}

		if( isset($gpLayouts[$layout]['css']) ){
			unset($gpLayouts[$layout]['css']);
		}

		return true;
	}


	/**
	 * Save changes to the css settings for a layout
	 *
	 */
	public function CSSPreferences(){
		global $langmessage, $gpLayouts;

		$new_info = $gpLayouts[$this->curr_layout];

		if( isset($_POST['menu_css_ordered']) ){
			if( $_POST['menu_css_ordered'] === 'off' ){
				$new_info['menu_css_ordered'] = false;
			}else{
				unset($new_info['menu_css_ordered']);
			}
		}

		if( isset($_POST['menu_css_indexed']) ){
			if( $_POST['menu_css_indexed'] === 'off' ){
				$new_info['menu_css_indexed'] = false;
			}else{
				unset($new_info['menu_css_indexed']);
			}
		}

		$gpLayouts[$this->curr_layout] = $new_info;

		if( !$this->SaveLayouts(false) ){
			return;
		}

		if( $this->layout_request || $this->page->gpLayout == $this->curr_layout ){
			$this->page->SetTheme($this->curr_layout);
		}


		$content = $this->CSSPreferenceForm($this->curr_layout,$new_info);
		$this->page->ajaxReplace = array();
		$this->page->ajaxReplace[] = array('replace','#layout_css_ul_'.$this->curr_layout,$content);
	}


	/**
	 * Remove a gadget from a layout
	 * @return null
	 *
	 */
	public function RmGadget(){
		global $langmessage;

		//$this->page->ajaxReplace	= array();

		$gadget =& $_REQUEST['gadget'];

		$handlers = $this->GetAllHandlers($this->curr_layout);
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

		$this->SaveHandlersNew($handlers,$this->curr_layout);
	}


	public static function GetRandColor(){
		$colors = self::GetColors();
		$color_key = array_rand($colors);
		return $colors[$color_key];
	}

	public static function GetColors(){

		return array(
			'#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#0000ff', '#9900ff', '#ff00ff',
			'#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#cfe2f3', '#d9d2e9', '#ead1dc',
			'#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#9fc5e8', '#b4a7d6', '#d5a6bd',
			'#e06666', '#f6b26b', '#ffd966', '#93c47d', '#76a5af', '#6fa8dc', '#8e7cc3', '#c27ba0',
			'#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3d85c6', '#674ea7', '#a64d79',
			'#990000', '#b45f06', '#bf9000', '#38761d', '#134f5c', '#0b5394', '#351c75', '#741b47',
		);
	}



	/**
	 * Update theme hooks and references in any related layouts
	 *
	 *
	 */
	public function UpgradeTheme(){
		global $langmessage, $gpLayouts;

		$theme			=& $_REQUEST['source'];
		$theme_info		= $this->ThemeInfo($theme);

		if( !$theme_info ){
			message($langmessage['OOPS'].' (Invalid Source)');
			return false;
		}


		//install addon
		$installer						= new \gp\admin\Addon\Installer();
		$installer->addon_folder_rel	= dirname($theme_info['rel']);
		$installer->code_folder_name	= '_themes';
		$installer->source				= $theme_info['full_dir'];
		$success						= $installer->Install();

		$installer->OutputMessages();

		if( !$success ){
			return;
		}

		$this->UpdateLayouts( $installer );
	}


	/**
	 * Update related layouts with new $theme_info
	 *
	 */
	public function UpdateLayouts( $installer ){
		global $gpLayouts, $langmessage;

		$theme_folder		= basename($installer->dest);

		if( strpos($installer->dest,'/data/_themes') !== false ){
			$new_layout_info = $this->AvailableTheme('/data/_themes',true, $theme_folder);
		}else{
			$new_layout_info = $this->AvailableTheme('/themes',false, $theme_folder);
		}

		if( $new_layout_info === false ){
			return;
		}

		if( $installer->has_hooks ){
			$new_layout_info['addon_key'] = $installer->config_key;
		}

		// update each layout
		foreach($gpLayouts as $layout => $layout_info){

			if( !$this->SameTheme( $layout_info, $new_layout_info ) ){
				continue;
			}

			unset( $layout_info['is_addon'], $layout_info['addon_id'], $layout_info['version'], $layout_info['name'], $layout_info['addon_key'] );

			$layout_info			+= $new_layout_info;
			$layout_info['theme']	= $theme_folder.'/'.basename($layout_info['theme']);
			$gpLayouts[$layout]		= $layout_info;
		}

		$this->SaveLayouts();
	}


	/**
	 * Return true if two layouts use the same theme
	 *
	 */
	public function SameTheme($layout_info, $new_layout_info ){


		//if we have addon ids
		if( isset($new_layout_info['addon_id']) && isset($layout_info['addon_id']) && $layout_info['addon_id'] == $new_layout_info['addon_id'] ){
			return true;
		}

		if( isset($layout_info['is_addon']) && $layout_info['is_addon'] ){
			$layout_info['rel']	= '/data/_themes/'.dirname($layout_info['theme']);
		}else{
			$layout_info['rel']	= '/themes/'.dirname($layout_info['theme']);
		}


		$keys	= array('is_addon'=>'','rel'=>'');
		$testa	= array_intersect_key($layout_info,$keys);
		$testb	= array_intersect_key($new_layout_info,$keys);
		if( $testa === $testb ){
			return true;
		}

		return false;
	}


	/**
	 *
	 */
	public function RemoteInstallConfirmed($type='themes'){
		$installer = parent::RemoteInstallConfirmed($type);
		$this->GetPossible();
		$this->UpdateLayouts( $installer );
	}



	/**
	 * Display some options before copying a layout
	 *
	 */
	public function CopyLayoutPrompt(){
		global $langmessage, $gpLayouts;

		$layout = $this->ReqLayout();
		if( $layout === false ){
			return;
		}

		$label = self::NewLabel($gpLayouts[$layout]['label']);

		echo '<h2>'.$langmessage['new_layout'].'</h2>';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content').'" method="post">';
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
		echo ' <button type="submit" name="cmd" value="CopyLayout" class="gpsubmit">'.$langmessage['save'].'</button>';
		echo ' <input type="button" name="" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';

	}

	/**
	 * Copy a layout
	 *
	 */
	public function CopyLayout(){
		global $gpLayouts, $langmessage;

		$copy_id = $this->ReqLayout();
		if( $copy_id === false ){
			return;
		}

		if( empty($_POST['label']) ){
			message($langmessage['OOPS'].'(Empty Label)');
			return;
		}

		$newLayout				= $gpLayouts[$copy_id];
		$newLayout['color']		= self::GetRandColor();
		$newLayout['label']		= htmlspecialchars($_POST['label']);

		//get new unique layout id
		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );


		$gpLayouts[$layout_id]	= $newLayout;

		if( !\gp\tool\Files::ArrayInsert($copy_id,$layout_id,$newLayout,$gpLayouts,1) ){
			message($langmessage['OOPS'].'(Not Inserted)');
			return;
		}


		//copy any css
		$css = $this->layoutCSS($copy_id);
		if( !$this->SaveCustom($layout_id, $css) ){
			return false;
		}

		$this->SaveLayouts();
	}


	/**
	 * Save the gpLayouts data
	 *
	 */
	protected function SaveLayouts($notify_user = true){
		global $gpLayouts;

		if( \gp\admin\Tools::SavePagesPHP($notify_user, $notify_user) ){
			return true;
		}

		if( is_array($this->gpLayouts_before) ){
			$gpLayouts = $this->gpLayouts_before;
		}
		return false;
	}


	/**
	 * Save the config setting
	 *
	 */
	protected function SaveConfig(){
		global $config;

		if( \gp\admin\Tools::SaveConfig(true, true) ){
			return true;
		}

		if( is_array($this->config_before) ){
			$config = $this->config_before;
		}
		return false;
	}


	/**
	 * Create a new unique layout label
	 * @static
	 */
	public function NewLabel($label){
		global $gpLayouts;
		$labels = array();

		foreach($gpLayouts as $info){
			$labels[$info['label']] = true;
		}

		$len = strlen($label);
		if( $len > 25 ){
			$label = substr($label,0,$len-2);
		}
		if( substr($label,$len-2,1) === '_' && is_numeric(substr($label,$len-1,1)) ){
			$label = substr($label,0,$len-2);
		}


		$int = 1;
		do{
			$new_label = $label.'_'.$int;
			$int++;
		}while( isset($labels[$new_label]) );

		return $new_label;
	}


	public function LoremIpsum(){
		global $langmessage, $gp_titles, $gp_menu;

		ob_start();

		echo '<h1>H1 Lorem Ipsum Heading</h1>';

		echo '<p style="font-size:larger;">Paragraph (larger): Lorem ipsum dolor sit amet, consectetur adipisicing elit, ';
		echo 'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ';
		echo 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>';

		echo '<h2>H2 Lorem Ipsum Heading</h2>';

		echo '<p>Paragraph: Excepteur sint <em>emphazize</em> cupidatat non <strong>strong</strong> proident, sunt in ';
		echo '<em><strong>emphasized strong</strong></em> culpa qui officia <a href="#">anchor</a> ';
		echo 'deserunt <u>underline</u> mollit anim id est laborum. Duis aute irure dolor in reprehenderit in voluptate ';
		echo 'velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint  occaecat cupidatat non proident, sunt in culpa qui ';
		echo '<abbr title="abbreviation">abbr</abbr> officia deserunt mollit <mark>mark</mark> anim id est <code>code</code> laborum. </p>';

		echo  '<blockquote>Blockquote: Lorem ipsum dolor sit amet, consectetur adipisicing elit.</blockquote>';

		echo '<div class="gpRow">';

		echo '<div class="gpCol-4">';
		echo   '<h4>Unordered list</h4>';
		echo   '<ul>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo   '</ul>';
		echo '</div>'; // /.gpCol-4

		echo '<div class="gpCol-4">';
		echo   '<h4>Ordered list</h4>';
		echo   '<ol>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo   '</ol>';
		echo '</div>'; // /.gpCol-4

		echo '<div class="gpCol-4">';
		echo   '<h4>Description list</h4>';
		echo   '<dl>';
		echo     '<dt>Lorem Ipsum term</dt><dd>Lorem Ipsum description</dd>';
		echo     '<dt>Lorem Ipsum term</dt><dd>Lorem Ipsum description</dd>';
		echo   '</dl>';
		echo '</div>'; // /.gpCol-4

		echo '</div>'; // /.gpRow

		echo '<hr/>';

		echo '<div class="gpRow">';

		echo '<div class="gpCol-6">';
		echo   '<h3>H3 Lorem Ipsum Heading</h3>';
		echo   '<h4>H4 Lorem Ipsum Heading</h4>';
		echo   '<h5>H5 Lorem Ipsum Heading</h5>';
		echo   '<h6>H6 Lorem Ipsum Heading</h6>';
		echo   '<p style="font-size:smaller;">Paragraph (smaller): Excepteur sint cupidatat non proident, sunt in ';
		echo   'culpa qui officia deserunt mollit anim id est laborum. Duis aute irure dolor in reprehenderit ';
		echo   'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat ';
		echo   'non proident. </p>';
		echo '</div>'; // /.gpCol-6

		echo '<div class="gpCol-6">';
		echo   '<table>';
		echo     '<thead>';
		echo       '<tr>';
		echo          '<th colspan="3">Unstyled Table - Heading</th>';
		echo       '</tr>';
		echo     '</thead>';
		echo     '<tbody>';
		echo       '<tr><td>Row&nbsp;1, Cell&nbsp;1</td><td>Row&nbsp;1, Cell&nbsp;2</td><td>Row&nbsp;1, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;2, Cell&nbsp;1</td><td>Row&nbsp;2, Cell&nbsp;2</td><td>Row&nbsp;2, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;3, Cell&nbsp;1</td><td>Row&nbsp;3, Cell&nbsp;2</td><td>Row&nbsp;3, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;4, Cell&nbsp;1</td><td>Row&nbsp;4, Cell&nbsp;2</td><td>Row&nbsp;4, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;5, Cell&nbsp;1</td><td>Row&nbsp;5, Cell&nbsp;2</td><td>Row&nbsp;5, Cell&nbsp;3</td></tr>';
		echo     '</tbody>';
		echo   '</table>';
		echo '</div>'; // /.gpCol-6

		echo '</div>'; // /.gpRow

		$this->page->non_admin_content = ob_get_clean();


		// boostrap content
		ob_start();

		echo '<h1>H1 Lorem Ipsum Heading <small>+ small</small></h1>';

		echo '<p class="lead">Lead: Lorem ipsum dolor sit amet, consectetur adipisicing elit, ';
		echo 'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ';
		echo 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>';

		echo '<h2>H2 Lorem Ipsum Heading <small>+ small</small></h2>';

		echo '<p>Default paragraph: Excepteur sint <em>emphazize</em> cupidatat non <strong>strong</strong> proident, sunt in ';
		echo '<em><strong>emphasized strong</strong></em> culpa qui officia <a href="#">anchor</a> ';
		echo 'deserunt <u>underline</u> mollit anim id est laborum. Duis aute irure dolor in reprehenderit in voluptate ';
		echo 'velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint <kbd>kbd</kbd> occaecat cupidatat non proident, sunt in culpa qui ';
		echo '<abbr title="abbreviation">abbr</abbr> officia deserunt mollit <mark>mark</mark> anim id est <code>code</code> laborum. </p>';

		echo '<p>';
		echo   '<span class="text-muted">text-muted</span> &nbsp;|&nbsp; ';
		echo   '<span class="text-primary">text-primary</span> &nbsp;|&nbsp; ';
		echo   '<span class="text-success">text-success</span> &nbsp;|&nbsp; ';
		echo   '<span class="text-info">text-info</span> &nbsp;|&nbsp; ';
		echo   '<span class="text-warning">text-warning</span> &nbsp;|&nbsp; ';
		echo   '<span class="text-danger">text-danger</span> &nbsp;|&nbsp;  ';
		echo   '<span class="badge">badge</span></a> &nbsp;|&nbsp; ';
		echo   '<span class="label label-default">label label-default</span></a>';
		echo '</p>';

		echo  '<blockquote>Blockquote: Lorem ipsum dolor sit amet, consectetur adipisicing elit.</blockquote>';

		echo '<div class="row">';

		echo '<div class="col-sm-4">';
		echo   '<h4>Unordered list</h4>';
		echo   '<ul>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo     '<li>Lorem Ipsum unordered list item</li>';
		echo   '</ul>';
		echo '</div>'; // /.col-sm-4

		echo '<div class="col-sm-4">';
		echo   '<h4>Ordered list</h4>';
		echo   '<ol>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo     '<li>Lorem Ipsum ordered list item</li>';
		echo   '</ol>';
		echo '</div>'; // /.col-sm-4

		echo '<div class="col-sm-4">';
		echo   '<h4>Description list</h4>';
		echo   '<dl>';
		echo     '<dt>Lorem Ipsum term</dt><dd>Lorem Ipsum description</dd>';
		echo     '<dt>Lorem Ipsum term</dt><dd>Lorem Ipsum description</dd>';
		echo   '</dl>';
		echo '</div>'; // /.col-sm-4

		echo '</div>'; // /.row


		echo '<div class="row">';

		echo '<div class="col-sm-6">';
		echo   '<h3>H3 Lorem Ipsum Heading <small>+ small</small></h3>';
		echo   '<h4>H4 Lorem Ipsum Heading <small>+ small</small></h4>';
		echo   '<h5>H5 Lorem Ipsum Heading <small>+ small</small></h5>';
		echo   '<h6>H6 Lorem Ipsum Heading <small>+ small</small></h6>';
		echo   '<p class="small">Small text paragraph: Excepteur sint cupidatat non proident, sunt in ';
		echo   'culpa qui officia deserunt mollit anim id est laborum. Duis aute irure dolor in reprehenderit ';
		echo   'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat ';
		echo   'non proident. </p>';
		echo '</div>'; // /.col-sm-6

		echo '<div class="col-sm-6">';
		echo   '<table class="table table-bordered table-striped table-hover">';
		echo     '<thead>';
		echo       '<tr>';
		echo          '<th>Table Heading</th>';
		echo          '<th colspan="2"><span class="text-muted" style="font-weight:normal;">';
		echo             'class=&quot;table table-bordered table-striped table-hover&quot;</span></th>';
		echo       '</tr>';
		echo     '</thead>';
		echo     '<tbody>';
		echo       '<tr><td>Row&nbsp;1, Cell&nbsp;1</td><td>Row&nbsp;1, Cell&nbsp;2</td><td>Row&nbsp;1, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;2, Cell&nbsp;1</td><td>Row&nbsp;2, Cell&nbsp;2</td><td>Row&nbsp;2, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;3, Cell&nbsp;1</td><td>Row&nbsp;3, Cell&nbsp;2</td><td>Row&nbsp;3, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;4, Cell&nbsp;1</td><td>Row&nbsp;4, Cell&nbsp;2</td><td>Row&nbsp;4, Cell&nbsp;3</td></tr>';
		echo       '<tr><td>Row&nbsp;5, Cell&nbsp;1</td><td>Row&nbsp;5, Cell&nbsp;2</td><td>Row&nbsp;5, Cell&nbsp;3</td></tr>';
		echo     '</tbody>';
		echo   '</table>';
		echo '</div>'; // /.col-sm-6

		echo '</div>'; // /.row

		echo '<hr/>';

		echo '<p>';
		echo   '<a href="#" class="btn btn-default">btn btn-default</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-link">btn btn-link</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-primary">btn btn-primary</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-success">btn btn-success</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-info">btn btn-info</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-warning">btn btn-warning</a> &nbsp;&nbsp; ';
		echo   '<a href="#" class="btn btn-danger">btn btn-danger</a>';
		echo '</p>';

		$this->page->non_admin_content_bootstrap = ob_get_clean();

	}




	public function ThemeInfo($theme){

		$template = dirname($theme);
		$color = basename($theme);

		if( !isset($this->avail_addons[$template]) || !isset($this->avail_addons[$template]['colors'][$color]) ){
			return false;
		}

		$theme_info = $this->avail_addons[$template];
		$theme_info['color'] = $color;

		return $theme_info;
	}

	/**
	 * Return an array of available themes
	 * @return array
	 *
	 */
	public function GetPossible(){

		$this->avail_addons		= array();
		$this->versions			= array();


		$this->AvailableThemes('/themes',false);			//local themes
		$this->AvailableThemes('/data/_themes',true);		//downloaded themes


		//remove older versions
		if( gp_unique_addons ){
			$themes = $this->avail_addons;
			$this->avail_addons = array();

			foreach($themes as $index => $info){

				if( !isset($info['id']) || !isset($info['version']) ){
					$this->avail_addons[$index] = $info;
					continue;
				}

				if( version_compare($this->versions[$info['id']]['version'], $info['version'],'>') ){
					continue;
				}

				$this->avail_addons[$index] = $info;
			}

			uksort($this->avail_addons,'strnatcasecmp');
		}

		$this->avail_count = count($this->avail_addons);
	}


	/**
	 * Scan the directory for available themes
	 *
	 */
	private function AvailableThemes( $dir_rel, $is_addon ){
		global $dataDir;

		$dir		= $dataDir.$dir_rel;
		$folders	= \gp\tool\Files::readDir($dir,1);

		foreach($folders as $folder){

			$addon = $this->AvailableTheme($dir_rel, $is_addon, $folder);
			if( $addon === false ){
				continue;
			}

			$index						= $addon['index'];
			$this->avail_addons[$index] = $addon;
		}
	}


	/**
	 * Get info about one theme
	 *
	 */
	private function AvailableTheme($dir_rel, $is_addon, $folder){
		global $dataDir;

		$full_dir	= $dataDir.'/'.$dir_rel.'/'.$folder;
		$ini_info	= $this->GetAvailInstall($full_dir);

		if( $ini_info === false ){
			return false;
		}

		if( $is_addon ){
			$index		= $ini_info['Addon_Name'].'(remote)';
		}else{
			$index		= $folder.'(local)';
		}
		$this->AddVersionInfo($ini_info, $index);


		$addon					= $this->IniExtract($ini_info);

		if( empty($addon['name']) ){
			$addon['name']		= $folder;
		}

		$addon['folder']		= $folder;
		$addon['colors']		= $this->GetThemeColors($full_dir);
		$addon['is_addon']		= $is_addon;
		$addon['full_dir']		= $full_dir;
		$addon['rel']			= $dir_rel.'/'.$folder;
		$addon['index']			= $index;

		return $addon;
	}


	/**
	 * Extract addon information from ini content
	 *
	 */
	public function IniExtract($ini_info){

		$extracted = array();

		if( isset($ini_info['Addon_Unique_ID']) ){
			$extracted['addon_id'] = $ini_info['Addon_Unique_ID'];
		}

		if( isset($ini_info['Addon_Version']) ){
			$extracted['version'] = $ini_info['Addon_Version'];
		}

		if( isset($ini_info['Addon_Name']) ){
			$extracted['name'] = $ini_info['Addon_Name'];
		}

		return $extracted;
	}


	/**
	 * Keep track of theme versions
	 *
	 */
	private function AddVersionInfo($ini_info,$index){

		if( isset($ini_info['Addon_Version']) && isset($ini_info['Addon_Unique_ID']) ){

			$addon_id	= $ini_info['Addon_Unique_ID'];
			$version	= $ini_info['Addon_Version'];

			if( !isset($this->versions[$addon_id]) ){
				$this->versions[$addon_id] = array('version'=>$version,'index'=>$index);
			}elseif( version_compare($this->versions[$addon_id]['version'],$version,'<') ){
				$this->versions[$addon_id] = array('version'=>$version,'index'=>$index);
			}
		}
	}


	/**
	 * Return ini info if the addon is installable
	 *
	 * @return false|array
	 */
	public function GetAvailInstall($dir){
		global $langmessage;

		$iniFile		= $dir.'/Addon.ini';
		$template_file	= $dir.'/template.php';
		$dirname		= basename($dir);

		if( !is_readable($dir) ){
			$this->invalid_folders[$dirname]	= 'Directory is not readable';
			return false;
		}

		if( !file_exists($template_file) ){
			$this->invalid_folders[$dirname]	= 'template.php is not readable or does not exist';
			return false;
		}

		if( !file_exists($iniFile) ){
			return array();
		}

		$array = \gp\tool\Ini::ParseFile($iniFile);
		if( $array === false ){
			return array();
		}

		$array += array('Addon_Version'=>'');

		return $array;
	}


	/**
	 * Get a list of theme subfolders that have style.css files
	 *
	 */
	public function GetThemeColors($dir){
		$subdirs = \gp\tool\Files::readDir($dir,1);
		$colors = array();
		asort($subdirs);
		foreach($subdirs as $subdir){

			if( \gp\tool\Output::StyleType($dir.'/'.$subdir) !== false ){
				$colors[$subdir] = $subdir;
			}

		}
		return $colors;
	}


	/**
	 * Save $layout as the default layout for the site
	 *
	 */
	public function MakeDefault(){
		global $config;

		$config['gpLayout'] = $this->curr_layout;

		if( $this->SaveConfig() ){
			$this->page->SetTheme();
			$this->SetLayoutArray();
		}
	}


	/**
	 * Save the color and label of a layout
	 *
	 */
	public function LayoutLabel(){
		global $gpLayouts, $langmessage;

		$this->page->ajaxReplace	= array();

		$layout = $this->ReqLayout();
		if( $layout === false ){
			return;
		}

		if( !empty($_POST['color']) && (strlen($_POST['color']) == 7) && $_POST['color']{0} == '#' ){
			$gpLayouts[$layout]['color'] = $_POST['color'];
		}

		$gpLayouts[$layout]['label'] = htmlspecialchars($_POST['layout_label']);


		if( !$this->SaveLayouts(false) ){
			return;
		}

		//send new label
		$layout_info				= \gp\tool::LayoutInfo($layout,false);
		$replace					= $this->GetLayoutLabel($layout, $layout_info);
		$this->page->ajaxReplace[]	= array( 'replace', '.layout_label_'.$layout, $replace);
	}


	/**
	 * Display the color selector for
	 * @param string $layout The layout being edited
	 *
	 */
	public function ColorSelector($layout = false){

		$colors = self::GetColors();
		echo '<div id="layout_ident" class="gp_floating_area">';
		echo '<div>';

		if( $layout === false ){
			echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content').'" method="post">';
		}else{
			echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content/Edit/'.$layout).'" method="post">';
		}
		echo '<input type="hidden" name="layout" value="" />';
		echo '<input type="hidden" name="color" value="" />';
		echo '<input type="hidden" name="cmd" value="LayoutLabel" />';

		echo '<table>';


		echo '<tr><td>';
		echo ' <a class="layout_color_id" id="current_color"></a> ';
		echo '<input type="text" name="layout_label" value="" maxlength="25"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo '<div class="colors">';
		foreach($colors as $color){
			echo '<a class="color" style="background-color:'.$color.'" title="'.$color.'" data-arg="'.$color.'"></a>';
		}
		echo '</div>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo ' <input type="submit" name="" value="Ok" class="gpajax close_color_dialog gpsubmit" />';
		echo ' <input type="button" class="close_color_dialog gpcancel" name="" value="Cancel" />';
		echo '</td></tr>';

		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';

	}


	/**
	 * Display layout label and options
	 *
	 */
	public function LayoutDiv($layout,$info){
		global $langmessage;

		$layout_info = \gp\tool::LayoutInfo($layout,false);


		echo '<div class="panelgroup" id="panelgroup_'.md5($layout).'">';
		echo $this->GetLayoutLabel($layout, $info);


		echo '<div class="panelgroup2">';
		echo '<ul class="submenu">';

		echo '<li>';
		echo \gp\tool::Link('Admin_Theme_Content/Edit/'.rawurlencode($layout),$langmessage['edit_this_layout'],'',' title="'.htmlspecialchars($langmessage['Arrange Content']).'" ');
		echo '</li>';



		//layout options
		echo '<li class="expand_child_click">';
		echo '<a>'.$langmessage['Layout Options'].'</a>';
		echo '<ul>';
		$this->LayoutOptions($layout,$layout_info);
		echo '</ul>';



		//css options
		echo '<li class="expand_child_click">';
		echo '<a>CSS</a>';
		echo $this->CSSPreferenceForm($layout,$layout_info);
		echo '</li>';

		$this->LayoutDivAddon($layout_info);

		//new versions
		if( isset($layout_info['addon_id']) ){
			$addon_id = $layout_info['addon_id'];
			$version =& $layout_info['version'];

			//local or already downloaded
			if( isset($this->versions[$addon_id]) && version_compare($this->versions[$addon_id]['version'],$version,'>') ){
				$version_info = $this->versions[$addon_id];
				$label = $langmessage['upgrade'].' &nbsp; '.$version_info['version'];
				$source = $version_info['index'].'/'.$layout_info['theme_color']; //could be different folder
				echo '<div class="gp_notice">';
				echo \gp\tool::Link('Admin_Theme_Content',$label,'cmd=UpgradeTheme&source='.$source,array('data-cmd'=>'creq'));
				echo '</div>';


			//remote version
			}elseif( gp_remote_themes && isset(\gp\admin\Tools::$new_versions[$addon_id]) && version_compare(\gp\admin\Tools::$new_versions[$addon_id]['version'],$version,'>') ){
				$version_info = \gp\admin\Tools::$new_versions[$addon_id];
				$label = $langmessage['new_version'].' &nbsp; '.$version_info['version'].' &nbsp; ('.CMS_READABLE_DOMAIN.')';
				echo '<div class="gp_notice">';
				echo \gp\tool::Link('Admin_Theme_Content',$label,'cmd=RemoteInstall&id='.$addon_id.'&name='.rawurlencode($version_info['name']).'&layout='.$layout);
				echo '</div>';
			}

		}


		echo '</ul>';

		echo '</div>';
		echo '</div>';
	}


	/**
	 * Output addon information about a layout
	 *
	 */
	public function LayoutDivAddon($layout_info){
		global $langmessage;

		// layouts with hooks
		ob_start();
		$addon_config = false;
		if( isset($layout_info['addon_key']) ){
			$addon_key = $layout_info['addon_key'];
			$addon_config = \gp\tool\Plugins::GetAddonConfig($addon_key);
			echo '<li>';
			echo \gp\tool::link('Admin/Addons/'.\gp\admin\Tools::encode64($addon_key),'<i class="fa fa-plug"></i> '.$addon_config['name']);
			echo '</li>';

			//hooks
			$this->AddonPanelGroup($addon_key, false );
		}


		//version
		if( !empty($layout_info['version']) ){
			echo '<li><a>'.$langmessage['Your_version'].' '.$layout_info['version']. '</a></li>';
		}elseif( $addon_config && !empty($addon_config['version']) ){
			echo '<li><a>'.$langmessage['Your_version'].' '.$addon_config['version']. '</a></li>';
		}

		//upgrade
		if( $addon_config !== false ){
			echo '<li>';
			if( $layout_info['is_addon'] ){
				$source = $layout_info['name'].'(remote)/'.$layout_info['theme_color'];
			}else{
				$source = $layout_info['theme_name'].'(local)/'.$layout_info['theme_color'];
			}
			echo \gp\tool::Link('Admin_Theme_Content',$langmessage['upgrade'],'cmd=UpgradeTheme&source='.rawurlencode($source),array('data-cmd'=>'creq'));
			echo '</li>';
		}


		$options = ob_get_clean();

		if( !empty($options) ){
			echo '<li class="expand_child_click">';
			echo '<a>'.$langmessage['options'].'</a>';
			echo '<ul>';

			echo $options;

			echo '</ul></li>';
		}
	}


	/**
	 * Return the layout name and id color
	 *
	 */
	public function GetLayoutLabel( $layout, $layout_info ){
		global $config, $langmessage, $config;

		ob_start();
		echo '<span class="layout_label_'.$layout.' layout_label">';
		echo '<a data-cmd="layout_id" title="'.$layout_info['color'].'" data-arg="'.$layout_info['color'].'">';
		echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
		echo '<input type="hidden" name="layout_label" value="'.$layout_info['label'].'"  /> ';
		echo '<span class="layout_color_id" style="background-color:'.$layout_info['color'].';"></span> ';
		if( $config['gpLayout'] == $layout ){
			echo ' <span class="layout_default"> ('.$langmessage['default'].')</span>';
			echo '&nbsp;';
		}
		echo $layout_info['label'];

		echo '</a>';
		echo '</span>';
		return ob_get_clean();
	}



	/**
	 * Return form for name based menu classes and ordered menu classes
	 *
	 */
	public function CSSPreferenceForm($layout,$layout_info){
		global $langmessage;

		ob_start();
		echo '<ul id="layout_css_ul_'.$layout.'">';


		// name based menu classes
		echo '<li>';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="layout" value="'.$layout.'" />';
		echo '<input type="hidden" name="cmd" value="CSSPreferences" />';
		$checked = '';
		$value = 'on';
		if( !isset($layout_info['menu_css_ordered']) ){
			$checked = 'checked="checked"';
			$value = 'off';
		}
		echo '<input type="hidden" name="menu_css_ordered" value="'.$value.'" />';
		echo '<label>';
		echo '<input type="checkbox" name="none" value="" '.$checked.' class="gpajax" />';
		echo ' Name Based Menu Classes';
		echo '</label>';
		echo '</form>';
		echo '</li>';

		//ordered menu classes
		echo '<li>';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="layout" value="'.$layout.'" />';
		echo '<input type="hidden" name="cmd" value="CSSPreferences" />';
		$checked = '';
		$value = 'on';
		if( !isset($layout_info['menu_css_indexed']) ){
			$checked = 'checked="checked"';
			$value = 'off';
		}
		echo '<input type="hidden" name="menu_css_indexed" value="'.$value.'" />';
		echo '<label>';
		echo '<input type="checkbox" name="none" value="" '.$checked.' class="gpajax" />';
		echo ' Ordered Menu Classes';
		echo '</label>';
		echo '</form>';
		echo '</li>';
		echo '</ul>';
		return ob_get_clean();
	}

	public function ThemeLabel($theme_color){

		$theme = $theme_color;
		$color = false;
		if( strpos($theme_color,'/') ){
			list($theme,$color) = explode('/',$theme_color);
		}

		foreach($this->avail_addons as $info){

			if( $info['folder'] == $theme ){
				$theme = $info['name'];
				break;
			}
		}

		if( $color ){
			return $theme.'/'.$color;
		}
		return $theme;
	}

	public function TitlesCount($layout){
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
	public function RestoreLayout(){
		$this->SaveHandlersNew(array(),$this->curr_layout);
	}

	public function SaveHandlersNew($handlers,$layout=false){
		global $config, $langmessage, $gpLayouts;

		//make sure the keys are sequential
		foreach($handlers as $container => $container_info){
			if( is_array($container_info) ){
				$handlers[$container] = array_values($container_info);
			}
		}

		if( $layout === false ){
			$layout = $this->curr_layout;
		}

		if( !isset( $gpLayouts[$layout] )  ){
			message($langmessage['OOPS']);
			return false;
		}

		if( count($handlers) === 0 ){
			unset($gpLayouts[$layout]['handlers']);
		}else{
			$gpLayouts[$layout]['handlers'] = $handlers;
		}

		$this->SaveLayouts();
	}


	public function GetAllHandlers($layout=false){
		global $gpLayouts, $config;

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
	public function PrepContainerHandlers(&$handlers,$container,$gpOutCmd){
		if( isset($handlers[$container]) && is_array($handlers[$container]) ){
			return;
		}
		$handlers[$container] = $this->GetDefaultList($container,$gpOutCmd);
	}



	public function GetDefaultList($container,$gpOutCmd){
		global $config;

		if( $container !== 'GetAllGadgets' ){

			//Just a container that doesn't have content by default
			// ex: 		\gp\tool\Output::Get('AfterContent');
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


	public function ReturnHeader(){
		global $page;

		$page->ajaxReplace		= array();
		$page->ajaxReplace[]	= array('reload');
	}



	public function SetLayoutArray(){
		global $gp_menu, $gp_titles, $gp_index, $config;


		$titleThemes	= array();
		$customThemes	= array();
		$max_level		= 5;


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


	/**
	 * Remote a layout
	 *
	 */
	public function DeleteLayout(){
		global $gpLayouts, $langmessage, $gp_titles;

		$layout =& $_POST['layout'];
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (Layout not set)');
			return false;
		}

		//remove from $gp_titles
		$this->RmLayout($layout);
	}


	/**
	 * Remove a layout from $gp_titles and $gpLayouts
	 *
	 */
	public function RmLayout($layout){
		global $gp_titles, $gpLayouts, $langmessage;

		$this->RmLayoutPrep($layout);


		//determine if code in /data/_theme should be removed
		$rm_addon = $this->RemoveAddonCode($layout);

		unset($gpLayouts[$layout]);

		//delete and save
		if( $rm_addon ){
			$installer = new \gp\admin\Addon\Installer();
			$installer->rm_folders = false;
			if( !$installer->Uninstall($rm_addon) ){
				$gpLayouts = $this->gpLayouts_before;
			}
			$installer->OutputMessages();
		}

		if( !$this->SaveLayouts() ){
			return false;
		}

		//remove custom css
		$this->RemoveCSS($layout);
	}


	/**
	 * Determine if the code in /data/_theme should be removed
	 *
	 */
	public function RemoveAddonCode($layout){
		global $gpLayouts;


		if( !isset($gpLayouts[$layout]['addon_key']) ){
			return false;
		}

		$rm_addon = $gpLayouts[$layout]['addon_key'];

		//don't remove if there are other layouts using the same code
		foreach($gpLayouts as $layout_id => $info){
			if( $layout_id == $layout ){
				continue;
			}
			if( !array_key_exists('addon_key',$info) ){
				continue;
			}
			if( $info['addon_key'] == $rm_addon ){
				$rm_addon = false;
			}
		}

		return $rm_addon;
	}


	public function RmLayoutPrep($layout){
		global $gp_titles;

		//remove from $gp_titles
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
	}


	public function LayoutUrl($layout,&$query=''){
		$url = 'Admin_Theme_Content';
		if( $this->layout_request ){
			$url = 'Admin_Theme_Content/Edit/'.rawurlencode($layout);
		}else{
			$query .= '&layout='.rawurlencode($layout);
		}
		return $url;
	}

	public function LayoutLink($layout,$label,$query,$attr){
		$url = $this->LayoutUrl($layout,$query);
		return \gp\tool::Link($url,$label,$query,$attr);
	}

	/**
	 * Get the requested layout
	 *
	 */
	public function ReqLayout(){
		global $langmessage, $gpLayouts;

		if( !isset($_REQUEST['layout']) || !isset($gpLayouts[$_REQUEST['layout']]) ){
			message($langmessage['OOPS'].'(Invalid layout)');
			return;
		}

		return $_REQUEST['layout'];
	}

}

