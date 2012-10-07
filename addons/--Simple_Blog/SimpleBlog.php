<?php
defined('is_running') or die('Not an entry point...');

require_once('SimpleBlogCommon.php');

/**
 * Class for displaying the Special_Blog page and performing it's actions
 *
 */

class SimpleBlog extends SimpleBlogCommon{



	function SimpleBlog(){
		global $page, $langmessage, $addonFolderName;

		$this->Init();

		$cmd = common::GetCommand();
		$show = true;
		if( common::LoggedIn() ){

			switch($cmd){

				/* inline editing */
				case 'inlineedit':
					$this->InlineEdit();
				die();
				case 'save':
					$this->SaveInline();
				break;


				//delete prompts
				case 'deleteentry':
					$this->DeleteEntryPrompt();
				return;
				case 'delete_comment_prompt':
					$this->DeleteCommentPrompt();
				return;

				//delete
				case 'delete':
					if( $this->Delete() ){
						$this->GenStaticContent();
					}
				break;

				//editing
				case 'save_edit':
					if( $this->SaveEdit() ){
						$this->GenStaticContent();
						break;
					}
				case 'edit':
					$this->EditPost();
					$show = false;
				break;

				//creating
				case 'save_new';
					if( $this->SaveNew() ){
						$this->GenStaticContent();
						break;
					}
				case 'new_form':
					$this->NewForm();
					$show = false;
				break;

			}


			$page->admin_links[] = array('Special_Blog','Blog Home');

			$page->admin_links[] = array('Special_Blog','New Blog Post','cmd=new_form');

			$page->admin_links[] = array('Admin_Blog','Configuration');

			$page->admin_links[] = array('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addonFolderName),' name="ajax_box" ');

			$label = 'Number of Posts: '. $this->blogData['post_count'];
			$page->admin_links[$label] = '';
		}

		if( $show ){
			if( empty($cmd) && isset($_REQUEST['id']) && ctype_digit($_REQUEST['id']) ){
				$cmd = 'post';
			}
			switch($cmd){
				case 'opencomments':
				case 'closecomments':
				case 'delete_comment':
				case 'Add Comment':
				case 'save_edit':
				case 'post':
					$this->ShowPost($cmd);
				break;
				case 'page':
				default:
					$this->ShowPage();
				break;
			}

			if( common::LoggedIn() && !file_exists($this->indexFile) ){
				echo '<p>Congratulations on successfully installing Simple Blog for gpEasy.</p> ';
				echo '<p>You\'ll probably want to get started by '.common::Link('Special_Blog','creating a blog post','cmd=new_form').'.</p>';
			}

		}

	}


	/**
	 * Prompt the user if they want to delete the blog comment
	 *
	 */
	function DeleteCommentPrompt(){
		global $langmessage;

		echo '<div class="inline_box">';

		echo '<form method="post" action="'.common::GetUrl('Special_Blog').'">';
			echo $langmessage['delete_confirm'];
			echo ' <input type="hidden" name="id" value="'.$_GET['id'].'" />';
			echo ' <input type="hidden" name="comment_id" value="'.$_GET['comment'].'" />';
			echo  ' <input type="hidden" name="cmd" value="delete_comment" />';
			echo  ' <input type="submit" name="aaa" value="'.$langmessage['delete'].'" />';
		echo '</form>';
		echo '</div>';

	}

	/**
	 * Prompt the user if they want to delete the blog post
	 *
	 */
	function DeleteEntryPrompt(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form method="post" action="'.common::GetUrl('Special_Blog').'">';
		echo $langmessage['delete_confirm'];
		echo ' <input type="hidden" name="id" value="'.htmlspecialchars($_GET['del_id']).'" />';
		echo  ' <input type="hidden" name="cmd" value="delete" />';

		echo '<p>';
		echo ' <input type="submit" name="aaa" value="'.$langmessage['delete'].'" />';
		echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Output the html for a single blog post
	 * Handle comment actions
	 */
	function ShowPost($cmd){
		global $langmessage,$page;

		if( !isset($_REQUEST['id']) ){
			message($langmessage['OOPS']);
			return;
		}

		$post_index = $_REQUEST['id'];
		$posts = $this->GetPostFile($post_index,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS']);
			return;
		}


		if( !isset($posts[$post_index]) ){
			message($langmessage['OOPS']);
			return;
		}

		$commentSaved = false;
		switch($cmd){

			//close comments
			case 'closecomments':
				$this->CloseComments($post_index);
			break;
			case 'opencomments':
				$this->OpenComments($post_index);
			break;


			//commments
			case 'Add Comment':
				if( $this->AddComment($post_index) ){
					$commentSaved = true;
				}else{
					$this->CommentForm($post_index,true);
					return;
				}
			break;
			case 'delete_comment':
				$this->DeleteComment($post_index);
			break;
		}



		$post =& $posts[$post_index];
		$this->ShowPostContent($post,$post_index);

		$page->label = SimpleBlogCommon::Underscores( $post['title'] );
		$this->PostLinks($post_index);

		//comments
		if( $this->blogData['allow_comments'] ){
			echo '<div class="comment_container">';
			$this->ShowComments($post_index);
			if( !$commentSaved ){
				$this->CommentForm($post_index);
			}
			echo '</div>';
		}
	}


	/**
	 * Show the comments for a single blog post
	 *
	 */
	function ShowComments($post_index){

		$data = $this->GetCommentData($post_index);
		if( empty($data) ){
			return;
		}

		echo '<h3>';
		echo gpOutput::GetAddonText('Comments');
		echo '</h3>';

		$this->GetCommentHtml($data,$post_index,true);

	}

	/**
	 * Close the comments for a blog post
	 *
	 */
	function CloseComments($post_index){
		global $langmessage;

		$this->blogData['post_info'][$post_index]['closecomments'] = true;
		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
		}else{
			message($langmessage['SAVED']);
		}
	}

	/**
	 * Allow commenting for a blog post
	 *
	 */
	function OpenComments($post_index){
		global $langmessage;

		unset($this->blogData['post_info'][$post_index]['closecomments']);
		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
		}else{
			message($langmessage['SAVED']);
		}
	}

