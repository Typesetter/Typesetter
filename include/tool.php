<?php


namespace gp{

	defined('is_running') or die('Not an entry point...');

	class tool{


		/**
		 * Return the type of response was requested by the client
		 * @since 3.5b2
		 * @return string
		 */
		public static function RequestType(){
			if( isset($_REQUEST['gpreq']) ){
				switch($_REQUEST['gpreq']){
					case 'body':
					case 'flush':
					case 'json':
					case 'content':
					case 'admin';
					return $_REQUEST['gpreq'];
				}
			}
			return 'template';
		}


		/**
		 * Send a 304 Not Modified Response to the client if HTTP_IF_NONE_MATCH matched $etag and headers have not already been sent
		 * Othewise, send the etag
		 * @param string $etag The calculated etag for the current page
		 *
		 */
		public static function Send304($etag){
			global $config;

			if( !$config['etag_headers'] ) return;

			if( headers_sent() ) return;

			//always send the etag
			header('ETag: "'.$etag.'"');

			if( empty($_SERVER['HTTP_IF_NONE_MATCH'])
				|| trim($_SERVER['HTTP_IF_NONE_MATCH'],'"') != $etag ){
					return;
			}

			//don't use ob_get_level() in while loop to prevent endless loops;
			$level = ob_get_level();
			while( $level > 0 ){
				@ob_end_clean();
				$level--;
			}

			// 304 should not have a response body or Content-Length header
			//header('Not Modified',true,304);
			self::status_header(304,'Not Modified');
			header('Connection: close');
			exit();
		}


		/**
		 * Set HTTP status header.
		 * Modified From Wordpress
		 *
		 * @since 2.3.3
		 * @uses apply_filters() Calls 'status_header' on status header string, HTTP
		 *		HTTP code, HTTP code description, and protocol string as separate
		 *		parameters.
		 *
		 * @param int $header HTTP status code
		 * @param string $text HTTP status
		 * @return unknown
		 */
		public static function status_header( $header, $text ) {

			$protocol = '';
			if( isset($_SERVER['SERVER_PROTOCOL']) ){
				$protocol = $_SERVER['SERVER_PROTOCOL'];
			}
			if( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ){
				$protocol = 'HTTP/1.0';
			}

			$status_header = "$protocol $header $text";
			return @header( $status_header, true, $header );
		}

		public static function GenEtag(){
			global $dirPrefix, $dataDir;
			$etag = '';
			$args = func_get_args();
			$args[] = $dataDir.$dirPrefix;
			foreach($args as $arg){
				if( !ctype_digit($arg) ){
					$arg = crc32( $arg );
					$arg = sprintf("%u\n", $arg );
				}
				$etag .= base_convert( $arg, 10, 36);
			}
			return $etag;
		}


		/**
		 * Generate an etag from the filemtime and filesize of each file
		 * @param array $files
		 *
		 */
		public static function FilesEtag( $files ){
			$modified = 0;
			$content_length = 0;
			foreach($files as $file ){
				$content_length += @filesize( $file );
				$modified = max($modified, @filemtime($file) );
			}

			return self::GenEtag( $modified, $content_length );
		}


		public static function CheckTheme(){
			global $page;
			if( $page->theme_name === false ){
				$page->SetTheme();
			}
		}

		/**
		 * Return an array of information about the layout
		 * @param string $layout The layout key
		 * @param bool $check_existence Whether or not to check for the existence of the template.php file
		 *
		 */
		public static function LayoutInfo( $layout, $check_existence = true ){
			global $gpLayouts,$dataDir;

			if( !isset($gpLayouts[$layout]) ){
				return false;
			}

			$layout_info = $gpLayouts[$layout];
			$layout_info += array('is_addon'=>false);
			$layout_info['theme_name'] = self::DirName($layout_info['theme']);
			$layout_info['theme_color'] = basename($layout_info['theme']);

			$relative = '/themes/';
			if( $layout_info['is_addon'] ){
				$relative = '/data/_themes/';
			}
			$layout_info['path'] = $relative.$layout_info['theme'];

			$layout_info['dir'] = $dataDir.$relative.$layout_info['theme_name'];
			if( $check_existence && !file_exists($layout_info['dir'].'/template.php') ){
				return false;
			}

			return $layout_info;
		}



		/*
		 *
		 *
		 * Entry Functions
		 *
		 *
		 */

		public static function EntryPoint($level=0,$expecting='index.php',$sessions=true){

			self::CheckRequest();

			clearstatcache();

			$ob_gzhandler = false;
			if( !self::IniGet('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') ){
				@ob_start( 'ob_gzhandler' ); //ini_get() does not always work for this test
				$ob_gzhandler = true;
			}


			self::SetGlobalPaths($level,$expecting);
			spl_autoload_register( array('\\gp\\tool','Autoload') );


			includeFile('tool/functions.php');
			if( $sessions ){
				ob_start(array('\\gp\\tool\\Output','BufferOut'));
			}elseif( !$ob_gzhandler ){
				ob_start();
			}

			self::RequestLevel();
			self::GetConfig();
			self::SetLinkPrefix();
			self::SetCookieArgs();
			if( $sessions ){
				self::sessions();
			}
		}


		/**
		 * Setup SPL Autoloading
		 *
		 */
		public static function Autoload($class){
			global $config, $dataDir;

			$class		= trim($class,'\\');
			$parts		= explode('\\',$class);
			$part_0		= array_shift($parts);


			if( !$parts ){
				return;
			}

			//gp namespace
			if( $part_0 === 'gp' ){
				$path	= $dataDir.'/include/'.implode('/',$parts).'.php';
				if( file_exists($path) ){
					include_once( $path );
				}else{
					trigger_error('Autoload for gp namespace failed. Class: '.$class.' path: '.$path);
				}
				return;
			}

			//look for addon namespace
			if( $part_0 === 'Addon' ){

				$namespace = array_shift($parts);
				if( !$parts ){
					return;
				}

				foreach($config['addons'] as $addon_key => $addon){
					if( isset($addon['Namespace']) && $addon['Namespace'] == $namespace ){


						\gp\tool\Plugins::SetDataFolder($addon_key);
						$path			= \gp\tool\Plugins::$current['code_folder_full'].'/'.implode('/',$parts).'.php';

						if( file_exists($path) ){
							include_once($path);
						}else{
							trigger_error('Autoload for addon namespace failed. Class: '.$class.' path: '.$path);
						}

						\gp\tool\Plugins::ClearDataFolder();
					}
				}
				return;
			}

			//thirdparty
			$path = $dataDir.'/include/thirdparty/'.str_replace('\\','/',$class).'.php';
			if( file_exists($path) ){
				include_once($path);
			}
		}


		/**
		 * Reject Invalid Requests
		 *
		 */
		public static function CheckRequest(){

			if( count($_POST) == 0 ){
				return;
			}


			if( !isset($_SERVER['CONTENT_LENGTH']) ){
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				header('Retry-After: 300');//300 seconds
				die();
			}


			if( function_exists('getallheaders') ){

				$headers = getallheaders();
				if( !isset($headers['Content-Length']) ){
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 300');//300 seconds
					die();
				}

			}
		}

		/**
		 * @deprectated
		 */
		public static function gpInstalled(){}

