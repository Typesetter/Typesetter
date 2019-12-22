<?php

defined('is_running') or die('Not an entry point...');

require_once('EasyComments.php');

class EasyComments_Admin extends EasyComments{

	var $index;
	var $ajax_delete = false;

	public function __construct(){
		parent::__construct();


		$this->GetIndex();
		$cmd = \gp\tool::GetCommand();

		if( isset($_REQUEST['pg']) ){
			$this->InitPage($_REQUEST['pg']);

			switch($cmd){
				case 'easy_comment_rm':
					$this->CommentRm();
				return;
			}

		}


		$this->ShowAdmin();
	}




	/**
	 * Show the default admin window that display recent comments
	 *
	 */
	public function ShowAdmin(){
		global $page;


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

	public function CommentRow($page_index,$comment){
		global $gp_index, $gp_titles, $langmessage;

		$key =& $comment['key'];

		echo '<tr class="easy_comment_'.$page_index.'_'.$key.'">';
		echo '<td>';
		$title = \gp\tool::IndexToTitle($page_index);
		if( $title === false ){
			echo 'Deleted page';
		}else{
			$label = \gp\tool::GetLabelIndex($page_index);
			echo \gp\tool::Link($title,$label);
		}
		echo '</td><td>';
		echo date('D, j M Y H:i',$comment['time']);
		echo '</td><td>';
		if( !empty($comment['website']) ){
			echo '<b><a href="'.$comment['website'].'">'.$comment['name'].'</a></b>';
		}else{
			echo 'no website';
			echo $comment['name'];
		}
		echo '</td><td>';
		echo $comment['abbr'];
		echo '</td><td>';

		echo \gp\tool::Link('Admin_Recent_Comments',$langmessage['delete'],'cmd=easy_comment_rm&pg='.$page_index.'&i='.$key,' name="gpajax"');

		echo '</td></tr>';

	}


	/**
	 * Prompt the administrator if they really want to remove the comment
	 *
	 */
	public function CommentRm(){
		global $page, $langmessage;

		$page->ajaxReplace = [];

		if( !isset($_REQUEST['i']) ){
			msg($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}

		if( !isset($this->comment_data[$_REQUEST['i']]) ){
			msg($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}

		$comment_key = $_REQUEST['i'];
		$nonce_str = 'easy_comment_rm:'.count($this->comment_data).':'.$comment_key;

		//prompt for confirmation first
		if( !isset($_POST['confirmed']) ){
			$this->CommentRm_Prompt();
			return true;
		}

		if( !\gp\tool::verify_nonce($nonce_str,$_POST['nonce']) ){
			msg($langmessage['OOPS'].' (Invalid Nonce)');
			return false;
		}


		//remove from this page's comment data
		unset($this->comment_data[$comment_key]);
		if( !$this->SaveCommentData() ){
			msg($langmessage['OOPS'].' (Not Saved)');
			return false;
		}


		//update the index file
		$this->UpdateIndex($comment_key);

		$class = '.easy_comment_'.$this->current_index.'_'.$comment_key;
		$page->ajaxReplace[] = array('detach',$class);
		$page->ajaxReplace[] = array('detach','.messages');

		return true;
	}

	public function CommentRm_Prompt(){
		global $page, $langmessage;

		$page->ajaxReplace = array();
		$del_comment = \gp\tool\Output::SelectText('Delete Comment');
		$nonce_str = 'easy_comment_rm:'.count($this->comment_data).':'.$_REQUEST['i'];

		ob_start();

		echo '<form method="post" action="'.\gp\tool::GetUrl('Admin_Recent_Comments').'">';
		echo '<div>';
		echo '<input type="hidden" name="nonce" value="'.htmlspecialchars(\gp\tool::new_nonce($nonce_str)).'" />';
		echo \gp\tool\Output::SelectText('Are you sure you want to remove this comment?');
		echo ' <input type="hidden" name="i" value="'.htmlspecialchars($_REQUEST['i']).'" />';
		echo ' <input type="hidden" name="cmd" value="easy_comment_rm" />';
		echo ' <input type="hidden" name="confirmed" value="confirmed" />';
		echo ' <input type="hidden" name="pg" value="'.htmlspecialchars($this->current_index).'" />';
		echo ' <input type="submit" name="" value="'.htmlspecialchars($del_comment).'" class="gpajax" />';
		echo '</div>';
		echo '</form>';

		$message = ob_get_clean();
		msg($message);
	}




}
