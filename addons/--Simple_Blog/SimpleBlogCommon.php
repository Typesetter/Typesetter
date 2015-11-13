<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/recaptcha.php');

/**
 * To Do
 *
 * Comment approval
 * Clean Category Storage
 *
 *
 */

class SimpleBlogCommon{

	static $index_file;
	static $data		= false;
	static $root_url	= 'Special_Blog';

	var $new_install	= false;
	var $addonPathData;
	var $post_id		= false;

	static $data_dir;



	/**
	 * When SimpleBlogCommon is created as an object, it will regenerate the static files
	 *
	 */
	function __construct(){
		self::Init();
		self::GenStaticContent();
	}

	/**
	 * Set variables for blog display
	 *
	 */
	static function Init(){
		global $addonPathData;

		if( self::$data ){
			return;
		}

		self::$data_dir			= $addonPathData;
		self::$index_file		= self::$data_dir.'/index.php';


		self::$root_url = 'Special_Blog';
		if( is_callable( array('common','SpecialHref') ) ){
			self::$root_url = common::SpecialHref('Special_Blog');
		}


		self::GetBlogData();
		self::AddCSS();


		//regenerate if there are pending posts that need to be published
		if( SimpleBlogCommon::$data['next_regen'] < time()  ){
			if( @gpFiles::WriteLock() ){
				self::GenStaticContent();
				SimpleBlogCommon::NextGenTime();
				SimpleBlogCommon::SaveIndex();
				gpFiles::Unlock('write',gp_random);
			}
		}
	}

	static function GenStaticContent(){
		gpPlugin::incl('Admin/StaticGenerator.php','require_once');
		StaticGenerator::Generate();

	}

	/**
	 * Get next static gen time
	 *
	 */
	static function NextGenTime(){

		$post_times			= SimpleBlogCommon::AStrToArray('post_times');
		arsort($post_times);


		asort($post_times);
		$next_regen = false;
		foreach($post_times as $time){
			if( $time > time() ){
				$next_regen = $time;
				break;
			}
		}
		SimpleBlogCommon::$data['next_regen'] = $next_regen;

	}



	/**
	 * Add css and some js to the page
	 *
	 */
	static function AddCSS(){
		global $addonFolderName,$page;

		static $added			= false;

		if( !$added ){
			$page->jQueryCode	.= '$(".blog_gadget_link").click(function(){ $(this).next(".nodisplay").toggle(); });';
			$page->css_user[]	= '/data/_addoncode/'.$addonFolderName.'/static/style.css';
			$added				= true;
		}
	}


	/**
	 * Get the user configuration and information about the current blog
	 *
	 */
	function GetBlogData(){

		$blogData = array();
		if( file_exists(self::$index_file) ){
			require(self::$index_file);
		}

		//old twitter auth no longer works
		if( isset($blogData['twitter_username']) || isset($blogData['twitter_password']) ){
			unset($blogData['twitter_username']);
			unset($blogData['twitter_password']);
		}

		SimpleBlogCommon::$data = $blogData + SimpleBlogCommon::Defaults();
		self::GenIndexStr();

		//update to simple blog 2.0 data
		if( isset(SimpleBlogCommon::$data['post_info']) ){
			self::DataUpdate20();
		}
	}


	/**
	 * Generate a string to use as the post index
	 * Using a string of numbers can use 1/4 of the memory of an array
	 *
	 * As of 2.0, Uses " and > instead , and :
	 */
	static function GenIndexStr(){

		if( !empty(SimpleBlogCommon::$data['str_index']) ){
			if( SimpleBlogCommon::$data['str_index'][0] == ',' ){
				SimpleBlogCommon::$data['str_index'] = str_replace( array(',',':'), array('"','>'), SimpleBlogCommon::$data['str_index']);
			}
			return;
		}
		if( !isset(SimpleBlogCommon::$data['post_list']) ){
			return;
		}

		SimpleBlogCommon::$data['str_index'] = SimpleBlogCommon::AStrFromArray(SimpleBlogCommon::$data['post_list']);
	}


