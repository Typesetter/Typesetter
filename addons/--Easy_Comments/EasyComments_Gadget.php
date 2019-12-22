<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Gadget extends EasyComments{

	public function __construct(){
		parent::__construct();


		echo '<div class="easy_comments_wrap">';
		echo '<h2>Comments</h2>';

		if( !$this->current_index ){
			echo '<p>Comments are not available for this page</p>';
			echo '</div>';
			return;
		}


		$cmd = \gp\tool::GetCommand();

		$comment_added = false;
		switch($cmd){
			case 'easy_comment_add':
				$comment_added = $this->CommentAdd();
			break;
		}

		$this->ShowComments();

		if( !$comment_added ){
			$this->CommentForm();
		}
		echo '</div>';
	}


	/**
	 * Save a user submitted comment
	 *
	 */
	public function CommentAdd(){
		global $langmessage;


		// check the nonce
		// includes the comment count so resubmissions won't work
		if( !\gp\tool::verify_nonce('easy_comments:'.count($this->comment_data),$_POST['nonce'],true) ){
			$message = \gp\tool\Output::GetAddonText('Sorry, your comment was not saved.');
			msg($message);
			return false;
		}


		//check captcha
		if( $this->config['comment_captcha'] && \gp\tool\Recaptcha::isActive() ){

			if( !\gp\tool\Recaptcha::Check() ){
				//recaptcha::check adds message on failure
				return false;
			}
		}


		if( empty($_POST['name']) ){
			$field = \gp\tool\Output::SelectText('Name');
			msg($langmessage['OOPS_REQUIRED'],$field);
			return false;
		}

		if( empty($_POST['comment']) ){
			$field = \gp\tool\Output::SelectText('Comment');
			msg($langmessage['OOPS_REQUIRED'],$field);
			return false;
		}


		$temp = array();
		$temp['name'] = htmlspecialchars($_POST['name']);
		$temp['comment'] = nl2br(strip_tags($_POST['comment']));
		$temp['time'] = time();

		if( !empty($_POST['website']) && ($_POST['website'] !== 'http://') ){
			$website = $_POST['website'];
			if( strpos($website,'://') === false ){
				$website = false;
			}
			if( $website ){
				$temp['website'] = $website;
			}
		}

		$index = $this->NewIndex();
		$this->comment_data[$index] = $temp;


		//save to index file first
		if( !$this->UpdateIndex() ){
			$message = \gp\tool\Output::GetAddonText('Sorry, your comment was not saved.');
			msg($message);
			return false;
		}


		//then save actual comment
		if( $this->SaveCommentData() ){
			$message = \gp\tool\Output::GetAddonText('Your comment has been saved.');
			msg($message);
			return true;
		}else{
			$message = \gp\tool\Output::GetAddonText('Sorry, your comment was not saved.');
			msg($message);
			return false;
		}
	}


	/**
	 * Show the comments for the current page
	 *
	 */
	public function ShowComments( ){
		global $langmessage;


		echo '<div class="easy_comments_comments">';
		foreach($this->comment_data as $key => $comment){
			echo '<div class="comment_area easy_comment_'.$this->current_index.'_'.$key.'">';
			echo '<p class="name">';
			if( ($this->config['commenter_website'] == 'nofollow') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'" rel="nofollow">'.$comment['name'].'</a></b>';
			}elseif( ($this->config['commenter_website'] == 'link') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'">'.$comment['name'].'</a></b>';
			}else{
				echo '<b>'.$comment['name'].'</b>';
			}
			echo ' &nbsp; ';
			echo '<span>';
			echo date($this->config['date_format'],$comment['time']);
			echo '</span>';


			if( \gp\tool::LoggedIn() ){
				echo ' &nbsp; ';
				echo \gp\tool::Link('Admin_Recent_Comments',$langmessage['delete'],'cmd=easy_comment_rm&i='.$key.'&pg='.$this->current_index,' name="gpajax"');
			}


			echo '</p>';
			echo '<p class="comment">';
			echo $comment['comment'];
			echo '</p>';
			echo '</div>';
		}
		echo '</div>';
	}



	/**
	 * Generate a new comment index
	 * skip indexes that are just numeric
	 *
	 */
	public function NewIndex(){

		$num_index = 0;

		/* prevent reusing old indexes */
		if( count($this->comment_data) > 0 ){
			end($this->comment_data);
			$last_index = key($this->comment_data);
			reset($this->comment_data);
			$num_index = base_convert($last_index,36,10);
			$num_index++;
		}

		do{
			$index = base_convert($num_index,10,36);
			$num_index++;
		}while( is_numeric($index) || isset($this->comment_data[$index]) );

		return $index;
	}


	/**
	 * Show the comment form
	 *
	 */
	public function CommentForm(){


		$_POST += array('name'=>'','website'=>'http://','comment'=>'');

		echo '<div class="easy_comment_form">';
		echo '<h3>';
		echo \gp\tool\Output::GetAddonText('Leave Comment');
		echo '</h3>';


		echo '<form method="post" action="'.\gp\tool::GetUrl($this->current_title).'">';
		echo '<table>';
		echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo \gp\tool\Output::GetAddonText('Name');
			echo '</div>';
			echo '<input type="text" name="name" class="text" value="'.htmlspecialchars($_POST['name']).'" />';
			echo '</td>';
			echo '</tr>';

		if( !empty($this->config['commenter_website']) ){
			echo '<tr>';
				echo '<td>';
				echo '<div>';
				echo \gp\tool\Output::GetAddonText('Website');
				echo '</div>';
				echo '<input type="text" name="website" class="text" value="'.htmlspecialchars($_POST['website']).'" />';
				echo '</td>';
				echo '</tr>';
		}

		echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo \gp\tool\Output::GetAddonText('Comment');
			echo '</div>';
			echo '<textarea name="comment" cols="30" rows="7" >';
			echo htmlspecialchars($_POST['comment']);
			echo '</textarea>';
			echo '</td>';
			echo '</tr>';


		if( $this->config['comment_captcha'] && \gp\tool\Recaptcha::isActive() ){

			echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo \gp\tool\Output::GetAddonText('captcha');
			echo '</div>';
			\gp\tool\Recaptcha::Form();
			echo '</td></tr>';
		}

		echo '<tr>';
			echo '<td>';
			echo '<input type="hidden" name="nonce" value="'.htmlspecialchars(\gp\tool::new_nonce('easy_comments:'.count($this->comment_data),true)).'" />';

			echo '<input type="hidden" name="cmd" value="easy_comment_add" />';
			$html = '<input type="submit" name="" class="submit" value="%s" />';
			echo \gp\tool\Output::GetAddonText('Add Comment',$html);
			echo '</td>';
			echo '</tr>';

		echo '</table>';
		echo '</form>';
		echo '</div>';

	}

}
