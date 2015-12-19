<?php
defined('is_running') or die('Not an entry point...');

gpPlugin_incl('Admin/Admin.php');

class AdminSimpleBlogCategories extends SimipleBlogAdmin{

	var $itlist = array();
	var $categories;


	function __construct(){
		global $langmessage, $addonRelativeCode, $addonFolderName, $page;

		parent::__construct();

		$this->categories = SimpleBlogCommon::AStrToArray( 'categories' );


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
		}


		$this->Heading('Admin_BlogCategories');


		// print all categories and settings
		echo '<form name="categories" action="'.common::GetUrl('Admin_BlogCategories').'" method="post">';
		echo '<table class="bordered">';
		echo '<tr><th>&nbsp;</th><th>Category</th><th>Number of Posts</th><th>Visible</th><th>Options</th></tr>';

		echo '<tbody class="sortable_table">';
		foreach( $this->categories as $catindex => $catname ){
			echo '<tr><td style="vertical-align:middle">';
			echo '<img src="'.$addonRelativeCode.'/static/grip.png" height="15" width="15" style="padding:2px;cursor:pointer;"/>';
			echo '</td><td>';
			echo '<input type="text" name="cattitle['.$catindex.']" value="'.$catname.'" class="gpinput" />';
			echo '</td><td>';

			$astr =& SimpleBlogCommon::$data['category_posts_'.$catindex];
			echo substr_count($astr,'>');

			echo '</td><td>';

			$checked = '';
			if( !SimpleBlogCommon::AStrGet('categories_hidden',$catindex) ){
				$checked = ' checked="checked"';
			}

			echo ' <input type="checkbox" name="catvis['.$catindex.']"'.$checked.'/> ';
			echo '</td><td>';
			echo common::Link('Admin_BlogCategories',$langmessage['delete'],'cmd=delete_category&index='.$catindex,' name="postlink" class="gpconfirm" title="Delete this Category?" ');
			echo '</td></tr>';
		}
		echo '</tbody>';

		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="save_categories" />';
		echo '<input type="submit" value="'.$langmessage['save_changes'].'" class="gpsubmit"/>';
		echo ' &nbsp; ';
		echo common::Link('Admin_BlogCategories','Add New Category','cmd=new_category',' name="gpabox" ');
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
	}



	/**
	 * Save changes to the list of existing categories
	 *
	 */
	function SaveCategories(){
		global $langmessage;

		$_POST += array('cattitle'=>array(),'catvis'=>array());

		//category titles
		$categories_hidden = array();
		$this->categories = array();
		foreach($_POST['cattitle'] as $key => $title){
			$this->categories[$key] = htmlspecialchars($title);
			$categories_hidden[$key] = 1;
		}

		//visibility
		foreach($_POST['catvis'] as $key => $title){
			unset($categories_hidden[$key]);
		}


		SimpleBlogCommon::$data['categories'] = SimpleBlogCommon::AStrFromArray($this->categories);
		SimpleBlogCommon::$data['categories_hidden'] = SimpleBlogCommon::AStrFromArray($categories_hidden);

		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		SimpleBlogCommon::GenStaticContent();
		message($langmessage['SAVED']);
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

		$new_catindex = $this->NewCatIndex();

		$this->categories[$new_catindex] = $new_title;

		SimpleBlogCommon::$data['categories'] = SimpleBlogCommon::AStrFromArray($this->categories);

		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		SimpleBlogCommon::GenStaticContent();
		message($langmessage['SAVED']);
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
		unset(SimpleBlogCommon::$data['category_posts_'.$index]);
		SimpleBlogCommon::AStrRm('categories_hidden',$index);

		SimpleBlogCommon::$data['categories'] = SimpleBlogCommon::AStrFromArray($this->categories);

		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		SimpleBlogCommon::GenStaticContent();
		message($langmessage['SAVED']);
	}

}