	/**
	 * Display the visitor form for adding comments
	 *
	 */
	function CommentForm($post_index,$showCaptcha=false){

		if( isset($this->blogData['post_info'][$post_index]) && isset($this->blogData['post_info'][$post_index]['closecomments']) ){
			echo '<div class="comments_closed">';
			echo gpOutput::GetAddonText('Comments have been closed.');
			echo '</div>';
			return;
		}


		$_POST += array('name'=>'','website'=>'http://','comment'=>'');

		echo '<h3>';
		echo gpOutput::GetAddonText('Leave Comment');
		echo '</h3>';


		echo '<form method="post" action="'.common::GetUrl('Special_Blog','id='.$post_index).'">';
		echo '<ul>';
		echo '<li>';
			echo '<label>';
			echo gpOutput::GetAddonText('Name');
			echo '</label><br/>';
			echo '<input type="text" name="name" class="text" value="'.htmlspecialchars($_POST['name']).'" />';
			echo '</li>';

		if( !empty($this->blogData['commenter_website']) ){
			echo '<li>';
				echo '<label>';
				echo gpOutput::GetAddonText('Website');
				echo '</label><br/>';
				echo '<input type="text" name="website" class="text" value="'.htmlspecialchars($_POST['website']).'" />';
				echo '</li>';
		}

		echo '<li>';
			echo '<label>';
			echo gpOutput::ReturnText('Comment');
			echo '</label><br/>';
			echo '<textarea name="comment" cols="30" rows="7" >';
			echo htmlspecialchars($_POST['comment']);
			echo '</textarea>';
			echo '</li>';


		if( $showCaptcha && $this->blogData['comment_captcha'] && gp_recaptcha::isActive() ){
			echo '<input type="hidden" name="anti_spam_submitted" value="anti_spam_submitted" />';
			echo '<li>';
			echo '<label>';
			echo gpOutput::ReturnText('captcha');
			echo '</label><br/>';
			gp_recaptcha::Form();
			echo '</li>';
		}

		echo '<li>';
			echo '<input type="hidden" name="cmd" value="Add Comment" />';
			$html = '<input type="submit" name="" class="submit" value="%s" />';
			echo gpOutput::GetAddonText('Add Comment',$html);
			echo '</li';

		echo '</ul>';
		echo '</form>';

	}

	/**
	 * Display the links at the bottom of a post
	 *
	 */
	function PostLinks($post_index){

		$post_key = $this->KeyFromIndex($post_index);

		echo '<p class="blog_nav_links">';

		//check for older posts
		$prev_index = $this->IndexFromKey($post_key+1);
		if( $prev_index ){
			$html = common::Link('Special_Blog','%s','id='.$prev_index,'class="blog_older"');
			echo gpOutput::GetAddonText('Older Entry',$html);
			echo '&nbsp;';
		}

		//blog home
		$html = common::Link('Special_Blog','%s');
		echo gpOutput::GetAddonText('Blog Home',$html);
		echo '&nbsp;';

		// check for newer posts
		if( $post_key > 0 ){
			$next_index = $this->IndexFromKey($post_key-1);
			$html = common::Link('Special_Blog','%s','id='.$next_index,'class="blog_newer"');
			echo gpOutput::GetAddonText('Newer Entry',$html);
		}

		if( common::LoggedIn() ){
			echo '&nbsp;';
			echo common::Link('Special_Blog','New Post','cmd=new_form');
		}

		echo '</p>';
	}




