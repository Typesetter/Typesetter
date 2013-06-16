<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/recaptcha.php');

if( function_exists('mb_internal_encoding') ){
	mb_internal_encoding('UTF-8');
}

/**
 * To Do
 *
 * Deleting a post should delete from other AStrings as well.. especially titles!
 * WhichPosts() should just return an array with the post content
 *
 */

class SimpleBlogCommon{

	var $indexFile;
	static $data = false;
	static $root_url = 'Special_Blog';

	var $new_install = false;
	var $addonPathData;
	var $post_id = false;

	var $categories;
	var $categories_file;

	var $archives;
	var $archives_file;
	var $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');


	/**
	 * When SimpleBlogCommon is created as an object, it will regenerate the static files
	 *
	 */
	function SimpleBlogCommon(){
		$this->Init();
		$this->GenStaticContent();
	}

	/**
	 * Set variables for blog display
	 *
	 */
	function Init(){
		global $addonPathData;

		if( SimpleBlogCommon::$data ){
			return;
		}

		$this->addonPathData = $addonPathData;
		$this->indexFile = $this->addonPathData.'/index.php';
		self::$root_url = common::SpecialHref('Special_Blog');

		$this->GetBlogData();
		SimpleBlogCommon::AddCSS();
	}


	function AddCSS(){
		global $addonFolderName,$page;

		//$page->head_script .= 'gplinks.blog_gadget = function(){$(this).next(".nodisplay").toggle();};';
		$page->jQueryCode .= '$(".blog_gadget_link").click(function(){$(this).next(".nodisplay").toggle();});';
		$page->css_user[] = '/data/_addoncode/'.$addonFolderName.'/style.css';
		$added = true;
	}


	/**
	 * Get the user configuration and information about the current blog
	 *
	 */
	function GetBlogData(){

		$blogData = array();
		if( file_exists($this->indexFile) ){
			require($this->indexFile);
		}

		//old twitter auth no longer works
		if( isset($blogData['twitter_username']) || isset($blogData['twitter_password']) ){
			unset($blogData['twitter_username']);
			unset($blogData['twitter_password']);
		}

		SimpleBlogCommon::$data = $blogData + SimpleBlogCommon::Defaults();
		$this->GenIndexStr();

		//update to simple blog 1.9 data
		if( isset(SimpleBlogCommon::$data['post_info']) ){
			$this->DataUpdate19();
		}

	}

	/**
	 * Generate a string to use as the post index
	 * Using a string of numbers can use 1/4 of the memory of an array
	 *
	 * As of 1.9, Uses " and > instead , and :
	 */
	function GenIndexStr(){

		if( !empty(SimpleBlogCommon::$data['str_index']) ){
			if( SimpleBlogCommon::$data['str_index'][0] == ',' ){
				SimpleBlogCommon::$data['str_index'] = str_replace( array(',',':'), array('"','>'), SimpleBlogCommon::$data['str_index']);
			}
			return;
		}
		if( !isset(SimpleBlogCommon::$data['post_list']) ){
			return;
		}

		SimpleBlogCommon::$data['str_index'] = self::AStrFromArray(SimpleBlogCommon::$data['post_list']);
	}


	/**
	 * Serialize comment counts
	 *
	 */
	function DataUpdate19(){

		$comment_counts = array();
		$comments_closed = array();
		foreach(SimpleBlogCommon::$data['post_info'] as $post_id => $info){
			if( isset($info['comments']) ){
				$comment_counts[$post_id] = $info['comments'];
			}

			if( isset($info['closecomments']) ){
				$comments_closed[$post_id] = 1;
			}
		}


		SimpleBlogCommon::$data['comment_counts'] = self::AStrFromArray($comment_counts);
		SimpleBlogCommon::$data['comments_closed'] = self::AStrFromArray($comments_closed);


		//post data
		$drafts = array();
		$titles = array();
		for($i=0; $i<SimpleBlogCommon::$data['post_index']; $i++ ){
			$post_id = self::AStrValue('str_index',$i);
			$post = $this->GetPostContent($post_id);
			if( !$post ){
				continue;
			}

			if( isset($post['isDraft']) && $post['isDraft'] ){
				$drafts[$post_id] = 1;
			}
			$titles[$post_id] = $post['title'];
		}
		SimpleBlogCommon::$data['drafts'] = self::AStrFromArray($drafts);
		SimpleBlogCommon::$data['titles'] = self::AStrFromArray($titles);

		unset(SimpleBlogCommon::$data['post_info']);
		unset(SimpleBlogCommon::$data['post_list']);

	}