	/**
	 * Serialize comment counts
	 *
	 */
	static function DataUpdate20(){

		$comment_counts	= array();
		$comments_closed = array();
		if( isset(SimpleBlogCommon::$data['post_info']) && is_array(SimpleBlogCommon::$data['post_info']) ){
			foreach(SimpleBlogCommon::$data['post_info'] as $post_id => $info){
				if( isset($info['comments']) ){
					$comment_counts[$post_id] = $info['comments'];
				}

				if( isset($info['closecomments']) ){
					$comments_closed[$post_id] = 1;
				}
			}
		}


		SimpleBlogCommon::$data['comment_counts'] = SimpleBlogCommon::AStrFromArray($comment_counts);
		SimpleBlogCommon::$data['comments_closed'] = SimpleBlogCommon::AStrFromArray($comments_closed);


		//use AStr data for categories
		$categories = $categories_hidden = $category_posts = $post_categories = array();
		if( !isset(SimpleBlogCommon::$data['categories']) ){
			$old_categories = self::load_blog_categories();
			foreach($old_categories as $key => $cat){
				$cat['ct'] = htmlspecialchars($cat['ct'],ENT_COMPAT,'UTF-8',false);
				$categories[$key] = $cat['ct'];
				if( isset($cat['visible']) && !$cat['visible'] ){
					$categories_hidden[$key] = 1;
				}

				if( isset($cat['posts']) && is_array($cat['posts']) ){
					$category_posts[$key] = array();
					foreach($cat['posts'] as $post => $title){
						$category_posts[$key][] = $post;
						$post_categories[$post][] = $key;
					}
				}
			}
		}


		//post data
		$drafts = array();
		$titles = array();
		$post_times = array();

		$i = 0;
		do{

			$posts = self::GetPostFile($i,$post_file);
			if( !$posts ){
				break;
			}

			foreach($posts as $post_id => $post){

				if( isset($post['isDraft']) && $post['isDraft'] ){
					$drafts[$post_id] = 1;
				}
				$titles[$post_id] = $post['title'];
				$post_times[$post_id] = $post['time'];

				$posts[$post_id]['categories'] = array();
				if( isset($post_categories[$post_id]) ){
					$posts[$post_id]['categories'] = $post_categories[$post_id];
				}
			}

			gpFiles::SaveArray($post_file,'posts',$posts);

			$i +=20 ;
		}while( $posts );


		//convert arrays to astr
		SimpleBlogCommon::$data['drafts'] = SimpleBlogCommon::AStrFromArray($drafts);
		SimpleBlogCommon::$data['titles'] = SimpleBlogCommon::AStrFromArray($titles);
		SimpleBlogCommon::$data['post_times'] = SimpleBlogCommon::AStrFromArray($post_times);

		SimpleBlogCommon::$data['categories'] = SimpleBlogCommon::AStrFromArray($categories);
		SimpleBlogCommon::$data['categories_hidden'] = SimpleBlogCommon::AStrFromArray($categories_hidden);
		foreach($category_posts as $key => $posts){
			SimpleBlogCommon::$data['category_posts_'.$key] = SimpleBlogCommon::AStrFromArray($posts);
		}


		//generate static content
		self::GenStaticContent();


		unset(SimpleBlogCommon::$data['post_info']);
		unset(SimpleBlogCommon::$data['post_list']);


		SimpleBlogCommon::SaveIndex();
	}



	/**
	 * Get a list of post indeces
	 *
	 */
	static function WhichPosts($start, $len, $include_drafts = false){

		$posts		= array();
		$end		= $start+$len;
		for($i = $start; $i < $end; $i++){

			//get post id
			$post_id = SimpleBlogCommon::AStrValue('str_index',$i);
			if( !$post_id ){
				continue;
			}

			if( !$include_drafts ){
				//exclude drafts
				if( SimpleBlogCommon::AStrValue('drafts',$post_id) ){
					continue;
				}

				//exclude future posts
				$time = SimpleBlogCommon::AStrValue('post_times',$post_id);
				if( $time > time() ){
					continue;
				}
			}


			$posts[] = $post_id;
		}
		return $posts;
	}



