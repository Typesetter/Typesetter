<?php
defined('is_running') or die('Not an entry point...');



class AdminSimpleBlogPage extends SimpleBlogPage{

	function PostCommands(){

		parent::PostCommands();

		$cmd = common::GetCommand();

		switch($cmd){


			//close comments
			case 'closecomments':
				$this->CloseComments();
			break;
			case 'opencomments':
				$this->OpenComments();
			break;


			//commments
			case 'delete_comment':
				$this->DeleteComment();
			break;
		}
	}




	/**
	 * Close the comments for a blog post
	 *
	 */
	function CloseComments(){
		global $langmessage;

		SimpleBlogCommon::AStrValue('comments_closed',$this->post_id,1);
		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
		}else{
			message($langmessage['SAVED']);
		}
	}

	/**
	 * Allow commenting for a blog post
	 *
	 */
	function OpenComments(){
		global $langmessage;

		SimpleBlogCommon::AStrRm('comments_closed',$this->post_id);
		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
		}else{
			message($langmessage['SAVED']);
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