		public static function SetGlobalPaths($DirectoriesAway,$expecting){
			global $dataDir, $dirPrefix, $rootDir;

			$rootDir = self::DirName( __FILE__, 2 );

			// dataDir, make sure it contains $expecting. Some servers using cgi do not set this properly
			// required for the Multi-Site plugin
			$dataDir = self::GetEnv('SCRIPT_FILENAME',$expecting);
			if( $dataDir !== false ){
				$dataDir = self::ReduceGlobalPath($dataDir,$DirectoriesAway);
			}else{
				$dataDir = $rootDir;
			}
			if( $dataDir == '/' ){
				$dataDir = '';
			}

			//$dirPrefix
			$dirPrefix = self::GetEnv('SCRIPT_NAME',$expecting);
			if( $dirPrefix === false ){
				$dirPrefix = self::GetEnv('PHP_SELF',$expecting);
			}

			//remove everything after $expecting, $dirPrefix can at times include the PATH_INFO
			$pos = strpos($dirPrefix,$expecting);
			$dirPrefix = substr($dirPrefix,0,$pos+strlen($expecting));

			$dirPrefix = self::ReduceGlobalPath($dirPrefix,$DirectoriesAway);
			if( $dirPrefix == '/' ){
				$dirPrefix = '';
			}
		}

		/**
		 * Convert backslashes to forward slashes
		 *
		 */
		public static function WinPath($path){
			return str_replace('\\','/',$path);
		}

		/**
		 * Returns parent directory's path with forward slashes
		 * php's dirname() method may change slashes from / to \
		 *
		 */
		public static function DirName( $path, $dirs = 1 ){
			for($i=0;$i<$dirs;$i++){
				$path = dirname($path);
			}
			return self::WinPath( $path );
		}

		/**
		 * Determine if this installation is supressing index.php in urls or not
		 *
		 */
		public static function SetLinkPrefix(){
			global $linkPrefix, $dirPrefix, $config;

			$linkPrefix = $dirPrefix;

			// gp_rewrite = 'On' and gp_rewrite = 'gpuniq' are deprecated since 4.1
			// gp_rewrite = bool will still be used internally
			if( isset($_SERVER['gp_rewrite']) ){
				if( $_SERVER['gp_rewrite'] === true || $_SERVER['gp_rewrite'] == 'On' ){
					$_SERVER['gp_rewrite'] = true;
				}elseif( $_SERVER['gp_rewrite'] == @substr($config['gpuniq'],0,7) ){
					$_SERVER['gp_rewrite'] = true;
				}

			}elseif( isset($_REQUEST['gp_rewrite']) ){
				$_SERVER['gp_rewrite'] = true;

			// gp_indexphp is deprecated since 4.1
			}elseif( defined('gp_indexphp') ){

				if( gp_indexphp === false ){
					$_SERVER['gp_rewrite'] = true;
				}

			}

			unset($_GET['gp_rewrite']);
			unset($_REQUEST['gp_rewrite']);

			if( !isset($_SERVER['gp_rewrite']) ){
				$_SERVER['gp_rewrite'] = false;
			}

			if( !$_SERVER['gp_rewrite'] ){
				$linkPrefix .= '/index.php';
			}
		}

		/**
		 * Get the environment variable and make sure it contains an expected value
		 *
		 * @param string $var The key of the requested environment variable
		 * @param string $expected Optional string that is expected as part of the environment variable value
		 *
		 * @return mixed Returns false if $expected is not found, otherwise it returns the environment value.
		 *
		 */
		public static function GetEnv($var,$expecting=false){
			$value = false;
			if( isset($_SERVER[$var]) ){
				$value = $_SERVER[$var];
			}else{
				$value = getenv($var);
			}
			if( $expecting && strpos($value,$expecting) === false ){
				return false;
			}
			return $value;
		}

		/**
		 * Get the ini value and return a boolean casted value when appropriate: On, Off, 1, 0, True, False, Yes, No
		 *
		 */
		public static function IniGet($key){
			$value = ini_get($key);
			if( empty($value) ){
				return false;
			}

			$lower_value = trim(strtolower($value));
			switch($lower_value){
				case 'true':
				case 'yes':
				case 'on':
				case '1':
				return true;

				case 'false':
				case 'no':
				case 'off':
				case '0':
				return false;
			}

			return $value;
		}


		public static function ReduceGlobalPath($path,$DirectoriesAway){
			return self::DirName($path,$DirectoriesAway+1);
		}



		//use dirPrefix to find requested level
		public static function RequestLevel(){
			global $dirPrefixRel,$dirPrefix;

			$path = $_SERVER['REQUEST_URI'];

			//strip the query string.. in case it contains "/"
			$pos = mb_strpos($path,'?');
			if( $pos > 0 ){
				$path =  mb_substr($path,0,$pos);
			}

			//dirPrefix will be percent-decoded
			$path = rawurldecode($path); //%20 ...

			if( !empty($dirPrefix) ){
				$pos = mb_strpos($path,$dirPrefix);
				if( $pos !== false ){
					$path = mb_substr($path,$pos+mb_strlen($dirPrefix));
				}
			}

			$path = ltrim($path,'/');
			$count = substr_count($path,'/');
			if( $count == 0 ){
				$dirPrefixRel = '.';
			}else{
				$dirPrefixRel = str_repeat('../',$count);
				$dirPrefixRel = rtrim($dirPrefixRel,'/');//GetDir() arguments always start with /
			}
		}



		/**
		 * Escape ampersands in hyperlink attributes and other html tag attributes
		 *
		 * @param string $str The string value of an html attribute
		 * @return string The escaped string
		 */
		public static function Ampersands($str){
			return preg_replace('/&(?![#a-zA-Z0-9]{2,9};)/S','&amp;',$str);
		}


		/**
		 * Similar to htmlspecialchars, but designed for labels
		 * Does not convert existing ampersands "&"
		 *
		 */
		public static function LabelSpecialChars($string){
			return str_replace( array('<','>','"',"'"), array('&lt;','&gt;','&quot;','&#39;') , $string);
		}


		/**
		 * Return an html hyperlink
		 *
		 * @param string $href The href value relative to the installation root (without index.php)
		 * @param string $label Text or html to be displayed within the hyperlink
		 * @param string $query Optional query to be used with the href
		 * @param string|array $attr Optional string of attributes like title=".." and class=".."
		 * @param mixed $nonce_action If false, no nonce will be added to the query. Given a string, it will be used as the first argument in \gp\tool::new_nonce()
		 *
		 * @return string The formatted html hyperlink
		 */
		public static function Link($href='',$label='',$query='',$attr='',$nonce_action=false){
			return '<a href="'.self::GetUrl($href,$query,true,$nonce_action).'" '.self::LinkAttr($attr,$label).'>'.self::Ampersands($label).'</a>';
		}


		/**
		 * @param string|array $attr
		 * @param string $label
		 */
		public static function LinkAttr($attr='',$label=''){
			$string = '';
			$has_title = false;
			if( is_array($attr) ){
				$attr = array_change_key_case($attr);
				$has_title = isset($attr['title']);
				if( isset($attr['name']) && !isset($attr['data-cmd']) ){
					$attr['data-cmd'] = $attr['name'];
					unset($attr['name']);
				}

				if( isset($attr['data-cmd']) ){
					switch( $attr['data-cmd'] ){
						case 'creq':
						case 'cnreq':
						case 'postlink':
							$attr['data-nonce'] = self::new_nonce('post',true);
						break;
					}
				}
				foreach($attr as $attr_name => $attr_value){
					$string .= ' '.$attr_name.'="'.htmlspecialchars($attr_value,ENT_COMPAT,'UTF-8',false).'"';
				}
			}else{
				$string = $attr;
				if( strpos($attr,'title="') !== false){
					$has_title = true;
				}

				// backwards compatibility hack to be removed in future releases
				// @since 3.6
				if( strpos($string,'name="postlink"') !== false ){
					$string .= ' data-nonce="'.self::new_nonce('post',true).'"';

				// @since 4.1
				}elseif( strpos($string,'name="cnreq"') !== false || strpos($string,'name="creq"') !== false ){
					$string .= ' data-nonce="'.self::new_nonce('post',true).'"';
				}

			}

			if( !$has_title && !empty($label) ){
				$string .= ' title="'.self::Ampersands(strip_tags($label)).'" ';
			}

			return trim($string);
		}

