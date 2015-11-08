<?php
defined('is_running') or die('Not an entry point...');

class StaticGenerator{

	static $months		= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	/**
	 * Regenerate all of the static content: gadget and feed
	 *
	 */
	static function Generate(){
		self::GenFeed();
		self::GenGadget();
		self::GenCategoryGadget();
		self::GenArchiveGadget();
	}


	/**
	 * Regenerate the atom.feed file
	 *
	 */
	static function GenFeed(){
		global $config, $addonFolderName, $dirPrefix;
		ob_start();

		$atomFormat = 'Y-m-d\TH:i:s\Z';
		if( version_compare('phpversion', '5.1.3', '>=') ){
			$atomFormat = 'Y-m-d\TH:i:sP';
		}

		$posts = array();
		$show_posts = SimpleBlogCommon::WhichPosts(0,SimpleBlogCommon::$data['feed_entries']);


		if( isset($_SERVER['HTTP_HOST']) ){
			$server = 'http://'.$_SERVER['HTTP_HOST'];
		}else{
			$server = 'http://'.$_SERVER['SERVER_NAME'];
		}
		$serverWithDir = $server.$dirPrefix;


		echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
		echo '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";
		echo '<title>'.$config['title'].'</title>'."\n";
		echo '<link href="'.$serverWithDir.'/data/_addondata/'.str_replace(' ', '%20',$addonFolderName).'/feed.atom" rel="self" />'."\n";
		echo '<link href="'.$server.common::GetUrl('Special_Blog').'" />'."\n";
		echo '<id>urn:uuid:'.self::uuid($serverWithDir).'</id>'."\n";
		echo '<updated>'.date($atomFormat, time()).'</updated>'."\n";
		echo '<author><name>'.$config['title'].'</name></author>'."\n";


		foreach($show_posts as $post_index){

			$post = SimpleBlogCommon::GetPostContent($post_index);

			if( !$post ){
				continue;
			}

			echo '<entry>'."\n";
			echo '<title>'.SimpleBlogCommon::Underscores( $post['title'] ).'</title>'."\n";
			echo '<link href="'.$server.SimpleBlogCommon::PostUrl($post_index).'"></link>'."\n";
			echo '<id>urn:uuid:'.self::uuid($post_index).'</id>'."\n";
			echo '<updated>'.date($atomFormat, $post['time']).'</updated>'."\n";

			$content =& $post['content'];
			if( (SimpleBlogCommon::$data['feed_abbrev']> 0) && (mb_strlen($content) > SimpleBlogCommon::$data['feed_abbrev']) ){
				$content = mb_substr($content,0,SimpleBlogCommon::$data['feed_abbrev']).' ... ';
				$label = gpOutput::SelectText('Read More');
				$content .= '<a href="'.$server.SimpleBlogCommon::PostUrl($post_index,$label).'">'.$label.'</a>';
			}

			//old images
			$replacement = $server.'/';
			$content = str_replace('src="/', 'src="'.$replacement,$content);

			//new images
			$content = str_replace('src="../', 'src="'.$serverWithDir,$content);

			//images without /index.php/
			$content = str_replace('src="./', 'src="'.$serverWithDir,$content);


			//href
			SimpleBlogCommon::FixLinks($content,$server,0);

			echo '<summary type="html"><![CDATA['.$content.']]></summary>'."\n";
			echo '</entry>'."\n";

		}
		echo '</feed>'."\n";

		$feed = ob_get_clean();
		$feedFile = SimpleBlogCommon::$data_dir.'/feed.atom';
		gpFiles::Save($feedFile,$feed);
	}




