<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/recaptcha.php');

if( function_exists('mb_internal_encoding') ){
	mb_internal_encoding('UTF-8');
}


class SimpleBlogCommon{

	var $indexFile;
	var $blogData = array();
	var $new_install = false;
	var $addonPathData;

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
		$this->addonPathData = $addonPathData;
		$this->indexFile = $this->addonPathData.'/index.php';
		$this->GetBlogData();
		SimpleBlogCommon::AddCSS();
	}


	function AddCSS(){
		global $addonFolderName,$page;
		static $added = false;
		if( $added ) return;

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

		$this->blogData = $blogData + SimpleBlogCommon::Defaults();
		$this->GenIndexStr();
	}

	/**
	 * Generate a string to use as the post index
	 * Using a string of numbers can use 1/4 of the memory of an array
	 *
	 */
	function GenIndexStr(){

		if( !empty($this->blogData['str_index']) ){
			return;
		}
		if( !isset($this->blogData['post_list']) ){
			return;
		}

		$str = '';
		foreach($this->blogData['post_list'] as $key => $value){
			$str .= ','.$key.':'.$value;
		}
		$str .= ',';
		$this->blogData['str_index'] = $str;
	}


	/**
	 * Reassign indexes to the index string
	 *
	 */
	function AdjustIndex(){
		static $index = 0;
		return ','.$index++.':';
	}

	/**
	 * Get a post index from it's key
	 *
	 */
	function IndexFromKey($key){
		static $integers = '0123456789';
		$index = $this->blogData['str_index'];

		$prev_key_str = ','.$key.':';
		$prev_pos = SimpleBlogCommon::strpos($index,$prev_key_str);
		if( $prev_pos === false ){
			return false;
		}
		$offset = $prev_pos+1;

		$prev_comma = SimpleBlogCommon::strpos($index,',',$offset);

		$offset = $prev_pos+SimpleBlogCommon::strlen($prev_key_str);
		return SimpleBlogCommon::substr($index,$offset,$prev_comma - $offset);
	}

	/**
	 * Get a post key from it's index
	 *
	 */
	function KeyFromIndex($post_index){
		static $integers = '0123456789';
		$index = $this->blogData['str_index'];
		$len = strlen($index);
		$post_pos = strpos($index,':'.$post_index.',');

		$offset = $post_pos-$len;


		$post_key_pos = strrpos($index,',',$offset);
		$post_key_len = strspn($index,$integers,$post_key_pos+1);
		return substr($index,$post_key_pos+1,$post_key_len);
	}


	/**
	 * Get a list of post indeces
	 *
	 */
	function WhichPosts($start,$len){
		$posts = array();
		$end = $start+$len;
		for($i = $start; $i < $end; $i++){
			$index = $this->IndexFromKey($i);
			if( $index ) $posts[] = $index;
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
						'post_info'=>'',
						'allow_comments'=>false,
						'commenter_website'=>'',
						'comment_captcha'=>true,
						'subtitle_separator'=>' <span class="space"> | </span> ',
						'post_count'=>0,
						'str_index'=>''
						);

	}


	/**
	 * Save the blog configuration and details about the blog
	 *
	 */
	function SaveIndex(){

		$this->GenIndexStr();
		unset($this->blogData['post_list']);

		//set some stats
		$this->blogData['str_index'] = ','.trim($this->blogData['str_index'],',').',';
		$this->blogData['post_count'] = substr_count($this->blogData['str_index'],':');

		return gpFiles::SaveArray($this->indexFile,'blogData',$this->blogData);
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

		$post_index = $_POST['id'];
		$posts = $this->GetPostFile($post_index,$post_file);
		if( $posts === false ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($posts[$post_index]) ){
			message($langmessage['OOPS']);
			return false;
		}

		//now delete post also from categories:
		$this->delete_post_from_categories($post_index);
		$this->delete_post_from_archive($post_index, $posts[$post_index]['time']);

		unset($posts[$post_index]); //don't use array_splice here because it will reset the numeric keys

		//reset the index string
		$new_index = $this->blogData['str_index'];
		$new_index = preg_replace('#,\d+:'.$post_index.',#',',',$new_index);
		$this->blogData['str_index'] = preg_replace_callback('#,(\d+):#',array('SimpleBlogCommon','AdjustIndex'),$new_index);


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
	 */
	function SaveNew(){
		global $langmessage;

		$_POST += array('title'=>'','content'=>'','subtitle'=>'','isDraft'=>'');

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
		$post_index = $this->blogData['post_index'] +1;
		$posts = $this->GetPostFile($post_index,$post_file);


		$posts[$post_index] = array();
		$posts[$post_index]['title'] = $title;
		$posts[$post_index]['content'] = $content;
		$posts[$post_index]['subtitle'] = $_POST['subtitle'];
		if( $_POST['isDraft'] === 'on' ){
			$posts[$post_index]['isDraft'] = true;
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
		$new_index = ',0:'.$post_index.$this->blogData['str_index'];
		$this->blogData['str_index'] = preg_replace_callback('#,(\d+):#',array('SimpleBlogCommon','AdjustIndex'),$new_index);

		//save index file
		$this->blogData['post_index'] = $post_index;
		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);

		$this->UpdateThirdParty($post_index,$title,$content);

		return true;
	}


	/**
	 * Save an edited blog post
	 * @return bool
	 */
	function SaveEdit(){
		global $langmessage;

		$_POST += array('title'=>'','content'=>'','subtitle'=>'','isDraft'=>'');
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
		if( $_POST['isDraft'] === 'on' ){
			$posts[$post_index]['isDraft'] = true;
		}

		//save to data file
		if( !gpFiles::SaveArray($post_file,'posts',$posts) ){
			message($langmessage['OOPS']);
			return false;
		}

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

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Edit a post with inline editing
	 *
	 */
	function InlineEdit(){

		$post_index = (int)$_REQUEST['id'];
		$content = $this->GetPostContent($post_index);
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

		if( isset($_POST['id']) ){

			$post_index = $_POST['id'];
			$post = $_POST;

		}else{

			$post_index = $_REQUEST['id'];
			$posts = $this->GetPostFile($post_index,$post_file);
			if( $posts === false ){
				message($langmessage['OOPS']);
				return;
			}

			if( !isset($posts[$post_index]) ){
				message($langmessage['OOPS']);
				return;
			}
			$post = $posts[$post_index];
		}



		echo '<h2>Edit Post</h2>';
		$this->PostForm($post,'save_edit',$post_index);
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

		$array += array('title'=>'','content'=>'','subtitle'=>'', 'isDraft'=>false);
		$array['title'] = SimpleBlogCommon::Underscores( $array['title'] );

		echo '<form action="'.common::GetUrl('Special_Blog').'" method="post">';
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
		if( version_compare('phpversion','5.1.3','>=') ){
			$atomFormat = 'Y-m-d\TH:i:sP';
		}

		$posts = array();
		$show_posts = $this->WhichPosts(0,$this->blogData['feed_entries']);


		if( isset($_SERVER['HTTP_HOST']) ){
			$server = 'http://'.$_SERVER['HTTP_HOST'];
		}else{
			$server = 'http://'.$_SERVER['SERVER_NAME'];
		}
		$serverWithDir = $server.$dirPrefix;


		echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
		echo '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";
		echo '<title>'.$config['title'].'</title>'."\n";
		echo '<link href="'.$serverWithDir.'/data/_addondata/'.str_replace(' ','%20',$addonFolderName).'/feed.atom" rel="self" />'."\n";
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
			echo '<link href="'.$server.common::GetUrl('Special_Blog','id='.$post_index).'"></link>'."\n";
			echo '<id>urn:uuid:'.$this->uuid($post_index).'</id>'."\n";
			echo '<updated>'.date($atomFormat, $post['time']).'</updated>'."\n";

			$content =& $post['content'];
			if( ($this->blogData['feed_abbrev']> 0) && (SimpleBlogCommon::strlen($content) > $this->blogData['feed_abbrev']) ){
				$content = SimpleBlogCommon::substr($content,0,$this->blogData['feed_abbrev']).' ... ';
				$label = gpOutput::SelectText('Read More');
				$content .= '<a href="'.$server.common::GetUrl('Special_Blog',$label,'id='.$post_index).'">'.$label.'</a>';
			}

			//old images
			$replacement = $server.'/';
			$content = str_replace('src="/','src="'.$replacement,$content);

			//new images
			$content = str_replace('src="../','src="'.$serverWithDir,$content);

			//images without /index.php/
			$content = str_replace('src="./','src="'.$serverWithDir,$content);


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
		$show_posts = $this->WhichPosts(0,$this->blogData['gadget_entries']);


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
			$header .= common::Link('Special_Blog',$label,'id='.$post_index);
			$header .= '</b>';

			$this->BlogHead($header,$post_index,$post,true);


			$content = strip_tags($post['content']);

			if( $this->blogData['gadget_abbrev'] > 6 && (SimpleBlogCommon::strlen($content) > $this->blogData['gadget_abbrev']) ){

				$cut = $this->blogData['gadget_abbrev'];

				$pos = SimpleBlogCommon::strpos($content,' ',$cut-5);
				if( ($pos > 0) && ($cut+20 > $pos) ){
					$cut = $pos;
				}
				$content = SimpleBlogCommon::substr($content,0,$cut).' ... ';

				$label = gpOutput::SelectText('Read More');
				$content .= common::Link('Special_Blog',$label,'id='.$post_index);
			}

			echo '<p class="simple_blog_abbrev">';
			echo $content;
			echo '</p>';

		}

		if( $this->blogData['post_count'] > 3 ){

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
		$blog_date .= strftime($this->blogData['strftime_format'],$post['time']);
		$blog_date .= '</span>';

		$blog_comments = '{empty_blog_piece}';
		if( $this->blogData['allow_comments']
			&& isset($this->blogData['post_info'][$post_index])
			&& isset($this->blogData['post_info'][$post_index]['comments']) ){

				$blog_comments = '<span class="simple_blog_comments">';
				if( $cacheable ){
					$blog_comments .= gpOutput::SelectText('Comments');
				}else{
					$blog_comments .= gpOutput::GetAddonText('Comments');
				}
				$blog_comments .= ': '.$this->blogData['post_info'][$post_index]['comments'];
				$blog_comments .= '</span>';
		}


		$format = '{header} <div class="simple_blog_info"> {blog_info} {separator} {blog_date} {separator} {blog_comments} </div>';
		$search = array('{header}','{blog_info}','{blog_date}','{blog_comments}');
		$replace = array($header, $blog_info, $blog_date, $blog_comments);

		$result = str_replace($search,$replace,$format);


		$reg = '#\{empty_blog_piece\}(\s*)\{separator\}#';
		$result = preg_replace($reg,'\1',$result);

		$reg = '#\{separator\}(\s*){empty_blog_piece\}#';
		$result = preg_replace($reg,'\1',$result);

		echo str_replace('{separator}',$this->blogData['subtitle_separator'],$result);
	}



	/**
	 * Update twitter status
	 * Not updated for oauth
	 * @deprecated
	 */
	function UpdateThirdParty($post_index,$title,$message){

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = 'http://'.$_SERVER['HTTP_HOST'];
		}else{
			$server = 'http://'.$_SERVER['SERVER_NAME'];
		}
		$link = $server.common::GetUrl('Special_Blog','id='.$post_index,false);

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
			return mb_ereg_replace('_',' ',$str);
		}
		return str_replace('_',' ',$str);
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

		$this->blogData['post_info'][$post_index]['comments'] = count($data);
		$this->SaveIndex();

		//clear comments cache
		$cache_file = $this->addonPathData.'/comments/cache.txt';
		if( file_exists($cache_file) ){
			unlink($cache_file);
		}

		return true;
	}

}