		/**
		 * Return an html hyperlink for a page
		 *
		 * @param string $title The title of the page
		 * @return string The formatted html hyperlink
		 */
		public static function Link_Page($title=''){
			global $config, $gp_index;

			if( empty($title) && !empty($config['homepath']) ){
				$title = $config['homepath'];
			}

			$label = self::GetLabel($title);

			return self::Link($title,$label);
		}


		public static function GetUrl($href='',$query='',$ampersands=true,$nonce_action=false){
			global $linkPrefix, $config;

			$filtered = \gp\tool\Plugins::Filter('GetUrl',array(array($href,$query)));
			if( is_array($filtered) ){
				list($href,$query) = $filtered;
			}

			$href = self::SpecialHref($href);


			//home page link
			if( isset($config['homepath']) && $href == $config['homepath'] ){
				$href = $linkPrefix;
				if( !$_SERVER['gp_rewrite'] ){
					$href = self::DirName($href);
				}
				$href = rtrim($href,'/').'/';
			}else{
				$href = $linkPrefix.'/'.ltrim($href,'/');
			}

			$query = self::QueryEncode($query,$ampersands);

			if( $nonce_action ){
				$nonce = self::new_nonce($nonce_action);
				if( !empty($query) ){
					$query .= '&amp;'; //in the cases where $ampersands is false, nonces are not used
				}
				$query .= '_gpnonce='.$nonce;
			}
			if( !empty($query) ){
				$query = '?'.ltrim($query,'?');
			}

			return self::HrefEncode($href,$ampersands).$query;
		}

		//translate special pages from key to title
		public static function SpecialHref($href){
			global $gp_index;

			$href2 = '';
			$pos = mb_strpos($href,'/');
			if( $pos !== false ){
				$href2 = mb_substr($href,$pos);
				$href = mb_substr($href,0,$pos);
			}

			$lower = mb_strtolower($href);
			if( !isset($gp_index[$href])
					&& strpos($lower,'special_') === 0
					&& $index_title = self::IndexToTitle($lower)
					){
						$href = $index_title;
			}

			return $href.$href2;
		}

		/**
		 * RawUrlEncode but keeps the following characters: &, /, \
		 * Slash is needed for hierarchical links
		 * In case you'd like to learn about percent encoding: http://www.blooberry.com/indexdot/html/topics/urlencoding.htm
		 *
		 */
		public static function HrefEncode($href,$ampersands=true){
			$ampersand = '&';
			if( $ampersands ){
				$ampersand = '&amp;';
			}
			$href = rawurlencode($href);
			return str_replace( array('%26amp%3B','%26','%2F','%5C'),array($ampersand,$ampersand,'/','\\'),$href);
		}

		/**
		 * RawUrlEncode parts of the query string ( characters except & and = )
		 *
		 */
		public static function QueryEncode($query,$ampersands = true){

			if( empty($query) ){
				return '';
			}

			$query = str_replace('+','%20',$query);//in case urlencode() was used instead of rawurlencode()
			if( strpos($query,'&amp;') !== false ){
				$parts = explode('&amp;',$query);
			}else{
				$parts = explode('&',$query);
			}

			$ampersand = $query = '';
			foreach($parts as $part){
				if( strpos($part,'=') ){
					list($key,$value) = explode('=',$part,2);
					$query .= $ampersand.rawurlencode(rawurldecode($key)).'='.rawurlencode(rawurldecode($value));
				}else{
					$query .= $ampersand.rawurlencode(rawurldecode($part));
				}
				if( $ampersands ){
					$ampersand = '&amp;';
				}else{
					$ampersand = '&';
				}
			}
			return $query;
		}

		public static function AbsoluteLink($href,$label,$query='',$attr=''){

			if( strpos($attr,'title="') === false){
				$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
			}

			return '<a href="'.self::AbsoluteUrl($href,$query).'" '.$attr.'>'.self::Ampersands($label).'</a>';
		}

		public static function AbsoluteUrl($href='',$query='',$with_schema=true,$ampersands=true,$with_port=false){

			$server = self::ServerName(false, $with_port);
			if( $server === false ){
				return self::GetUrl($href,$query,$ampersands);
			}

			$schema = '';
			if( $with_schema ){
				$schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
			}

			return $schema.$server.self::GetUrl($href,$query,$ampersands);
		}

		/**
		 * Return ther server name
		 *
		 */
		public static function ServerName($strip_www = false, $with_port=false){

			$add_port = '';
			if( isset($_SERVER['SERVER_NAME']) ){
				$server = self::UrlChars($_SERVER['SERVER_NAME']);
				if( $with_port && isset($_SERVER['SERVER_PORT']) ){
					$port = $_SERVER['SERVER_PORT'];
					if( $port != 80 && $port != 443 ){
						$add_port = ':' . $port;
					}
				}
			}else{
				return false;
			}


			if( $strip_www && strpos($server,'www.') === 0 ){
				$server = substr($server,4);
			}

			return $server . $add_port;
		}

		public static function UrlChars($string){
			$string = str_replace( ' ', '%20', $string );
			return preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $string);
		}

		/**
		 * Get the full path of a physical file on the server
		 * The query string component of a path should not be included but will be protected from being encoded
		 *
		 */
		public static function GetDir($dir='',$ampersands = false){
			global $dirPrefix;

			$query = '';
			$pos = mb_strpos($dir,'?');
			if( $pos !== false ){
				$query = mb_substr($dir,$pos);
				$dir = mb_substr($dir,0,$pos);
			}
			$dir = $dirPrefix.'/'.ltrim($dir,'/');
			return self::HrefEncode($dir,$ampersands).$query;
		}


		/**
		 * Get the label for a page from it's index
		 * @param string $index
		 * @param bool $amp Whether or not to escape ampersand characters
		 */
		public static function GetLabelIndex($index=null,$amp=false){
			global $gp_titles,$langmessage;

			$info = array();
			if( isset($gp_titles[$index]) ){
				$info = $gp_titles[$index];
			}

			if( isset($info['label']) ){
				$return = $info['label'];

			}elseif( isset($info['lang_index']) ){
				$return = $langmessage[$info['lang_index']];

			}else{
				$return = self::IndexToTitle($index);
				$return = \gp\tool\Files::CleanLabel($return);
			}
			if( $amp ){
				return str_replace('&','&amp;',$return);
			}
			return $return;
		}

		/**
		 * Get the label for a page from it's title
		 * @param string $title
		 *
		 */
		public static function GetLabel($title=null){
			global $gp_titles, $gp_index, $langmessage;

			$return = false;
			if( isset($gp_index[$title]) ){
				$id = $gp_index[$title];
				$info =& $gp_titles[$id];

				if( isset($info['label']) ){
					$return = $info['label'];

				}elseif( isset($info['lang_index']) ){

					$return = $langmessage[$info['lang_index']];
				}
			}

			if( $return === false ){
				$return = \gp\tool\Files::CleanLabel($title);
			}

			return $return;
		}