	/**
	 * Regenerate the static content used to display the gadget
	 *
	 */
	static function GenGadget(){
		global $langmessage;

		$posts = array();
		$show_posts = SimpleBlogCommon::WhichPosts(0,SimpleBlogCommon::$data['gadget_entries']);


		ob_start();
		$label = gpOutput::SelectText('Blog');
		if( !empty($label) ){
			echo '<h3>';
			echo common::Link('Special_Blog',$label);
			echo '</h3>';
		}

		foreach($show_posts as $post_index){

			$post		= SimpleBlogCommon::GetPostContent($post_index);

			if( !$post ){
				continue;
			}

			$header		= '<b class="simple_blog_title">';
			$label		= SimpleBlogCommon::Underscores( $post['title'] );
			$header		.= SimpleBlogCommon::PostLink($post_index,$label);
			$header		.= '</b>';

			SimpleBlogCommon::BlogHead($header,$post_index,$post,true);


			$content = strip_tags($post['content']);

			if( SimpleBlogCommon::$data['gadget_abbrev'] > 6 && (mb_strlen($content) > SimpleBlogCommon::$data['gadget_abbrev']) ){

				$cut = SimpleBlogCommon::$data['gadget_abbrev'];

				$pos = mb_strpos($content,' ',$cut-5);
				if( ($pos > 0) && ($cut+20 > $pos) ){
					$cut = $pos;
				}
				$content = mb_substr($content,0,$cut).' ... ';

				$label = gpOutput::SelectText('Read More');
				$content .= SimpleBlogCommon::PostLink($post_index,$label);
			}

			echo '<p class="simple_blog_abbrev">';
			echo $content;
			echo '</p>';

		}

		if( SimpleBlogCommon::$data['post_count'] > 3 ){

			$label = gpOutput::SelectText('More Blog Entries');
			echo common::Link('Special_Blog',$label);
		}

		$gadget = ob_get_clean();
		$gadgetFile = SimpleBlogCommon::$data_dir.'/gadget.php';
		gpFiles::Save($gadgetFile,$gadget);
	}


	/**
	 * Regenerate the static content used to display the category gadget
	 *
	 */
	static function GenCategoryGadget(){
		global $addonPathData;

		$categories = SimpleBlogCommon::AStrToArray( 'categories' );

		ob_start();
		echo '<ul>';
		foreach($categories as $catindex => $catname){

			//skip hidden categories
			if( SimpleBlogCommon::AStrValue('categories_hidden',$catindex) ){
				continue;
			}

			$posts = SimpleBlogCommon::AStrToArray('category_posts_'.$catindex);
			$sum = count($posts);
			if( !$sum ){
				continue;
			}

			echo '<li>';
			echo '<a class="blog_gadget_link">'.$catname.' ('.$sum.')</a>';
			echo '<ul class="nodisplay">';
			foreach($posts as $post_id){
				$post_title = SimpleBlogCommon::AStrValue('titles',$post_id);
				echo '<li>';
				echo SimpleBlogCommon::PostLink( $post_id, $post_title );
				echo '</li>';
			}
			echo '</ul></li>';
		}
		echo '</ul>';

		$content = ob_get_clean();


		$gadgetFile = $addonPathData.'/gadget_categories.php';
		gpFiles::Save( $gadgetFile, $content );
	}


	/**
	 * Regenerate the static content used to display the archive gadget
	 *
	 */
	static function GenArchiveGadget(){
		global $addonPathData;

		//get list of posts and times
		$list = SimpleBlogCommon::AStrToArray( 'post_times' );
		if( !count($list) ) return;

		//get year counts
		$archive = array();
		foreach($list as $post_id => $time){
			$ym = date('Ym',$time); //year&month
			$archive[$ym][] = $post_id;
		}


		ob_start();

		$prev_year = false;
		echo '<ul>';
		foreach( $archive as $ym => $posts ){
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
			echo '<li><a class="blog_gadget_link">'.self::$months[$m-1].' ('.$sum.')</a>';
			echo '<ul class="simple_blog_category_posts nodisplay">';
			foreach($posts as $post_id ){
				$post_title = SimpleBlogCommon::AStrValue('titles',$post_id);
				echo '<li>';
				echo SimpleBlogCommon::PostLink($post_id, $post_title );
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
			echo '</ul>';
		}

		echo '</li></ul>';

		$content = ob_get_clean();

		$gadgetFile = $addonPathData.'/gadget_archive.php';
		gpFiles::Save( $gadgetFile, $content );
	}

	static function uuid($str){
		$chars = md5($str);
		return mb_substr($chars,0,8)
				.'-'. mb_substr($chars,8,4)
				.'-'. mb_substr($chars,12,4)
				.'-'. mb_substr($chars,16,4)
				.'-'. mb_substr($chars,20,12);
		return $uuid;
	}

}
