<?php

defined('is_running') or die('Not an entry point...');



/**
 * @todo
 *
 * What happens when a page is deleted
 * Email to owner on comment
 * Option to hide comment till approved
 *
 */

class EasyComments{

	/*
	 * Information about the current page
	 *
	 */
	var $current_index = false;
	var $current_title = false;


	/*
	 * Easy Comments configuration
	 *
	 */
	var $config_file;
	var $config = [];



	/*
	 * comment_data is unique for each page being viewed/commented on
	 *
	 */
	var $comment_folder;
	var $comment_data_file;
	var $comment_data = [];


	/*
	 * the index file keeps track of which titles have had the most recent comments
	 *
	 */
	var $index_file;
	var $index = [];



	public function __construct(){
		global $page, $addonPathData, $addonFolderName;

		$this->current_title = $page->title;


		$this->config_file = $addonPathData.'/config.php';
		$this->GetConfig();

		// index is not required for all page displays
		$this->index_file = $addonPathData.'/index.php';

		//only available for pages with a gp_index
		if( empty($page->gp_index) ){
			return;
		}


		$this->InitPage($page->gp_index);
	}


	/**
	 * Initialize page specific variables
	 *
	 */
	public function InitPage($index){
		global $gp_titles,$addonPathData;

		if( !isset($gp_titles[$index]) ){
			return;
		}


		$this->current_index		= $index;
		$this->comment_folder		= $addonPathData.'/comments';


		$this->comment_data_file	= $this->comment_folder.'/'.$this->current_index.'.gpjson';
		if( file_exists($this->comment_data_file) ){
			$content				= file_get_contents($this->comment_data_file);
			$this->comment_data		= json_decode($content,true);
			return;
		}


		// get data saved before v1.2
		//$this->comment_data_file = $this->comment_folder.'/'.$this->current_index.'.txt';
		$data_file = $this->comment_folder.'/'.$this->current_index.'.txt';
		if( file_exists($data_file) ){
			$content				= file_get_contents($data_file);
			$this->comment_data		= unserialize($content);
		}
	}


	/**
	 * Add Comment to index file
	 *
	 */
	public function UpdateIndex($rm_key=false){

		$this->GetIndex();


		//update the information for the $current_index
		unset($this->index['pages'][$this->current_index]);
		if( count($this->comment_data) > 0){

			$temp = end($this->comment_data);
			$last_key = key($this->comment_data);
			reset($this->comment_data);


			$last_comment = array();
			$last_comment['abbr'] = substr($temp['comment'],0,100);
			$last_comment['time'] = $temp['time'];
			$last_comment['count'] = count($this->comment_data);
			$last_comment['key'] = $last_key;
			$last_comment['page'] = $this->current_index;
			$last_comment['name'] = $temp['name'];
			if( isset($temp['website']) ){
				$last_comment['website'] = $temp['website'];
			}

			$this->index['pages'][$this->current_index] = $last_comment;


			//if it's a new comment
			if( $rm_key === false ){
				$this->index['recent'][] = $last_comment;
			}
		}


		//remove from the recent comments base on current_index and comment time
		if( $rm_key !== false ){
			foreach($this->index['recent'] as $i => $recent){
				if( ($recent['page'] == $this->current_index) && ($recent['key'] == $rm_key) ){
					unset($this->index['recent'][$i]);
				}
			}
		}


		//only keep the 20 most recent comments
		while( count($this->index['recent']) > 20 ){
			array_shift($this->index['recent']);
		}

		return $this->SaveIndex();
	}


	public function SaveIndex(){
		return \gp\tool\Files::SaveData($this->index_file, 'index', $this->index);
	}

	public function GetIndex(){

		if( file_exists($this->index_file) ){
			$index = \gp\tool\Files::Get($this->index_file, 'index');
		}

		if( !isset($index['pages']) ){
			$index['pages'] = array();
		}
		if( !isset($index['recent']) ){
			$index['recent'] = array();
		}

		$this->index = $index;

		return $index;
	}


	/**
	 * Save the comment data
	 *
	 */
	public function SaveCommentData(){
		global $langmessage;

		$text = json_encode($this->comment_data);
		if( !\gp\tool\Files::Save($this->comment_data_file,$text) ){
			return false;
		}

		return true;
	}


	/**
	 * Get the current configuration for Easy Comments
	 *
	 */
	public function GetConfig(){

		$config = array();
		if( file_exists($this->config_file) ){
			require($this->config_file);
		}

		$this->config = $config + $this->Defaults();
	}

	/**
	 * Return Easy Comments configuration defaults
	 *
	 */
	public function Defaults(){
		return array(
						'date_format'=>'n/j/Y',
						'commenter_website'=>'',
						'comment_captcha'=>false,
						'email'=>false
						);
	}



}
