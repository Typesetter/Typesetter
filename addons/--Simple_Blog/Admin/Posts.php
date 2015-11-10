<?php

defined('is_running') or die('Not an entry point...');

gpPlugin::incl('Admin/Admin.php','require_once');


class AdminSimpleBlogPosts extends SimipleBlogAdmin{

	function __construct(){
		global $langmessage, $page;
		parent::__construct();


		//post request
		if( strpos($page->requested,'/') ){
			$parts	= explode('/',$page->requested);
			if( $this->AdminPost($parts[1])  ){
				return;
			}
		}


		//general admin
		$cmd = common::GetCommand();
		switch( $cmd ){

			//creating
			case 'save_new';
				$this->SaveNew(); //will redirect on success
			case 'new_form':
				$this->NewForm();
			return;

		}

		$this->ShowPosts();
	}


	/**
	 * Show all blog posts
	 *
	 */
	function ShowPosts(){
		global $langmessage;

		$this->Heading('Admin_Blog');

		$post_ids			= SimpleBlogCommon::AStrToArray('str_index');
		$post_titles		= SimpleBlogCommon::AStrToArray('titles');
		$post_times			= SimpleBlogCommon::AStrToArray('post_times');
		$post_comments		= SimpleBlogCommon::AStrToArray('comment_counts');
		$post_closed		= SimpleBlogCommon::AStrToArray('comments_closed');
		$post_drafts		= SimpleBlogCommon::AStrToArray('drafts');




		echo '<table class="bordered full_width">';
		echo '<thead><tr><th></th><th>Post ('.number_format(count($post_ids)).')';
		echo '</th><th>Date</th><th>Comments</th>';
		echo '<th>Options</th>';
		echo '</tr></thead>';
		echo '<tbody>';
		foreach($post_ids as $i => $id){
			echo '<tr><td width="1%">';
			if( isset($post_drafts[$id]) ){
				echo 'Draft';
			}elseif( $post_times[$id] > time() ){
				echo 'Pending';
			}
			echo '</td><td>';
			$title = $post_titles[$id];
			echo SimpleBlogCommon::PostLink($id,$title);
			echo '</td><td>';
			if( isset($post_times[$id]) ){
				echo strftime(SimpleBlogCommon::$data['strftime_format'],$post_times[$id]);
			}
			echo '</td><td>';
			if( isset($post_comments[$id]) ){
				echo $post_comments[$id];
			}
			echo '</td><td>';
			if( isset($post_closed[$id]) ){
				echo 'Closed';
			}
			echo ' &nbsp; ';
			echo common::Link('Admin_Blog/'.$id,$langmessage['edit'],'cmd=edit_post');

			echo '</td></tr>';
		}
		echo '</tbody>';
		echo '</table>';


	}


	/**
	 * Admin a blog post
	 *
	 */
	function AdminPost($id){
		global $langmessage;

		if( !ctype_digit($id) ){
			message($langmessage['OOPS'].' (Invalid Request)');
			return false;
		}


		$this->post_id			= $id;
		$this->post				= SimpleBlogCommon::GetPostContent($id);
		if( !$this->post ){
			message($langmessage['OOPS'].' (No Post)');
			return false;
		}

		$this->_AdminPost();

		return true;
	}

	function _AdminPost(){

		$cmd = common::GetCommand();
		switch( $cmd ){

			//editing
			case 'save_edit':
				$this->SaveEdit();
			break;
		}

		$this->EditPost();
	}



	/**
	 * Display the form for editing an existing post
	 *
	 */
	function EditPost(){
		global $langmessage, $page;



		$page->ajaxReplace	= array();
		$post				= $this->post;
		$_POST				+= $post;
		$title				= htmlspecialchars($_POST['title'],ENT_COMPAT,'UTF-8',false);

		$this->PostForm('Edit Post',$_POST,'save_edit',$this->post_id);
	}


	/**
	 * Display the form for creating a new post
	 *
	 */
	function NewForm(){
		$this->PostForm('New Blog Post',$_POST);
	}


