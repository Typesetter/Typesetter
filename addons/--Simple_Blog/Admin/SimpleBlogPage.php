<?php
defined('is_running') or die('Not an entry point...');


gpPlugin::incl('SimpleBlogPage.php','require_once');

class AdminSimpleBlogPage extends SimpleBlogPage{

	function ShowPost(){


		$cmd = common::GetCommand();

		switch($cmd){

			//editing
			case 'save_edit':
				if( $this->SaveEdit() ){
					break;
				}
			case 'edit':
			case 'edit_post';
				$this->EditPost();
			return;

			//creating
			case 'save_new';
				$this->SaveNew(); //will redirect on success
			case 'new_form':
				$this->NewForm();
			return;



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


		parent::ShowPost();
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
	 * Display the form for creating a new post
	 *
	 */
	function NewForm(){
		echo '<div class="blog_post_new">';
		echo '<h2>New Blog Post</h2>';

		$this->PostForm($_POST);
		echo '</div>';
	}


	/**
	 * Display form for submitting posts (new and edit)
	 *
	 */
	function PostForm(&$array,$cmd='save_new',$post_id=false){
		global $langmessage;

		includeFile('tool/editing.php');

		$array += array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>false, 'categories'=>array() );
		$array['title'] = SimpleBlogCommon::Underscores( $array['title'] );

		echo '<form class="post_form" action="'.SimpleBlogCommon::PostUrl($post_id).'" method="post">';

		echo '<table class="bordered full_width">';
		echo '<thead><tr><th colspan="2">'.$langmessage['options'].'</th></tr></thead>';
		echo '<tbody>';

		//title
		echo '<tr><td>';
		echo 'Title';
		echo '</td><td>';
		echo '<input type="text" name="title" value="'.$array['title'].'" />';
		echo '</td></tr>';

		//sub title
		echo '<tr><td>';
		echo 'Sub-Title';
		echo '</td><td>';
		echo '<input type="text" name="subtitle" value="'.$array['subtitle'].'" />';
		echo '</td></tr>';

		//draft
		echo '<tr><td>';
		echo 'Draft';
		echo '</td><td>';
		echo '<input type="checkbox" name="isDraft" value="on" ';
			if( $array['isDraft'] ) echo 'checked="true"';
			echo '" />';
		echo '</td></tr>';

		self::ShowCategoryList($post_id,$array);

		//content
		echo '<tr><td colspan="2">';
		gp_edit::UseCK($array['content'],'content');
		echo '</td></tr>';

		//save
		echo '<tr><td colspan="2">';
		echo '<input type="hidden" name="cmd" value="'.$cmd.'" />';
		echo '<input type="hidden" name="id" value="'.$post_id.'" />';
		echo '<input class="post_form_save" type="submit" name="" value="'.$langmessage['save'].'" /> ';
		echo '<input class="post_form_cancel" type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</td></tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</form>';
	}


	/**
	 * Show a list of all categories
	 *
	 */
	static function ShowCategoryList( $post_id, $post ){

		$_POST += array('category'=>array());

		echo '<tr><td>Category</td><td>';
		echo '<select name="category[]" multiple="multiple">';

		$categories = SimpleBlogCommon::AStrToArray( 'categories' );
		foreach( $categories as $catindex => $catname ){

			$selected = '';
			$label = $catname;
			if( $post_id && in_array($catindex, $post['categories']) ){
				$selected = 'selected="selected"';
			}elseif( in_array($catindex, $_POST['category']) ){
				$selected = 'selected="selected"';
			}

			if( SimpleBlogCommon::AStrValue('categories_hidden', $catindex) ){
				$label .= ' (Hidden)';
			}

			echo '<option value="'.$catindex.'" '.$selected.'>'.$label.'</option>';
		}
		echo '</select></td></tr>';
	}




	/**
	 * Save a new blog post
	 * @return bool
	 *
	 */
	function SaveNew(){
		global $langmessage, $gpAdmin;

		$_POST		+= array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>'','category'=>array());

		$title		= $_POST['title'];
		$title		= htmlspecialchars($title);
		$title		= trim($title);

		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}


		$time					= time();
		$post					= array();
		$post['title']			= $title;
		$post['content']		= $_POST['content'];
		$post['subtitle']		= htmlspecialchars($_POST['subtitle']);
		$post['categories']		= $_POST['category'];
		$post['time']			= $time;


		//use current data file or create new one
		$post_index				= SimpleBlogCommon::$data['post_index'] +1;



