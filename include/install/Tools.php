<?php

namespace gp\install;

defined('is_running') or die('Not an entry point...');

class Tools{

	static $file_count = 0;

	/**
	 * Display the basic configuration options for installation:
	 *  - Website Title
	 *  - Username
	 *  - Email address (for password recovery)
	 *  - Password
	 *
	 */
	static function Form_UserDetails(){
		global $langmessage;

		$_POST += array('username'=>'','site_title'=>'My '.CMS_NAME,'email'=>'');

		echo '<tr><th colspan="2">'.$langmessage['configuration'].'</th></tr>';
		echo '<tr><td>'.$langmessage['Website_Title'].'</td><td><input type="text" class="text" name="site_title" value="'.htmlspecialchars($_POST['site_title']).'" required /></td></tr>';
		echo '<tr><td>'.$langmessage['email_address'].'</td><td><input type="email" class="text" name="email" value="'.htmlspecialchars($_POST['email']).'" required id="install_field_email" /></td></tr>';
		echo '<tr><td>'.$langmessage['Admin_Username'].'</td><td><input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" required id="install_field_username" /></td></tr>';
		echo '<tr><td>'.$langmessage['Admin_Password'].'</td><td><input type="password" class="text" name="password" value="" required /></td></tr>';
		echo '<tr><td>'.$langmessage['repeat_password'].'</td><td><input type="password" class="text" name="password1" value="" required /></td></tr>';
	}

	/**
	 * Display optional configuration options for installation
	 *  - jquery source (local or google)
	 *  - hide gplink
	 *
	 */
	static function Form_Configuration(){
		global $langmessage;

		echo '<tr><th colspan="2">';
		echo '<a href="javascript:toggleOptions()">'.$langmessage['more_options'].'...</a>';
		echo '</th></tr>';

		echo '<tbody id="config_options" style="display:none">';


		//combinejs
		echo '<tr><td>';
		echo $langmessage['combinejs'];
		echo '</td><td>';
		self::BooleanForm('combinejs',true);
		echo '</td></tr>';


		//combinejs
		echo '<tr><td>';
		echo $langmessage['combinecss'];
		echo '</td><td>';
		self::BooleanForm('combinecss',true);
		echo '</td></tr>';

		//combinejs
		echo '<tr><td>';
		echo $langmessage['etag_headers'];
		echo '</td><td>';
		self::BooleanForm('etag_headers',true);
		echo '</td></tr>';

		echo '</tbody>';

	}

	/**
	 * Display a checkbox for a boolean configuration option
	 *
 	 * @param string $key The configuration key being displayed
 	 * @param bool $default The default value if it hasn't already been set by the user
 	 *
	 */
	static function BooleanForm($key,$default=true){
		$checked = '';
		if( self::BooleanValue($key,$default) ){
			$checked = 'checked="checked"';
		}
		echo '<input type="hidden" name="'.$key.'" value="false" />';
		echo '<input type="checkbox" name="'.$key.'" value="true" '.$checked.'/>';
	}

	/**
	 * Determine if the boolean configuration option is true or false
	 *
 	 * @param string $key The configuration key
 	 * @param bool $default The default value if it hasn't already been set by the user
	 */
	static function BooleanValue($key,$default=true){
		if( !isset($_POST[$key]) ){
			return $default;
		}
		if( $_POST[$key] == 'true' ){
			return true;
		}
		return false;
	}


	//based on the user supplied values, make sure we can go forward with the installation

	static function gpInstall_Check(){
		global $langmessage;

		echo "\nInstall Check\n";

		$_POST += array('username'=>'','site_title'=>'My '.CMS_NAME,'email'=>'');

		$passed = array();
		$failed = array();

		//Email Address
			if( !(bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $_POST['email']) ){
				$failed[] = $langmessage['invalid_email'];
			}

		//Password
			if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
				$failed[] = $langmessage['invalid_password'];
			}else{
				$passed[] = $langmessage['PASSWORDS_MATCHED'];
			}

