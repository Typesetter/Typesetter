<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class AdminSimpleBlogCategories  extends SimpleBlogCommon{

	var $itlist = array();

	/**
	 * Set object variables used to manage categories
	 *
	 */
	function Init(){

		// load existing categories
		$this->load_blog_categories();
	}

	function AdminSimpleBlogCategories(){
		global $langmessage;

		$this->Init();

		$cmd = common::GetCommand();
		switch($cmd){

			//category commands
			case 'save_categories':
				$this->SaveCategories();
			break;
			case 'new_category':
				$this->NewCategory();
			return;
			case 'save_new_category':
				$this->SaveNewCategory();
			break;
			case 'delete_category':
				$this->DeleteCategory();
			break;


			//archive commands
			case 'fill_archive':
				$this->FillArchive();
			break;
			case 'clear_archive':
				$this->ClearArchive();
			break;
		}



		/*
		if( isset($_POST['save_posts']) ){
			foreach( $this->categories as $catindex => $catdata){
				$clean = array( 'ct'=>$catdata['ct'], 'visible'=>$catdata['visible']);
				$this->categories[$catindex] = $clean; //clean categories
			}
			foreach( $this->itlist as $postindex => $postdata){
				if( isset($_POST['post'.$postindex]) ){
					foreach ($_POST['post'.$postindex] as $catindex){
						$this->categories[$catindex][$postindex] = $postdata['title'];
					}
				}
			}
			gpFiles::SaveArray($this->categories_file,'categories',$this->categories); //save
		}
		*/

		$label = gpOutput::SelectText('Blog');
		echo '<h2>';
		echo common::Link('Special_Blog',$label);
		echo ' &#187; ';
		echo common::Link('Admin_Blog','Configuration');
		echo ' &#187; ';
		echo 'Categories</h2>';

		// print all categories and settings
		//echo '<h3>Existing Categories (empty field removes category)</h3>';

		echo '<form name="categories" action="'.common::GetUrl('Admin_BlogCategories').'" method="post">';
		echo '<table class="bordered">';
		echo '<tr><th>Category</th><th>Number of Posts</th><th>Visible</th><th>Options</th></tr>';
		foreach( $this->categories as $catindex => $catdata ){
			echo '<tr><td>';
			echo '<input type="text" name="cattitle'.$catindex.'" value="'.$catdata['ct'].'" class="gpinput" />';
			echo '</td><td>';
			echo count($catdata['posts']);
			echo '</td><td>';
			echo ' <input type="checkbox" name="catvis'.$catindex.'"'.($catdata['visible']?' checked="checked"':'').' /> ';
			echo '</td><td>';
			echo common::Link('Admin_BlogCategories',$langmessage['delete'],'cmd=delete_category&index='.$catindex,' name="postlink" class="gpconfirm" title="Delete this Category?" ');
			echo '</td></tr>';
		}
		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="save_categories" />';
		echo '<input type="submit" value="'.$langmessage['save_changes'].'" class="gpsubmit"/>';
		echo ' &nbsp; ';
		echo common::Link('Admin_BlogCategories','Add New Category','cmd=new_category',' name="admin_box" ');
		echo '</p>';
		echo '</form>';

		// print all posts
		/*
		if( count($this->itlist) ){
			echo '<h3 onclick="$(this).next(\'form\').toggle()" style="cursor:pointer">All Blog Posts</h3>';
			echo '<form name="allposts" action="'.common::GetUrl('Admin_BlogCategories').'" method="post" style="display:none">';
			echo '<table style="width:100%">';
			foreach( $this->itlist as $postindex => $postdata ){
				echo '<tr><td>'.$postdata['title'].' ';
				echo common::Link('Special_Blog','&#187;','id='.$postindex,'target="_blank"').'</td><td>';
				echo '<select id="post'.$postindex.'" name="post'.$postindex.'[]" multiple="multiple" class="gpselect">';
				foreach( $this->categories as $catindex => $catdata){
					echo '<option value="'.$catindex.'" '.(isset($catdata[$postindex])? 'selected="selected"':'').'>'.$catdata['ct'].'</option>';
				}
				echo '</select>';
				echo '</td></tr>';
			}
			echo '</table>';
			echo '<input name="save_posts" type="submit" value="'.$langmessage['save'].'" class="gpsubmit" />';
			echo '</form>';
		}
		*/

		// archives
		echo '<br/><h2>Archives</h2>';
		echo common::Link('Admin_BlogCategories','Fill archive with all posts','cmd=fill_archive',' name="cnreq"').'<br/>';
		echo common::Link('Admin_BlogCategories','Clear all posts from archive','cmd=clear_archive',' name="cnreq"');
	}

	/**
	 * Regenerate the blog's archive
	 *
	 */
	function FillArchive(){
		global $langmessage;

		$this->create_itlist();
		$this->load_blog_archives(); //only for setting the 'archives_file' path variable
		$this->archives = array();
		//$test = array(5, 'c'=>100, 10, 15, 20); $this->arrayReverse($test); var_export($test);

		$this->arrayReverse($this->itlist); //latest years first
		foreach( $this->itlist as $postindex => $postdata){
			$ym = date('Ym',$postdata['time']); //year&month
			$this->archives[$ym][$postindex] = $postdata['title'];
		}
		krsort($this->archives);
		foreach( $this->archives as $ym=>$posts ){
			krsort($this->archives[$ym]);
		}

		//save
		if( gpFiles::SaveArray($this->archives_file,'archives',$this->archives) ){
			message($langmessage['SAVED']);
		}
	}

	/**
	 * Empty the blog's archive
	 *
	 */
	function ClearArchive(){
		global $langmessage;
		$this->load_blog_archives(); //only for setting the 'archives_file' path variable
		$this->archives = array();
		if( gpFiles::SaveArray($this->archives_file,'archives',$this->archives) ){
			message($langmessage['SAVED']);
		}
	}


	/**
	 * Save changes to the list of existing categories
	 *
	 */
	function SaveCategories(){

		foreach( $this->categories as $catindex => $catdata ){
			if( isset($_POST['cattitle'.$catindex]) ){
				$this->categories[$catindex]['visible'] = isset($_POST['catvis'.$catindex]);
				$title = trim($_POST['cattitle'.$catindex]);
				if( !empty($title) ){
					$this->categories[$catindex]['ct'] = htmlspecialchars($title);
				}
			}
		}
		return gpFiles::SaveArray($this->categories_file,'categories',$this->categories); //save
	}


	/**
	 * Add a new category to the configuration
	 *
	 */
	function SaveNewCategory(){
		global $langmessage;

		//find free index
		$new_catindex = $this->NewCatIndex();

		$new_title = htmlspecialchars(trim($_POST['new_category']));
		if( empty($new_title) ){
			message($langmessage['OOPS'].' (Empty category title)');
			return false;
		}

		$this->categories[$new_catindex] = array();
		$this->categories[$new_catindex]['ct'] = $new_title;
		$this->categories[$new_catindex]['visible'] = true;
		$this->categories[$new_catindex]['posts'] = array();

		return gpFiles::SaveArray($this->categories_file,'categories',$this->categories);
	}

	/**
	 * Prompt user to create a new category
	 *
	 */
	function NewCategory(){
		global $langmessage;
		echo '<div class="inline_box">';
		echo '<h3>Add New Category</h3>';
		echo '<form name="addcategory" action="'.common::GetUrl('Admin_BlogCategories').'" method="post">';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="save_new_category" />';
		echo 'Title: <input type="text" name="new_category" value="" class="gpinput" />';
		echo '</p>';

		echo '<p>';
		echo ' <input type="submit" value="'.$langmessage['save'].'" class="gppost gpsubmit"/>';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel"/>';
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}


	/**
	 * Return a new non-numeric index
	 *
	 */
	function NewCatIndex(){
		$num_index = count($this->categories);
		do{
			$index = base_convert($num_index,10,36);
			$num_index++;
		}while( ctype_digit($index) || isset($this->categories[$index]) );

		return $index;
	}


	/**
	 * Remove a category
	 *
	 */
	function DeleteCategory(){
		global $langmessage;

		if( !isset($_POST['index']) ){
			message($langmessage['OOPS'].' (Invalid Index)');
			return false;
		}

		$index = $_POST['index'];
		if( !isset($this->categories[$index]) ){
			message($langmessage['OOPS'].' (Invalid Index)');
			return false;
		}

		unset($this->categories[$index]);
		return gpFiles::SaveArray($this->categories_file,'categories',$this->categories);
	}

	/**
	 * Creates the list of all posts (get all post indexes and titles and times)
	 *
	 */
	function create_itlist(){
		global $addonPathData;
		$file_index=0;
		while( file_exists($post_file = $addonPathData.'/posts_'.$file_index.'.php') ){
			//echo $post_file; //like in function GetPostFile() in SimpleBlogCommon.php
			include($post_file);
			foreach( $posts as $postindex => $postdata ){
				$this->itlist[$postindex]['title'] = $postdata['title'];
				$this->itlist[$postindex]['time'] = $postdata['time']; // this is useful for blog archives list
			}
			$file_index++;
		}
	}

	function arrayReverse(&$arr)
	{
		if (!is_array($arr) || empty($arr))
			return;
		$rev = array();
		while ( false !== ( $val=end($arr) ) ){
			$rev[key($arr)] = $val;
			unset( $arr[ key($arr) ] );
		}
		$arr = $rev;
	}

}

