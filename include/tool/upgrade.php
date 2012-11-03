<?php
defined('is_running') or die('Not an entry point...');


class gpupgrade{

	function gpupgrade(){
		global $config;

		includeFile('admin/admin_tools.php');


		if( version_compare($config['gpversion'],'1.6','<') ){
			die('Please upgrade to version 1.6, then 1.7 before upgrading to this version. You current version is '.$config['gpversion']);
		}

		if( version_compare($config['gpversion'],'1.7a2','<') ){
			die('Please upgrade to version 1.7 before upgrading to this version. You current version is '.$config['gpversion']);
		}

		if( version_compare($config['gpversion'],'1.8a1','<') ){
			die('Please upgrade to version 2.0 before upgrading to this version. You current version is '.$config['gpversion']);
		}

		if( version_compare($config['gpversion'],'2.3.4','<') ){
			$this->Upgrade_234();
		}

	}


	/**
	 * Update the gp_index, gp_titles and menus so that special pages can be renamed
	 *
	 */
	function Upgrade_234(){
		global $gp_index, $gp_titles, $gp_menu, $config, $dataDir;
		includeFile('tool/gpOutput.php');

		$special_indexes = array();
		$new_index = array();
		$new_titles = array();
		foreach($gp_index as $title => $index){

			$info = $gp_titles[$index];
			$type = common::SpecialOrAdmin($title);
			if( $type == 'special' ){
				$special_indexes[$index] = strtolower($title);
				$index = strtolower($title);
				$info['type'] = 'special'; //some older versions didn't maintain this value well
			}
			$new_index[$title] = $index;
			$new_titles[$index] = $info;
		}
		$gp_titles = $new_titles;
		$gp_index = $new_index;

		//update gp_menu
		$gp_menu = $this->FixMenu($gp_menu,$special_indexes);

		//save pages
		if( !admin_tools::SavePagesPHP() ){
			return;
		}

		$config['gpversion'] = '2.3.4';
		admin_tools::SaveConfig();


		//update alt menus
		if( isset($config['menus']) && is_array($config['menus']) ){
			foreach($config['menus'] as $key => $value){
				$menu_file = $dataDir.'/data/_menus/'.$key.'.php';
				if( file_exists($menu_file) ){
					$menu = gpOutput::GetMenuArray($key);
					$menu = $this->FixMenu($menu,$special_indexes);
					gpFiles::SaveArray($menu_file,'menu',$menu);
				}
			}
		}
	}

	function FixMenu($menu,$special_indexes){
		$new_menu = array();
		foreach($menu as $key => $value){
			if( isset($special_indexes[$key]) ){
				$key = $special_indexes[$key];
			}
			$new_menu[$key] = $value;
		}
		return $new_menu;
	}

}

