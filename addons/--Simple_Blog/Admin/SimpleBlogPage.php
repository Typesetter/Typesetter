<?php
defined('is_running') or die('Not an entry point...');



class AdminSimpleBlogPage extends SimpleBlogPage{

	function PostCommands(){

		parent::PostCommands();

		$cmd = common::GetCommand();

		switch($cmd){


			// inline editing
			case 'inlineedit':
				$this->InlineEdit();
			die();
			case 'save_inline':
			case 'save':
				$this->SaveInline();
			break;


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


	/**
	 * Edit a post with inline editing
	 *
	 */
	function InlineEdit(){


		if( !$this->post ){
			echo 'false';
			return false;
		}

		$this->post += array('type'=>'text');

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($this->post);
	}



	/**
	 * Save an inline edit
	 *
	 */
	function SaveInline(){
		global $page, $langmessage;
		$page->ajaxReplace = array();

		if( $this->post === false || empty($_POST['gpcontent']) ){
			message($langmessage['OOPS']);
			return;
		}


		$this->post['content'] = $_POST['gpcontent'];

		//save to data file
		if( !SimpleBlogCommon::SavePost($this->post_id, $this->post) ){
			return false;
		}

		$page->ajaxReplace[] = array('ck_saved', '', '');
		message($langmessage['SAVED']);
		return true;
	}


}
