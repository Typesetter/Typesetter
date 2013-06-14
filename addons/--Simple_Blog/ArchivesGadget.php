<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class SimpleBlogArchives extends SimpleBlogCommon{

	function SimpleBlogArchives(){
		$this->Init();
		$this->load_blog_archives();
		$this->Run();
		SimpleBlogCommon::AddCSS();
	}

	/**
	 *  Print all archives and their contents on gadget
	 *
	 */
	function Run(){

		if( !count($this->archives) ) return;

		echo '<div class="simple_blog_gadget"><div>';

		echo '<span class="simple_blog_gadget_label">';
		echo gpOutput::GetAddonText('Archives');
		echo '</span>';


		$prev_year = false;
		echo '<ul>';
		foreach( $this->archives as $ym => $posts ){
			$y = floor($ym/100);
			$m = $ym%100;
			if( $y != $prev_year ){
				if( $prev_year !== false ){
					echo '</li>';
				}
				echo '<li><div class="simple_blog_gadget_year">'.$y.'</div>';
				$prev_year = $y;
			}
			$sum = count($posts);
			if( !$sum ){
				continue;
			}

			echo '<ul>';
			echo '<li><a class="blog_gadget_link">'.$this->months[$m-1].' ('.$sum.')</a>';
			echo '<ul class="simple_blog_category_posts nodisplay">';
			foreach($posts as $post_index => $post_title){
				echo '<li>';
				echo $this->PostLink($post_index,$post_title);
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
			echo '</ul>';
		}

		echo '</li></ul>';
		echo '</div></div>';
	}

}
