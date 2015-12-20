<?php
defined('is_running') or die('Not an entry point...');

class admin_menu_tools{

	var $Inherit_Info = array();
	var $settings_cache = array();
	var $is_main_menu = false;


	function CacheSettings(){
		global $gp_index, $gp_titles, $gp_menu;

		$this->settings_cache['gp_index'] = $gp_index;
		$this->settings_cache['gp_titles'] = $gp_titles;
		$this->settings_cache['gp_menu'] = $gp_menu;

		if( !$this->is_main_menu ){
			$this->settings_cache['curr_menu_array'] = $this->curr_menu_array;
		}
	}

	function RestoreSettings(){
		global $gp_index, $gp_titles, $gp_menu;


		if( isset($this->settings_cache['gp_titles']) ){
			$gp_titles = $this->settings_cache['gp_titles'];
		}

		if( isset($this->settings_cache['gp_menu']) ){
			$gp_menu = $this->settings_cache['gp_menu'];
		}

		if( isset($this->settings_cache['gp_index']) ){
			$gp_index = $this->settings_cache['gp_index'];
		}

		if( isset($this->settings_cache['curr_menu_array']) ){
			$this->curr_menu_array = $this->settings_cache['curr_menu_array'];
		}
	}


	/*
	 * @static
	 */
	static function Inheritance_Info(){
		global $gp_menu, $gp_titles;

		$current_par_info = array();
		$prev_level = 0;
		$inherit_info = array();


		foreach($gp_menu as $id => $titleInfo){

			$level = $titleInfo['level'];
			$inherit_info[$id] = array();

			//no longer parents
			if( $prev_level >= $level ){

				$temp_level = $prev_level;
				while( $temp_level >= $level ){
					unset($current_par_info[$temp_level]);
					$temp_level--;
				}
			}

			foreach($current_par_info as $parent_level => $parent_info){
				$parent_id = $parent_info['id'];

				if( $parent_level < $level ){
					if( isset($inherit_info[$parent_id]['children']) ){
						$inherit_info[$parent_id]['children']++;
					}else{
						$inherit_info[$parent_id]['children'] = 1;
					}
				}

				if( isset($parent_info['gpLayout']) ){
					$inherit_info[$id]['parent_layout'] = $parent_info['gpLayout'];
				}
			}

			$array = array();
			$array['id'] = $id;
			if( isset($gp_titles[$id]['gpLayout']) ){
				$array['gpLayout'] = $gp_titles[$id]['gpLayout'];
			}

			$current_par_info[$level] = $array;

			$prev_level = $level;
		}

		return $inherit_info;
	}


	/*
	 * @static
	 */
	static function CurrentLayout($index){
		global $config, $gp_titles,$gpLayouts;
		static $Inherit_Info;

		if( is_null($Inherit_Info) ){
			$Inherit_Info = admin_menu_tools::Inheritance_Info();
		}

		if( isset($gp_titles[$index]['gpLayout']) ){
			$layout = $gp_titles[$index]['gpLayout'];
			if( isset($gpLayouts[$layout]) ){
				return $layout;
			}
		}

		if( isset($Inherit_Info[$index]['parent_layout']) ){
			$layout = $Inherit_Info[$index]['parent_layout'];
			if( isset($gpLayouts[$layout]) ){
				return $layout;
			}
		}

		return $config['gpLayout'];
	}


}