	/**
	 * Return the configuration defaults
	 * @static
	 */
	function Defaults(){
		global $config;

		$zero_strip = stristr(PHP_OS,'win') ? '' : '-';

		return array(	'post_index'			=> 0,
						'per_page'				=> 10,
						'post_abbrev'			=> '',
						'gadget_entries'		=> 3,
						'gadget_abbrev'			=> 90,
						'feed_entries'			=> 10,
						'feed_abbrev'			=> 1200,
						'feed_author'			=> $config['title'],
						'date_format'			=> 'n/j/Y',
						'strftime_format'		=> '%'.$zero_strip.'m/%'.$zero_strip.'e/%Y',
						'allow_comments'		=> false,
						'commenter_website'		=> '',
						'comment_captcha'		=> true,
						'subtitle_separator'	=> ' <span class="space"> | </span> ',
						'post_count'			=> 0,
						'str_index'				=> '',
						'urls'					=> 'Default',
						'drafts'				=> '',
						'email_comments'		=> '',
						'abbrev_image'			=> true,
						);

	}


	/**
	 * Save the blog configuration and details about the blog
	 *
	 */
	static function SaveIndex(){

		SimpleBlogCommon::$data['str_index'] = '"'.trim(SimpleBlogCommon::$data['str_index'],'"').'"';
		SimpleBlogCommon::$data['post_count'] = substr_count(SimpleBlogCommon::$data['str_index'],'>');

		return gpFiles::SaveArray(self::$index_file,'blogData',SimpleBlogCommon::$data);
	}


	/**
	 * Get the data file for a blog post
	 * Return the data if it exists
	 * 20 posts per file... if someone posts a lot (once a day for a year that would be 18 files)
	 * @deprecated 3.0
	 *
	 */
	static function GetPostFile($post_index,&$post_file){

		if( !is_numeric($post_index) ){
			return false;
		}

		$file_index		= floor($post_index/20);
		$post_file		= self::$data_dir.'/posts_'.$file_index.'.php';

		if( !file_exists($post_file) ){
			return false;
		}

		require($post_file);
		if( !is_array($posts) ){
			return false;
		}

		return $posts;
	}


	/**
	 * Get the content from a single post
	 *
	 */
	static function GetPostContent($post_index){

		$file		= self::PostFilePath($post_index);
		if( file_exists($file) ){
			require($file);
			return $post;
		}

		$posts = self::GetPostFile($post_index,$post_file);
		if( $posts === false ){
			return false;
		}

		if( !isset($posts[$post_index]) ){
			return false;
		}
		return $posts[$post_index];
	}


	/**
	 * Return the file path of the post
	 * @since 3.0
	 *
	 */
	static function PostFilePath($post_index){
		return self::$data_dir.'/posts/'.$post_index.'.php'; //3.0+
	}


	/**
	 * Delete a blog post
	 * @return bool
	 *
	 */
	static function Delete(){
		global $langmessage;

		$post_id		= $_POST['del_id'];
		$posts			= false;


		//post in single file or collection
		$post_file		= self::PostFilePath($post_id);
		if( !file_exists($post_file) ){

			$posts		= self::GetPostFile($post_id,$post_file);
			if( $posts === false ){
				message($langmessage['OOPS']);
				return false;
			}

			if( !isset($posts[$post_id]) ){
				message($langmessage['OOPS']);
				return false;
			}

			unset($posts[$post_id]); //don't use array_splice here because it will reset the numeric keys
		}

		//now delete post also from categories:
		self::DeletePostFromCategories($post_id);


		//reset the index string
		$new_index = SimpleBlogCommon::$data['str_index'];
		$new_index = preg_replace('#"\d+>'.$post_id.'"#', '"', $new_index);
		preg_match_all('#(?:"\d+>)([^">]*)#',$new_index,$matches);
		SimpleBlogCommon::$data['str_index'] = SimpleBlogCommon::AStrFromArray($matches[1]);


		//remove post from other index strings
		SimpleBlogCommon::AStrRm('drafts',$post_id);
		SimpleBlogCommon::AStrRm('comments_closed',$post_id);
		SimpleBlogCommon::AStrRm('titles',$post_id);
		SimpleBlogCommon::AStrRm('post_times',$post_id);



		if( !SimpleBlogCommon::SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		//save data file or remove the file
		if( $posts ){
			if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
				message($langmessage['OOPS']);
				return false;
			}
		}elseif( !unlink($post_file) ){
			message($langmessage['OOPS']);
			return false;
		}


		//delete the comments
		$commentDataFile = self::$data_dir.'/comments/'.$post_id.'.txt';
		if( file_exists($commentDataFile) ){
			unlink($commentDataFile);
			SimpleBlogCommon::ClearCommentCache();
		}


		SimpleBlogCommon::GenStaticContent();
		message($langmessage['file_deleted']);

		return true;
	}