		/**
		 * Get the browser title for a page
		 * @param string $title
		 *
		 */
		public static function GetBrowserTitle($title){
			global $gp_titles, $gp_index;

			if( !isset($gp_index[$title]) ){
				return false;
			}

			$index = $gp_index[$title];
			$title_info = $gp_titles[$index];

			if( isset($title_info['browser_title']) ){
				return $title_info['browser_title'];
			}

			$label = self::GetLabel($title);

			return strip_tags($label);
		}


		/**
		 * Add js and css components to the current web page
		 *
		 * @static
		 * @since 2.0b1
		 * @param string $names A comma separated list of ui components to include. Avail since 3.5.
		 */
		public static function LoadComponents( $names = ''){
			\gp\tool\Output::$components .= ','.$names.',';
			\gp\tool\Output::$components = str_replace(',,',',',\gp\tool\Output::$components);
		}


		/**
		 * Add gallery js and css to the <head> section of a page
		 *
		 */
		public static function ShowingGallery(){
			global $page, $config;
			static $showing = false;
			if( $showing ) return;
			$showing = true;

			self::AddColorBox();
			$css = \gp\tool\Plugins::OneFilter('Gallery_Style');
			if( $css === false ){

				if( !empty($config['gallery_legacy_style']) ){
					$page->css_user[] = '/include/css/legacy_gallery.css';
					return;
				}

				$page->css_user[] = '/include/css/default_gallery.css';
				self::LoadComponents('dotdotdot');
				$page->jQueryCode .= "\n".'$(".filetype-gallery .caption").dotdotdot({ watch : "window", callback : function(isTruncated,orgContent){ $(this).data("originalContent",orgContent); } });';
				// $page->head_script .= "\n".'$(window).load( function(){ $(".filetype-gallery .caption").dotdotdot({ watch : "window", callback : function(isTruncated,orgContent){ $(this).data("originalContent",orgContent); } }); });';
				if( \gp\tool::LoggedIn() ){
					$page->head_script 	.= "\n".'var gallery_editing_options = { legacy_style : false };';
					$page->jQueryCode 	.= "\n".'$(document).on("editor_area:loaded", function(){ $(".filetype-gallery .caption").trigger("destroy.dot") });';
				}

				return;
			}
			$page->head .= "\n".'<link type="text/css" media="screen" rel="stylesheet" href="'.$css.'" />';
		}

		/**
		 * Add js and css elements to the <head> section of a page
		 *
		 */
		public static function AddColorBox(){
			global $langmessage;
			static $init = false;

			if( $init ){
				return;
			}
			$init = true;

			\gp\tool\Output::$inline_vars['colorbox_lang'] = array('previous'=>$langmessage['Previous'],'next'=>$langmessage['Next'],'close'=>$langmessage['Close'],'caption'=>$langmessage['caption'],'current'=>sprintf($langmessage['Image_of'],'{current}','{total}')); //'Start Slideshow'=>'slideshowStart','Stop Slideshow'=>'slideshowStop'

			self::LoadComponents( 'colorbox' );
		}

		/**
		 * Set the $config array from /data/_site/config.php
		 *
		 */
		public static function GetConfig(){
			global $config, $gp_hooks;


			$config = \gp\tool\Files::Get('_site/config');

			if( !is_array($config) || !array_key_exists('gpversion',$config) ){
				self::stop();
			}


			//make sure defaults are set
			$config += array(
					'maximgarea'				=> '2073600',
					'preserve_icc_profiles'		=> true,		//5.1
					'preserve_image_metadata'	=> true,		//5.1
					'maxthumbsize'				=> '300',
					'maxthumbheight'			=> '',			//5.1
					'check_uploads'				=> false,
					'colorbox_style'			=> 'example1',
					'gallery_legacy_style'		=> true,
					'combinecss'				=> true,
					'combinejs'					=> true,
					'etag_headers'				=> true,
					'customlang'				=> array(),
					'showgplink'				=> true,
					'showsitemap'				=> true,
					'showlogin'					=> true,
					'auto_redir'				=> 90,			//2.5
					'history_limit'				=> min(gp_backup_limit,30),
					'resize_images'				=> true,		//3.5
					'addons'					=> array(),
					'themes'					=> array(),
					'gadgets'					=> array(),
					'passhash'					=> 'sha1',
					'hooks'						=> array(),
					'space_char'				=> '-',			//4.6
					'cdn'						=> '',
					);


			//cdn settings
			if( isset($config['jquery']) && $config['jquery'] != 'local' ){
				$config['cdn']   = 'CloudFlare';
				unset($config['jquery']);
			}


			//shahash deprecated 4.0
			if( isset($config['shahash']) && !$config['shahash'] ){
				$config['passhash'] = 'md5';
			}


			// default gadgets
			$config['gadgets']['Contact'] = array('class'=>'\\gp\\special\\ContactGadget');
			$config['gadgets']['Search'] = array('method'=>array('\\gp\\special\\Search','gadget'));


			foreach($config['hooks'] as $hook => $hook_info){
				if( isset($gp_hooks[$hook]) ){
					$gp_hooks[$hook] += $hook_info;
				}else{
					$gp_hooks[$hook] = $hook_info;
				}
			}

			self::GetLangFile();
			self::GetPagesPHP();


			//upgrade?
			if( version_compare($config['gpversion'],'2.3.4','<') ){
				new \gp\tool\Upgrade();
			}
		}


		/**
		 * Stop loading
		 * Check to see if the cms has already been installed
		 *
		 */
		public static function stop(){
			global $dataDir;

			if( !\gp\tool\Files::Exists($dataDir.'/data/_site/config.php') ){

				if( file_exists($dataDir.'/include/install/install.php') ){
					self::SetLinkPrefix();
					includeFile('install/install.php');
					die();
				}
			}

			die('<p>Notice: The site configuration did not load properly.</p>'
				.'<p>If you are the site administrator, you can troubleshoot the problem turning debugging "on" or bypass it by enabling '.CMS_NAME.' safe mode.</p>'
				.'<p>More information is available in the <a href="'.CMS_DOMAIN.'/Docs/Main/Troubleshooting">Documentation</a>.</p>'
				.self::ErrorBuffer(true,false)
				);
		}


		/**
		 * Set global variables ( $gp_index, $gp_titles, $gp_menu and $gpLayouts ) from _site/pages.php
		 *
		 */
		public static function GetPagesPHP(){
			global $gp_index, $gp_titles, $gp_menu, $gpLayouts, $config;
			$gp_index = array();


			$pages		= \gp\tool\Files::Get('_site/pages');


			//update for < 2.0a3
			if( array_key_exists('gpmenu',$pages)
				&& array_key_exists('gptitles',$pages)
				&& !array_key_exists('gp_titles',$pages)
				&& !array_key_exists('gp_menu',$pages) ){

				foreach($pages['gptitles'] as $title => $info){
					$index = self::NewFileIndex();
					$gp_index[$title] = $index;
					$gp_titles[$index] = $info;
				}

				foreach($pages['gpmenu'] as $title => $level){
					$index = $gp_index[$title];
					$gp_menu[$index] = array('level' => $level);
				}
				return;
			}

			$gpLayouts		= $pages['gpLayouts'];
			$gp_index		= $pages['gp_index'];
			$gp_titles		= $pages['gp_titles'];
			$gp_menu		= $pages['gp_menu'];

			if( !is_array($gp_menu) ){
				self::stop();
			}

			//update for 3.5,
			if( !isset($gp_titles['special_gpsearch']) ){
				$gp_titles['special_gpsearch'] = array();
				$gp_titles['special_gpsearch']['label'] = 'Search';
				$gp_titles['special_gpsearch']['type'] = 'special';
				$gp_index['Search'] = 'special_gpsearch'; //may overwrite special_search settings
			}

			//fix the gpmenu
			if( version_compare(\gp\tool\Files::$last_version,'3.0b1','<') ){
				$gp_menu = \gp\tool\Output::FixMenu($gp_menu);

				// fix gp_titles for 3.0+
				// just make sure any ampersands in the label are escaped
				foreach($gp_titles as $key => $value){
					if( isset($gp_titles[$key]['label']) ){
						$gp_titles[$key]['label'] = self::GetLabelIndex($key,true);
					}
				}
			}

			//title related configuration settings
			if( empty($config['homepath_key']) ){
				$config['homepath_key'] = key($gp_menu);
			}
			$config['homepath'] = self::IndexToTitle($config['homepath_key']);

		}