		//Username
			$test = str_replace(array('.','_'),array(''),$_POST['username'] );
			if( empty($test) || !ctype_alnum($test) ){
				$failed[] = $langmessage['invalid_username'];
			}else{
				$passed[] = $langmessage['Username_ok'];
			}


		if( count($passed) > 0 ){
			foreach($passed as $message){
				echo '<li class="passed">';
				echo $message;
				echo '</li>';
			}
		}

		if( count($failed) > 0 ){
			foreach($failed as $message){
				echo '<li class="failed">';
				echo $message;
				echo '</li>';
			}
			return false;
		}
		return true;
	}

	static function Install_Title(){
		$_POST += array( 'site_title' => '');
		$title = $_POST['site_title'];
		$title = htmlspecialchars($title);
		$title = trim($title);
		if( empty($title) ){
			return 'My '.CMS_NAME;
		}
		return $title;
	}

	static function Install_DataFiles_New($destination = false, $config = array(), $base_install = true ){
		global $langmessage;


		if( $destination === false ){
			$destination = $GLOBALS['dataDir'];
		}


		//set config variables
		//$config = array(); //because of ftp values

		$gpLayouts = array();


		//use bootswatch theme if server has enough memory
		$gpLayouts['default']['theme'] = 'Bootswatch_Scss/Flatly';
		$gpLayouts['default']['label'] = 'Bootswatch_Scss/Flatly';
		$gpLayouts['default']['color'] = '#93c47d';


		$_config['toemail']					= $_POST['email'];
		$_config['gpLayout']				= 'default';
		$_config['title']					= self::Install_Title();
		$_config['keywords']				= CMS_NAME.' , Easy CMS, Content Management, PHP, Free CMS, Website builder, Open Source';
		$_config['desc']					= 'A new '.CMS_NAME.' installation. You can change your site\'s description in the configuration.';
		$_config['timeoffset']				= '0';
		$_config['langeditor']				= 'inherit';
		$_config['dateformat']				= '%m/%d/%y - %I:%M %p';
		$_config['gpversion']				= gpversion;
		$_config['passhash']				= 'sha512';
		$_config['gpuniq']					= \gp\tool::RandomString(20);
		$_config['combinecss']				= self::BooleanValue('combinecss',true);
		$_config['combinejs']				= self::BooleanValue('combinejs',true);
		$_config['etag_headers'] 			= self::BooleanValue('etag_headers',true);
		$_config['gallery_legacy_style']	= false;
		$_config['language']				= 'en';
		$_config['addons']					= array();

		$config 							+= $_config;

		//directories
		\gp\tool\Files::CheckDir($destination.'/data/_uploaded/image');
		\gp\tool\Files::CheckDir($destination.'/data/_uploaded/media');
		\gp\tool\Files::CheckDir($destination.'/data/_uploaded/file');
		\gp\tool\Files::CheckDir($destination.'/data/_uploaded/flash');
		\gp\tool\Files::CheckDir($destination.'/data/_sessions');


		// gp_index
		$new_index = array();
		$new_index['Home'] = 'a';
		$new_index['Heading_Page'] = 'b';
		$new_index['Child_Page'] = 'd';
		$new_index['More'] = 'e';
		$new_index['About'] = 'f';
		$new_index['Contact'] = 'special_contact';
		$new_index['Site_Map'] = 'special_site_map';
		$new_index['Galleries'] = 'special_galleries';
		$new_index['Missing'] = 'special_missing';
		$new_index['Search'] = 'special_gpsearch';


		//	gpmenu
		$new_menu = array();
		$new_menu['a'] = array('level'=>0);
		$new_menu['b'] = array('level'=>0);
		$new_menu['c'] = array('level'=>1);
		$new_menu['d'] = array('level'=>1);
		$new_menu['e'] = array('level'=>0);
		$new_menu['f'] = array('level'=>1);
		$new_menu['special_contact'] = array('level'=>1);

		//	links
		$new_titles = array();
		$new_titles['a']['label'] = 'Home';
		$new_titles['a']['type'] = 'text';

		$new_titles['b']['label'] = 'Heading Page';
		$new_titles['b']['type'] = 'text';

		$new_titles['d']['label'] = 'Child Page';
		$new_titles['d']['type'] = 'text';

		$new_titles['e']['label'] = 'More';
		$new_titles['e']['type'] = 'text';

		$new_titles['f']['label'] = 'About';
		$new_titles['f']['type'] = 'text';

		$new_titles['special_contact']['lang_index'] = 'contact';
		$new_titles['special_contact']['type'] = 'special';

		$new_titles['special_site_map']['lang_index'] = 'site_map';
		$new_titles['special_site_map']['type'] = 'special';

		$new_titles['special_galleries']['lang_index'] = 'galleries';
		$new_titles['special_galleries']['type'] = 'special';

		$new_titles['special_missing']['label'] = 'Missing';
		$new_titles['special_missing']['type'] = 'special';

		$new_titles['special_gpsearch']['label'] = 'Search';
		$new_titles['special_gpsearch']['type'] = 'special';

		$pages = array();
		$pages['gp_index'] = $new_index;
		$pages['gp_menu'] = $new_menu;
		$pages['gp_titles'] = $new_titles;
		$pages['gpLayouts'] = $gpLayouts;

		echo '<li>';
		if( !\gp\tool\Files::SaveData($destination.'/data/_site/pages.php','pages',$pages) ){
			echo '<span class="failed">';
			//echo 'Could not save pages.php';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'pages.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		//echo 'Pages.php saved.';
		echo sprintf($langmessage['_SAVED'],'pages.php');
		echo '</span>';
		echo '</li>';



		// Home
		$content = '<h2>Welcome to Your '.CMS_NAME.' Powered Site!</h2>
		<p class="lead">Now that '.CMS_NAME.' is installed, you can start editing the content and customizing your site.</p>
		<div class="row">
		<div class="col-sm-6">

		<h3>Getting Started</h3>
		<hr/>
		<p>You are currently viewing the default home page of your website. Here\'s a quick description of how to edit this page.</p>
		<ol>
		<li>First make sure you&#39;re '.self::Install_Link_Content('Admin','logged in','file=Home').'.</li>
		<li>Then click the &quot;Edit&quot; link that appears when you move your mouse over the content.</li>
		<li>Your changes will be saved to a draft automatically. Click "Publish Draft" to make them live.</li>
		</ol>

		</div>
		<div class="col-sm-6">

		<h3>More Options</h3>
		<hr/>
		<ul>
		<li>Adding, renaming, deleting and organising your pages can all be done in the '.self::Install_Link_Content('Admin/Menu','Page Manager').'.</li>
		<li>Choose from a '.self::Install_Link_Content('Admin_Theme_Content','variety of themes').' to give your site a custom look.</li>
		<li>Then, you can '.self::Install_Link_Content('Admin_Theme_Content/Edit','add, remove and rearrange').' the content of your site without editing the html.</li>
		<li>Take a look at the Administrator Toolbar to access all the features of '.CMS_NAME.'.</li>
		</ul>

		</div>
		</div>

		<div class="row">
		<div class="col-sm-6">

		<h3>Online Resources</h3>
		<hr/>
		<p>'.CMS_READABLE_DOMAIN.' has a number of resources to help you do even more.</p>
		<ul>
		<li>Find more community developed <a href="'.CMS_DOMAIN.'/Themes" title="'.CMS_NAME.' Themes" rel="nofollow">themes</a> and <a href="'.CMS_DOMAIN.'/Plugins" title="'.CMS_NAME.' Plugin" rel="nofollow">plugins</a> to enhance your site.</li>
		<li>Get help in the <a href="'.CMS_DOMAIN.'/Forum" title="'.CMS_NAME.' Forum" rel="nofollow">'.CMS_NAME.' forum</a>.</li>
		<li>Show off your <a href="'.CMS_DOMAIN.'/Showcase" title="Sites Using '.CMS_NAME.'" rel="nofollow">'.CMS_NAME.' powered site</a> or list your <a href="'.CMS_DOMAIN.'/Providers" title="Businesses Using '.CMS_NAME.'" rel="nofollow">'.CMS_NAME.' related business</a>.</li>
		</ul>

		</div>
		<div class="col-sm-6">

		<h3>Git Social</h3>
		<hr/>
		<p>There are many ways to contribute to our project:</p>
		<ul>
		<li>Fork '.CMS_NAME.' on <a href="https://github.com/Typesetter/Typesetter" target="_blank" rel="nofollow">github</a>.</li>
		<li>Like us on <a href="https://www.facebook.com/Typesetter.cms" target="_blank" rel="nofollow">Facebook</a>.</li>
		<li>Follow us on <a href="https://twitter.com/TypesetterCMS" target="_blank" rel="nofollow">Twitter</a>.</li>
		</ul>

		</div>
		</div>
		';
		self::NewTitle( $destination, 'Home', $content, $config, $new_index);


		// Heading Page
		$content = '<h1>A Heading Page</h1>
		<li>'.self::Install_Link_Content('Child_Page','Child Page').'</li>
		</ul>';
		self::NewTitle( $destination, 'Heading_Page',$content, $config, $new_index);


		// Child Page
		$content = '<h1>A Child Page</h1><p>You can easily change the arrangement of all your pages using the '.self::Install_Link_Content('Admin/Menu','Page Manager').'.</p>';
		self::NewTitle( $destination, 'Child_Page',$content, $config, $new_index);

		// More
		$content = '<h1>More</h1>
		<ul><li>'.self::Install_Link_Content('About','About').'</li>
		<li>'.self::Install_Link_Content('Contact','Contact').'</li>
		</ul>';
		self::NewTitle( $destination, 'More',$content, $config, $new_index);


		// About
		$content = '<h1>About '.CMS_NAME.'</h1><p><a href="'.CMS_DOMAIN.'" title="'.CMS_READABLE_DOMAIN.'" rel="nofollow">'.CMS_NAME.'</a> is a complete Content Management System (CMS) that can help you create rich and flexible web sites with a simple and easy to use interface.</p>
		<h2>'.CMS_NAME.' How To</h2>
		<p>Learn how to <a href="'.CMS_DOMAIN.'/Docs/Main/Admin" title="'.CMS_NAME.' File Management" rel="nofollow">manage your files</a>,
		<a href="'.CMS_DOMAIN.'/Docs/Main/Creating%20Galleries" title="Creating Galleries in '.CMS_NAME.'" rel="nofollow">create galleries</a> and more in the
		<a href="'.CMS_DOMAIN.'/Docs/index.php/" title="'.CMS_NAME.' Documentation" rel="nofollow">'.CMS_NAME.' Documentation</a>.
		</p>

		<h2>'.CMS_NAME.' Features</h2>
		<ul>
		<li>True WYSIWYG (Using CKEditor)</li>
		<li>Galleries (Using ColorBox)</li>
		<li>SEO Friendly Links</li>
		<li>Free and Open Source (GPL)</li>
		<li>Runs on PHP</li>
		<li>File Upload Manager</li>
		<li>Drag \'n Drop Theme Content</li>
		<li>Deleted File Trash Can</li>
		<li>Multiple User Administration</li>
		<li>Flat File Storage</li>
		<li>Fast Page Loading</li>
		<li>Fast and Easy Installation</li>
		<li>reCaptcha for Contact Form</li>
		<li>HTML Tidy (when available)</li>
		</ul>';
		self::NewTitle( $destination, 'About',$content, $config, $new_index);

		//Side_Menu
		$file		= $destination.'/data/_extra/Side_Menu/page.php';
		$content	= '<h3>Join the '.CMS_NAME.' Community</h3>
		<p>Visit '.CMS_READABLE_DOMAIN.' to access the many <a href="'.CMS_DOMAIN.'/Resources" title="'.CMS_NAME.' Community Resources" rel="nofollow">available resources</a> to help you get the most out of our CMS.</p>
		<ul>
		<li><a href="'.CMS_DOMAIN.'/Themes" title="'.CMS_NAME.' Themes" rel="nofollow">Download Themes</a></li>
		<li><a href="'.CMS_DOMAIN.'/Plugins" title="'.CMS_NAME.' Plugin" rel="nofollow">Download Plugins</a></li>
		<li><a href="'.CMS_DOMAIN.'/Forum" title="'.CMS_NAME.' Forum" rel="nofollow">Get Help in the Forum</a></li>
		<li><a href="'.CMS_DOMAIN.'/Powered_by" title="Sites using '.CMS_NAME.'" rel="nofollow">Show off Your Site</a></li>
		<li><a href="'.CMS_DOMAIN.'/Resources" title="'.CMS_NAME.' Community Resources" rel="nofollow">And Much More...</a></li>
		</ul>
		<p class="sm">(Edit this content by clicking &quot;Edit&quot;, it&#39;s that easy!)</p>';
		self::NewExtra($file,$content);

		//Header
		$file		= $destination.'/data/_extra/Header/page.php';
		$content	= '<h1>'.$config['title'].'</h1>
		<h4>'.'The Fast and Easy CMS'.'</h4>';
		self::NewExtra($file,$content);

		//Footer
		$file		= $destination.'/data/_extra/Footer/page.php';
		$content	= '<h3><a href="'.CMS_DOMAIN.'/Our_CMS" title="Features of Our CMS" rel="nofollow">'.CMS_NAME.' Features</a></h3>
		<p>Easy to use True WYSIWYG Editing.</p>
		<p>Flat-file data storage and advanced resource management for fast websites.</p>
		<p>Community driven development</p>
		<p><a href="'.CMS_DOMAIN.'/Our_CMS" title="Features of Our CMS" rel="nofollow">And More...</a></p>
		<p>If you like '.CMS_NAME.', then you might also like
		<a href="http://lessphp.typesettercms.com" title="A Less to CSS compiler based on the official lesscss project" rel="nofollow">Less.php</a>,
		<a href="http://whatcms.org" title="What CMS? Find out what CMS a site is using" rel="nofollow">WhatCMS.org</a> and
		<a href="http://whichcms.org" title="Which CMS? Find out which CMS has the features you\'re looking for." rel="nofollow">WhichCMS.org</a>.
		</p>';
		self::NewExtra($file,$content);


		//Another example area
		$file		= $destination.'/data/_extra/Lorem/page.php';
		$content	= '<h3>Heading</h3>
		<p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>';
		self::NewExtra($file,$content);


		//contact html
		$file = $destination.'/data/_extra/Contact/page.php';
		self::NewExtra($file,'<h2>Contact Us</h2><p>Use the form below to contact us, and be sure to enter a valid email address if you want to hear back from us.</p>');


		//users
		echo '<li>';
		$user_info = array();
		$user_info['password']		= \gp\tool::hash($_POST['password'],'sha512');
		$user_info['passhash']		= 'sha512';
		$user_info['granted']		= 'all';
		$user_info['editing']		= 'all';
		$user_info['email']			= $_POST['email'];

		$users = array();
		$username = $_POST['username'];

		//log user in here to finish user_info
		if( $base_install ){
			gp_defined('gp_session_cookie',\gp\tool\Session::SessionCookie($config['gpuniq']));
			\gp\tool\Session::create($user_info, $username, $sessions);
		}
		$users[$username] = $user_info;

		if( !\gp\tool\Files::SaveData($destination.'/data/_site/users.php','users',$users) ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'users.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'users.php');
		echo '</span>';
		echo '</li>';



		//save config
		//not using SaveConfig() because $config is not global here
		echo '<li>';
		$config['file_count'] = self::$file_count;
		if( !\gp\tool\Files::SaveData($destination.'/data/_site/config.php','config',$config) ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'config.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'config.php');
		echo '</span>';
		echo '</li>';


		if( $base_install ){
			self::InstallHtaccess($destination,$config);
		}

		\gp\tool\Files::Unlock('write',gp_random);

		return true;
	}


	static function NewTitle( $dataDir, $title, $content, $config, $index ){

		$file = $dataDir.'/data/_pages/'.substr($config['gpuniq'],0,7).'_'.$index[$title].'/page.php';
		self::$file_count++;

		$file_sections = array();
		$file_sections[0] = array(
				'type' => 'text',
				'content' => $content
				);

		$meta_data = array(
			'file_number' => self::$file_count,
			'file_type' => 'text',
			);

		return \gp\tool\Files::SaveData($file,'file_sections',$file_sections,$meta_data);
	}

	static function NewExtra($file, $content){
		$extra_content = array(array('type'=>'text','content'=>$content));
		return \gp\tool\Files::SaveData($file,'file_sections',$extra_content);
	}


	/**
	 * attempt to create an htaccess file
	 * .htaccess creation only works for base_installations because of the $dirPrefix variable
	 * 		This is for the rewrite_rule and TestResponse() which uses AbsoluteUrl()
	 *
	 * @access public
	 * @static
	 * @since 1.7
	 *
	 * @param string $destination The root path of the installation
	 * @param array $config Current installation configuration
	 */
	static function InstallHtaccess($destination,$config){
		global $install_ftp_connection, $dirPrefix;

		//only proceed with save if we can test the results
		if( \gp\tool\RemoteGet::Test() === false ){
			return;
		}

		$GLOBALS['config']['homepath'] = false; //to prevent a warning from absoluteUrl()
		$file = $destination.'/.htaccess';

		$original_contents = null;
		if( file_exists($file) ){
			$original_contents = file_get_contents($file);
		}

		$contents = \gp\admin\Settings\Permalinks::Rewrite_Rules(true, $dirPrefix, $original_contents );

		if( !isset($config['useftp']) ){
			//echo 'not using ftp';
			$fp = @fopen($file,'wb');
			if( !$fp ){
				return;
			}

			@fwrite($fp,$contents);
			fclose($fp);
			@chmod($file,0666);

			//return .htaccess to original state
			if( !\gp\admin\Settings\Permalinks::TestResponse() ){
				if( $original_contents === false ){
					unlink($file);
				}else{
					$fp = @fopen($file,'wb');
					if( $fp ){
						@fwrite($fp,$original_contents);
						fclose($fp);
					}
				}
			}
			return;
		}


		//using ftp
		$file = $config['ftp_root'].'/.htaccess';

		$temp = tmpfile();
		if( !$temp ){
			return false;
		}

		fwrite($temp, $contents);
		fseek($temp, 0); //Skip back to the start of the file being written to
		@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
		fclose($temp);


		//return .htaccess to original state
		if( !\gp\admin\Settings\Permalinks::TestResponse() ){
			if( $original_contents === false ){
				@ftp_delete($install_ftp_connection, $file);
			}else{
				$temp = tmpfile();
				fwrite($temp,$original_contents);
				fseek($temp,0);
				@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
				fclose($temp);
			}
		}
	}

	function GetPathInfo(){
		$UsePathInfo =
			( strpos( php_sapi_name(), 'cgi' ) === false ) &&
			( strpos( php_sapi_name(), 'apache2filter' ) === false ) &&
			( strpos( php_sapi_name(), 'isapi' ) === false );

		return $UsePathInfo;
	}

	static function Install_Link_Content($href,$label,$query='',$attr=''){

		$query = str_replace('&','&amp;',$query);
		$href = str_replace('&','&amp;',$href);

		if( !empty($query) ){
			$query = '?'.$query;
		}

		return '<a href="$linkPrefix/'.$href.$query.'">'.$label.'</a>';
	}

}


