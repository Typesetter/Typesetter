<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class BlogSearch extends SimpleBlogCommon{

	function BlogSearch($args){
		global $addonPathData;

		$this->Init();

		$search_obj		= $args[0];
		$label			= common::GetLabelIndex('special_blog');
		$full_path		= $addonPathData.'/index.php';				// config of installed addon to get to know how many post files are

		if( !file_exists($full_path) ){
			return;
		}

		require($full_path);
		$fileIndexMax = floor($blogData['post_index']/20); // '20' I found in SimpleBlogCommon.php function GetPostFile (line 62)

		for ($fileIndex = 0; $fileIndex <= $fileIndexMax; $fileIndex++) {
			$postFile = $addonPathData.'/posts_'.$fileIndex.'.php';
			if( !file_exists($postFile) ){
				continue;
			}
			require($postFile);

			foreach($posts as $id => $post){
				$title = $label.': '.str_replace('_',' ',$post['title']);
				$content = str_replace('_',' ',$post['title']).' '.$post['content'];

				SimpleBlogCommon::UrlQuery( $id, $url, $query );
				$search_obj->FindString($content, $title, $url, $query);
			}
			$posts = array();
		}
	}
}