		/**
		 * Generate a new file index
		 * skip indexes that are just numeric
		 */
		public static function NewFileIndex(){
			global $gp_index, $gp_titles, $dataDir, $config;

			$num_index = 0;

			/*prevent reusing old indexes */
			if( count($gp_index) > 0 ){
				$max = count($gp_index);
				$title = end($gp_index);
				for($i = $max; $i > 0; $i--){
					$last_index = current($gp_index);
					$type = self::SpecialOrAdmin($title);
					if( $type === 'special' ){
						$title = prev($gp_index);
						continue;
					}
					$i = 0;
				}
				reset($gp_index);
				$num_index = base_convert($last_index,36,10);
				$num_index++;
			}

			do{
				$index = base_convert($num_index,10,36);
				$num_index++;


				//check backup dir
				$backup_dir = $dataDir.'/data/_backup/pages/'.$index;
				if( file_exists($backup_dir) ){
					$index = false;
					continue;
				}

				//check for page directory
				$draft_file	= $dataDir.'/data/_pages/'.substr($config['gpuniq'],0,7).'_'.$index;
				if( file_exists($draft_file) ){
					$index = false;
					continue;
				}

			}while( !$index || is_numeric($index) || isset($gp_titles[$index]) );

			return $index;
		}


		/**
		 * Return the title of file using the index
		 * Will return false for titles that are external links
		 * @param string $index The index of the file
		 */
		public static function IndexToTitle($index){
			global $gp_index;
			return array_search($index,$gp_index);
		}



		/**
		 * Traverse the $menu upwards looking for the parents of the a title given by it's index
		 * @param string $index The data index of the child title
		 * @return array
		 *
		 */
		public static function Parents($index,$menu){
			$parents = array();

			if( !isset($menu[$index]) || !isset($menu[$index]['level']) ){
				return $parents;
			}

			$checkLevel = $menu[$index]['level'];
			$menu_ids = array_keys($menu);
			$key = array_search($index,$menu_ids);
			for($i = ($key-1); $i >= 0; $i--){
				$id = $menu_ids[$i];

				//check the level
				$level = $menu[$id]['level'];
				if( $level >= $checkLevel ){
					continue;
				}
				$checkLevel = $level;

				$parents[] = $id;

				//no need to go further
				if( $level == 0 ){
					return $parents;
				}
			}
			return $parents;
		}

		/**
		 * Traverse the $menu and gather all the descendants of a title given by it's $index
		 * @param string $index The data index of the child title
		 * @param array $menu The menu to use to check for descendants
		 * @param bool $children_only Option to return a list of children instead of all descendants. Since 4.3
		 * @return array
		 */
		public static function Descendants( $index, $menu, $children_only = false){

			$titles = array();

			if( !isset($menu[$index]) || !isset($menu[$index]['level']) ){
				return $titles;
			}

			$start_level = $menu[$index]['level'];
			$menu_ids = array_keys($menu);
			$key = array_search($index,$menu_ids);
			for($i = $key+1; $i < count($menu); $i++){
				$id = $menu_ids[$i];
				$level = $menu[$id]['level'];

				if( $level <= $start_level ){
					return $titles;
				}

				if( !$children_only ){
					$titles[] = $id;
				}elseif( $level == $start_level +1 ){
					$titles[] = $id;
				}
			}
			return $titles;

		}


		/**
		 * Return the configuration value or default if it's not set
		 *
		 * @since 1.7
		 *
		 * @param string $key The key to the $config array
		 * @param mixed $default The value to return if $config[$key] is not set
		 * @return mixed
		 */
		public static function ConfigValue($key,$default=false){
			global $config;
			if( !isset($config[$key]) ){
				return $default;
			}
			return $config[$key];
		}

		/**
		 * Generate a random alphanumeric string of variable length
		 *
		 * @param int $len length of string to return
		 * @param bool $cases Whether or not to use upper and lowercase characters
		 */
		public static function RandomString($len = 40, $cases = true ){

			$string = 'abcdefghijklmnopqrstuvwxyz1234567890';
			if( $cases ){
				$string .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			}

			$string = str_repeat($string,round($len/2));
			$string = str_shuffle( $string );
			$start = mt_rand(1, (strlen($string)-$len));

			return substr($string,$start,$len);
		}

		/**
		 * Include the main.inc language file for $language
		 * Language files were renamed to main.inc for version 2.0.2
		 *
		 */
		public static function GetLangFile($file='main.inc',$language=false){
			global $dataDir, $config, $langmessage;


			$language	= $language ? $language : $config['language'];
			$path		= $dataDir.'/include/languages/'.$language.'.main.inc';

			if( !file_exists($path) ){
				$path	= $dataDir.'/include/languages/en.main.inc'; //default to en
			}

			include($path);
		}


		/**
		 * Determine if the $title is a special or admin page
		 * @param string $title
		 * @return mixed 'admin','special' or false
		 */
		public static function SpecialOrAdmin($title){
			global $gp_index,$gp_titles;

			$lower_title = strtolower($title);

			if( $lower_title === 'admin' ){
				return 'admin';
			}elseif( strpos($lower_title,'admin_') === 0 || strpos($lower_title,'admin/') === 0 ){
				return 'admin';
			}

			if( strpos($lower_title,'special_') === 0 ){
				return 'special';
			}


			$parts = explode('/',$title);
			do{
				$title = implode('/',$parts);
				if( isset($gp_index[$title]) ){
					$key = $gp_index[$title];
					$info = $gp_titles[$key];
					if( $info['type'] == 'special' ){
						return 'special';
					}
				}
				array_pop($parts);
			}while( count($parts) );

			return false;
		}


		/**
		 * Return the name of the page being requested based on $_SERVER['REQUEST_URI']
		 * May also redirect the request
		 *
		 * @return string The title to display based on the request uri
		 *
		 */
		public static function WhichPage(){
			global $config, $gp_menu;

			$path	= \gp\tool\Editing::Sanitize($_SERVER['REQUEST_URI']);
			$path	= self::CleanRequest($path);

			$pos = mb_strpos($path,'?');
			if( $pos !== false ){
				$path = mb_substr($path,0,$pos);
			}

			$path = \gp\tool\Plugins::Filter('WhichPage',array($path));

			//redirect if an "external link" is the first entry of the main menu
			if( empty($path) && isset($gp_menu[$config['homepath_key']]) ){
				$homepath_info = $gp_menu[$config['homepath_key']];
				if( isset($homepath_info['url']) ){
					self::Redirect($homepath_info['url'],302);
				}
			}

			if( empty($path) ){
				return $config['homepath'];
			}

			//redirect to / for homepath request
			if( isset($config['homepath']) && $path == $config['homepath'] ){
				self::Redirect(self::GetUrl('','',false));
			}

			return $path;
		}