	/**
	 * Display form for submitting posts (new and edit)
	 *
	 */
	function PostForm($label,&$array,$cmd='save_new',$post_id=false){
		global $langmessage;

		includeFile('tool/editing.php');

		$array 				+= array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>false, 'categories'=>array(), 'time'=>time() );
		$array				+= array('isDraft'=>SimpleBlogCommon::AStrValue('drafts',$this->post_id));
		$array['title']		= SimpleBlogCommon::Underscores( $array['title'] );

		$action = common::GetUrl('Admin_Blog');
		if( $post_id ){
			$action = common::GetUrl('Admin_Blog/'.$post_id);
		}

		echo '<form class="post_form" action="'.$action.'" method="post">';


		//save
		echo '<div style="float:right">';
		echo '<input type="hidden" name="cmd" value="'.$cmd.'" />';
		echo '<input class="gpsubmit" type="submit" name="" value="'.$langmessage['save'].'" /> ';
		echo common::Link('Admin_Blog',$langmessage['cancel'],'',' class="gpcancel"');
		echo '</div>';

		//heading
		echo '<h2 class="hmargin">'.$label.'</h2>';

		echo '<div class="sb_post_container cf">';

		echo '<div class="sb_post_container_right">';


		//title + sub-title
		echo '<div class="sb_edit_box">';

		echo '<div class="sb_edit_group">';
		echo '<label>Title</label>';
		echo '<input type="text" name="title" value="'.$array['title'].'" required class="gpinput" />';
		echo '</div>';

		echo '<div class="sb_edit_group">';
		echo '<label>Sub-Title</label>';
		echo '<input type="text" name="subtitle" value="'.$array['subtitle'].'" class="gpinput" />';
		echo '</div>';

		echo '</div>'; //.sb_edit_box


		//draft + date
		echo '<div class="sb_edit_box">';

		echo '<div class="sb_edit_group">';
		echo '<label>';
		echo '<input type="checkbox" name="isDraft" value="on" data-cmd="DraftCheckbox" ';
		if( $array['isDraft'] ) echo 'checked="true"';
		echo '" /> Draft</label>';
		echo '</div>';

		$this->FieldPublish($array);

		echo '</div>';


		//categories
		echo '<div class="sb_edit_box">';
		echo '<div class="sb_edit_group">';
		self::ShowCategoryList($post_id,$array);
		echo '</div>';
		echo '</div>';

		echo '</div>'; //.sb_container_right


		//content
		echo '<div class="sb_post_container_left">';
		gp_edit::UseCK($array['content'],'content');
		echo '</div>';

		echo '</div>';



		//save
		echo '<div>';
		echo '</div>';

