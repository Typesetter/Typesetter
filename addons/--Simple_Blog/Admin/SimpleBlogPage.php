<?php
defined('is_running') or die('Not an entry point...');



class AdminSimpleBlogPage extends SimpleBlogPage{

	function PostCommands(){

		parent::PostCommands();

		$cmd = common::GetCommand();

		switch($cmd){


			//close comments
			case 'closecomments':
				$this->ToggleComments(true);
			break;
			case 'opencomments':
				$this->ToggleComments(false);
			break;


			//commments
			case 'delete_comment':
				$this->DeleteComment();
			break;
		}
	}


	/**
	 * Open/Close the comments for a blog post
	 *
	 */
	function ToggleComments($closed ){
		global $langmessage;

		if( $closed ){
			SimpleBlogCommon::AStrValue('comments_closed',$this->post_id,1);
		}else{
			SimpleBlogCommon::AStrRm('comments_closed',$this->post_id);
		}


		if( SimpleBlogCommon::SaveIndex() ){
			$this->comments_closed = $closed;
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS']);
		}
	}


	/**
	 * Remove a comment entry from the comment data
	 *
	 */
	function DeleteComment(){
		global $langmessage;

		$data		= SimpleBlogCommon::GetCommentData($this->post_id);
		$comment	= $_POST['comment_index'];

		if( !isset($data[$comment]) ){
			message($langmessage['OOPS']);
			return;
		}

		unset($data[$comment]);

		if( $this->SaveCommentData($data) ){
			message($langmessage['SAVED']);
			return true;
		}else{
			message($langmessage['OOPS']);
			return false;
		}

	}




}