		/**
		 * Redirect the request to $path with http $code
		 *
		 * @param string $path url to redirect to
		 * @param string $code http redirect code: 301 or 302
		 *
		 */
		public static function Redirect($path,$code = 302){
			global $wbMessageBuffer, $gpAdmin;

			//store any messages for display after the redirect
			if( self::LoggedIn() && count($wbMessageBuffer) ){
				$gpAdmin['message_buffer'] = $wbMessageBuffer;
			}


			//prevent a cache from creating an infinite redirect
			Header( 'Last-Modified: ' . gmdate( 'D, j M Y H:i:s' ) . ' GMT' );
			Header( 'Expires: ' . gmdate( 'D, j M Y H:i:s', time() ) . ' GMT' );
			Header( 'Cache-Control: no-store, no-cache, must-revalidate' ); // HTTP/1.1
			Header( 'Cache-Control: post-check=0, pre-check=0', false );
			Header( 'Pragma: no-cache' ); // HTTP/1.0

			switch((int)$code){
				case 301:
					self::status_header(301,'Moved Permanently');
				break;
				case 302:
					self::status_header(302,'Found');
				break;
			}

			header('Location: '.$path);
			die();
		}


		/**
		 * Remove $dirPrefix and index.php from a path to get the page title
		 *
		 * @param string $path A full relative url like /install_dir/index.php/request_title
		 * @param string The request_title portion of $path
		 *
		 */
		public static function CleanRequest($path){
			global $dirPrefix;

			//use dirPrefix to find requested title
			if( !empty($dirPrefix) ){
				$pos = strpos($path,$dirPrefix);
				if( $pos !== false ){
					$path = substr($path,$pos+strlen($dirPrefix));
				}
			}


			//remove /index.php/
			$pos = strpos($path,'/index.php');
			if( $pos === 0 ){
				$path = substr($path,11);
			}

			$path = ltrim($path,'/');

			return $path;
		}


		/**
		 * Handle admin login/logout/session_start if admin session parameters exist
		 *
		 */
		public static function sessions(){

			//alternate sessions
			if( defined('gpcom_sessions') ){
				include(gpcom_sessions);
			}

			$cmd = '';
			if( isset($_GET['cmd']) && $_GET['cmd'] == 'logout' ){
				$cmd = 'logout';
			}elseif( isset($_POST['cmd']) && $_POST['cmd'] == 'login' ){
				$cmd = $_POST['cmd'];
			}elseif( count($_COOKIE) ){
				foreach($_COOKIE as $key => $value){
					if( strpos($key,'gpEasy_') === 0 ){
						$cmd = 'start';
						break;
					}
				}
			}

			if( empty($cmd) ){
				return;
			}

			\gp\tool\Session::Init();
		}


		/**
		 * Return true if an administrator is logged in
		 * @return bool
		 */
		public static function LoggedIn(){
			global $gpAdmin;

			$loggedin = false;
			if( isset($gpAdmin) && is_array($gpAdmin) ){
				$loggedin = true;
			}

			return \gp\tool\Plugins::Filter('LoggedIn',array($loggedin));
		}

		public static function new_nonce($action = 'none', $anon = false, $factor = 43200 ){
			global $gpAdmin;

			$nonce = $action;
			if( !$anon && !empty($gpAdmin['username']) ){
				$nonce .= $gpAdmin['username'];
			}

			return self::nonce_hash($nonce, 0, $factor );
		}


		/**
		 * Verify a nonce ($check_nonce)
		 *
		 * @param string $action Should be the same $action that is passed to new_nonce()
		 * @param mixed $check_nonce The user submitted nonce or false if $_REQUEST['_gpnonce'] can be used
		 * @param bool $anon True if the nonce is being used for anonymous users
		 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
		 * @return mixed Return false if the $check_nonce did not pass. 1 or 2 if it passes.
		 *
		 */
		public static function verify_nonce($action = 'none', $check_nonce = false, $anon = false, $factor = 43200 ){
			global $gpAdmin;

			if( $check_nonce === false ){
				$check_nonce =& $_REQUEST['_gpnonce'];
			}

			if( empty($check_nonce) ){
				return false;
			}

			$nonce = $action;
			if( !$anon ){
				if( empty($gpAdmin['username']) ){
					return false;
				}
				$nonce .= $gpAdmin['username'];
			}

			// Nonce generated 0-12 hours ago
			if( self::nonce_hash( $nonce, 0, $factor ) == $check_nonce ){
				return 1;
			}

			// Nonce generated 12-24 hours ago
			if( self::nonce_hash( $nonce, 1, $factor ) == $check_nonce ){
				return 2;
			}

			// Invalid nonce
			return false;
		}


		/**
		 * Generate a nonce hash
		 *
		 * @param string $nonce
		 * @param int $tick_offset
		 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
		 *
		 */
		public static function nonce_hash( $nonce, $tick_offset=0, $factor = 43200 ){
			global $config;

			$nonce_tick		= ceil( time() / $factor ) - $tick_offset;
			$nonce			= $nonce.$config['gpuniq'].$nonce_tick;


			//nonces before version 5.0
			if( gp_nonce_algo === 'legacy' ){
				return substr( md5($nonce), -12, 10);
			}

			return \gp\tool::hash($nonce,gp_nonce_algo,2);
		}


		/**
		 * Return the command sent by the user
		 * Don't use $_REQUEST here because SetCookieArgs() uses $_GET
		 *
		 */
		public static function GetCommand($type='cmd'){
			global $gpAdmin;

			if( is_array($gpAdmin) && isset($gpAdmin['locked']) && $gpAdmin['locked'] ){
				return false;
			}

			if( isset($_POST[$type]) ){
				return $_POST[$type];
			}

			if( isset($_GET[$type]) ){
				return $_GET[$type];
			}
			return false;
		}


		/**
		 * Used for receiving arguments from javascript without having to put variables in the $_GET request
		 * nice for things that shouldn't be repeated!
		 */
		public static function SetCookieArgs(){
			static $done = false;

			if( $done || !gp_cookie_cmd ){
				return;
			}

			self::RawCookies();

			//get cookie arguments
			if( empty($_COOKIE['cookie_cmd']) ){
				return;
			}
			$test = $_COOKIE['cookie_cmd'];
			if( $test{0} === '?' ){
				$test = substr($test,1);
			}

			parse_str($test,$cookie_args);
			if( !$cookie_args ){
				return;
			}


			//parse_str will overwrite values in $_GET/$_REQUEST
			$_GET = $cookie_args + $_GET;
			$_REQUEST = $cookie_args + $_REQUEST;

			//for requests with verification, we'll set $_POST
			if( !empty($_GET['verified']) ){
				$_POST = $cookie_args + $_POST;
			}

			$done = true;
		}


