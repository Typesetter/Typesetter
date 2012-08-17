<?php
defined('is_running') or die('Not an entry point...');

require_once('SimpleBlogCommon.php');

class SimpleBlogCategories extends SimpleBlogCommon{
	var $categories;
	var $categories_file;

	function SimpleBlogCategories(){
		$this->load_blog_categories();
		$this->Run();
		SimpleBlogCommon::AddCSS();
	}

	/**
	 *  Print all categories and their contents on gadget
	 *
	 */
	function Run(){

		echo '<div class="simple_blog_gadget"><div>';

		echo '<span class="simple_blog_gadget_label">';
		echo gpOutput::GetAddonText('Categories');
		echo '</span>';

		echo '<ul>';
		foreach( $this->categories as $catdata ){
			if( !$catdata['visible']){
				continue; //skip hidden categories
			}

			echo '<li>';

			$sum = count($catdata['posts']);
			echo '<a class="blog_gadget_link">'.$catdata['ct'].' ('.$sum.')</a>';
			if( $sum ){
				echo '<ul class="nodisplay">';
				foreach($catdata['posts'] as $post_index => $post_title){
					echo '<li>';
					echo common::Link('Special_Blog',$post_title,'id='.$post_index);
					echo '</li>';
				}
				echo '</ul>';
			}
			echo '</li>';
		}
		echo '</ul>';

		echo '</div></div>';
	}

}