		echo '</form>';
	}


	/**
	 * Display fields for setting publish date
	 *
	 */
	function FieldPublish($array){

		$style = '';
		if( $array['isDraft'] ){
			$style = ' style="display:none"';
		}

		echo '<div class="sb_edit_group" id="sb_field_publish" '.$style.'>';
		echo '<label>Publish Time</label>';


		//month
		$pub_month	= date('n',$array['time']);
		$months		= array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

		echo '<select name="pub_month" style="width:2.5em">';
		for($i = 1; $i <= 12; $i++){
			$selected = '';
			if( $i == $pub_month ){
				$selected = 'selected';
			}
			echo '<option value="'.$i.'" '.$selected.'>'.$months[$i-1].' ('.$i.')</option>';
		}
		echo '</select>';

		//day
		echo '<input name="pub_day" value="'.date('d',$array['time']).'" style="width:1.5em"/>';
		echo ', ';
		echo '<input name="pub_year" value="'.date('Y',$array['time']).'" style="width:3.5em"/>';

		//time
		echo '@';
		echo '<input name="pub_hour" value="'.date('H',$array['time']).'" style="width:1.5em"/>';
		echo ':';
		echo '<input name="pub_min" value="'.date('i',$array['time']).'" style="width:1.5em"/>';


		echo '</div>';
	}



	/**
	 * Save a new blog post
	 * @return bool
	 *
	 */
	function SaveNew(){
		global $langmessage, $gpAdmin;

		//use current data file or create new one
		SimpleBlogCommon::$data['post_index']++;
		$new_id				= SimpleBlogCommon::$data['post_index'];

		//add new_id to list of indeces
		$str_index = SimpleBlogCommon::AStrToArray('str_index');
		array_unshift($str_index,$new_id);
		SimpleBlogCommon::$data['str_index']	= SimpleBlogCommon::AStrFromArray($str_index);


		//save to data file
		$post = array();
		if( !self::SavePost($new_id, $post) ){
			return false;
		}

		//redirect to new post
		$url = common::GetUrl('Admin_Blog','',false);
		common::Redirect($url);
	}




	/**
	 * Save an edited blog post
	 * @return bool
	 *
	 */
	function SaveEdit(){
		global $langmessage;


		//save to data file
		if( !self::SavePost($this->post_id, $this->post) ){
			message($langmessage['OOPS'].' (Post not saved)');
			return false;
		}


		$this->post			= SimpleBlogCommon::GetPostContent($this->post_id);

		return true;
	}


	/**
	 * Save the post
	 *
	 */
	static function SavePost($post_id, $post){
		global $langmessage;

		$_POST			+= array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>'','category'=>array());
		$title			= htmlspecialchars($_POST['title']);
		$title			= trim($title);

		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}


		//get post time
		$_POST['pub_year']		= ($_POST['pub_year'] <= 0 )	? date('Y') : $_POST['pub_year'];
		$_POST['pub_month']		= ($_POST['pub_month'] <= 0 )	? date('n') : $_POST['pub_month'];
		$_POST['pub_day']		= ($_POST['pub_day'] > 31 )		? 31 : $_POST['pub_day'];
		$_POST['pub_day']		= ($_POST['pub_day'] <= 0 )		? date('j') : $_POST['pub_day'];
		$_POST['pub_hour']		= ($_POST['pub_hour'] > 23 )	? $_POST['pub_hour'] -24 : $_POST['pub_hour'];
		$_POST['pub_min']		= ($_POST['pub_min'] > 59 ) ? $_POST['pub_min'] -60 : $_POST['pub_min'];
		$_POST['time']			= gmmktime( $_POST['pub_hour'], $_POST['pub_min'], 0, $_POST['pub_month'], $_POST['pub_day'], $_POST['pub_year'] );


		//different time
		//organize posts based on publish time
		SimpleBlogCommon::AStrValue('post_times',$post_id,$_POST['time']);
		$post_times			= SimpleBlogCommon::AStrToArray('post_times');
		arsort($post_times);

		$str_index			= array_keys($post_times);
		SimpleBlogCommon::$data['str_index']	= SimpleBlogCommon::AStrFromArray($str_index);


		//get next static gen time
		SimpleBlogCommon::NextGenTime();


		//create post array
		$post['title']			= $title;
		$post['content']		= $_POST['content'];
		$post['subtitle']		= htmlspecialchars($_POST['subtitle']);
		$post['categories']		= $_POST['category'];
		$post['time']			= $_POST['time'];
		unset($post['isDraft']);


		//save to data file
		if( !parent::SavePost($post_id, $post) ){
			return false;
		}


		//draft
		if( $_POST['isDraft'] === 'on' ){
			SimpleBlogCommon::AStrValue('drafts',$post_id,1);
		}else{
			SimpleBlogCommon::AStrRm('drafts',$post_id);
		}



		SimpleBlogCommon::AStrValue('titles',$post_id,$title);
		self::UpdatePostCategories($post_id,$title);	//find and update the edited post in categories and archives

		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS'].' (Index not saved)');
			return false;
		}

		SimpleBlogCommon::GenStaticContent();
		message($langmessage['SAVED']);



		return true;
	}


	/**
	 * Show a list of all categories
	 *
	 */
	static function ShowCategoryList( $post_id, $post ){

		$_POST += array('category'=>array());

		echo '<label>Category</label>';
		echo '<select name="category[]" multiple="multiple"  class="gpinput">';

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
		echo '</select>';
	}




	/**
	 * Update a category when a blog entry is edited
	 *
	 */
	static function UpdatePostCategories( $post_id, $title ){

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