		if( $_POST['isDraft'] === 'on' ){
			SimpleBlogCommon::AStrValue('drafts',$post_index,1);
		}else{
			SimpleBlogCommon::AStrRm('drafts',$post_index);
		}

		//save to data file
		if( !self::SavePost($post_index, $post) ){
			return false;
		}


		//add new entry to the beginning of the index string then reorder the keys
		$new_index = '"0>'.$post_index.SimpleBlogCommon::$data['str_index'];
		preg_match_all('#(?:"\d+>)([^">]*)#',$new_index,$matches);
		SimpleBlogCommon::$data['str_index'] = SimpleBlogCommon::AStrFromArray($matches[1]);


		//save index file
		SimpleBlogCommon::AStrValue('titles',$post_index,$title);
		SimpleBlogCommon::AStrValue('post_times',$post_index,$time);
		$this->UpdatePostCategories($post_index,$title);

		SimpleBlogCommon::$data['post_index'] = $post_index;
		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS'].' (Index not Saved)');
			return false;
		}

		SimpleBlogCommon::GenStaticContent();
		message($langmessage['SAVED']);


		//redirect to new post
		SimpleBlogCommon::UrlQuery( $post_index, $url, $query );
		$url = str_replace('&amp;','&',$url); //because of htmlspecialchars($cattitle)
		$url = common::GetUrl( $url, $query, false );
		common::Redirect($url);
	}


	/**
	 * Save an edited blog post
	 * @return bool
	 *
	 */
	function SaveEdit(){
		global $langmessage;

		$_POST			+= array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>'','category'=>array());

		if( $this->post === false ){
			message($langmessage['OOPS'].' (Invalid Post)');
			return;
		}


		$title			= htmlspecialchars($_POST['title']);
		$title			= trim($title);

		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}

		$post					= $this->post;
		$post['title']			= $title;
		$post['content']		= $_POST['content'];
		$post['subtitle']		= htmlspecialchars($_POST['subtitle']);
		$post['categories']		= $_POST['category'];
		unset($post['isDraft']);
		if( $_POST['isDraft'] === 'on' ){
			SimpleBlogCommon::AStrValue('drafts',$this->post_id,1);
		}else{
			SimpleBlogCommon::AStrRm('drafts',$this->post_id);
		}


		//save to data file
		if( !self::SavePost($this->post_id, $post) ){
			return false;
		}

		//update title
		SimpleBlogCommon::AStrValue('titles',$this->post_id,$title);

		//find and update the edited post in categories and archives
		$this->UpdatePostCategories($this->post_id,$title);


		SimpleBlogCommon::SaveIndex();
		SimpleBlogCommon::GenStaticContent();

		$this->post			= SimpleBlogCommon::GetPostContent($this->post_id);

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


	/**
	 * Display the form for editing an existing post
	 *
	 */
	function EditPost(){
		global $langmessage, $page;

		$page->ajaxReplace = array();


		if( $this->post === false ){
			message($langmessage['OOPS'].' (No Post)');
			return;
		}

		$post				= $this->post;
		$post['isDraft']	= SimpleBlogCommon::AStrValue('drafts',$this->post_id);
		$_POST				+= $post;
		$title				= htmlspecialchars($_POST['title'],ENT_COMPAT,'UTF-8',false);


		ob_start();
		echo '<h2>Edit Post</h2>';
		$this->PostForm($_POST,'save_edit',$this->post_id);


		$array = array();
		$array[0] = 'admin_box_data';
		$array[1] = '';
		$array[2] = ob_get_clean();
		$page->ajaxReplace[] = $array;
	}


	/**
	 * Update a category when a blog entry is edited
	 *
	 */
	function UpdatePostCategories( $post_id, $title ){

		$_POST += array('category'=>array());

		//get order of all posts
		$post_times = SimpleBlogCommon::AStrToArray( 'post_times' );
		arsort($post_times);
		$post_times = array_keys($post_times);


		//loop through each category
		$categories = SimpleBlogCommon::AStrToArray( 'categories' );
		$edited_categories = array();
		foreach( $categories as $catindex => $catname ){

			SimpleBlogCommon::AStrRmValue('category_posts_'.$catindex, $post_id );
			if( in_array($catindex, $_POST['category']) ){

				//add and order correctly
				$category_posts = SimpleBlogCommon::AStrToArray( 'category_posts_'.$catindex );
				$category_posts[] = $post_id;
				$category_posts = array_intersect($post_times, $category_posts);
				SimpleBlogCommon::$data['category_posts_'.$catindex] = SimpleBlogCommon::AStrFromArray($category_posts);
			}
		}

	}



}
