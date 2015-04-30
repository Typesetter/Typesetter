<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_tools.php');

class admin_display extends display{
	var $pagetype = 'admin_display';
	var $requested = false;

	var $editable_content = false;
	var $editable_details = false;

	var $show_admin_content = true;
	var $non_admin_content = '';
	var $admin_html = '';

	function __construct($title){
		global $langmessage, $gpAdmin;


		$this->requested	= str_replace(' ','_',$title);
		$scripts			= admin_tools::AdminScripts();

		$pos = strpos($title,'/');
		if( $pos > 0 ){
			$title = substr($title,0,$pos);
		}
		$this->title		= $title;


		if( isset($scripts[$title]) && isset($scripts[$title]['label']) ){
			$this->label = $scripts[$title]['label'];
		}else{
			//$this->label = str_replace('_',' ',$title);
			$this->label = $langmessage['administration'];
		}

		$this->head .= "\n".'<meta name="robots" content="noindex,nofollow" />';
		@header( 'X-Frame-Options: SAMEORIGIN' );
	}


	function RunScript(){

		ob_start();
		$this->RunAdminScript();
		$this->contentBuffer = ob_get_clean();


		//display admin area in full window?
		if( $this->FullDisplay() ){
			$this->get_theme_css = false;
			$_REQUEST['gpreq'] = 'admin';
		}
	}

	//display admin area in full window
	function FullDisplay(){

		if( common::RequestType() == 'template'
			&& $this->show_admin_content
			){
				return true;
		}

		return false;
	}


	//called by templates
	function GetContent(){

		$this->GetGpxContent();

		if( !empty($this->non_admin_content) ){
			echo '<div class="filetype-text cf">';
			//echo '<div id="gpx_content" class="filetype-text">'; //id="gpx_content" conflicts with admin content
			echo $this->non_admin_content;
			echo '</div>';
		}

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent($ajax = false){
		global $gp_admin_html;

		if( empty($this->show_admin_content) ){
			return;
		}

		$request_type = common::RequestType();
		if( $request_type == 'body' ){
			echo $this->contentBuffer;
			return;
		}

		ob_start();
		echo '<div id="gpx_content"><div id="admincontent">';
		$this->AdminContentPanel();
		$this->BreadCrumbs();
		echo '<div id="admincontent_inner">';



		echo $this->contentBuffer;
		echo '</div></div></div>';
		$admin_content = ob_get_clean();

		if( !$ajax ){
			$gp_admin_html .= '<div id="admincontainer" >'.$admin_content.'</div>';
			return;
		}
		echo $admin_content;
	}

	function BreadCrumbs(){
		global $langmessage;

		echo '<div id="admin_breadcrumbs" class="cf">';

		echo common::Link('',$langmessage['Homepage']);
		echo ' &#187; ';
		echo common::Link('Admin',$langmessage['administration']);


		if( !empty($this->title) && !empty($this->label) && $this->label != $langmessage['administration'] ){
			echo ' &#187; ';
			echo common::Link($this->title,$this->label);
		}
		echo '</div>';
	}


	/**
	 * Output toolbar for admin window
	 *
	 */
	function AdminContentPanel(){
		global $langmessage;

		echo '<div id="admincontent_panel" class="toolbar cf">';
		gpOutput::Get('Menu');


		echo '<form method="get" action="/index.php/Search" id="panel_search" class="cf">';
		echo '<input type="text" value="" name="q">';
		echo '<button class="gpabox" type="submit"></button>';
		echo '</form>';

		echo '</div>';
	}


	/**
	 * Find the requested admin script and execute it if the user has permissions to view it
	 *
	 */
	function RunAdminScript(){
		global $dataDir,$langmessage;

		//resolve request for /Admin_Theme_Content if the request is for /Admin_Theme_Conent/1234
		$parts = explode('/',$this->requested);
		do{

			$request_string = implode('/',$parts);
			$scriptinfo = false;
			$scripts = admin_tools::AdminScripts();
			if( isset($scripts[$request_string]) ){
				$scriptinfo = $scripts[$request_string];
				if( admin_tools::HasPermission($request_string) ){

					admin_display::OrganizeFrequentScripts($request_string);
					gpOutput::ExecInfo($scriptinfo);

					return;
				}else{
					message($langmessage['not_permitted']);
					$parts = array();
				}
			}elseif( count($scripts) > 0 ){

				//check case
				$case_check = array_keys($scripts);
				$case_check = array_combine($case_check, $case_check);
				$case_check = array_change_key_case( $case_check, CASE_LOWER );

				$lower = strtolower($request_string);
				if( isset($case_check[$lower]) ){
					$location = common::GetUrl($case_check[$lower],http_build_query($_GET),false);
					common::Redirect($location);
				}
			}


			//these are here because they should be available to everyone
			switch($request_string){
				case 'Admin_Browser':
					includeFile('admin/admin_browser.php');
					new admin_browser();
				return;

				case 'Admin_Preferences':
					$this->label = $langmessage['Preferences'];
					includeFile('admin/admin_preferences.php');
					new admin_preferences();
				return;

				case 'Admin_About':
					$this->label = 'About gpEasy';
					includeFile('admin/admin_about.php');
					new admin_about();
				return;

				case 'Admin_Finder':
					if( admin_tools::HasPermission('Admin_Uploaded') ){
						includeFile('thirdparty/finder/connector.php');
						return;
					}
				break;

			}
			array_pop($parts);
		}while( count($parts) );

		$this->AdminPanel();
	}


	/**
	 * Show the default admin page
	 *
	 */
	function AdminPanel(){
		global $langmessage;

		$cmd = common::GetCommand();
		switch($cmd){
			case 'embededcheck':
				includeFile('tool/update.php');
				new update_class('embededcheck');
			return;

			case 'autocomplete-titles':
			$opts = array('var_name'=>false);
			echo gp_edit::AutoCompleteValues(false,$opts);
			die();
		}

		$this->head_js[] = '/include/js/auto_width.js';

		echo '<h2>'.$langmessage['administration'].'</h2>';

		echo '<div id="adminlinks2">';
		admin_tools::AdminPanelLinks(false);
		echo '</div>';
	}


	static function OrganizeFrequentScripts($page){
		global $gpAdmin;

		if( !isset($gpAdmin['freq_scripts']) ){
			$gpAdmin['freq_scripts'] = array();
		}
		if( !isset($gpAdmin['freq_scripts'][$page]) ){
			$gpAdmin['freq_scripts'][$page] = 0;
		}else{
			$gpAdmin['freq_scripts'][$page]++;
			if( $gpAdmin['freq_scripts'][$page] >= 10 ){
				admin_display::CleanFrequentScripts();
			}
		}

		arsort($gpAdmin['freq_scripts']);
	}

	static function CleanFrequentScripts(){
		global $gpAdmin;

		//reduce to length of 5;
		$count = count($gpAdmin['freq_scripts']);
		if( $count > 3 ){
			for($i=0;$i < ($count - 5);$i++){
				array_pop($gpAdmin['freq_scripts']);
			}
		}

		//reduce the hit count on each of the top five
		$min_value = end($gpAdmin['freq_scripts']);
		foreach($gpAdmin['freq_scripts'] as $page => $hits){
			$gpAdmin['freq_scripts'][$page] = $hits - $min_value;
		}
	}


}
