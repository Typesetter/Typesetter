<?php

defined('is_running') or die('Not an entry point...');

gpPlugin::incl('Admin/Admin.php','require_once');


class AdminSimpleBlogPosts extends SimipleBlogAdmin{

	function __construct(){
		global $langmessage;
		parent::__construct();


		$cmd = common::GetCommand();
		switch( $cmd ){
			//creating
			case 'save_new';
				$this->SaveNew(); //will redirect on success
			case 'new_form':
				$this->NewForm();
			return;

		}

		$this->ShowPosts();
	}

	function ShowPosts(){

		$this->Heading('Admin_Blog');

		$post_ids			= SimpleBlogCommon::AStrToArray('str_index');
		$post_titles		= SimpleBlogCommon::AStrToArray('titles');
		$post_times			= SimpleBlogCommon::AStrToArray('post_times');
		$post_comments		= SimpleBlogCommon::AStrToArray('comment_counts');
		$post_closed		= SimpleBlogCommon::AStrToArray('comments_closed');
		$post_drafts		= SimpleBlogCommon::AStrToArray('drafts');




		echo '<table class="bordered full_width">';
		echo '<thead><tr><th></th><th>Post ('.number_format(count($post_ids)).')';
		echo '</th><th>Date</th><th>Comments</th>';
		echo '<th>Options</th>';
		echo '</tr></thead>';
		echo '<tbody>';
		foreach($post_ids as $i => $id){
			echo '<tr><td>';
			if( isset($post_drafts[$id]) ){
				echo 'Draft';
			}
			echo '</td><td>';
			$title = $post_titles[$id];
			echo SimpleBlogCommon::PostLink($id,$title);
			echo '</td><td>';
			if( isset($post_times[$id]) ){
				echo strftime(SimpleBlogCommon::$data['strftime_format'],$post_times[$id]);
			}
			echo '</td><td>';
			if( isset($post_comments[$id]) ){
				echo $post_comments[$id];
			}
			echo '</td><td>';
			if( isset($post_closed[$id]) ){
				echo 'Closed';
			}
			echo '</td></tr>';
		}
		echo '</tbody>';
		echo '</table>';


	}



}
