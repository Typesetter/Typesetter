<?php

defined('is_running') or die('Not an entry point...');

if( !class_exists('gp_recaptcha') ){
	includeFile('tool/recaptcha.php');
}



/**
 * @todo
 *
 * What happens when a page is deleted
 * Email to owner on comment
 * Option to hide comment till approved
 *
 */

class EasyComments{

	/*
	 * Information about the current page
	 *
	 */
	var $current_index = false;
	var $current_title = false;


	/*
	 * Easy Comments configuration
	 *
	 */
	var $config_file;
	var $config = array();



	/*
	 * comment_data is unique for each page being viewed/commented on
	 *
	 */
	var $comment_folder;
	var $comment_data_file;
	var $comment_data = array();


	/*
	 * the index file keeps track of which titles have had the most recent comments
	 *
	 */
	var $index_file;
	var $index = false;


	var $ajax_delete = true;


	function Init(){
		global $page, $addonPathData, $addonFolderName;

		$this->current_title = $page->title;


		$this->config_file = $addonPathData.'/config.php';
		$this->GetConfig();

		// index is not required for all page displays
		$this->index_file = $addonPathData.'/index.php';

		//only available for pages with a gp_index
		if( empty($page->gp_index) ){
			return;
		}

		$this->InitPage($page->gp_index);
	}


	/**
	 * Initialize page specific variables
	 *
	 */
	function InitPage($index){
		global $gp_titles,$addonPathData;

		if( !isset($gp_titles[$index]) ){
			return;
		}

		$this->current_index = $index;

		//the location of the current page's
		$this->comment_folder = $addonPathData.'/comments';
		$this->comment_data_file = $this->comment_folder.'/'.$this->current_index.'.txt';
		$this->GetCommentData();

	}


	function Run(){

		echo '<div class="easy_comments_wrap">';
		echo '<h2>Comments</h2>';

		if( !$this->current_index ){
			echo '<p>Comments are not available for this page</p>';
			echo '</div>';
			return;
		}


		$cmd = common::GetCommand();

		$show = true;
		$comment_added = false;
		switch($cmd){
			//delete prompts
			case 'easy_comment_add':
				$comment_added = $this->CommentAdd();
			break;

			case 'easy_comment_rm':
				$this->CommentRm($cmd);
			break;
		}

		$this->ShowComments();

		if( !$comment_added ){
			$this->CommentForm();
		}
		echo '</div>';
	}