		/**
		 * Fix the $_COOKIE array if RAW_HTTP_COOKIE is set
		 * Some servers encrypt cookie values before sending them to the client
		 * Since cookies set by the client (with JavaScript) are not encrypted, the values won't be set in $_COOOKIE
		 *
		 */
		public static function RawCookies(){
			if( empty($_SERVER['RAW_HTTP_COOKIE']) ){
				return;
			}
			$csplit = explode(';', $_SERVER['RAW_HTTP_COOKIE']);
			foreach( $csplit as $pair ){
				if( !strpos($pair,'=') ){
					continue;
				}
				list($key,$value) = explode( '=', $pair );
				$key = rawurldecode(trim($key));
				if( !array_key_exists($key,$_COOKIE) ){
					$_COOKIE[$key] = rawurldecode(trim($value));
				}
			}
		}

		/**
		 * Output Javascript code to set variable defaults
		 *
		 */
		public static function JsStart(){
			global $linkPrefix;

			//default Variables
			\gp\tool\Output::$inline_vars['isadmin']			= false;
			\gp\tool\Output::$inline_vars['gpBase']				= rtrim(self::GetDir(''),'/');
			\gp\tool\Output::$inline_vars['post_nonce']			= '';
			\gp\tool\Output::$inline_vars['req_type']			= strtolower(htmlspecialchars($_SERVER['REQUEST_METHOD']));


			if( gpdebugjs ){
				if( is_string(gpdebugjs) ){
					\gp\tool\Output::$inline_vars['debugjs']	= 'send';
				}else{
					\gp\tool\Output::$inline_vars['debugjs']	= true;
				}
			}

			if( self::LoggedIn() ){

				\gp\tool\Output::$inline_vars['isadmin']		= true;
				\gp\tool\Output::$inline_vars['req_time']		= time();
				\gp\tool\Output::$inline_vars['gpBLink']		= self::HrefEncode($linkPrefix,false);
				\gp\tool\Output::$inline_vars['post_nonce']		= self::new_nonce('post',true);
				\gp\tool\Output::$inline_vars['gpFinderUrl']	= \gp\tool::GetUrl('Admin/Browser');

				\gp\tool\Session::GPUIVars();
			}

			echo 'var gplinks={},gpinputs={},gpresponse={}';
			foreach(\gp\tool\Output::$inline_vars as $key => $value){
				echo ','.$key.'='.json_encode($value);
			}
			echo ';';
		}


		/**
		 * Return the hash of $arg using the appropriate hashing function for the installation
		 *
		 * @param string $arg The string to be hashed
		 * @param string $algo The hashing algorithm to be used
		 * @param int $loops The number of times to loop the $arg through the algorithm
		 *
		 */
		public static function hash( $arg, $algo='sha512', $loops = 1000){
			$arg = trim($arg);

			switch($algo){

				//md5
				case 'md5':
				trigger_error('md5 should not be used, please reset your password');
				return md5($arg);

				//sha1
				case 'sha1':
				return sha1($arg);
			}


			//sha512: looped with dynamic salt
			for( $i=0; $i<$loops; $i++ ){

				$ints			= preg_replace('#[a-f]#','',$arg);
				$salt_start		= (int)substr($ints,0,1);
				$salt_len		= (int)substr($ints,2,1);
				$salt			= substr($arg,$salt_start,$salt_len);
				$arg			= hash($algo,$arg.$salt);
			}

			return $arg;
		}

		public static function AjaxWarning(){
			global $page,$langmessage;
			$page->ajaxReplace[] = array(0=>'admin_box_data',1=>'',2=>$langmessage['OOPS_Start_over']);
		}


		public static function IdUrl($request_cmd='cv'){
			global $config, $dataDir, $gpLayouts;

			//command
			$args['cmd'] = $request_cmd;

			$_SERVER += array('SERVER_SOFTWARE'=>'');


			//checkin
			$args['mdu']		= substr(md5($config['gpuniq']),0,20);
			$args['site']		= self::AbsoluteUrl(''); //keep full path for backwards compat
			$args['gpv']		= gpversion;
			$args['php']		= phpversion();
			$args['se']			= $_SERVER['SERVER_SOFTWARE'];
			$args['data']		= $dataDir;
			//$args['zlib'] = (int)function_exists('gzcompress');


			//service provider
			if( defined('service_provider_id') && is_numeric(service_provider_id) ){
				$args['provider'] = service_provider_id;
			}

			//testing
			if( defined('gp_unit_testing') ){
				$args['gp_unit_testing'] = 1;
			}

			//plugins
			$addon_ids = array();
			if( isset($config['addons']) && is_array($config['addons']) ){
				self::AddonIds($addon_ids, $config['addons']);
			}

			//themes
			if( isset($config['themes']) && is_array($config['themes']) ){
				self::AddonIds($addon_ids, $config['themes']);
			}

			//layouts
			if( is_array($gpLayouts) ){
				foreach($gpLayouts as $layout_info){
					if( !isset($layout_info['addon_id']) ){
						continue;
					}
					$addon_ids[] = $layout_info['addon_id'];
				}
			}

			$addon_ids		= array_unique($addon_ids);
			$args['as']		= implode('-',$addon_ids);

			return addon_browse_path.'/Resources?' . http_build_query($args,'','&');
		}


		public static function AddonIds( &$addon_ids, $array ){

			foreach($array as $addon_info){
				if( !isset($addon_info['id']) ){
					continue;
				}
				$addon_id = $addon_info['id'];
				if( isset($addon_info['order']) ){
					$addon_id .= '.'.$addon_info['order'];
				}
				$addon_ids[] = $addon_id;
			}
		}


		/**
		 * Used to send error reports without affecting the display of a page
		 *
		 */
		public static function IdReq($img_path,$jquery = true){
			global $page;

			//using jquery asynchronously doesn't affect page loading
			//error function defined to prevent the default error function in main.js from firing
			if( $jquery ){
				$page->head_script .= '$.ajax('.json_encode($img_path).',{error:function(){}, dataType: "jsonp"});';
				return;
			}

			return '<img src="'.self::Ampersands($img_path).'" height="1" width="1" alt="" style="border:0 none !important;height:1px !important;width:1px !important;padding:0 !important;margin:0 !important;"/>';
		}


		/**
		 * Return a debug message with link to online debug info
		 *
		 */
		public static function Debug($lang_key, $debug = array()){
			global $langmessage, $dataDir;


			//add backtrace info
			$backtrace = debug_backtrace();
			while( count($backtrace) > 0 && !empty($backtrace[0]['function']) && $backtrace[0]['function'] == 'Debug' ){
				array_shift($backtrace);
			}

			$debug['trace']			= array_intersect_key($backtrace[0], array('file'=>'','line'=>'','function'=>'','class'=>''));

			if( !empty($debug['trace']['file']) && !empty($dataDir) && strpos($debug['trace']['file'],$dataDir) === 0 ){
				$debug['trace']['file'] = substr($debug['trace']['file'], strlen($dataDir) );
			}


			//add php and cms info
			$debug['lang_key']		= $lang_key;
			$debug['phpversion']	= phpversion();
			$debug['gpversion']		= gpversion;
			$debug['Rewrite']		= $_SERVER['gp_rewrite'];
			$debug['Server']		= isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';


			//create string
			$debug	= json_encode($debug);
			$debug	= base64_encode($debug);
			$debug	= trim($debug,'=');
			$debug	= strtr($debug, '+/', '-_');

			$label	= isset($langmessage[$lang_key]) ? $langmessage[$lang_key] : $lang_key;

			return ' <span>'.$label.' <a href="'.debug_path.'?data='.$debug.'" target="_blank">More Info...</a></span>';
		}