	/**
	 * Recursively turn relative links into absolute links
	 * @static
	 */
	function FixLinks(&$content,$server,$offset){

		$pos = mb_strpos($content,'href="',$offset);
		if( $pos <= 0 ){
			return;
		}
		$pos = $pos+6;

		$pos2 = mb_strpos($content,'"',$pos);

		if( $pos2 <= 0 ){
			return;
		}

		//well formed link
		$check = mb_strpos($content,'>',$pos);
		if( ($check !== false) && ($check < $pos2) ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}

		$title = mb_substr($content,$pos,$pos2-$pos);

		//internal link
		if( mb_strpos($title,'mailto:') !== false ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}
		if( mb_strpos($title,'://') !== false ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}

		if( mb_strpos($title,'/') === 0 ){
			$replacement = $server.$title;
		}else{
			$replacement = $server.common::GetUrl($title);
		}

		$content = mb_substr_replace($content,$replacement,$pos,$pos2-$pos);

		SimpleBlogCommon::FixLinks($content,$server,$pos2);
	}




	/**
	 * Potential method for allowing users to format the header area of their blog
	 * However, this would make it more difficult for theme developers to design for the blog plugin
	 *
	 */
	static function BlogHead($header,$post_index,$post,$cacheable=false){


		//subtitle
		$blog_info = '{empty_blog_piece}';
		if( !empty($post['subtitle']) ){
			$blog_info = '<span class="simple_blog_subtitle">';
			$blog_info .= $post['subtitle'];
			$blog_info .= '</span>';
		}

		//blog date
		$blog_date = '<span class="simple_blog_date">';
		$blog_date .= strftime(SimpleBlogCommon::$data['strftime_format'],$post['time']);
		$blog_date .= '</span>';


		//blog comments
		$blog_comments = '{empty_blog_piece}';
		$count = SimpleBlogCommon::AStrValue('comment_counts',$post_index);
		if( $count > 0 ){
			$blog_comments = '<span class="simple_blog_comments">';
			if( $cacheable ){
				$blog_comments .= gpOutput::SelectText('Comments');
			}else{
				$blog_comments .= gpOutput::GetAddonText('Comments');
			}
			$blog_comments .= ': '.$count;
			$blog_comments .= '</span>';
		}



		// format content
		$format = '{header} <div class="simple_blog_info"> {blog_info} {separator} {blog_date} {separator} {blog_comments} </div>';
		$search = array('{header}', '{blog_info}', '{blog_date}', '{blog_comments}');
		$replace = array($header, $blog_info, $blog_date, $blog_comments);

		$result = str_replace($search,$replace,$format);


		$reg = '#\{empty_blog_piece\}(\s*)\{separator\}#';
		$result = preg_replace($reg,'\1',$result);

		$reg = '#\{separator\}(\s*){empty_blog_piece\}#';
		$result = preg_replace($reg,'\1',$result);

		echo str_replace('{separator}',SimpleBlogCommon::$data['subtitle_separator'],$result);
	}