	/**
	 * Get a list of post indeces
	 *
	 */
	function WhichPosts($start, $len, $include_drafts = false){

		$posts = array();
		$end = $start+$len;
		for($i = $start; $i < $end; $i++){

			//get post id
			$post_id = self::AStrValue('str_index',$i);
			if( !$post_id ){
				continue;
			}

			//exclude drafts
			if( !$include_drafts && SimpleBlogCommon::AStrValue('drafts',$post_id) ){
				continue;
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

		return array(	'post_index'=>0,
						'per_page'=>10,
						'post_abbrev'=>'',
						'gadget_entries'=>3,
						'gadget_abbrev'=>90,
						'feed_entries'=>10,
						'feed_abbrev'=>1200,
						'feed_author'=>$config['title'],
						'date_format'=>'n/j/Y',
						'strftime_format'=>'%'.$zero_strip.'m/%'.$zero_strip.'e/%Y',
						'bitly_login'=>'',
						'bitly_key'=>'',
						'allow_comments'=>false,
						'commenter_website'=>'',
						'comment_captcha'=>true,
						'subtitle_separator'=>' <span class="space"> | </span> ',
						'post_count'=>0,
						'str_index'=>'',
						'urls'=>'Default',
						'drafts'=>'',
						);

	}


	/**
	 * Save the blog configuration and details about the blog
	 *
	 */
	function SaveIndex(){

		$this->GenIndexStr();
		unset(SimpleBlogCommon::$data['post_list']);

		//set some stats
		SimpleBlogCommon::$data['str_index'] = '"'.trim(SimpleBlogCommon::$data['str_index'],'"').'"';
		SimpleBlogCommon::$data['post_count'] = substr_count(SimpleBlogCommon::$data['str_index'],'>');

		return gpFiles::SaveArray($this->indexFile,'blogData',SimpleBlogCommon::$data);
	}

	/**
	 * Get the data file for a blog post
	 * Return the data if it exists
	 * 20 posts per file... if someone posts a lot (once a day for a year that would be 18 files)
	 *
	 */
	function GetPostFile($post_index,&$post_file){

		if( !is_numeric($post_index) ){
			return false;
		}

		$file_index = floor($post_index/20);
		$post_file = $this->addonPathData.'/posts_'.$file_index.'.php';
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
	function GetPostContent($post_index){
		$posts = $this->GetPostFile($post_index,$post_file);
		if( $posts === false ){
			return false;
		}

		if( !isset($posts[$post_index]) ){
			return false;
		}
		return $posts[$post_index];
	}

	/**
	 * Delete a blog post
	 * @return bool
	 *
	 */
	function Delete(){
		global $langmessage;

		$post_id = $_POST['del_id'];
		$posts = $this->GetPostFile($post_id,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($posts[$post_id]) ){
			message($langmessage['OOPS']);
			return false;
		}

		//now delete post also from categories:
		$this->delete_post_from_categories($post_id);
		$this->delete_post_from_archive($post_id, $posts[$post_id]['time']);

		unset($posts[$post_id]); //don't use array_splice here because it will reset the numeric keys

		//reset the index string
		$new_index = SimpleBlogCommon::$data['str_index'];
		$new_index = preg_replace('#"\d+>'.$post_id.'"#', '"', $new_index);
		preg_match_all('#(?:"\d+>)([^">])*#',$new_index,$matches);
		SimpleBlogCommon::$data['str_index'] = self::AStrFromArray($matches[1]);


		//remove post from other index strings
		self::AStrRemove('drafts',$post_id);
		self::AStrRemove('comments_closed',$post_id);
		self::AStrRemove('titles',$post_id);


		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		//save to data file
		if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['file_deleted']);
		return true;
	}




	/**
	 * Save a new blog post
	 * @return bool
	 *
	 */
	function SaveNew(){
		global $langmessage;

		$_POST += array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>'');

		$_POST['subtitle'] = htmlspecialchars($_POST['subtitle']);

		$title =& $_POST['title'];
		$title = htmlspecialchars($title);
		$title = trim($title);
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}

		$content =& $_POST['content'];
		gpFiles::cleanText($content);


		//use current data file or create new one
		$post_index = SimpleBlogCommon::$data['post_index'] +1;
		$posts = $this->GetPostFile($post_index,$post_file);


		$posts[$post_index] = array();
		$posts[$post_index]['title'] = $title;
		$posts[$post_index]['content'] = $content;
		$posts[$post_index]['subtitle'] = $_POST['subtitle'];
		if( $_POST['isDraft'] === 'on' ){
			SimpleBlogCommon::AStrValue('drafts',$post_index,1);
		}else{
			SimpleBlogCommon::AStrRemove('drafts',$post_index);
		}
		$posts[$post_index]['time'] = time();

		//save to data file
		if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
			message($langmessage['OOPS']);
			return false;
		}

