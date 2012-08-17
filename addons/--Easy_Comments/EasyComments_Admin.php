<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Admin extends EasyComments{

	var $index;
	var $ajax_delete = false;

	function EasyComments_Admin(){

		$this->Init();
		$this->GetIndex();
		$cmd = common::GetCommand();

		switch($cmd){

			case 'easy_admin_rm':
			default:
				$this->ShowAdmin($cmd);
			break;
		}

	}




	/**
	 * Show the default admin window that display recent comments
	 *
	 */
	function ShowAdmin($cmd){
		global $page;

		if( isset($_REQUEST['pg']) ){
			$this->InitPage($_REQUEST['pg']);
		}

		switch($cmd){
			case 'easy_admin_rm':
				$this->CommentRm($cmd);
			break;
		}

		echo '<h3>Most Recent Comments</h3>';

		if( count($this->index['recent']) > 0 ){
			echo '<table class="bordered" style="width:100%">';
			echo '<tr><th>Page</th><th>Comment Time</th><th>Commenter</th><th>Comment</th><th>Options</th></tr>';
			$recent = array_reverse($this->index['recent']);
			foreach($recent as $comment){
				$this->CommentRow($comment['page'],$comment);
			}
			echo '</table>';
		}else{
			echo 'No comments to display';
		}


		echo '<br/>';

		echo '<h3>Recently Commented Pages</h3>';

		if( count($this->index['pages']) > 0 ){
			echo '<table class="bordered" style="width:100%">';
			echo '<tr><th>Page</th><th>Comment Time</th><th>Commenter</th><th>Comment</th><th>Options</th></tr>';
			$pages = array_reverse($this->index['pages'],true);
			foreach($pages as $page_key => $comment){
				$this->CommentRow($page_key,$comment);
			}
			echo '</table>';
		}else{
			echo '<p>';
			echo 'No comments to display';
			echo '</p>';
		}


	}

	function CommentRow($page_index,$comment){
		global $gp_index, $gp_titles, $langmessage;

		$key =& $comment['key'];

		echo '<tr class="easy_comment_'.$page_index.'_'.$key.'">';
		echo '<td>';
		$title = common::IndexToTitle($page_index);
		$label = common::GetLabelIndex($page_index);
		echo common::Link($title,$label);
		echo '</td>';
		echo '<td>';
		echo date('D, j M Y H:i',$comment['time']);
		echo '</td>';
		echo '<td>';
		if( !empty($comment['website']) ){
			echo '<b><a href="'.$comment['website'].'">'.$comment['name'].'</a></b>';
		}else{
			echo 'no website';
			echo $comment['name'];
		}
		echo '</td>';
		echo '<td>';
		echo $comment['abbr'];
		echo '</td>';
		echo '<td>';

		echo common::Link('Admin_Easy_Comments',$langmessage['delete'],'cmd=easy_admin_rm&pg='.$page_index.'&i='.$key,' name="gpajax"');

		echo '</td>';
		echo '</tr>';

	}

}