	/**
	 *  B L O G    C A T E G O R Y     F U N C T I O N S
	 *
	 */


	/**
	 * Get all the categories in use by the blog
	 * @deprecated 2.0
	 *
	 */
	static function load_blog_categories(){
		global $addonPathData;
		$categories_file = $addonPathData.'/categories.php';

		$categories = array( 'a' => array( 'ct'=>'Unsorted posts', 'visible'=>false,'posts'=>array() ) );
		if( file_exists($categories_file) ){
			include($categories_file);
		}
		return $categories;
	}


	/**
	 * Remove a blog entry from a category
	 *
	 */
	static function DeletePostFromCategories( $post_id ){

		$categories = SimpleBlogCommon::AStrToArray( 'categories' );
		foreach($categories as $catindex => $catname){
			SimpleBlogCommon::AStrRmValue( 'category_posts_'.$catindex, $post_id );
		}
	}


	function Underscores($str){
		if( function_exists('mb_ereg_replace') ){
			return mb_ereg_replace('_', ' ', $str);
		}
		return str_replace('_', ' ', $str);
	}

	static function FileData($file){

		if( !file_exists($file) ){
			return false;
		}
		$dataTxt = file_get_contents($file);
		if( empty($dataTxt) ){
			return false;
		}

		return unserialize($dataTxt);
	}


	/**
	 * Get the comment data for a single post
	 *
	 */
	static function GetCommentData($post_id){

		// pre 1.7.4
		$file = self::$data_dir.'/comments_data_'.$post_id.'.txt';
		$data = SimpleBlogCommon::FileData($file);
		if( is_array($data) ){
			return $data;
		}

		// 1.7.4+
		$file = self::$data_dir.'/comments/'.$post_id.'.txt';
		$data = SimpleBlogCommon::FileData($file);
		if( is_array($data) ){
			return $data;
		}


		return array();
	}



	/**
	 * Save the comment data for a blog post
	 *
	 */
	static function SaveCommentData($post_index,$data){
		global $langmessage;


		// check directory
		$dir = self::$data_dir.'/comments';
		if( !gpFiles::CheckDir($dir) ){
			return false;
		}

		$commentDataFile = $dir.'/'.$post_index.'.txt';
		$dataTxt = serialize($data);
		if( !gpFiles::Save($commentDataFile,$dataTxt) ){
			return false;
		}


		// clean pre 1.7.4 files
		$commentDataFile = self::$data_dir.'/comments_data_'.$post_index.'.txt';
		if( file_exists($commentDataFile) ){
			unlink($commentDataFile);
		}

		SimpleBlogCommon::AStrValue('comment_counts',$post_index,count($data));

		SimpleBlogCommon::SaveIndex();

		SimpleBlogCommon::ClearCommentCache();

		return true;
	}

	/**
	 * Delete the comments cache
	 *
	 */
	static function ClearCommentCache(){
		$cache_file = self::$data_dir.'/comments/cache.txt';
		if( file_exists($cache_file) ){
			unlink($cache_file);
		}
	}

	static function PostLink($post,$label,$query='',$attr=''){
		return '<a href="'.SimpleBlogCommon::PostUrl($post,$query,true).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}

	static function PostUrl( $post = false, $query='' ){
		SimpleBlogCommon::UrlQuery( $post, $url, $query );
		return common::GetUrl( $url, $query );
	}

	static function UrlQuery( $post_id = false, &$url, &$query ){

		$url = SimpleBlogCommon::$root_url;

		if( $post_id > 0 ){
			switch( SimpleBlogCommon::$data['urls'] ){

				case 'Full':
					$title = SimpleBlogCommon::AStrValue('titles',$post_id);
					$title = str_replace(array('?',' '),array('','_'),$title);
					$url .= '/'.$post_id.'_'.$title;
				break;

				case 'Tiny':
				$url .= '/'.$post_id;
				break;

				case 'Title':
					$title = SimpleBlogCommon::AStrValue('titles',$post_id);
					$title = str_replace(array('?',' '),array('','_'),$title);
					$url .= '/'.$title;
				break;

				default:
				$query = trim($query.'&id='.$post_id,'&');
				break;
			}
		}
	}

