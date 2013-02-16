<?php
defined('is_running') or die('Not an entry point...');


class special_display extends display{
	var $pagetype = 'special_display';
	var $requested = false;

	var $editable_content = false;
	var $editable_details = false; //true; //could be true

	function special_display($title){
		global $langmessage,$config;

		$this->requested = $title;
		$this->title = $title;
	}

	function RunScript(){
		global $gp_index, $langmessage,$page;

		$scriptinfo = special_display::GetScriptInfo($this->title);
		if( $scriptinfo === false ){

			switch($this->title){
				case 'Special_ExtraJS';
					$this->ExtraJS();
				//dies
			}


			$this->Error_404($this->title);
			return;
		}

		$this->gp_index = $gp_index[$this->title];
		$this->TitleInfo = $scriptinfo;

		$menu_permissions = false;
		if( common::LoggedIn() ){
			$menu_permissions = admin_tools::HasPermission('Admin_Menu');
			if( $menu_permissions ){
				$page->admin_links[] = common::Link($this->title,$langmessage['rename/details'],'cmd=renameform','data-cmd="gpajax"');
				$page->admin_links[] = common::Link('Admin_Menu',$langmessage['current_layout'],'cmd=layout&from=page&index='.urlencode($this->gp_index),array('title'=>$langmessage['current_layout'],'data-cmd'=>'gpabox'));
			}
			if( admin_tools::HasPermission('Admin_User') ){
				$page->admin_links[] = common::Link('Admin_Users',$langmessage['permissions'],'cmd=file_permissions&index='.urlencode($this->gp_index),array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox'));
			}
		}


		//allow addons to affect page actions and how a page is displayed
		$cmd = common::GetCommand();
		$cmd_after = gpPlugin::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

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
			}
		}

		$this->contentBuffer = special_display::ExecInfo($scriptinfo);
	}

	function RenameForm(){
		global $page,$gp_index;

		includeFile('tool/Page_Rename.php');
		$action = common::GetUrl($this->title);
		gp_rename::RenameForm($this->title,$action);
	}

	function RenameFile(){
		global $langmessage, $gp_index, $page;

		includeFile('tool/Page_Rename.php');
		$new_title = gp_rename::RenameFile($this->title);
		if( ($new_title !== false) && $new_title != $this->title ){
			message(sprintf($langmessage['will_redirect'],common::Link_Page($new_title)));
			$page->head .= '<meta http-equiv="refresh" content="15;url='.common::GetUrl($new_title).'">';
			return true;
		}
		return false;
	}

	/**
	 *
	 * @static
	 */
	static function GetScriptInfo(&$requested,$redirect=true){
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
		$translated = common::SpecialHref($requested);
		if( $translated != $requested ){
			$requested = $translated;
			if( $redirect ){
				$title = common::GetUrl($requested,http_build_query($_GET),false);
				common::Redirect($title);
			}
		}


		//get the script info
		$parts = explode('/',$requested);
		do{
			$requested = implode('/',$parts);
			if( isset($gp_index[$requested]) ){

				$index = $gp_index[$requested];
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


	static function ExecInfo($scriptinfo){
		global $dataDir;

		ob_start();
		gpOutput::ExecInfo($scriptinfo);
		return ob_get_clean();
	}


	function ExtraJS(){
		header('Content-type: application/javascript');
		includeFile('tool/editing.php');

		$_GET += array('which'=>array());

		foreach((array)$_GET['which'] as $which_code){

			switch($which_code){

				case 'autocomplete2':
					$options['admin_vals'] = false;
					$options['var_name'] = 'gp_include_titles';
					echo gp_edit::AutoCompleteValues(false,$options);
				break;

				case 'autocomplete':
					echo gp_edit::AutoCompleteValues(true);
				break;

				case 'gp_ckconfig':
					$options = array();
					echo gp_edit::CKConfig($options,'gp_ckconfig');
				break;
			}
		}

		die();
	}

}