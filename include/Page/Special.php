<?php

namespace gp\Page;

defined('is_running') or die('Not an entry point...');


class Special extends \gp\Page{
	public $pagetype = 'special_display';
	public $requested = false;

	public function __construct($title){

		$this->requested	= $title;
		$this->title		= $title;
	}

	public function RunScript(){
		global $gp_index, $langmessage;

		$scriptinfo = self::GetScriptInfo($this->title);
		if( $scriptinfo === false ){

			switch($this->title){
				case 'Special_ExtraJS';
					$this->ExtraJS();
				//dies
			}


			$this->Error_404($this->title);
			return;
		}

		$this->gp_index			= $gp_index[$this->title];
		$this->TitleInfo		= $scriptinfo;


		if( !$this->CheckVisibility() ){
			return false;
		}

		//allow addons to affect page actions and how a page is displayed
		$cmd			= \common::GetCommand();
		$cmd_after		= \gpPlugin::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		if( \common::LoggedIn() ){
			$menu_permissions = \admin_tools::HasPermission('Admin_Menu');
			if( $menu_permissions ){
				switch($cmd){
					// rename & details
					case 'renameform':
						$this->RenameForm();
					return;
					case 'renameit':
						if( $this->RenameFile() ){
							return;
						}
					break;
					case 'ToggleVisibility':
						$this->ToggleVisibility();
					break;
				}
			}

		}


		$this->contentBuffer = self::ExecInfo($scriptinfo);
	}


	/**
	 * Generate admin toolbar links
	 *
	 */
	public function AdminLinks(){
		global $langmessage;

		$admin_links			= $this->admin_links;

		$menu_permissions		= \admin_tools::HasPermission('Admin_Menu');


		if( $menu_permissions ){
			//visibility
			$q							= 'cmd=ToggleVisibility';
			$label						= '<i class="fa fa-eye-slash"></i> '.$langmessage['Visibility'].': '.$langmessage['Private'];
			if( !$this->visibility ){
				$label					= '<i class="fa fa-eye"></i> '.$langmessage['Visibility'].': '.$langmessage['Public'];
				$q						.= '&visibility=private';
			}
			$attrs						= array('title'=>$label,'data-cmd'=>'creq');
			$admin_links[]		= \common::Link($this->title,$label,$q,$attrs);
		}


		// page options: less frequently used links that don't have to do with editing the content of the page
		$option_links		= array();
		if( $menu_permissions ){
			$option_links[] = \common::Link($this->title,$langmessage['rename/details'],'cmd=renameform','data-cmd="gpajax"');
			$option_links[] = \common::Link('Admin/Menu',$langmessage['current_layout'],'cmd=layout&from=page&index='.urlencode($this->gp_index),array('title'=>$langmessage['current_layout'],'data-cmd'=>'gpabox'));
		}

		if( \admin_tools::HasPermission('Admin_User') ){
			$option_links[] = \common::Link('Admin/Users',$langmessage['permissions'],'cmd=file_permissions&index='.urlencode($this->gp_index),array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox'));
		}

		if( !empty($option_links) ){
			$admin_links[$langmessage['options']] = $option_links;
		}


		return $admin_links;
	}


	public function RenameForm(){
		global $gp_index;

		$action = \common::GetUrl($this->title);
		\gp\Page\Rename::RenameForm( $this->gp_index, $action );
	}

	public function RenameFile(){
		return \gp\Page\Rename::RenamePage($this);
	}


	/**
	 * Toggle the visibility of the current page
	 *
	 */
	public function ToggleVisibility(){
		$_REQUEST += array('visibility'=>'');
		\gp\Page\Visibility::TogglePage($this, $_REQUEST['visibility']);
	}


	/**
	 *
	 */
	public static function GetScriptInfo(&$requested,$redirect=true){
		global $dataDir,$gp_index,$gp_titles;

		$scripts['special_site_map']['script'] = '/include/special/special_map.php';
		$scripts['special_site_map']['class'] = 'special_map';

		$scripts['special_galleries']['script'] = '/include/special/special_galleries.php';
		$scripts['special_galleries']['class'] = 'special_galleries';

		$scripts['special_contact']['script'] = '/include/special/special_contact.php';
		$scripts['special_contact']['class'] = 'special_contact';

		$scripts['special_missing']['script'] = '/include/special/special_missing.php';
		$scripts['special_missing']['class'] = 'special_missing';

		$scripts['special_gpsearch']['script'] = '/include/special/special_search.php';
		$scripts['special_gpsearch']['class'] = 'special_gpsearch';

		//check for use of a index instead of a page title
		$translated = \common::SpecialHref($requested);
		if( $translated != $requested ){
			$requested = $translated;
			if( $redirect ){
				$title = \common::GetUrl($requested,http_build_query($_GET),false);
				\common::Redirect($title);
			}
		}


		//get the script info
		$parts = explode('/',$requested);
		do{
			$requested = implode('/',$parts);
			if( isset($gp_index[$requested]) ){

				$index = $gp_index[$requested];
				// Merge page data & script data if both exist
				if( isset($scripts[$index]) && isset($gp_titles[$index])){
					return array_merge($scripts[$index], $gp_titles[$index]);
				}
				if( isset($scripts[$index]) ){
					return $scripts[$index];
				}

				if( isset($gp_titles[$index]) ){
					return $gp_titles[$index];
				}
			}
			array_pop($parts);
		}while( count($parts) );

		return false;
	}


	public static function ExecInfo($scriptinfo){
		global $dataDir;

		ob_start();
		\gpOutput::ExecInfo($scriptinfo);
		return ob_get_clean();
	}


	public function ExtraJS(){
		header('Content-type: application/javascript');
		includeFile('tool/editing.php');

		trigger_error('Deprecated: special_extrajs');

		$_GET += array('which'=>array());

		foreach((array)$_GET['which'] as $which_code){

			switch($which_code){

				case 'autocomplete2':
					$options['admin_vals'] = false;
					$options['var_name'] = 'gp_include_titles';
					echo \gp_edit::AutoCompleteValues(false,$options);
				break;

				case 'autocomplete':
					echo \gp_edit::AutoCompleteValues(true);
				break;

				case 'gp_ckconfig':
					$options = array();
					echo \gp_edit::CKConfig($options,'gp_ckconfig');
				break;
			}
		}

		die();
	}

}