		//only include error buffer when admin is logged in
		public static function ErrorBuffer($check_user = true, $jquery = true){
			global $wbErrorBuffer, $config, $dataDir, $rootDir;

			if( count($wbErrorBuffer) == 0 ) return;

			if( isset($config['Report_Errors']) && !$config['Report_Errors'] ) return;

			if( $check_user && !self::LoggedIn() ) return;

			$dataDir_len = strlen($dataDir);
			$rootDir_len = strlen($rootDir);
			$img_path = self::IdUrl('er');
			$i = 0;

			foreach($wbErrorBuffer as $error){

				//remove $dataDir or $rootDir from the filename
				$file_name = self::WinPath($error['ef'.$i]);
				if( $dataDir_len > 1 && strpos($file_name,$dataDir) === 0 ){
					$file_name = substr($file_name,$dataDir_len);
				}elseif( $rootDir_len > 1 && strpos($file_name,$rootDir) === 0 ){
					$file_name = substr($file_name,$rootDir_len);
				}
				$error['ef'.$i] = substr($file_name,-100);

				$new_path = $img_path.'&'.http_build_query($error,'','&');

				//maximum length of 2000 characters
				if( strlen($new_path) > 2000 ){
					break;
				}
				$img_path = $new_path;
				$i++;
			}

			return self::IdReq($img_path, $jquery);
		}


		/**
		 * Test if function exists.  Also handles case where function is disabled via Suhosin.
		 * Modified from: http://dev.piwik.org/trac/browser/trunk/plugins/Installation/Controller.php
		 *
		 * @param string $function Function name
		 * @return bool True if function exists (not disabled); False otherwise.
		 */
		public static function function_exists($function){
			$function = strtolower($function);

			// eval() is a language construct
			if( $function == 'eval' ){
				// does not check suhosin.executor.eval.whitelist (or blacklist)
				if( extension_loaded('suhosin') && self::IniGet('suhosin.executor.disable_eval') ){
					return false;
				}
				return true;
			}

			if( !function_exists($function) ){
				return false;
			}

			$blacklist = @ini_get('disable_functions');
			if( extension_loaded('suhosin') ){
				$blacklist .= ','.@ini_get('suhosin.executor.func.blacklist');
			}

			$blacklist = explode(',', $blacklist);
			$blacklist = array_map('trim',$blacklist);
			$blacklist = array_map('strtolower',$blacklist);
			if( in_array($function, $blacklist) ){
				return false;
			}

			return true;
		}

		/**
		 * A more functional JSON Encode function
		 * @param mixed $data
		 *
		 */
		public static function JsonEncode($data){

			$search		= array('<script','<\/script>');
			$repl		= array('<"+"script','<"+"\/script>');

			$type = gettype($data);
			switch( $type ){
				case 'NULL':
				return 'null';

				case 'boolean':
				return ($data ? 'true' : 'false');

				case 'integer':
				case 'double':
				case 'float':
				return $data;

				case 'string':
				if( gp_php53 ){
					$data		= htmlspecialchars_decode(htmlspecialchars($data, ENT_IGNORE, 'UTF-8'));
				}else{
					$data		= htmlspecialchars_decode(htmlspecialchars($data, ENT_SUBSTITUTE, 'UTF-8'));
				}
				$data = json_encode($data);
				return str_replace($search,$repl,$data);

				case 'object':
					$data = get_object_vars($data);
				case 'array':
					$output_index_count = 0;
					$output_indexed = array();
					$output_associative = array();
					foreach( $data as $key => $value ){
						$output_indexed[] = self::JsonEncode($value);
						$output_associative[] = self::JsonEncode($key) . ':' . self::JsonEncode($value);
						if( $output_index_count !== NULL && $output_index_count++ !== $key ){
							$output_index_count = NULL;
						}
					}
					if ($output_index_count !== NULL) {
						return '[' . implode(',', $output_indexed) . ']';
					} else {
						return '{' . implode(',', $output_associative) . '}';
					}
				default:
				return ''; // Not supported
			}
		}

		/**
		 * Date format funciton, uses formatting similar to php's strftime function
		 * http://php.net/manual/en/function.strftime.php
		 *
		 */
		public static function Date($format='',$time=null){

			if( empty($format) ){
				return '';
			}

			if( is_null($time) ){
				$time = time();
			}
			$time = (int)$time;

			$match_count = preg_match_all('#%+[^\s]#',$format,$matches,PREG_OFFSET_CAPTURE);
			if( $match_count ){
				$matches = array_reverse($matches[0]);
				foreach($matches as $match){
					$len = strlen($match[0]);
					if( $len%2 ){
						$replacement = strftime($match[0],$time);
					}else{
						$piece = substr($match[0],-2,2);
						switch($piece){
							case '%e':
								$replacement = strftime( substr($match[0],0,-2),$time).ltrim(strftime('%d',$time),'0');
							break;
							default:
								$replacement = strftime($match[0],$time);
							break;
						}
					}
					$format = substr_replace($format,$replacement,$match[1],strlen($match[0]));
				}
			}
			return $format;
		}



		/**
		 * Get an image's thumbnail path
		 *
		 */
		public static function ThumbnailPath($img){

			//already thumbnail path
			if( strpos($img,'/data/_uploaded/image/thumbnails') !== false ){
				return $img;
			}

			$dir_part = '/data/_uploaded/';
			$pos = strpos($img,$dir_part);
			if( $pos === false ){
				return $img;
			}

			// svg or not svg
			$nameParts = explode('.',$img);
			$type = array_pop($nameParts);
			$type = strtolower($type);
			if( strpos('svgz',$type) !== 0 ){
				$type = 'jpg';
			}

			return substr_replace($img,'/data/_uploaded/image/thumbnails/',$pos, strlen($dir_part) ).'.'.$type;
		}


		/**
		 * Generate a checksum for the $array
		 *
		 */
		public static function ArrayHash($array){
			return md5(json_encode($array) );
		}


		/**
		 * Return the key of an array if found
		 * Alert if $msg is not null
		 *
		 * @param string $key
		 * @param array $array
		 * @param string $msg
		 * @return mixed
		 */
		public static function ArrayKey( $key, $array, $msg = null ){
			global $langmessage;

			if( !isset($array[$key]) ){

				if( !is_null($msg) ){
					msg($langmessage['OOPS'].' '.$msg);
				}

				return false;
			}

			return array_search( $array[$key], $array, true);
		}


		/**
		 * Convert a string representation of a byte value to an number
		 * @param string $value
		 * @return int
		 */
		public static function getByteValue($value){

			if( is_numeric($value) ){
				return (int)$value;
			}

			$lastChar = strtolower(substr($value,-1));
			$num = (int)substr($value,0,-1);

			switch($lastChar){

				case 'g':
					$num *= 1024;
				case 'm':
					$num *= 1024;
				case 'k':
					$num *= 1024;
				break;
			}

			return $num;
		}


		/**
		 * Get the extension of the $file
		 *
		 */
		public static function Ext($file){
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			return strtolower($ext);
		}


		/**
		 * @deprecated 3.0
		 * use \gp\tool\Editing::UseCK();
		 */
		public static function UseFCK($contents,$name='gpcontent'){
			trigger_error('Deprecated Function');
			\gp\tool\Editing::UseCK($contents,$name);
		}

		/**
		 * @deprecated 3.0
		 * Use \gp\tool\Editing::UseCK();
		 */
		public static function UseCK($contents,$name='gpcontent',$options=array()){
			trigger_error('Deprecated Function');
			\gp\tool\Editing::UseCK($contents,$name,$options);
		}
	}
}

namespace{
	class common extends \gp\tool{}
}
