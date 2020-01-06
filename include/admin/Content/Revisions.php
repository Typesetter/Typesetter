<?php

namespace gp\admin\Content;


class Revisions extends \gp\Page\Edit{


	public $cmds_post = [
							'DeleteRevision'	=> 'DefaultDisplay',
							'UseRevision'		=> 'DefaultDisplay',
						];


	public function __construct($args){
		global $gp_index;

		//parent::__construct($args);

		if( empty($args['path_parts']) ){
			$url = \gp\tool::GetUrl('Admin');
			\gp\tool::Redirect($url);
		}

		$index		= $args['path_parts'][0];
		$title		= array_search($index, $gp_index);

		if( $title === false ){
			$url = \gp\tool::GetUrl('Admin');
			\gp\tool::Redirect($url);
		}

		parent::__construct($title,'');

	}

	public function RunScript(){

		$this->SetVars();
		$this->GetFile();

		$cmd = \gp\tool::GetCommand();
		$this->RunCommands($cmd);
	}


	public function DefaultDisplay(){
		global $page, $langmessage;


		$page->head_js[]				= '/include/js/admin/revisions.js';

		//show site in iframe
		$url		= \gp\tool::GetUrl($this->title,'cmd=ViewRevision&revision=draft');
		$toolbar	= '<br/><h2>' . $langmessage['Revision History'] . '</h2>';
		$toolbar	.= \gp\tool::Link_Page($this->title);
		$toolbar	.= ' &nbsp; <a data-cmd="PreviousRevision"><i class="fa fa-backward fa-fw"></i> ' . $langmessage['Previous'] . '</a>';
		$toolbar	.= ' &nbsp; <a data-cmd="NextRevision">' . $langmessage['Next'] . ' <i class="fa fa-forward fa-fw"></i></a>';



		ob_start();
		$this->ViewHistory();
		$content = ob_get_clean();


		\gp\admin\Tools\Iframe::Output( $page, $url, $toolbar, $content);
	}


	/**
	 * Display the revision history of the current file
	 *
	 */
	public function ViewHistory(){
		global $langmessage, $config;

		$files		= $this->BackupFiles();
		$rows		= array();

		foreach($files as $time => $file){
			$info		= $this->BackupInfo($file);
			$rows[]		= ['time'=>$time,'row'=>$this->HistoryRow($info['time'], $info['size'], $info['username'])];
		}

		// current page
		// this will overwrite one of the history entries if there is a draft
		$page_file		= \gp\tool\Files::FilePath($this->file);
		$rows[]			= ['time'=>$this->fileModTime,'row'=>$this->HistoryRow($this->fileModTime, filesize($page_file), $this->file_stats['username'], 'current')];

		usort($rows,function($a,$b){
			return strnatcmp($b['time'],$a['time']);
		});

		// working draft
		// always make it the first row
		if( $this->draft_exists ){
			$draft_file		= \gp\tool\Files::FilePath($this->draft_file);
			$size			= filesize($draft_file);
			$time			= $this->file_stats['modified'];

			array_unshift($rows,['time'=>$time,'row'=>$this->HistoryRow($time, $size, $this->file_stats['username'], 'draft')]);
		}


		echo '<br/>';
		echo '<table class="bordered full_width striped hover"><tr>';
		echo '<th>' . $langmessage['Modified'] . '</th>';
		echo '<th>' . $langmessage['File Size'] . '</th>';
		echo '<th>' . $langmessage['username'] . '</th>';
		echo '<th>&nbsp;</th>';
		echo '</tr><tbody id="revision_rows">';

		foreach($rows as $row){
			echo $row['row'];
		}

		echo '</tbody>';
		echo '</table>';

		echo '<p>' . $langmessage['history_limit'] . ': ' . $config['history_limit'] . '</p>';
	}



	/**
	 * Return content for history row
	 *
	 */
	protected function HistoryRow($time, $size, $username, $which='history'){
		global $langmessage;
		static $i = 1;

		ob_start();
		$date = \gp\tool::date($langmessage['strftime_datetime'], $time);
		echo '<tr><td title="' . htmlspecialchars($date) . '">';
		switch($which){
			case 'current':
				echo '<b>' . $langmessage['Current Page'] . '</b><br/>';
				break;

			case 'draft':
				echo '<b>' . $langmessage['Working Draft'] . '</b><br/>';
				break;
		}

		$elapsed = \gp\admin\Tools::Elapsed(time() - $time);
		echo sprintf($langmessage['_ago'], $elapsed);
		echo '</td><td>';
		if( $size && is_numeric($size) ){
			echo \gp\admin\Tools::FormatBytes($size);
		}
		echo '</td><td>';
		if( !empty($username) ){
			echo $username;
		}
		echo '</td><td>';


		switch($which){
			case 'current':
				echo \gp\tool::Link(
					$this->title,
					$langmessage['View'],
					'cmd=ViewCurrent',
					['target' => 'gp_layout_iframe']
				);
				break;

			case 'draft':
				echo \gp\tool::Link(
					$this->title,
					$langmessage['View'],
					'cmd=ViewRevision&revision=draft',
					['target' => 'gp_layout_iframe']
				);

				echo ' &nbsp; ' . \gp\tool::Link(
					$this->title,
					$langmessage['edit']
				);
				break;

			case 'history':
				echo \gp\tool::Link(
					$this->title,
					$langmessage['View'],
					'cmd=ViewRevision&revision=' . $time,
					array(
						'target'	=> 'gp_layout_iframe',
					)
				);

				echo ' &nbsp; ';
				echo \gp\tool::Link(
					'Admin/Revisions/' . $this->gp_index,
					$langmessage['restore'],
					'cmd=UseRevision&revision=' . $time,
					array(
						'data-cmd'	=> 'post',
						'class'		=> 'msg_publish_draft admin-link admin-link-publish-draft'
					)
				);

				echo ' &nbsp; ';
				echo \gp\tool::Link(
					'/Admin/Revisions/'.$this->gp_index,
					'<i class="fa fa-trash fa-fw"></i>',
					'cmd=DeleteRevision&revision=' . $time,
					array(
						'title'		=> $langmessage['delete'],
						'class'		=> 'gpconfirm',
						'data-cmd'	=> 'post',
					)
				);
				break;
		}

		echo '</td></tr>';
		return ob_get_clean();
	}

	/**
	 * Delete a revision backup
	 *
	 */
	public function DeleteRevision(){

		$full_path	= $this->BackupFile($_REQUEST['revision']);
		if( is_null($full_path) ){
			return false;
		}
		unlink($full_path);
	}

	/**
	 * Revert the file data to a previous revision
	 *
	 */
	protected function UseRevision(){

		$revision			=& $_REQUEST['revision'];
		$file_sections		= $this->GetRevision($revision);

		if( $file_sections === false ){
			debug('section not found');
			return false;
		}

		debug('savethis');

		$this->file_sections = $file_sections;
		$this->SaveThis();

		$url = \gp\tool::GetUrl('Admin/Revisions/'.$this->gp_index);
		\gp\tool::Redirect($url);
	}


	/**
	 * Get info about a backup from the filename
	 *
	 */
	public function BackupInfo($file){

		$info = array();

		//remove .gze
		if( strpos($file,'.gze') === (strlen($file)-4) ){
			$file = substr($file, 0, -4);
		}

		$name				= basename($file);
		$parts				= explode('.', $name, 3);

		$info['time']		= array_shift($parts);
		$info['size']		= array_shift($parts);
		$info['username']	= '';

		if( count($parts) ){
			$info['username'] = array_shift($parts);
		}

		return $info;
	}

}
