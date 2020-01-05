<?php

namespace gp\admin\Content;

//class Revisions extends \gp\special\Base{
class Revisions extends \gp\Page\Edit{


	public $cmds = [
						'DeleteRevision' => 'DefaultDisplay'
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
		global $page;


		//show site in iframe
		$url		= \gp\tool::GetUrl($this->title,'cmd=ViewCurrent');
		$toolbar	= \gp\tool::Link_Page($this->title);


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

		//working draft
		if( $this->draft_exists ){
			$draft_file = \gp\tool\Files::FilePath($this->draft_file);
			$size = filesize($draft_file);
			$time = $this->file_stats['modified'];
			$rows[$time] = $this->HistoryRow($time, $size, $this->file_stats['username'], 'draft');
		}

		foreach($files as $time => $file){
			$info = $this->BackupInfo($file);
			$rows[$time] = $this->HistoryRow($info['time'], $info['size'], $info['username']);
		}

		// current page
		// this will overwrite one of the history entries if there is a draft
		$page_file = \gp\tool\Files::FilePath($this->file);
		$rows[$this->fileModTime] = $this->HistoryRow($this->fileModTime, filesize($page_file), $this->file_stats['username'], 'current');

		echo '<br/>';
		echo '<h2>' . $langmessage['Revision History'] . '</h2>';
		echo '<table class="bordered full_width striped"><tr>';
		echo '<th>' . $langmessage['Modified'] . '</th>';
		echo '<th>' . $langmessage['File Size'] . '</th>';
		echo '<th>' . $langmessage['username'] . '</th>';
		echo '<th>&nbsp;</th>';
		echo '</tr><tbody>';

		krsort($rows);
		echo implode('', $rows);

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
					array(
						'target'	=> 'gp_layout_iframe',
					)
				);
				break;

			case 'draft':
				echo \gp\tool::Link($this->title, $langmessage['View'],'cmd=ViewRevision&time=draft',['target'=>'gp_layout_iframe']);
				echo ' &nbsp; ' . \gp\tool::Link(
					$this->title,
					$langmessage['Publish Draft'],
					'cmd=PublishDraft',
					array(
						'data-cmd' => 'cnreq',
					)
				);
				break;

			case 'history':
				echo \gp\tool::Link(
					$this->title,
					$langmessage['View'],
					'cmd=ViewRevision&time=' . $time,
					array(
						'target'	=> 'gp_layout_iframe',
					)
				);
				echo ' &nbsp; ';
				echo \gp\tool::Link(
					'/Admin/Revisions/'.$this->gp_index,
					$langmessage['delete'],
					'cmd=DeleteRevision&time=' . $time,
					array(
						'class'		=> 'gpconfirm',
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

		$full_path	= $this->BackupFile($_REQUEST['time']);
		if( is_null($full_path) ){
			return false;
		}
		unlink($full_path);
	}



}
