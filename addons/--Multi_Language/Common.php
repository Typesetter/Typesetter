<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Languages.php');

class MultiLang_Common{

	var $config_file;
	var $config;
	var $lists = array();
	var $titles = array();
	var $langs = array();

	function Init(){
		global $addonPathData, $config;
		$this->config_file = $addonPathData.'/config.php';
		$this->lang = $config['language'];
		$this->GetData();
	}

	function GetData(){
		global $ml_languages;

		$config = array();
		if( file_exists($this->config_file) ){
			require($this->config_file);
		}

		$config += array('titles'=>array(),'lists'=>array(),'langs'=>array());

		$this->config = $config;
		$this->FixConfig();
		$this->lists = $this->config['lists'];
		$this->titles = $this->config['titles'];

		if( !count($this->config['langs']) ){
			$this->langs = $ml_languages;
		}else{
			$this->langs =$this->config['langs'];
		}
	}

	/**
	 * Remove entries from titles if they aren't in lists
	 *
	 */
	function FixConfig(){
		if( !isset($this->config['titles']) || !is_array($this->config['titles']) ){
			return;
		}

		foreach($this->config['titles'] as $title_index => $list){
			if( !isset($this->config['lists'][$list]) || !is_array($this->config['lists'][$list]) ){
				unset($this->config['titles'][$title_index]);
				continue;
			}

			if( !in_array($title_index,$this->config['lists'][$list]) ){
				unset($this->config['titles'][$title_index]);
				continue;
			}
		}

	}

	/**
	 * Get the list for a title
	 *
	 */
	function GetList($page_index){
		global $config;

		$list_index = $this->GetListIndex($page_index);
		if( $list_index === false ){
			return false;
		}

		return $this->lists[$list_index];
	}

	/**
	 * Get the list index for a title
	 *
	 */
	function GetListIndex($page_index){
		if( isset($this->titles[$page_index]) ){
			return $this->titles[$page_index];
		}
		return false;
	}


	/**
	 * Create a new list index
	 *
	 */
	function NewListIndex(){

		$num_index = 0;
		if( is_array($this->lists) ){
			foreach($this->lists as $index => $values){
				$temp = base_convert($index,36,10);
				$num_index = max($temp,$num_index);
			}
		}
		$num_index++;

		do{
			$index = base_convert($num_index,10,36);
			$num_index++;
		}while( is_numeric($index) );

		return $index;
	}
}
