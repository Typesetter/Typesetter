<?php

namespace gp\admin\Tools;

defined('is_running') or die('Not an entry point...');

class Cache{

	private $cache_dir;
	private $all_files;

	public function __construct(){
		global $page, $langmessage, $dataDir;

		$this->cache_dir	= $dataDir.'/data/_cache';
		$this->AllFiles();


		echo '<h2>'.$langmessage['Resource Cache'].'</h2>';


		$cmd = \common::GetCommand();
		switch($cmd){
			case 'ViewFile';
				$this->ViewFile();
			return;

			case 'DeleteFile';
				$this->DeleteFile();
			break;

			case 'EmptyResourceCache':
				$this->EmptyResourceCache();
			break;
		}


		$this->ShowFiles();
	}

	protected function AllFiles(){
		$this->all_files	= scandir($this->cache_dir);
		$this->all_files	= array_diff($this->all_files,array('.','..'));
	}



	/**
	 * Show files in the cache
	 *
	 */
	protected function ShowFiles(){
		global $page, $langmessage;

		$page->head_js[] = '/include/thirdparty/tablesorter/tablesorter.js';
		$page->jQueryCode .= '$("table.tablesorter").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"});';

		if( !$this->all_files ){
			return;
		}



		echo '<p>';
		echo \common::Link('Admin/Cache','Empty Cache','cmd=EmptyResourceCache',array('data-cmd'=>'cnreq','class'=>'gpconfirm','title'=>'Empty the resource cache?'));
		echo '</p>';

		echo '<table class="bordered tablesorter full_width">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['File Size'];
		echo '</th><th>';
		echo 'Touched';
		echo '</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';
		echo '</thead>';

		$total_size = 0;
		echo '<tbody>';
		foreach($this->all_files as $file){
			$full = $this->cache_dir.'/'.$file;

			echo '<tr><td>';
			echo '<a href="?cmd=ViewFile&amp;file='.rawurlencode($file).'">';
			echo $file;
			echo '</a>';
			echo '</td><td>';
			$size = filesize($full);
			echo '<span style="display:none">'.$size.'</span>';
			echo \admin_tools::FormatBytes($size);
			$total_size += $size;

			echo '</td><td>';
			$elapsed = \admin_tools::Elapsed( time() - filemtime($full) );
			echo sprintf($langmessage['_ago'],$elapsed);
			echo '</td><td>';

			echo \common::Link('Admin/Cache',$langmessage['delete'],'cmd=DeleteFile&amp;file='.rawurlencode($file),array('data-cmd'=>'cnreq','class'=>'gpconfirm','title'=>$langmessage['delete_confirm']));

			echo '</tr>';
		}
		echo '</tbody>';
		//totals
		echo '<tfoot>';
		echo '<tr><td>';
		echo number_format(count($this->all_files)).' Files';
		echo '</td><td>';
		echo \admin_tools::FormatBytes($total_size);

		echo '</td><td>';
		echo '</tr>';
		echo '</table>';
	}


	/**
	 * Empty the resource cache
	 *
	 */
	protected function EmptyResourceCache(){


		foreach($this->all_files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$full = $this->cache_dir.'/'.$file;
			unlink($full);
		}
		$this->AllFiles();
	}


	/**
	 * View a cache file
	 *
	 */
	protected function ViewFile(){

		$file	= $this->RequestedFile();
		$full	= $this->cache_dir.'/'.$file;
		$text	= file_get_contents($full);

		echo '<h2>'.$file.'</h2>';
		echo '<pre>';
		echo $text;
		echo '</pre>';
	}


	/**
	 * Delete a cache file
	 *
	 */
	protected function DeleteFile(){
		global $page, $langmessage;

		$page->ajaxReplace = array();

		$file	= $this->RequestedFile();
		if( !$file ){
			msg('Invalid Request');
			return;
		}

		$full	= $this->cache_dir.'/'.$file;
		unlink($full);

		$this->AllFiles();
	}


	/**
	 * Get the requested filename
	 *
	 */
	protected function RequestedFile(){


		$file		= $_REQUEST['file'];

		if( !in_array($file,$this->all_files) ){
			return false;
		}

		if( $file == '.' || $file == '..' ){
			return false;
		}

		if( strpos($file,'/') !== false || strpos($file,'\\') !== false ){
			return false;
		}

		return $file;
	}



}