		//update categories and archive
		$this->update_post_in_categories($post_index,$title);
		$this->update_post_in_archives($post_index,$posts[$post_index]);

		//add new entry to the beginning of the index string then reorder the keys
		$new_index = '"0>'.$post_index.SimpleBlogCommon::$data['str_index'];
		preg_match_all('#(?:"\d+>)([^">]*)#',$new_index,$matches);
		SimpleBlogCommon::$data['str_index'] = self::AStrFromArray($matches[1]);


		//save index file
		self::AStrValue('titles',$post_index,$title);
		SimpleBlogCommon::$data['post_index'] = $post_index;
		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);

		return true;
	}


	/**
	 * Save an edited blog post
	 * @return bool
	 */
	function SaveEdit(){
		global $langmessage;

		$_POST += array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>'');
		$_REQUEST += array('id'=>'');

		$post_index = $_REQUEST['id'];
		$posts = $this->GetPostFile($post_index,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS'].' (Invalid ID)');
			return;
		}

		$_POST['subtitle'] = htmlspecialchars($_POST['subtitle']);

		$title =& $_POST['title'];
		$title = htmlspecialchars($title);
		$title = trim($title);
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}

		$content =& $_POST['content'];
		gpFiles::cleanText($content);


		$posts[$post_index]['title'] = $title;
		$posts[$post_index]['content'] = $content;
		$posts[$post_index]['subtitle'] = $_POST['subtitle'];
		unset($posts[$post_index]['isDraft']);
		if( $_POST['isDraft'] === 'on' ){
			SimpleBlogCommon::AStrValue('drafts',$post_index,1);
		}else{
			SimpleBlogCommon::AStrRemove('drafts',$post_index);
		}

		//save to data file
		if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
			message($langmessage['OOPS']);
			return false;
		}

		$this->SaveIndex();

		//find and update the edited post in categories and archives
		$this->update_post_in_categories($post_index,$title);
		$this->update_post_in_archives($post_index,$posts[$post_index]);

		message($langmessage['SAVED']);
		return true;
	}

	/**
	 * Save an inline edit
	 *
	 */
	function SaveInline(){
		global $page,$langmessage;
		$page->ajaxReplace = array();

		$post_index = $_REQUEST['id'];
		$posts = $this->GetPostFile($post_index,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS']);
			return;
		}

		$content =& $_POST['gpcontent'];
		gpFiles::cleanText($content);
		$posts[$post_index]['content'] = $content;

		//save to data file
		if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
			message($langmessage['OOPS']);
			return false;
		}

		$page->ajaxReplace[] = array('ck_saved', '', '');
		message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Edit a post with inline editing
	 *
	 */
	function InlineEdit(){


		$content = $this->GetPostContent($this->post_id);
		if( !$content ){
			echo 'false';
			return false;
		}

		$content += array('type'=>'text');

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($content);
	}

	/**
	 * Display the form for editing an existing post
	 *
	 */
	function EditPost(){
		global $langmessage;

		$posts = $this->GetPostFile($this->post_id,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS']);
			return;
		}

		if( !isset($posts[$this->post_id]) ){
			message($langmessage['OOPS']);
			return;
		}

		$post['isDraft'] = SimpleBlogCommon::AStrValue('drafts',$this->post_id);
		$_POST += $posts[$this->post_id];

		echo '<h2>';
		$title = htmlspecialchars($_POST['title'],ENT_COMPAT,'UTF-8',false);
		echo $this->PostLink($this->post_id,$title);
		echo ' &#187; ';
		echo 'Edit Post</h2>';
		$this->PostForm($_POST,'save_edit',$this->post_id);
		return true;
	}

	/**
	 * Display the form for creating a new post
	 *
	 */
	function NewForm(){
		global $langmessage;

		echo '<h2>New Blog Post</h2>';

		$this->PostForm($_POST);
	}


	/**
	 * Display form for submitting posts (new and edit)
	 *
	 */
	function PostForm(&$array,$cmd='save_new',$post_id=false){
		global $langmessage;

		includeFile('tool/editing.php');

		$array += array('title'=>'', 'content'=>'', 'subtitle'=>'', 'isDraft'=>false);
		$array['title'] = SimpleBlogCommon::Underscores( $array['title'] );

		echo '<form action="'.$this->PostUrl($post_id).'" method="post">';

		echo '<table style="width:100%">';

		echo '<tr><td>';
			echo 'Title';
			echo '</td><td>';
			echo '<input type="text" name="title" value="'.$array['title'].'" />';
			echo '</td></tr>';

		echo '<tr><td>';
			echo 'Sub-Title';
			echo '</td><td>';
			echo '<input type="text" name="subtitle" value="'.$array['subtitle'].'" />';
			echo '</td></tr>';

		echo '<tr><td>';
			echo 'Draft';
			echo '</td><td>';
			echo '<input type="checkbox" name="isDraft" value="on" ';
				if( $array['isDraft'] ) echo 'checked="true"';
				echo '" />';
			echo '</td></tr>';

		$this->show_category_list($post_id);

		echo '<tr><td colspan="2">';
			gp_edit::UseCK($array['content'],'content');
			echo '</td></tr>';

		echo '<tr><td colspan="2">';
			echo '<input type="hidden" name="cmd" value="'.$cmd.'" />';
			echo '<input type="hidden" name="id" value="'.$post_id.'" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td></tr>';

		echo '</table>';
		echo '</form>';
	}


	/**
	 * Regenerate the atom.feed file
	 *
	 */
	function GenFeed(){
		global $config, $addonFolderName,$dirPrefix;
		ob_start();

		$atomFormat = 'Y-m-d\TH:i:s\Z';
		if( version_compare('phpversion', '5.1.3', '>=') ){
			$atomFormat = 'Y-m-d\TH:i:sP';
		}

		$posts = array();
		$show_posts = $this->WhichPosts(0,SimpleBlogCommon::$data['feed_entries']);


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
		echo '<id>urn:uuid:'.$this->uuid($serverWithDir).'</id>'."\n";
		echo '<updated>'.date($atomFormat, time()).'</updated>'."\n";
		echo '<author><name>'.$config['title'].'</name></author>'."\n";


		foreach($show_posts as $post_index){

			//get $posts
			if( !isset($posts[$post_index]) ){
				$posts = $this->GetPostFile($post_index,$post_file);
			}

			if( !isset($posts[$post_index]) ){
				continue;
			}

			$post =& $posts[$post_index];

			echo '<entry>'."\n";
			echo '<title>'.SimpleBlogCommon::Underscores( $post['title'] ).'</title>'."\n";
			echo '<link href="'.$server.$this->PostUrl($post_index).'"></link>'."\n";
			echo '<id>urn:uuid:'.$this->uuid($post_index).'</id>'."\n";
			echo '<updated>'.date($atomFormat, $post['time']).'</updated>'."\n";

			$content =& $post['content'];
			if( (SimpleBlogCommon::$data['feed_abbrev']> 0) && (SimpleBlogCommon::strlen($content) > SimpleBlogCommon::$data['feed_abbrev']) ){
				$content = SimpleBlogCommon::substr($content,0,SimpleBlogCommon::$data['feed_abbrev']).' ... ';
				$label = gpOutput::SelectText('Read More');
				$content .= '<a href="'.$server.$this->PostUrl($post_index,$label).'">'.$label.'</a>';
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
		$feedFile = $this->addonPathData.'/feed.atom';
		gpFiles::Save($feedFile,$feed);
	}


	/**
	 * Recursively turn relative links into absolute links
	 * @static
	 */
	function FixLinks(&$content,$server,$offset){

		$pos = SimpleBlogCommon::strpos($content,'href="',$offset);
		if( $pos <= 0 ){
			return;
		}
		$pos = $pos+6;

		$pos2 = SimpleBlogCommon::strpos($content,'"',$pos);

		if( $pos2 <= 0 ){
			return;
		}

		//well formed link
		$check = SimpleBlogCommon::strpos($content,'>',$pos);
		if( ($check !== false) && ($check < $pos2) ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}

		$title = SimpleBlogCommon::substr($content,$pos,$pos2-$pos);

		//internal link
		if( SimpleBlogCommon::strpos($title,'mailto:') !== false ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}
		if( SimpleBlogCommon::strpos($title,'://') !== false ){
			SimpleBlogCommon::FixLinks($content,$server,$pos2);
			return;
		}

		if( SimpleBlogCommon::strpos($title,'/') === 0 ){
			$replacement = $server.$title;
		}else{
			$replacement = $server.common::GetUrl($title);
		}

		$content = SimpleBlogCommon::substr_replace($content,$replacement,$pos,$pos2-$pos);

		SimpleBlogCommon::FixLinks($content,$server,$pos2);
	}

	function uuid($str){
		$chars = md5($str);
		return SimpleBlogCommon::substr($chars,0,8)
				.'-'. SimpleBlogCommon::substr($chars,8,4)
				.'-'. SimpleBlogCommon::substr($chars,12,4)
				.'-'. SimpleBlogCommon::substr($chars,16,4)
				.'-'. SimpleBlogCommon::substr($chars,20,12);
		return $uuid;
	}


	/**
	 * Regenerate all of the static content: gadget and feed
	 *
	 */
	function GenStaticContent(){
		$this->GenFeed();
		$this->GenGadget();
	}

	/**
	 * Regenerate the static content used to display the gadget
	 *
	 */
	function GenGadget(){
		global $langmessage;

		$posts = array();
		$show_posts = $this->WhichPosts(0,SimpleBlogCommon::$data['gadget_entries']);


		ob_start();
		$label = gpOutput::SelectText('Blog');
		if( !empty($label) ){
			echo '<h3>';
			echo common::Link('Special_Blog',$label);
			echo '</h3>';
		}

		foreach($show_posts as $post_index){

			//get $posts
			if( !isset($posts[$post_index]) ){
				$posts = $this->GetPostFile($post_index,$post_file);
			}

			if( !isset($posts[$post_index]) ){
				continue;
			}

			$post =& $posts[$post_index];

			$header = '<b class="simple_blog_title">';
			$label = SimpleBlogCommon::Underscores( $post['title'] );
			$header .= $this->PostLink($post_index,$label);
			$header .= '</b>';

			$this->BlogHead($header,$post_index,$post,true);


			$content = strip_tags($post['content']);

			if( SimpleBlogCommon::$data['gadget_abbrev'] > 6 && (SimpleBlogCommon::strlen($content) > SimpleBlogCommon::$data['gadget_abbrev']) ){

				$cut = SimpleBlogCommon::$data['gadget_abbrev'];

				$pos = SimpleBlogCommon::strpos($content,' ',$cut-5);
				if( ($pos > 0) && ($cut+20 > $pos) ){
					$cut = $pos;
				}
				$content = SimpleBlogCommon::substr($content,0,$cut).' ... ';

				$label = gpOutput::SelectText('Read More');
				$content .= $this->PostLink($post_index,$label);
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
		$gadgetFile = $this->addonPathData.'/gadget.php';
		gpFiles::Save($gadgetFile,$gadget);
	}





	/**
	 * Potential method for allowing users to format the header area of their blog
	 * However, this would make it more difficult for theme developers to design for the blog plugin
	 *
	 */
	function BlogHead($header,$post_index,$post,$cacheable=false){


		$blog_info = '{empty_blog_piece}';
		if( !empty($post['subtitle']) ){
			$blog_info = '<span class="simple_blog_subtitle">';
			$blog_info .= $post['subtitle'];
			$blog_info .= '</span>';
		}

		$blog_date = '<span class="simple_blog_date">';
		$blog_date .= strftime(SimpleBlogCommon::$data['strftime_format'],$post['time']);
		$blog_date .= '</span>';

		$blog_comments = '{empty_blog_piece}';
		$count = self::AStrValue('comment_counts',$post_index);
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
	 *
	 */
	function load_blog_categories(){
		global $addonPathData;
		$this->categories_file = $addonPathData.'/categories.php';
		if( file_exists($this->categories_file) ){
			include($this->categories_file);
			$this->categories = $categories;
		}else{
			$this->categories = array( 'a' => array( 'ct'=>'Unsorted posts', 'visible'=>false,'posts'=>array() ) );
		}
	}

	/**
	 * Remove a blog entry from a category
	 *
	 */
	function delete_post_from_categories($post_index){
		$this->load_blog_categories();
		foreach ($this->categories as $catindex => $catdata){
			if (isset($catdata['posts'][$post_index]))	{
				unset($this->categories[$catindex]['posts'][$post_index]);
			}
		}
		gpFiles::SaveArray($this->categories_file,'categories',$this->categories); //save
	}


	/**
	 * Update a category when a blog entry is edited
	 *
	 */
	function update_post_in_categories($post_index,$title){
		$this->load_blog_categories();
		foreach( $this->categories as $catindex => $catdata ){
			$selected = false;
			if( isset($_POST['category']) ){
				foreach( $_POST['category'] as $catindex1 ){
					if( $catindex == $catindex1 ){
						$selected = true;
					}
				}
			}

			if( $selected ){
				$this->categories[$catindex]['posts'][$post_index] = $title;
			}elseif( isset($catdata['posts'][$post_index]) ){
				unset($this->categories[$catindex]['posts'][$post_index]);
			}
		}
		gpFiles::SaveArray($this->categories_file,'categories',$this->categories); //save
	}

	/**
	 * Show a list of all categories
	 *
	 */
	function show_category_list($postindex){
		if( $postindex == false ){
			$postindex =- 1;
		}
		$this->load_blog_categories();
		echo '<tr><td>Category</td><td>';
		echo '<select name="category[]" multiple="multiple">';
		foreach( $this->categories as $catindex => $catdata ){
			echo '<option value="'.$catindex.'" '.(isset($catdata['posts'][$postindex])? 'selected="selected"':'').'>'.$catdata['ct'].'</option>';
		}
		echo '</select></td></tr>';
	}



	/**
	 *  B L O G    A R C H I V E   F U N C T I O N S
	 *
	 */


	/**
	 * Get all the archives in use by the blog
	 *
	 */
	function load_blog_archives(){
		global $addonPathData;
		$this->archives_file = $addonPathData.'/archives.php';
		if( file_exists($this->archives_file) ){
			include($this->archives_file);
			$this->archives = $archives;
		}else{
			$this->archives = array();
		}
	}

	/**
	 * Remove a blog entry from an archive
	 *
	 */
	function delete_post_from_archive($post_index,$time){
		$this->load_blog_archives();
		$ym=date('Ym',$time); //year&month
		if ( isset($this->archives[$ym][$post_index]) ){
			unset($this->archives[$ym][$post_index]);
			if (!count($this->archives[$ym]))
				unset($this->archives[$ym]); //remove empty yearmonth
			gpFiles::SaveArray($this->archives_file,'archives',$this->archives); //save archives
		}
	}

	/**
	 * Update an archive when a blog entry is edited  -- called from SaveNew() and SaveEdit()
	 *
	 */
	function update_post_in_archives($post_index,$array){
		$this->load_blog_archives();
		$ym=date('Ym',$array['time']); //year&month
		$this->archives[$ym][$post_index] = $array['title'];
		krsort($this->archives[$ym]);
		krsort($this->archives);
		gpFiles::SaveArray($this->archives_file,'archives',$this->archives); //save archives
	}

	function substr( $str, $start, $length = false ){
		if( function_exists('mb_substr') ){
			if( $length !== false ){
				return mb_substr($str,$start,$length);
			}
			return mb_substr($str,$start);
		}
		if( $length !== false ){
			return substr($str,$start,$length);
		}
		return substr($str,$start);
	}

	function strlen($str){
		if( function_exists('mb_strlen') ){
			return mb_strlen($str);
		}
		return strlen($str);
	}

	function strpos($haystack,$needle,$offset=0){
		if( function_exists('mb_strpos') ){
			return mb_strpos($haystack,$needle,$offset);
		}
		return strpos($haystack,$needle,$offset);
	}

	function substr_replace( $str, $repl, $start, $length ){
		$part_one = SimpleBlogCommon::substr( $str, 0, $start);
		$part_two = SimpleBlogCommon::substr( $str, $start+$length);
		return $part_one . $repl . $part_two;
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
	function GetCommentData($post_index){

		// pre 1.7.4
		$commentDataFile = $this->addonPathData.'/comments_data_'.$post_index.'.txt';
		$data = SimpleBlogCommon::FileData($commentDataFile);
		if( $data ){
			return $data;
		}

		// 1.7.4+
		$commentDataFile = $this->addonPathData.'/comments/'.$post_index.'.txt';
		$data = SimpleBlogCommon::FileData($commentDataFile);
		if( $data ){
			return $data;
		}


		return array();
	}



	/**
	 * Save the comment data for a blog post
	 *
	 */
	function SaveCommentData($post_index,$data){
		global $langmessage;


		// check directory
		$dir = $this->addonPathData.'/comments';
		if( !gpFiles::CheckDir($dir) ){
			return false;
		}

		$commentDataFile = $dir.'/'.$post_index.'.txt';
		$dataTxt = serialize($data);
		if( !gpFiles::Save($commentDataFile,$dataTxt) ){
			return false;
		}


		// clean pre 1.7.4 files
		$commentDataFile = $this->addonPathData.'/comments_data_'.$post_index.'.txt';
		if( file_exists($commentDataFile) ){
			unlink($commentDataFile);
		}

		SimpleBlogCommon::AStrValue('comment_counts',$post_index,count($data));

		$this->SaveIndex();

		//clear comments cache
		$cache_file = $this->addonPathData.'/comments/cache.txt';
		if( file_exists($cache_file) ){
			unlink($cache_file);
		}

		return true;
	}

	function PostLink($post,$label,$query='',$attr=''){
		return '<a href="'.$this->PostUrl($post,$query,true).'" '.common::LinkAttr($attr,$label).'>'.common::Ampersands($label).'</a>';
	}

	function PostUrl( $post = false, $query='' ){
		$this->UrlQuery( $post, $url, $query );
		return common::GetUrl( $url, $query );
	}

	function UrlQuery( $post_id = false, &$url, &$query ){

		$url = self::$root_url;

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

				default:
				$query = trim($query.'&id='.$post_id,'&');
				break;
			}
		}
	}



	/**
	 *		A R R A Y - S T R I N G   F U N C T I O N S
	 *
	 *
	 */


	/**
	 * Serialize a simple array into a string
	 *
	 */
	static function AStrFromArray($array){
		$str = '';
		foreach($array as $key => $value){
			$str .= '"'.$key.'>'.$value;
		}
		return $str.'"';
	}


	/**
	 * Get/Set the value from a serialized string
	 *
	 */
	static function AStrValue( $data_string, $key, $new_value = false ){
		static $integers = '0123456789';

		//get string
		if( !isset(SimpleBlogCommon::$data[$data_string]) && $new_value === false ){
			return false;
		}
		$string =& SimpleBlogCommon::$data[$data_string];


		//get position of current value
		$prev_key_str = '"'.$key.'>';
		$prev_pos = strpos($string,$prev_key_str);
		if( $prev_pos !== false ){
			$prev_comma = strpos($string,'"',$prev_pos+1);
			$offset = $prev_pos+strlen($prev_key_str);
		}

		$current_value = false;
		if( $prev_pos !== false ){
			$current_value = substr($string,$offset,$prev_comma - $offset);
		}


		//returning value
		if( $new_value === false ){
			return $current_value;
		}


		//new value operations
		$new_value = str_replace(array('"','>'),'',$new_value);
		if( ctype_digit(substr($new_value,1)) ){
			if( $new_value[0] === '+' || $new_value[0] === '-' ){
				$new_value = (int)$current_value+(int)$new_value;
			}
		}

		//setting values
		if( $prev_pos === false ){
			if( empty($string) ){
				$string = '"';
			}
			$string .= $key.'>'.$new_value.'"';
		}else{
			$string = substr_replace($string,$new_value,$offset,$prev_comma - $offset);
		}

		return true;
	}


	/**
	 * Get the key for a given value
	 *
	 */
	static function AStrKey( $data_string, $value ){
		static $integers = '0123456789';

		if( !isset(SimpleBlogCommon::$data[$data_string]) ){
			return false;
		}

		$string = SimpleBlogCommon::$data[$data_string];

		$len = strlen($string);
		$post_pos = strpos($string,'>'.$value.'"');

		$offset = $post_pos-$len;


		$post_key_pos = strrpos( $string, '"', $offset );
		$post_key_len = strspn( $string, $integers, $post_key_pos+1 );
		return substr( $string, $post_key_pos+1, $post_key_len );
	}

	/**
	 * Remove a key-value
	 *
	 */
	static function AStrRemove( $data_string, $key ){

		if( !isset(SimpleBlogCommon::$data[$data_string]) ){
			return false;
		}

		$string =& SimpleBlogCommon::$data[$data_string];

		$string = preg_replace('#"'.$key.'>[^">]*\"#', '"', $string);
	}

}


