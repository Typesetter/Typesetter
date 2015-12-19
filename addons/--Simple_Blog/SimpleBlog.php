<?php
defined('is_running') or die('Not an entry point...');

gpPlugin_incl('SimpleBlogCommon.php');

/**
 * Class for displaying the Special_Blog page and performing it's actions
 *
 */

class SimpleBlog extends SimpleBlogCommon{

	var $showing_category = false;

	function __construct(){
		global $page, $langmessage;

		SimpleBlogCommon::Init();

		//get the post id
		if( $page->pagetype == 'special_display' ){
			$this->post_id	= $this->PostID($page->requested);
		}


		if( common::LoggedIn() ){

			$page->admin_links[]		= array('Special_Blog','Blog Home');
			$page->admin_links[]		= array('Admin_Blog','New Blog Post','cmd=new_form');
			$page->admin_links[]		= array('Admin_Blog','Configuration');
			$page->admin_links[]		= array('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode(self::$data_dir),' name="gpabox" ');
			$label						= 'Number of Posts: '. SimpleBlogCommon::$data['post_count'];
			$page->admin_links[$label]	= '';
			$cmd						= common::GetCommand();


			switch($cmd){


				//delete
				case 'deleteentry':
				case 'delete':
					SimpleBlogCommon::Delete();
				break;
			}

		}


		if( $this->post_id ){
			$this->ShowPost();
			return;
		}


		$this->ShowPage();

		if( common::LoggedIn() && !file_exists(self::$index_file) ){
			echo '<p>Congratulations on successfully installing Simple Blog for gpEasy.</p> ';
			echo '<p>You\'ll probably want to get started by '.common::Link('Special_Blog','creating a blog post','cmd=new_form').'.</p>';
		}

	}


	/**
	 * Get the post id from the requested url
	 *
	 */
	public function PostID($requested){


		if( isset($_REQUEST['id']) && ctype_digit($_REQUEST['id']) ){
			return $_REQUEST['id'];
		}

		if( strpos($requested,'/') === false ){
			return;
		}

		$parts	= explode('/',$requested);

		if( SimpleBlogCommon::$data['urls'] != 'Title' ){
			$ints	= strspn($parts[1],'0123456789');
			if( $ints ){
				return substr($parts[1],0,$ints);
			}
		}

		$id = SimpleBlogCommon::AStrKey('titles',$parts[1], true);
		if( $id !== false ){
			return $id;
		}

		return $this->SimilarPost($parts[1]);
	}


	/**
	 * Get a id for the post that is most similar to the requested title
	 *
	 */
	public function SimilarPost($title){
		global $config;

		$titles				= SimpleBlogCommon::AStrToArray('titles');
		$post_times			= SimpleBlogCommon::AStrToArray('post_times');
		$similar			= array();
		$lower				= str_replace(' ','_',strtolower($title));

		foreach($titles as $post_id => $title){

			if( $post_times[$post_id] > time() ){
				continue;
			}

			similar_text($lower,strtolower($title),$percent);
			$similar[$percent] = $post_id; //if similarity is the same for two posts, the newer post will take precedence
		}

		krsort($similar);

		$similarity = key($similar);
		if( $config['auto_redir'] > 0 && $similarity >= $config['auto_redir'] ){
			return current($similar);
		}
	}



	/**
	 * Output the html for a single blog post
	 * Handle comment actions
	 */
	function ShowPost(){


		if( common::LoggedIn() ){
			gpPlugin_incl('Admin/SimpleBlogPage.php');
			$blog_page = new AdminSimpleBlogPage($this->post_id);
		}else{
			gpPlugin_incl('SimpleBlogPage.php');
			$blog_page = new SimpleBlogPage($this->post_id);
		}

		$blog_page->ShowPost();
	}


	/**
	 * Display a blog page with multiple blog posts
	 *
	 */
	public function ShowPage(){
		global $page;

		$per_page		= SimpleBlogCommon::$data['per_page'];
		$page_num		= 0;
		$expected_q		= '';
		if( isset($_GET['page']) && is_numeric($_GET['page']) ){
			$page_num		= (int)$_GET['page'];
			$expected_q		= 'page='.$page_num;
		}


		//redirect if the request isn't correct
		if( $page->requested != SimpleBlogCommon::$root_url ){
			$expected_url = common::GetUrl( SimpleBlogCommon::$root_url, $expected_q, false );
			common::Redirect($expected_url);
		}


		$start				= $page_num * $per_page;
		$include_drafts		= common::LoggedIn();
		$show_posts			= SimpleBlogCommon::WhichPosts($start,$per_page,$include_drafts);

		$this->ShowPosts($show_posts);

		//pagination links
		echo '<p class="blog_nav_links">';

		if( $page_num > 0 ){

			$html = common::Link('Special_Blog','%s');
			echo gpOutput::GetAddonText('Blog Home',$html);
			echo '&nbsp;';

			$html = common::Link('Special_Blog','%s','page='.($page_num-1),'class="blog_newer"');
			echo gpOutput::GetAddonText('Newer Entries',$html);
			echo '&nbsp;';

		}

		if( ( ($page_num+1) * $per_page) < SimpleBlogCommon::$data['post_count'] ){
			$html = common::Link('Special_Blog','%s','page='.($page_num+1),'class="blog_older"');
			echo gpOutput::GetAddonText('Older Entries',$html);
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
			$this->ShowPostContent( $post_index );
		}

	}

	/**
	 * Display the html for a single blog post
	 *
	 */
	function ShowPostContent( $post_index ){

		if( !common::LoggedIn() && SimpleBlogCommon::AStrValue('drafts',$post_index) ){
			return false;
		}

		$post	= SimpleBlogCommon::GetPostContent($post_index);
		$class	= $id = '';

		if( common::LoggedIn() ){
			SimpleBlog::EditLinks($post_index, $class, $id);
		}


		echo '<div class="blog_post post_list_item'.$class.'" '.$id.'>';

		$header = '<h2 id="blog_post_'.$post_index.'">';
		if( SimpleBlogCommon::AStrValue('drafts',$post_index) ){
			$header .= '<span style="opacity:0.3;">';
			$header .= gpOutput::SelectText('Draft');
			$header .= '</span> ';
		}elseif( $post['time'] > time() ){
			$header .= '<span style="opacity:0.3;">';
			$header .= gpOutput::SelectText('Pending');
			$header .= '</span> ';
		}

		$label = SimpleBlogCommon::Underscores( $post['title'] );
		$header .= SimpleBlogCommon::PostLink($post_index,$label);
		$header .= '</h2>';

		SimpleBlogCommon::BlogHead($header,$post_index,$post);

		echo '<div class="twysiwygr">';

		if( SimpleBlogCommon::$data['abbrev_image'] ){
			$this->GetImageFromPost($post['content']);
		}

		echo $this->AbbrevContent( $post['content'], $post_index, SimpleBlogCommon::$data['post_abbrev']);
		echo '</div>';

		echo '</div>';



		if( SimpleBlogCommon::$data['abbrev_cat'] && isset($post['categories']) && count($post['categories']) ){
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
				echo gpOutput::GetAddonText('Categories').' ';
				echo implode(', ',$temp);
				echo '</div>';
			}
		}


		echo '<div class="clear"></div>';

	}