	static function CategoryLink( $catindex, $cattitle, $label, $query = '', $attr = '' ){

		$url = 'Special_Blog_Categories';
		switch( SimpleBlogCommon::$data['urls'] ){

			case 'Title':
			case 'Full':
				$cattitle = str_replace(array('?',' '),array('','_'),$cattitle);
				$url .= '/'.$cattitle;
			break;

			case 'Tiny':
				$url .= '/'.$catindex;
			break;

			default:
				$query = trim('cat='.$catindex.'&'.$query,'&');
			break;
		}

		return '<a href="'.common::GetUrl( $url, $query ).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}



	/**
	 *		A R R A Y - S T R I N G   F U N C T I O N S
	 *
	 * Memory usage is much better for strings than arrays
	 * Speed is not significantly affected if used sparingly
	 *
	 */


	/**
	 * Serialize a simple array into a string
	 *
	 */
	static function AStrFromArray($array){
		$str = '';
		foreach($array as $key => $value){
			$key = str_replace(array('"','>'),'',$key);
			$value = str_replace(array('"','>'),'',$value);
			$str .= '"'.$key.'>'.$value;
		}
		return $str.'"';
	}


	/**
	 * Get/Set the value from a serialized string
	 *
	 */
	static function AStrValue( $data_string, $key, $new_value = false ){

		//get string
		$string =& SimpleBlogCommon::$data[$data_string];


		//get position of current value
		$prev_key_str = '"'.$key.'>';
		$offset = strpos($string,$prev_key_str);
		if( $offset !== false ){
			$offset += strlen($prev_key_str);
			$length = strpos($string,'"',$offset) - $offset;
			if( $new_value === false ){
				return substr($string,$offset,$length);
			}

		}elseif( $new_value === false ){
			return false;
		}


		//setting values
		if( $offset === false ){
			if( empty($string) ){
				$string = '"';
			}
			$key = str_replace(array('"','>'),'',$key);
			$string .= $key.'>'.$new_value.'"';
		}else{
			$string = substr_replace($string,$new_value,$offset,$length);
		}

		return true;
	}


	/**
	 * Get the key for a given value
	 * Should be changed to allow for non-numeric keys
	 *
	 */
	static function AStrKey( $data_string, $value, $url_search = false ){
		static $integers = '0123456789';

		if( !isset(SimpleBlogCommon::$data[$data_string]) ){
			return false;
		}

		$string			= SimpleBlogCommon::$data[$data_string];

		if( $url_search ){
			$string		= str_replace(array('?',' '),array('','_'),$string);
		}

		$len			= strlen($string);
		$post_pos		= strpos($string,'>'.$value.'"');

		if( $post_pos === false ){
			return false;
		}

		$offset			= $post_pos-$len;
		$post_key_pos	= strrpos( $string, '"', $offset );
		$post_key_len	= strspn( $string, $integers, $post_key_pos+1 );

		return substr( $string, $post_key_pos+1, $post_key_len );
	}


	/**
	 * Remove a key-value
	 *
	 */
	static function AStrRm( $data_string, $key ){

		$string =& SimpleBlogCommon::$data[$data_string];
		$string = preg_replace('#"'.$key.'>[^">]*\"#', '"', $string);
	}


	static function AStrRmValue( $data_string, $value ){

		$string =& SimpleBlogCommon::$data[$data_string];
		$string = preg_replace('#"[^">]*>'.$value.'\"#', '"', $string);

	}


	/**
	 * Convert an AStr to an array
	 *
	 */
	static function AStrToArray( $data_string ){

		$string =& SimpleBlogCommon::$data[$data_string];

		$count = preg_match_all('#(?:([^">]*)>)([^">]*)#', $string, $matches);
		if( !$count ){
			return array();
		}

		$keys = $matches[1];
		$values = $matches[2];

		return array_combine($matches[1],$matches[2]);
	}


}


