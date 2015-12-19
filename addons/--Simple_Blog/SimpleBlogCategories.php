<?php
defined('is_running') or die('Not an entry point...');

//gpPlugin_incl('SimpleBlogCommon.php');
gpPlugin_incl('SimpleBlog.php');

class BlogCategories extends SimpleBlog{

	var $categories = array();
	var $catindex = false;
	var $total_posts = 0;

	function __construct(){
		global $page;

		SimpleBlogCommon::Init();
		$this->categories = SimpleBlogCommon::AStrToArray( 'categories' );


		$this->catindex = $this->CatIndex($page->requested);

		if( $this->catindex && isset($this->categories[$this->catindex]) && !SimpleBlogCommon::AStrGet('categories_hidden',$this->catindex) ){
			$this->ShowCategory();
		}else{
			$this->ShowCategories();
		}
	}

	/**
	 * Get the category index from the request
	 *
	 */
	function CatIndex($requested){

		if( isset($_REQUEST['cat'])	){
			return $_REQUEST['cat'];
		}

		if( strpos($requested,'/') === false ){
			return false;
		}

		$parts	= explode('/',$requested);

		if( ctype_digit($parts[1]) ){
			return $parts[1];
		}


		$parts[1] = str_replace('_',' ',$parts[1]);
		return array_search($parts[1],$this->categories);
	}


	function ShowCategory(){

		$this->showing_category = $this->catindex;
		$catname = $this->categories[$this->catindex];

		//paginate
		$per_page = SimpleBlogCommon::$data['per_page'];
		$page = 0;
		if( isset($_GET['page']) && is_numeric($_GET['page']) ){
			$page = (int)$_GET['page'];
		}
		$start = $page * $per_page;

		$include_drafts = common::LoggedIn();
		$show_posts = $this->WhichCatPosts( $start, $per_page, $include_drafts);

		$this->ShowPosts($show_posts);


		//pagination links
		echo '<p class="blog_nav_links">';

		if( $page > 0 ){
			$html = SimpleBlogCommon::CategoryLink( $this->catindex, $catname, '%s', 'page='.($page-1), 'class="blog_newer"' );
			echo gpOutput::GetAddonText('Newer Entries',$html);
			echo '&nbsp;';
		}


		if( ( ($page+1) * $per_page) < $this->total_posts ){
			$html = SimpleBlogCommon::CategoryLink( $this->catindex, $catname, '%s', 'page='.($page+1), 'class="blog_older"' );
			echo gpOutput::GetAddonText('Older Entries',$html);
		}



		echo '</p>';
	}



	function WhichCatPosts($start, $len, $include_drafts = false){

		$cat_posts = SimpleBlogCommon::AStrToArray('category_posts_'.$this->catindex);


		//remove drafts
		$show_posts = array();
		if( !$include_drafts ){
			foreach($cat_posts as $post_id){
				if( SimpleBlogCommon::AStrGet('drafts',$post_id) ){
					continue;
				}

				$time = SimpleBlogCommon::AStrGet('post_times',$post_id);
				if( $time > time() ){
					continue;
				}

				$show_posts[] = $post_id;
			}
		}else{
			$show_posts = $cat_posts;
		}
		$this->total_posts = count($show_posts);

		return array_slice($show_posts,$start,$len);
	}


	function ShowCategories(){

		echo '<h2>';
		echo gpOutput::GetAddonText('Categories');
		echo '</h2>';


		echo '<ul>';
		foreach($this->categories as $catindex => $catname){

			//skip hidden categories
			if( SimpleBlogCommon::AStrGet('categories_hidden',$catindex) ){
				continue;
			}

			$cat_posts_str =& SimpleBlogCommon::$data['category_posts_'.$catindex];
			$count = substr_count($cat_posts_str ,'>');

			if( !$count ){
				continue;
			}

			echo '<li>';
			echo SimpleBlogCommon::CategoryLink( $catindex, $catname, $catname.' ('.$count.')' );
			echo '</li>';
		}
		echo '</ul>';

	}

}