	/**
	 * Get the fist image from the blog post
	 *
	 */
	function GetImageFromPost($item){

		$img_pos = strpos($item,'<img');
		if( $img_pos === false ){
			return;
		}
		$src_pos = strpos($item,'src=',$img_pos);
		if( $src_pos === false ){
			return;
		}
		$src = substr($item,$src_pos+4);
		$quote = $src[0];
		if( $quote != '"' && $quote != "'" ){
			return;
		}
		$src_pos = strpos($src,$quote,1);
		$src = substr($src,1,$src_pos-1);

		// check for resized image, get original source if img is resized
		if( strpos($src,'image.php') !== false && strpos($src,'img=') !== false ){
			$src = $dirPrefix . '/data/_uploaded/' . urldecode(substr($src,strpos($src,'img=')+4));
		}

		$thumb_path		= common::ThumbnailPath($src);

		//make it an absolute path
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$thumb_path = '//'.$server.$thumb_path;

		echo '<img class="img-thumbnail" src="'.$thumb_path.'"/>';
	}


	/**
	 * Get the edit links for the post
	 *
	 */
	static function EditLinks($post_index, &$class, &$id){
		global $langmessage;

		$query		= 'du'; //dummy parameter

		SimpleBlogCommon::UrlQuery( $post_index, $url, $query );

		$edit_link	= gpOutput::EditAreaLink($edit_index,$url,$langmessage['edit'].' (TWYSIWYG)',$query,'name="inline_edit_generic" rel="text_inline_edit"');
		$class 		= ' editable_area';
		$id			= 'id="ExtraEditArea'.$edit_index.'"';


		echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
		echo $edit_link;

		echo common::Link('Admin_Blog/'.$post_index,$langmessage['edit'].' (All)','cmd=edit_post',' style="display:none"');

		echo common::Link('Special_Blog',$langmessage['delete'],'cmd=deleteentry&del_id='.$post_index,array('class'=>'delete gpconfirm','data-cmd'=>'cnreq','title'=>$langmessage['delete_confirm']));

		if( SimpleBlogCommon::$data['allow_comments'] ){

			$comments_closed = SimpleBlogCommon::AStrValue('comments_closed',$post_index);
			if( $comments_closed ){
				$label = gpOutput::SelectText('Open Comments');
				echo SimpleBlogCommon::PostLink($post_index,$label,'cmd=opencomments','name="cnreq" style="display:none"');
			}else{
				$label = gpOutput::SelectText('Close Comments');
				echo SimpleBlogCommon::PostLink($post_index,$label,'cmd=closecomments','name="cnreq" style="display:none"');
			}
		}

		echo common::Link('Admin_Blog','New Blog Post','cmd=new_form',' style="display:none"');
		echo common::Link('Admin_Blog',$langmessage['administration'],'',' style="display:none"');
		echo '</span>';
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

		if( mb_strlen($content) < $limit ){
			return $content;
		}

		$pos = mb_strpos($content,' ',$limit-5);

		if( ($pos > 0) && ($limit+20 > $pos) ){
			$limit = $pos;
		}
		$content = mb_substr($content,0,$limit).' ... ';
		$label = gpOutput::SelectText('Read More');
		return $content . SimpleBlogCommon::PostLink($post_index,$label);
	}


}


