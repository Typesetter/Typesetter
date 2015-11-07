<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');

class BlogSearch extends SimpleBlogCommon{

	function __construct($args){

		$this->Init();

		$search_obj		= $args[0];
		$blog_label		= common::GetLabelIndex('special_blog');
		$post_ids		= SimpleBlogCommon::AStrToArray('str_index');

		foreach($post_ids as $id){
			$post		= $this->GetPostContent($id);

			if( !$post ){
				continue;
			}

			$title		= $blog_label.': '.str_replace('_',' ',$post['title']);
			$content	= str_replace('_',' ',$post['title']).' '.$post['content'];

			SimpleBlogCommon::UrlQuery( $id, $url, $query );
			$search_obj->FindString($content, $title, $url, $query);
		}

	}
}

