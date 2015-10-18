<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

/**
 * Class for displaying the Special_Blog page and performing it's actions
 *
 */

class SimpleBlog extends SimpleBlogCommon{

	var $showing_category = false;

	function __construct(){
		global $page, $langmessage, $addonFolderName;

		$this->Init();

		//get the post id
		if( isset($_REQUEST['id']) && ctype_digit($_REQUEST['id']) ){
			$this->post_id = $_REQUEST['id'];

		}elseif( strpos($page->requested,'/') !== false ){
			$parts = explode('/',$page->requested);
			$ints = strspn($parts[1],'0123456789');
			if( $ints > 0 ){
				$this->post_id = substr($parts[1],0,$ints);
			}
		}


		$cmd	= common::GetCommand();
		$show	= true;

		if( common::LoggedIn() ){

			switch($cmd){

				/* inline editing */
				case 'inlineedit':
					$this->InlineEdit();
				die();
				case 'save_inline':
				case 'save':
					$this->SaveInline();
				break;


				//delete
				case 'deleteentry':
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
				case 'edit_post';
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

			$page->admin_links[] = array('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addonFolderName),' name="gpabox" ');

			$label = 'Number of Posts: '. SimpleBlogCommon::$data['post_count'];
			$page->admin_links[$label] = '';
		}


		if( $show ){

			//post requests
			if( empty($cmd) ){
				if( $this->post_id > 0 ){
					$cmd = 'post';
				}
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
	 * Output the html for a single blog post
	 * Handle comment actions
	 */
	function ShowPost($cmd){
		global $langmessage, $page;

		$post	= $this->GetPostContent($this->post_id);

		if( $post === false ){
			message($langmessage['OOPS']);
			return;
		}

		$commentSaved = false;
		switch($cmd){

			//redirect to correct url if needed
			case 'post':
				SimpleBlogCommon::UrlQuery( $this->post_id, $expected_url, $query );
				$expected_url = str_replace('&amp;','&',$expected_url); //because of htmlspecialchars($cattitle)
				if( $page->requested != $expected_url ){
					$expected_url = common::GetUrl( $expected_url, $query, false );
					common::Redirect($expected_url,301);
				}
			break;

			//close comments
			case 'closecomments':
				$this->CloseComments($this->post_id);
			break;
			case 'opencomments':
				$this->OpenComments($this->post_id);
			break;


			//commments
			case 'Add Comment':
				if( $this->AddComment($this->post_id) ){
					$commentSaved = true;
				}else{
					echo '<div class="comment_container">';
					$this->CommentForm($this->post_id,true);
					echo '</div>';
					return;
				}
			break;
			case 'delete_comment':
				$this->DeleteComment($this->post_id);
			break;
		}



		$post	= $this->GetPostContent($this->post_id);

		if( !common::LoggedIn() && SimpleBlogCommon::AStrValue('drafts',$this->post_id) ){
			//How to make 404 page?
			message($langmessage['OOPS']);
			return;
		}
		$this->ShowPostContent($post,$this->post_id,0,'single_blog_item');

		$page->label = SimpleBlogCommon::Underscores( $post['title'] );

		//blog categories
		if( isset($post['categories']) && count($post['categories']) ){
			$temp = array();
			foreach($post['categories'] as $catindex){
				$title = SimpleBlogCommon::AStrValue( 'categories', $catindex );
				if( !$title ){
					continue;
				}
				if( SimpleBlogCommon::AStrValue('categories_hidden',$catindex) ){
					continue;
				}
				$temp[] = SimpleBlogCommon::CategoryLink($catindex, $title, $title);
			}

			if( count($temp) ){
				echo '<div class="category_container">';
				echo '<b>';
				echo gpOutput::GetAddonText('Categories');
				echo ':</b> ';
				echo implode(', ',$temp);
				echo '</div>';
			}
		}

		SimpleBlog::PostLinks($this->post_id);

		//comments
		if( SimpleBlogCommon::$data['allow_comments'] ){
			echo '<div class="comment_container">';
			$this->ShowComments($this->post_id);
			if( !$commentSaved ){
				$this->CommentForm($this->post_id);
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

		SimpleBlogCommon::AStrValue('comments_closed',$post_index,1);
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

		SimpleBlogCommon::AStrRm('comments_closed',$post_index);
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

		$comments_closed = SimpleBlogCommon::AStrValue('comments_closed',$post_index);
		if( $comments_closed ){
			echo '<div class="comments_closed">';
			echo gpOutput::GetAddonText('Comments have been closed.');
			echo '</div>';
			return;
		}


		$_POST += array('name'=>'','website'=>'http://','comment'=>'');

		echo '<h3>';
		echo gpOutput::GetAddonText('Leave Comment');
		echo '</h3>';


		echo '<form method="post" action="'.SimpleBlogCommon::PostUrl($post_index).'">';
		echo '<ul>';
		echo '<li>';
			echo '<label>';
			echo gpOutput::GetAddonText('Name');
			echo '</label><br/>';
			echo '<input type="text" name="name" class="text" value="'.htmlspecialchars($_POST['name']).'" />';
			echo '</li>';

		if( !empty(SimpleBlogCommon::$data['commenter_website']) ){
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


		if( $showCaptcha && SimpleBlogCommon::$data['comment_captcha'] && gp_recaptcha::isActive() ){
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
			echo '</li>';

		echo '</ul>';
		echo '</form>';

	}

	/**
	 * Display the links at the bottom of a post
	 *
	 */
	static function PostLinks($post_index){

		$post_key = SimpleBlogCommon::AStrKey('str_index',$post_index);

		echo '<p class="blog_nav_links">';


		//blog home
		$html = common::Link('Special_Blog','%s');
		echo gpOutput::GetAddonText('Blog Home',$html);
		echo '&nbsp;';


		// check for newer posts and if post is draft
		$isDraft = false;
		if( $post_key > 0 ){

			$i = 0;
			do {
				$i++;
				$next_index = SimpleBlogCommon::AStrValue('str_index',$post_key-$i);
				if( !common::loggedIn() ){
					$isDraft = SimpleBlogCommon::AStrValue('drafts',$next_index);
				}
			}while( $isDraft );

			if( !$isDraft ){
				$html = SimpleBlogCommon::PostLink($next_index,'%s','','class="blog_newer"');
				echo gpOutput::GetAddonText('Newer Entry',$html);
				echo '&nbsp;';
			}
		}


		//check for older posts and if older post is draft
		$i = 0;
		$isDraft = false;
		do{
			$i++;
			$prev_index = SimpleBlogCommon::AStrValue('str_index',$post_key+$i);

			if( $prev_index === false ){
				break;
			}

			if( !common::loggedIn() ){
				$isDraft = SimpleBlogCommon::AStrValue('drafts',$prev_index);
			}

			if( !$isDraft ){
				$html = SimpleBlogCommon::PostLink($prev_index,'%s','','class="blog_older"');
				echo gpOutput::GetAddonText('Older Entry',$html);
			}

		}while( $isDraft );


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

		$per_page = SimpleBlogCommon::$data['per_page'];
		$page = 0;
		if( isset($_GET['page']) && is_numeric($_GET['page']) ){
			$page = (int)$_GET['page'];
		}
		$start = $page * $per_page;

		$include_drafts = common::LoggedIn();
		$show_posts = $this->WhichPosts($start,$per_page,$include_drafts);

		$this->ShowPosts($show_posts);

		//pagination links
		echo '<p class="blog_nav_links">';

		if( $page > 0 ){

			$html = common::Link('Special_Blog','%s');
			echo gpOutput::GetAddonText('Blog Home',$html);
			echo '&nbsp;';

			$html = common::Link('Special_Blog','%s','page='.($page-1),'class="blog_newer"');
			echo gpOutput::GetAddonText('Newer Entries',$html);
			echo '&nbsp;';

		}

		if( ( ($page+1) * $per_page) < SimpleBlogCommon::$data['post_count'] ){
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
	 * Output the blog posts in the array $post_list
	 *
	 */
	function ShowPosts($post_list){

		$posts = array();
		foreach($post_list as $post_index){
			$post	= $this->GetPostContent($post_index);
			$this->ShowPostContent( $post, $post_index, SimpleBlogCommon::$data['post_abbrev'], 'post_list_item' );
		}

	}

	/**
	 * Display the html for a single blog post
	 *
	 */
	function ShowPostContent( &$post, &$post_index, $limit = 0, $class = '' ){
		global $langmessage;

		if( !common::LoggedIn() && SimpleBlogCommon::AStrValue('drafts',$post_index) ){
			return false; //How to make 404 page?
		}

		//If user enter random Blog url, he didn't see any 404, but nothng.
		$id = '';
		$class = $class == '' ? '' : ' '.$class;
		if( common::LoggedIn() ){

			$query = 'du'; //dummy parameter
			SimpleBlogCommon::UrlQuery( $post_index, $url, $query );
			$edit_link = gpOutput::EditAreaLink($edit_index,$url,$langmessage['edit'].' (TWYSIWYG)',$query,'name="inline_edit_generic" rel="text_inline_edit"');

			echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
			echo $edit_link;

			echo SimpleBlogCommon::PostLink($post_index,$langmessage['edit'].' (All)','cmd=edit_post',' style="display:none"');
			echo common::Link('Special_Blog',$langmessage['delete'],'cmd=deleteentry&del_id='.$post_index,array('class'=>'delete gpconfirm','data-cmd'=>'cnreq','title'=>$langmessage['delete_confirm']));

			if( SimpleBlogCommon::$data['allow_comments'] ){

				$comments_closed = SimpleBlogCommon::AStrValue('comments_closed',$post_index);
				if( $comments_closed ){
					$label = gpOutput::SelectText('Open Comments');
					echo SimpleBlogCommon::PostLink($post_index,$label,'cmd=opencomments','name="creq" style="display:none"');
				}else{
					$label = gpOutput::SelectText('Close Comments');
					echo SimpleBlogCommon::PostLink($post_index,$label,'cmd=closecomments','name="creq" style="display:none"');
				}
			}

			echo common::Link('Special_Blog','New Blog Post','cmd=new_form',' style="display:none"');
			echo common::Link('Admin_Blog',$langmessage['configuration'],'',' style="display:none"');
			echo '</span>';
			$class .= ' editable_area';
			$id = 'id="ExtraEditArea'.$edit_index.'"';
		}

		$isDraft = '';
		if( SimpleBlogCommon::AStrValue('drafts',$post_index) ){
			$isDraft = '<span style="opacity:0.3;">';
			$isDraft .= gpOutput::SelectText('Draft');
			$isDraft .= '</span> ';
		}
		echo '<div class="blog_post'.$class.'" '.$id.'>';

		$header = '<h2 id="blog_post_'.$post_index.'">';
		$header .= $isDraft;
		$label = SimpleBlogCommon::Underscores( $post['title'] );
		$header .= SimpleBlogCommon::PostLink($post_index,$label);
		$header .= '</h2>';

		$this->BlogHead($header,$post_index,$post);

		echo '<div class="twysiwygr">';
		echo $this->AbbrevContent( $post['content'], $post_index, $limit);
		echo '</div>';

		echo '</div>';

		echo '<br/>';

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
		return $content . SimpleBlogCommon::PostLink($post_index,$label);
	}



	/* comments */




	/**
	 * Remove a comment entry from the comment data
	 *
	 */
	function DeleteComment($post_index){
		global $langmessage;

		$data = $this->GetCommentData($post_index);

		$comment = $_POST['comment_index'];
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

		$comments_closed = SimpleBlogCommon::AStrValue('comments_closed',$post_index);
		if( $comments_closed ){
			return;
		}

		$data = $this->GetCommentData($post_index);

		//need a captcha?
		if( SimpleBlogCommon::$data['comment_captcha'] && gp_recaptcha::isActive() ){

			if( !isset($_POST['anti_spam_submitted']) ){
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

		if( !$this->SaveCommentData($post_index,$data) ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);


		//email new comments
		if( !empty(SimpleBlogCommon::$data['email_comments']) ){



			$subject = 'New Comment';
			$body = '';
			if( !empty($temp['name']) ){
				$body .= '<p>From: '.$temp['name'].'</p>';
			}
			if( !empty($temp['website']) ){
				$body .= '<p>Website: '.$temp['name'].'</p>';
			}
			$body .= '<p>'.$temp['comment'].'</p>';

			global $gp_mailer;
			includeFile('tool/email_mailer.php');
			$gp_mailer->SendEmail(SimpleBlogCommon::$data['email_comments'], $subject, $body);
		}


		return true;
	}


	/**
	 * Output the html for a blog post's comments
	 *
	 */
	function GetCommentHtml( $data, $post_index ){
		global $langmessage;

		if( !is_array($data) ){
			return;
		}

		foreach($data as $key => $comment){
			echo '<div class="comment_area">';
			echo '<p class="name">';
			if( (SimpleBlogCommon::$data['commenter_website'] == 'nofollow') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'" rel="nofollow">'.$comment['name'].'</a></b>';
			}elseif( (SimpleBlogCommon::$data['commenter_website'] == 'link') && !empty($comment['website']) ){
				echo '<b><a href="'.$comment['website'].'">'.$comment['name'].'</a></b>';
			}else{
				echo '<b>'.$comment['name'].'</b>';
			}
			echo ' &nbsp; ';
			echo '<span>';
			echo strftime(SimpleBlogCommon::$data['strftime_format'],$comment['time']);
			echo '</span>';


			if( common::LoggedIn() ){
				echo ' &nbsp; ';
				$attr = 'class="delete gpconfirm" title="'.$langmessage['delete_confirm'].'" name="postlink" data-nonce= "'.common::new_nonce('post',true).'"';
				echo SimpleBlogCommon::PostLink($post_index,$langmessage['delete'],'cmd=delete_comment&comment_index='.$key,$attr);
			}


			echo '</p>';
			echo '<p class="comment">';
			echo $comment['comment'];
			echo '</p>';
			echo '</div>';
		}
	}
}


