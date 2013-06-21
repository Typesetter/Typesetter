<?php
defined('is_running') or die('Not an entry point...');

//gpPlugin::incl('SimpleBlogCommon.php','require_once');
gpPlugin::incl('SimpleBlog.php','require_once');

class BlogCategories extends SimpleBlog{

	var $categories = array();
	var $catindex = false;
	var $total_posts = 0;

	function __construct(){
		global $page;

		$this->Init();
		$this->categories = SimpleBlogCommon::AStrToArray( 'categories' );

		//show category list
		if( isset($_REQUEST['cat'])	){
			$this->catindex = $_REQUEST['cat'];

		}elseif( strpos($page->requested,'/') !== false ){
			$parts = explode('/',$page->requested);

			if( SimpleBlogCommon::$data['urls'] === 'Full' ){

				$parts[1] = str_replace('_',' ',$parts[1]);
				$this->catindex = array_search($parts[1],$this->categories);

			}elseif( SimpleBlogCommon::$data['urls'] === 'Tiny' ){
				$this->catindex = $parts[1];
			}

		}

		if( $this->catindex && isset($this->categories[$this->catindex]) && !self::AStrValue('categories_hidden',$this->catindex) ){
			$this->ShowCategory();
		}else{
			$this->ShowCategories();
		}

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

		if( ( ($page+1) * $per_page) < $this->total_posts ){

			$html = self::CategoryLink( $this->catindex, $catname, '%s', 'page='.($page+1), 'class="blog_older"' );
			echo gpOutput::GetAddonText('Older Entries',$html);
		}


		if( $page > 0 ){
			$html = self::CategoryLink( $this->catindex, $catname, '%s', 'page='.($page-1), 'class="blog_newer"' );
			echo gpOutput::GetAddonText('Newer Entries',$html);
			echo '&nbsp;';
		}

		echo '</p>';
	}



	function WhichCatPosts($start, $len, $include_drafts = false){

		$cat_posts = self::AStrToArray('category_posts_'.$this->catindex);


		//remove drafts
		$show_posts = array();
		if( !$include_drafts ){
			foreach($cat_posts as $post_id){
				if( !SimpleBlogCommon::AStrValue('drafts',$post_id) ){
					$show_posts[] = $post_id;
				}
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


		//$gadgetFile = $this->addonPathData.'/gadget_categories.php';

		echo '<ul>';
		foreach($this->categories as $catindex => $catname){

			//skip hidden categories
			if( self::AStrValue('categories_hidden',$catindex) ){
				continue;
			}

			$cat_posts_str =& self::$data['category_posts_'.$catindex];
			$count = substr_count($cat_posts_str ,'>');

			if( !$count ){
				continue;
			}

			echo '<li>';
			echo self::CategoryLink( $catindex, $catname, $catname.' ('.$count.')' );
			echo '</li>';
		}
		echo '</ul>';

	}

}