	/**
	 * Display a blog page with multiple blog posts
	 *
	 */
	function ShowPage(){

		$per_page = $this->blogData['per_page'];
		$page = 0;
		if( isset($_GET['page']) && is_numeric($_GET['page']) ){
			$page = (int)$_GET['page'];
		}
		$start = $page * $per_page;

		$show_posts = $this->WhichPosts($start,$per_page);

		$posts = array();
		foreach($show_posts as $post_index){

			//get $posts
			if( !isset($posts[$post_index]) ){
				$posts = $this->GetPostFile($post_index,$post_file);
			}

			$post =& $posts[$post_index];

			$this->ShowPostContent( $post, $post_index, $this->blogData['post_abbrev'] );
		}

		echo '<p class="blog_nav_links">';
		if( $page > 0 ){
			$html = common::Link('Special_Blog','%s','page='.($page-1),'class="blog_newer"');
			echo gpOutput::GetAddonText('Newer Entries',$html);
			echo '&nbsp;';

			$html = common::Link('Special_Blog','%s');
			echo gpOutput::GetAddonText('Blog Home',$html);
			echo '&nbsp;';
		}

		if( ( ($page+1) * $per_page) < $this->blogData['post_count'] ){
			$html = common::Link('Special_Blog','%s','page='.($page+1),'class="blog_older"');
			echo gpOutput::GetAddonText('Older Entries',$html);
		}

		if( common::LoggedIn() ){
			echo '&nbsp;';
			echo common::Link('Special_Blog','New Post','cmd=new_form');
		}

		echo '</p>';

	}

	/**
	 * Get a list of post indeces
	 *
	 */
	function WhichPosts($start,$len){
		$posts = array();
		$end = $start+$len;
		for($i = $start; $i < $end; $i++){
			$index = $this->IndexFromKey($i);
			if( $index ) $posts[] = $index;
		}
		return $posts;
	}


	/**
	 * Display a blog page with multiple blog posts
	 *
	 */
	function ShowPageOld(){

		$posts = array();
		foreach($show_posts as $post_index){

			//get $posts
			if( !isset($posts[$post_index]) ){
				$posts = $this->GetPostFile($post_index,$post_file);
			}

			$post =& $posts[$post_index];

			$this->ShowPostContent($post,$post_index);
		}
	}

	/**
	 * Display the html for a single blog post
	 *
	 */
	function ShowPostContent( &$post, &$post_index, $limit = 0){
		global $langmessage;

		$id = $class = '';
		if( common::LoggedIn() ){

			//$edit_link = gpOutput::EditAreaLink($edit_index,'Special_Blog',$langmessage['edit'],'cmd=edit&id='.$post_index);
			$edit_link = gpOutput::EditAreaLink($edit_index,'Special_Blog',$langmessage['edit'].' (TWYSIWYG)','id='.$post_index,'name="inline_edit_generic" rel="text_inline_edit"');
			echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
			echo $edit_link;

			echo common::Link('Special_Blog',$langmessage['edit'].' (All)','cmd=edit&id='.$post_index,' style="display:none"');
			echo common::Link('Special_Blog',$langmessage['delete'],'cmd=deleteentry&del_id='.$post_index,' name="ajax_box" style="display:none"');

			if( $this->blogData['allow_comments'] ){
				if( isset($this->blogData['post_info'][$post_index]) && isset($this->blogData['post_info'][$post_index]['closecomments']) ){
					$label = gpOutput::SelectText('Open Comments');
					echo common::Link('Special_Blog',$label,'cmd=opencomments&id='.$post_index,'name="creq" style="display:none"');
					//echo common::Link('Special_Blog','%s','cmd=opencomments&id='.$post_index,'name="creq"');
					//echo gpOutput::GetAddonText('Open Comments',$html);
				}else{
					$label = gpOutput::SelectText('Close Comments');
					echo common::Link('Special_Blog',$label,'cmd=closecomments&id='.$post_index,'name="creq" style="display:none"');
					//echo common::Link('Special_Blog','%s','cmd=closecomments&id='.$post_index,'name="creq"');
					//echo gpOutput::GetAddonText('Close Comments',$html);
				}
			}

			echo common::Link('Special_Blog','New Blog Post','cmd=new_form',' style="display:none"');
			echo common::Link('Admin_Blog',$langmessage['configuration'],'',' style="display:none"');
			echo '</span>';
			$class .= ' editable_area';
			$id = 'id="ExtraEditArea'.$edit_index.'"';
		}

		//echo '<div class="blog_post">';
		echo '<div class="blog_post'.$class.'" '.$id.'>';

		$header = '<h2 id="blog_post_'.$post_index.'">';
		$label = SimpleBlogCommon::Underscores( $post['title'] );
		$header .= common::Link('Special_Blog',$label,'id='.$post_index);
		$header .= '</h2>';

		$this->BlogHead($header,$post_index,$post);

		echo '<div class="twysiwygr">';
		echo $this->AbbrevContent( $post['content'], $post_index, $limit);
		echo '</div>';

		echo '</div>';

		echo '<br/>';

		//echo showArray($post);
		echo '<div class="clear"></div>';

	}

