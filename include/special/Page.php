<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');


class Page extends \gp\Page{
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


			$this->Error_404();
			return;
		}

		$this->gp_index			= $gp_index[$this->title];
		$this->TitleInfo		= $scriptinfo;


		if( !$this->CheckVisibility() ){
			return false;
		}

		//allow addons to affect page actions and how a page is displayed
		$cmd			= \gp\tool::GetCommand();
		$cmd_after		= \gp\tool\Plugins::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		if( \gp\tool::LoggedIn() && \gp\admin\Tools::HasPermission('Admin_Menu') ){
			$this->cmds['RenameForm']			= '\\gp\\Page\\Rename::RenameForm';
			$this->cmds['RenameFile']			= '\\gp\\Page\\Rename::RenamePage';
			$this->cmds['ToggleVisibility']		= array('\\gp\\Page\\Visibility::TogglePage','DefaultDisplay');
			$this->cmds['ManageSections']		= '\gp\Page\Edit::ManageSections';
		}

		$this->RunCommands($cmd);
	}

	public function DefaultDisplay(){
		$this->contentBuffer = self::ExecInfo($this->TitleInfo);
	}

	public static function ExecInfo($scriptinfo ){
		ob_start();
		\gp\tool\Output::ExecInfo($scriptinfo);
		return ob_get_clean();
	}



	/**
	 * Generate admin toolbar links
	 *
	 */
	public function AdminLinks(){
		global $langmessage;

		$admin_links			= $this->admin_links;

		$menu_permissions		= \gp\admin\Tools::HasPermission('Admin_Menu');


		if( $menu_permissions ){
			//visibility
			$q							= 'cmd=ToggleVisibility';
			$label						= '<i class="fa fa-eye-slash"></i> '.$langmessage['Visibility'].': '.$langmessage['Private'];
			if( !$this->visibility ){
				$label					= '<i class="fa fa-eye"></i> '.$langmessage['Visibility'].': '.$langmessage['Public'];
				$q						.= '&visibility=private';
			}
			$attrs						= array('title'=>$label,'data-cmd'=>'creq');
			$admin_links[]				= \gp\tool::Link($this->title,$label,$q,$attrs);
		}


		// page options: less frequently used links that don't have to do with editing the content of the page
		$option_links		= array();
		if( $menu_permissions ){
			$option_links[] = \gp\tool::Link($this->title,$langmessage['rename/details'],'cmd=renameform&index='.urlencode($this->gp_index),'data-cmd="gpajax"');
			$option_links[] = \gp\tool::Link('Admin/Menu',$langmessage['current_layout'],'cmd=layout&from=page&index='.urlencode($this->gp_index),array('title'=>$langmessage['current_layout'],'data-cmd'=>'gpabox'));
		}

		if( \gp\admin\Tools::HasPermission('Admin_User') ){
			$option_links[] = \gp\tool::Link('Admin/Users',$langmessage['permissions'],'cmd=file_permissions&index='.urlencode($this->gp_index),array('title'=>$langmessage['permissions'],'data-cmd'=>'gpabox'));
		}

		if( !empty($option_links) ){
			$admin_links[$langmessage['options']] = $option_links;
		}


		return $admin_links;
	}


	/**
	 *
	 */
	public static function GetScriptInfo(&$requested,$redirect=true){
		global $dataDir,$gp_index,$gp_titles;

		$scripts['special_site_map']['class'] = '\\gp\\special\\Map';

		$scripts['special_galleries']['class'] = '\\gp\\special\\Galleries';

		$scripts['special_contact']['class'] = '\\gp\\special\\Contact';

		$scripts['special_missing']		= array(	'class'		=> '\\gp\\special\\Missing',
													'method'	=> 'RunScript',
													);

		$scripts['special_gpsearch']['class'] = '\\gp\\special\\Search';

		//check for use of a index instead of a page title
		$translated = \gp\tool::SpecialHref($requested);
		if( $translated != $requested ){
			$requested = $translated;
			if( $redirect ){
				$title = \gp\tool::GetUrl($requested,http_build_query($_GET),false);
				\gp\tool::Redirect($title);
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


	public function ExtraJS(){
		header('Content-type: application/javascript');

		trigger_error('Deprecated: special_extrajs');

		$_GET += array('which'=>array());

		foreach((array)$_GET['which'] as $which_code){

			switch($which_code){

				case 'autocomplete2':
					$options['admin_vals'] = false;
					$options['var_name'] = 'gp_include_titles';
					echo \gp\tool\Editing::AutoCompleteValues(false,$options);
				break;

				case 'autocomplete':
					echo \gp\tool\Editing::AutoCompleteValues(true);
				break;

				case 'gp_ckconfig':
					$options = array();
					echo \gp\tool\Editing::CKConfig($options,'gp_ckconfig');
				break;
			}
		}

		die();
	}

}
