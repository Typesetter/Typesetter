<?php
defined('is_running') or die('Not an entry point...');


gpPlugin::incl('SimpleBlogPage.php','require_once');

class AdminSimpleBlogPage extends SimpleBlogPage{

	function ShowPost(){


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
				$this->ToggleComments(true, $this->post_id);
			break;
			case 'opencomments':
				$this->ToggleComments(false, $this->post_id);
			break;


			//commments
			case 'delete_comment':
				$this->DeleteComment();
			break;

		}


		parent::ShowPost();
	}


	/**
	 * Open/Close the comments for a blog post
	 *
	 */
	function ToggleComments($closed, $post_id ){
		global $langmessage;

		if( $closed ){
			SimpleBlogCommon::AStrValue('comments_closed',$post_id,1);
		}else{
			SimpleBlogCommon::AStrRm('comments_closed',$post_id);
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

		if( SimpleBlogCommon::SaveCommentData($this->post_id,$data) ){
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
			message($langmessage['OOPS'].' (No Post)');
			return;
		}


		$this->post['content'] = $_POST['gpcontent'];

		//save to data file
		if( !self::SavePost($this->post_id, $this->post) ){
			return false;
		}

		$page->ajaxReplace[] = array('ck_saved', '', '');
		message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Save a post
	 *
	 */
	static function SavePost($post_index, $post){
		global $gpAdmin;

		gpFiles::cleanText($post['content']);
		$post['username']		= $gpAdmin['username'];
		$post_file				= SimpleBlogCommon::PostFilePath($post_index);

		if( !gpFiles::SaveArray($post_file,'post',$post) ){
			message($langmessage['OOPS'].' (Post not saved)');
			return false;
		}

		//remove from old data file
		$posts					= SimpleBlogCommon::GetPostFile($post_index,$post_file);
		if( isset($posts[$post_index]) ){
			unset($posts[$post_index]);
			gpFiles::SaveArray($post_file,'posts',$posts);
		}

		return true;
	}


}