	/**
	 * Abbreviate $content if a $limit greater than zero is given
	 *
	 */
	function AbbrevContent( $content, $post_index, $limit = 0 ){

		if( !is_numeric($limit) || $limit == 0 ){
			return $content;
		}

		$content = strip_tags($content);

		if( SimpleBlogCommon::strlen($content) < $limit ){
			return $content;
		}

		$pos = SimpleBlogCommon::strpos($content,' ',$limit-5);

		if( ($pos > 0) && ($limit+20 > $pos) ){
			$limit = $pos;
		}
		$content = SimpleBlogCommon::substr($content,0,$limit).' ... ';
		$label = gpOutput::SelectText('Read More');
		return $content . common::Link('Special_Blog',$label,'id='.$post_index);
	}



	/* comments */


	/**
	 * Get the comment data for a single post
	 *
	 */
	function GetCommentData($post_index){

		$data = array();
		$commentDataFile = $this->addonPathData.'/comments_data_'.$post_index.'.txt';
		if( file_exists($commentDataFile) ){
			$dataTxt = file_get_contents($commentDataFile);
			if(  !empty($dataTxt) ){
				$data = unserialize($dataTxt);
			}
		}
		return $data;
	}

	/**
	 * Remove a comment entry from the comment data
	 *
	 */
	function DeleteComment($post_index){
		global $langmessage;

		$data = $this->GetCommentData($post_index);

		$comment = $_POST['comment_id'];
		if( !isset($data[$comment]) ){
			message($langmessage['OOPS']);
			return;
		}

		unset($data[$comment]);

		if( $this->SaveCommentData($post_index,$data) ){
			message($langmessage['SAVED']);
			return true;
		}else{
			message($langmessage['OOPS']);
			return false;
		}

	}

	/**
	 * Add a comment to the comment data for a post
	 *
	 */
	function AddComment($post_index){
		global $langmessage;

		if( isset($this->blogData['post_info'][$post_index]) && isset($this->blogData['post_info'][$post_index]['closecomments']) ){
			return;
		}

		$data = $this->GetCommentData($post_index);

		//need a captcha?
		if( $this->blogData['comment_captcha'] && gp_recaptcha::isActive() ){

			if( !isset($_POST['anti_spam_submitted']) ){
			//if( !isset($_POST['recaptcha_challenge_field']) ){
				return false;

			}elseif( !gp_recaptcha::Check() ){
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
			if( SimpleBlogCommon::strpos($website,'://') === false ){
				$website = false;
			}
			if( $website ){
				$temp['website'] = $website;
			}
		}

		$data[] = $temp;

		if( $this->SaveCommentData($post_index,$data) ){
			message($langmessage['SAVED']);
			return true;
		}else{
			message($langmessage['OOPS']);
			return false;
		}
	}

	/**
	 * Save the comment data for a blog post
	 *
	 */
	function SaveCommentData($post_index,$data){
		global $langmessage;

		$dataTxt = serialize($data);
		$commentDataFile = $this->addonPathData.'/comments_data_'.$post_index.'.txt';
		if( !gpFiles::Save($commentDataFile,$dataTxt) ){
			return false;
		}

		$this->blogData['post_info'][$post_index]['comments'] = count($data);
		$this->SaveIndex();

		return true;
	}


	/**
	 * Output the html for a blog post's comments
	 *
	 */
	function GetCommentHtml( $data, $post_index ){
		global $langmessage;

		if( !is_array($data) ){
			continue;
		}

		foreach($data as $key => $comment){
			echo '<div class="comment_area">';
			echo '<p class="name">';
			if( ($this->blogData['commenter_website'] == 'nofollow') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'" rel="nofollow">'.$comment['name'].'</a></b>';
			}elseif( ($this->blogData['commenter_website'] == 'link') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'">'.$comment['name'].'</a></b>';
			}else{
				echo '<b>'.$comment['name'].'</b>';
			}
			echo ' &nbsp; ';
			echo '<span>';
			echo strftime($this->blogData['strftime_format'],$comment['time']);
			echo '</span>';


			if( common::LoggedIn() ){
				echo ' &nbsp; ';
				echo common::Link('Special_Blog',$langmessage['delete'],'cmd=delete_comment_prompt&id='.$post_index.'&comment='.$key,' name="ajax_box"');
			}


			echo '</p>';
			echo '<p class="comment">';
			echo $comment['comment'];
			echo '</p>';
			echo '</div>';
		}
	}
}