	/**
	 * Show the comments for the current page
	 *
	 */
	function ShowComments( ){
		global $langmessage;

		if( !is_array($this->comment_data) ){
			return;
		}
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


			if( common::LoggedIn() ){
				echo ' &nbsp; ';
				echo common::Link($this->current_title,$langmessage['delete'],'cmd=easy_comment_rm&i='.$key,' name="gpajax"');
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
	 * Prompt the administrator if they really want to remove the comment
	 *
	 */
	function CommentRm($cmd){
		global $page, $langmessage;

		if( !common::LoggedIn() ){
			return;
		}

		if( $this->ajax_delete ){
			$page->ajaxReplace = array();
		}

		if( !isset($_REQUEST['i']) || !isset($this->comment_data[$_REQUEST['i']]) ){
			message($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}

		$comment_key = $_REQUEST['i'];
		$nonce_str = 'easy_comment_rm:'.count($this->comment_data).':'.$comment_key;

		//prompt for confirmation first
		if( !isset($_POST['confirmed']) ){
			$this->CommentRm_Prompt($cmd);
			return true;
		}

		if( !common::verify_nonce($nonce_str,$_POST['nonce']) ){
			message($langmessage['OOPS'].' (Invalid Nonce)');
			return false;
		}


		//remove from this page's comment data
		unset($this->comment_data[$comment_key]);
		if( !$this->SaveCommentData() ){
			message($langmessage['OOPS'].' (Not Saved)');
			return false;
		}


		//update the index file
		$this->UpdateIndex($comment_key);

		if( $this->ajax_delete ){
			$class = '.easy_comment_'.$this->current_index.'_'.$comment_key;
			$page->ajaxReplace[] = array('eval','','$("'.$class.'").detach();');
		}

		return true;
	}

	function CommentRm_Prompt($cmd){
		global $page, $langmessage;

		$page->ajaxReplace = array();
		$del_comment = gpOutput::SelectText('Delete Comment');
		$nonce_str = 'easy_comment_rm:'.count($this->comment_data).':'.$_REQUEST['i'];

		ob_start();

		echo '<form method="post" action="'.common::GetUrl($this->current_title).'">';
		echo '<div>';
		echo '<input type="hidden" name="nonce" value="'.htmlspecialchars(common::new_nonce($nonce_str)).'" />';
		echo gpOutput::SelectText('Are you sure you want to remove this comment?');
		echo ' <input type="hidden" name="i" value="'.htmlspecialchars($_REQUEST['i']).'" />';
		echo ' <input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
		echo ' <input type="hidden" name="confirmed" value="confirmed" />';
		echo ' <input type="hidden" name="pg" value="'.htmlspecialchars($this->current_index).'" />';
		echo ' <input type="submit" name="" value="'.htmlspecialchars($del_comment).'" class="gpajax" />';
		echo '</div>';
		echo '</form>';

		$message = ob_get_clean();
		message($message);
	}





	/**
	 * Save a user submitted comment
	 *
	 */
	function CommentAdd(){
		global $langmessage;


		// check the nonce
		// includes the comment count so resubmissions won't work
		if( !common::verify_nonce('easy_comments:'.count($this->comment_data),$_POST['nonce'],true) ){
			$message = gpOutput::GetAddonText('Sorry, your comment was not saved.');
			message($message);
			return false;
		}


		//check captcha
		if( $this->config['comment_captcha'] && gp_recaptcha::isActive() ){

			if( !gp_recaptcha::Check() ){
				//recaptcha::check adds message on failure
				return false;
			}
		}


		if( empty($_POST['name']) ){
			$field = gpOutput::SelectText('Name');
			message($langmessage['OOPS_REQUIRED'],$field);
			return false;
		}

		if( empty($_POST['comment']) ){
			$field = gpOutput::SelectText('Comment');
			message($langmessage['OOPS_REQUIRED'],$field);
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
			$message = gpOutput::GetAddonText('Sorry, your comment was not saved.');
			message($message);
			return false;
		}


		//then save actual comment
		if( $this->SaveCommentData() ){
			$message = gpOutput::GetAddonText('Your comment has been saved.');
			message($message);
			return true;
		}else{
			$message = gpOutput::GetAddonText('Sorry, your comment was not saved.');
			message($message);
			return false;
		}
	}

	/**
	 * Generate a new comment index
	 * skip indexes that are just numeric
	 *
	 */
	function NewIndex(){

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
	function CommentForm( $showCaptcha=false ){


		$_POST += array('name'=>'','website'=>'http://','comment'=>'');

		echo '<div class="easy_comment_form">';
		echo '<h3>';
		echo gpOutput::GetAddonText('Leave Comment');
		echo '</h3>';


		echo '<form method="post" action="'.common::GetUrl($this->current_title).'">';
		echo '<table>';
		echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo gpOutput::GetAddonText('Name');
			echo '</div>';
			echo '<input type="text" name="name" class="text" value="'.htmlspecialchars($_POST['name']).'" />';
			echo '</td>';
			echo '</tr>';

		if( !empty($this->config['commenter_website']) ){
			echo '<tr>';
				echo '<td>';
				echo '<div>';
				echo gpOutput::GetAddonText('Website');
				echo '</div>';
				echo '<input type="text" name="website" class="text" value="'.htmlspecialchars($_POST['website']).'" />';
				echo '</td>';
				echo '</tr>';
		}

		echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo gpOutput::GetAddonText('Comment');
			echo '</div>';
			echo '<textarea name="comment" cols="30" rows="7" >';
			echo htmlspecialchars($_POST['comment']);
			echo '</textarea>';
			echo '</td>';
			echo '</tr>';


		if( $this->config['comment_captcha'] && gp_recaptcha::isActive() ){

			echo '<tr>';
			echo '<td>';
			echo '<div>';
			echo gpOutput::GetAddonText('captcha');
			echo '</div>';
			gp_recaptcha::Form();
			echo '</td></tr>';
		}

		echo '<tr>';
			echo '<td>';
			echo '<input type="hidden" name="nonce" value="'.htmlspecialchars(common::new_nonce('easy_comments:'.count($this->comment_data),true)).'" />';

			echo '<input type="hidden" name="cmd" value="easy_comment_add" />';
			$html = '<input type="submit" name="" class="submit" value="%s" />';
			echo gpOutput::GetAddonText('Add Comment',$html);
			echo '</td>';
			echo '</tr>';

		echo '</table>';
		echo '</form>';
		echo '</div>';

	}


	/**
	 * Add Comment to index file
	 *
	 */
	function UpdateIndex($rm_key=false){

		$this->GetIndex();

		$last_comment = false;

		//update the information for the $current_index
		unset($this->index['pages'][$this->current_index]);
		if( count($this->comment_data) > 0){

			$temp = end($this->comment_data);
			$last_key = key($this->comment_data);
			reset($this->comment_data);


			$last_comment = array();
			$last_comment['abbr'] = substr($temp['comment'],0,100);
			$last_comment['time'] = $temp['time'];
			$last_comment['count'] = count($this->comment_data);
			$last_comment['key'] = $last_key;
			$last_comment['page'] = $this->current_index;
			$last_comment['name'] = $temp['name'];
			if( isset($temp['website']) ){
				$last_comment['website'] = $temp['website'];
			}

			$this->index['pages'][$this->current_index] = $last_comment;


			//if it's a new comment
			if( $rm_key === false ){
				$this->index['recent'][] = $last_comment;
			}
		}


		//remove from the recent comments base on current_index and comment time
		if( $rm_key !== false ){
			foreach($this->index['recent'] as $i => $recent){
				if( ($recent['page'] == $this->current_index) && ($recent['key'] == $rm_key) ){
					unset($this->index['recent'][$i]);
				}
			}
		}


		//only keep the 20 most recent comments
		while( count($this->index['recent']) > 20 ){
			array_shift($this->index['recent']);
		}

		return $this->SaveIndex();
	}


	function SaveIndex(){
		return gpFiles::SaveArray($this->index_file,'index',$this->index);
	}

	function GetIndex(){

		if( is_array($this->index) ){
			return $this->index;
		}

		if( file_exists($this->index_file) ){
			require($this->index_file);
		}

		if( !isset($index['pages']) ){
			$index['pages'] = array();
		}
		if( !isset($index['recent']) ){
			$index['recent'] = array();
		}

		$this->index = $index;

		return $index;
	}


	/**
	 * Get the comment data for the current page
	 *
	 */
	function GetCommentData(){

		$data = array();
		if( file_exists($this->comment_data_file) ){
			$dataTxt = file_get_contents($this->comment_data_file);
			if(  !empty($dataTxt) ){
				$data = unserialize($dataTxt);
			}
		}

		$this->comment_data = $data;
	}


	/**
	 * Save the comment data
	 *
	 */
	function SaveCommentData(){
		global $langmessage;

		$dataTxt = serialize($this->comment_data);
		if( !gpFiles::Save($this->comment_data_file,$dataTxt) ){
			return false;
		}

		return true;
	}


	/**
	 * Get the current configuration for Easy Comments
	 *
	 */
	function GetConfig(){

		$config = array();
		if( file_exists($this->config_file) ){
			require($this->config_file);
		}

		$this->config = $config + $this->Defaults();
	}

	/**
	 * Return Easy Comments configuration defaults
	 *
	 */
	function Defaults(){
		return array(
						'date_format'=>'n/j/Y',
						'commenter_website'=>'',
						'comment_captcha'=>false,
						'email'=>false
						);
	}



}
