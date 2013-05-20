<?php
defined('is_running') or die('Not an entry point...');

/**
 * See gpconfig.php for these configuration options
 *
 */
gp_defined('gpdebug',false);
if( gpdebug ){
	error_reporting(E_ALL);
}
set_error_handler('showError');

gp_defined('gp_restrict_uploads',false);
gp_defined('gpdebugjs',gpdebug);
gp_defined('gpdebug_tools',false);
gp_defined('gptesting',false);
gp_defined('gptesting',false);
gp_defined('gp_cookie_cmd',true);
gp_defined('gp_browser_auth',false);
gp_defined('gp_require_encrypt',false);
gp_defined('gp_chmod_file',0666);
gp_defined('gp_chmod_dir',0755);
gp_defined('gp_index_filenames',true);
gp_defined('gp_safe_mode',false);
gp_defined('E_STRICT',2048);
gp_defined('E_RECOVERABLE_ERROR',4096);
gp_defined('E_DEPRECATED',8192);
gp_defined('E_USER_DEPRECATED',16384);
gp_defined('gpdebug_tools',false);
gp_defined('gp_backup_limit',10);
gp_defined('gp_write_lock_time',30);
//gp_defined('addon_browse_path','http://gpeasy.loc/index.php'); message('local browse path');
gp_defined('addon_browse_path','http://gpeasy.com/index.php');

define('gpversion','4.0');
define('gp_random',common::RandomString());


@ini_set( 'session.use_only_cookies', '1' );
@ini_set( 'default_charset', 'utf-8' );
@ini_set( 'html_errors', true );

if( function_exists('mb_internal_encoding') ){
	mb_internal_encoding('UTF-8');
}

//see /var/www/others/mediawiki-1.15.0/languages/Names.php
$languages = array(
	'ar' => 'العربية',			# Arabic
	'bg' => 'Български',		# Bulgarian
	'ca' => 'Català',
	'cs' => 'Česky',			# Czech
	'da' => 'Dansk',
	'de' => 'Deutsch',
	'el' => 'Ελληνικά',		# Greek
	'en' => 'English',
	'es' => 'Español',
	'fi' => 'Suomi',			# Finnish
	'fr' => 'Français',
	'gl' => 'Galego',			# Galician
	'hu' => 'Magyar',			# Hungarian
	'it' => 'Italiano',
	'ja' => '日本語',			# Japanese
	'lt' => 'Lietuvių',		# Lithuanian
	'nl' => 'Nederlands',		# Dutch
	'no' => 'Norsk',			# Norwegian
	'pl' => 'Polski',			# Polish
	'pt' => 'Português',
	'pt-br' => 'Português do Brasil',
	'ru' => 'Русский',		# Russian
	'sk' => 'Slovenčina',		# Slovak
	'sl' => 'Slovenščina',	# Slovenian
	'sv' => 'Svenska',		# Swedish
	'tr' => 'Türkçe',			# Turkish
	'uk' => 'Українська',		# Ukrainian
	'zh' => '中文',			# (Zhōng Wén) - Chinese
	);



$gpversion = gpversion; // @deprecated 3.5b2
$addonDataFolder = $addonCodeFolder = false;//deprecated
$addonPathData = $addonPathCode = false;
$checkFileIndex = true;
$wbErrorBuffer = $gp_not_writable = array();



/* from wordpress
 * wp-settings.php
 * see also classes.php
 */
// Fix for IIS, which doesn't set REQUEST_URI
if ( empty( $_SERVER['REQUEST_URI'] ) ) {

	// IIS Mod-Rewrite
	if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	}

	// IIS Isapi_Rewrite
	else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];

	}else{

		// Use ORIG_PATH_INFO if there is no PATH_INFO
		if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) ){
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
		}


		// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
		if ( isset($_SERVER['PATH_INFO']) ) {
			if( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] ){
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			}else{
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		}

		// Append the query string if it exists and isn't null
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
}

// Set default timezone in PHP 5.
if ( function_exists( 'date_default_timezone_set' ) )
	date_default_timezone_set( 'UTC' );





/**
 * Error Handling
 * Display the error and a debug_backtrace if gpdebug is not false
 * If gpdebug is an email address, send the error message to the address
 * @return false Always returns false so the standard PHP error handler is also used
 *
 */
function showError($errno, $errmsg, $filename, $linenum, $vars){
	global $wbErrorBuffer, $addon_current_id, $page, $addon_current_version, $config, $addonFolderName;
	static $reported = array();
	$report_error = true;


	$errortype = array (
				E_ERROR				=> 'Fatal Error',
				E_WARNING			=> 'Warning',
				E_PARSE				=> 'Parsing Error',
				E_NOTICE 			=> 'Notice',
				E_CORE_ERROR		=> 'Core Error',
				E_CORE_WARNING 		=> 'Core Warning',
				E_COMPILE_ERROR		=> 'Compile Error',
				E_COMPILE_WARNING 	=> 'Compile Warning',
				E_USER_ERROR		=> 'User Error',
				E_USER_WARNING 		=> 'User Warning',
				E_USER_NOTICE		=> 'User Notice',
				E_STRICT			=> 'Strict Notice',
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED		=> 'Deprecated',
				E_USER_DEPRECATED	=> 'User Deprecated',
			 );


	// since we supported php 4.3+, there may be a lot of strict errors
	if( $errno === E_STRICT ){
		//$report_error = false;
		return;
	}


	// for functions prepended with @ symbol to suppress errors
	$error_reporting = error_reporting();
	if( $error_reporting === 0 ){
		$report_error = false;

		//make sure the error is logged
		//error_log('PHP '.$errortype[$errno].':  '.$errmsg.' in '.$filename.' on line '.$linenum);

		if( gpdebug === false ){
			return false;
		}
		return false;
	}

	//get the backtrace and function where the error was thrown
	$backtrace = debug_backtrace();
	//remove showError() from backtrace
	if( strtolower($backtrace[0]['function']) == 'showerror' ){
		@array_shift($backtrace);
	}

	//record one error per function and only record the error once per request
	if( isset($backtrace[0]['function']) ){
		$uniq = $filename.$backtrace[0]['function'];
	}else{
		$uniq = $filename.$linenum;
	}
	if( isset($reported[$uniq]) ){
		return false;
	}
	$reported[$uniq] = true;

	if( gpdebug === false ){
		if( !$report_error ){
			return false;
		}

		//if it's an addon error, only report if the addon was installed remotely
		if( isset($addonFolderName) && $addonFolderName ){
			if( !isset($config['addons'][$addonFolderName]['remote_install'])  ){
				return false;
			}

		//if it's a core error, it should be in the include folder
		}elseif( strpos($filename,'/include/') === false ){
			return false;
		}

		//record the error
		$i = count($wbErrorBuffer);
		$args['en'.$i] = $errno;
		$args['el'.$i] = $linenum;
		$args['em'.$i] = substr($errmsg,0,255);
		$args['ef'.$i] = $filename; //filename length checked later
		if( isset($addon_current_id) ){
			$args['ea'.$i] = $addon_current_id;
		}
		if( isset($addon_current_version) && $addon_current_version ){
			$args['ev'.$i] = $addon_current_version;
		}
		if( is_object($page) && !empty($page->title) ){
			$args['ep'.$i] = $page->title;
		}
		$wbErrorBuffer[$uniq] = $args;
		return false;
	}


	$mess = '';
	$mess .= '<fieldset style="padding:1em">';
	$mess .= '<legend>'.$errortype[$errno].' ('.$errno.')</legend> '.$errmsg;
	$mess .= '<br/> &nbsp; &nbsp; <b>in:</b> '.$filename;
	$mess .= '<br/> &nbsp; &nbsp; <b>on line:</b> '.$linenum;
	if( isset($_SERVER['REQUEST_URI']) ){
		$mess .= '<br/> &nbsp; &nbsp; <b>Request:</b> '.$_SERVER['REQUEST_URI'];
	}
	if( isset($_SERVER['REQUEST_METHOD']) ){
		$mess .= '<br/> &nbsp; &nbsp; <b>Method:</b> '.$_SERVER['REQUEST_METHOD'];
	}


	//mysql.. for some addons
	if( function_exists('mysql_errno') && mysql_errno() ){
		$mess .= '<br/> &nbsp; &nbsp; Mysql Error ('.mysql_errno().')'. mysql_error();
	}

	//backtrace, don't add entire object to backtrace
	$backtrace = array_slice($backtrace,0,7);
	foreach($backtrace as $i => $trace){
		if( !empty($trace['object']) ){
			$backtrace[$i]['object'] = get_class($trace['object']);
		}
	}

	$mess .= '<div><a href="javascript:void(0)" onclick="var st = this.nextSibling.style; if( st.display==\'block\'){ st.display=\'none\' }else{st.display=\'block\'};return false;">Show Backtrace</a>';
	$mess .= '<div class="nodisplay">';
	$mess .= pre($backtrace);
	$mess .= '</div></div>';
	$mess .= '</p></fieldset>';

	if( gpdebug === true ){
		message($mess);
	}elseif( $report_error ){
		global $gp_mailer;
		includeFile('tool/email_mailer.php');
		$gp_mailer->SendEmail(gpdebug, 'debug ', $mess);
	}
	return false;
}


/**
 * Calculate the difference between two micro times
 *
 */
function microtime_diff($a, $b = false, $eff = 6) {
	if( !$b ) $b = microtime();
	$a = array_sum(explode(" ", $a));
	$b = array_sum(explode(" ", $b));
	return sprintf('%0.'.$eff.'f', $b-$a);
}


/**
 * Define a constant if it hasn't already been set
 * @param string $var The name of the constant
 * @param mixed $default The value to set the constant if it hasn't been set
 * @since 2.4RC2
 */
function gp_defined($var,$default){
	defined($var) or define($var,$default);
}


/**
 * Fix GPCR if magic_quotes_gpc is on
 * magic_quotes_gpc is deprecated, but still on by default in many versions of php
 *
 */
if( function_exists( 'get_magic_quotes_gpc' ) && version_compare(phpversion(),'5.4','<=') && @get_magic_quotes_gpc() ){
	fix_magic_quotes( $_GET );
	fix_magic_quotes( $_POST );
	fix_magic_quotes( $_COOKIE );
	fix_magic_quotes( $_REQUEST );

	//In version 4, $_ENV was also quoted
	//fix_magic_quotes( $_ENV ); //use GETENV() instead of $_ENV

	//doing this can break the application, the $_SERVER variable is not affected by magic_quotes
	//fix_magic_quotes( $_SERVER );
}

//If Register Globals
if( common::IniGet('register_globals') ){
	foreach($_REQUEST as $key => $value){
		$key = strtolower($key);
		if( ($key == 'globals') || $key == '_post'){
			die('Hack attempted.');
		}
	}
}

function fix_magic_quotes( &$arr ) {
	$new = array();
	foreach( $arr as $key => $val ) {
		$key = stripslashes($key);

		if( is_array( $val ) ){
			fix_magic_quotes( $val );
		}else{
			$val = stripslashes( $val );
		}
		$new[$key] = $val;
	}
	$arr = $new;
}


/**
 * Store a user message in the buffer
 *
 */
function message(){
	global $wbMessageBuffer;
	$wbMessageBuffer[] = func_get_args();
}
function msg(){
	global $wbMessageBuffer;
	$wbMessageBuffer[] = func_get_args();
}

/**
 * Output the message buffer
 *
 */
function GetMessages( $wrap = true ){
	global $wbMessageBuffer,$gp_not_writable,$langmessage;

	if( common::loggedIn() && count($gp_not_writable) > 0 ){
		$files = '<ul><li>'.implode('</li><li>',$gp_not_writable).'</li></ul>';
		$message = sprintf($langmessage['not_writable'],common::GetUrl('Admin_Status')).$files;
		message($message);
		$gp_not_writable = array();
	}

	$result = $wrap_end = '';

	if( $wrap ){
		$result = "\n<!-- message_start ".gp_random." -->";
		$wrap_end = "<!-- message_end -->\n";
	}
	if( !empty($wbMessageBuffer) ){

		$result .= '<div class="messages"><div>';
		$result .= '<a style="" href="#" class="req_script close_message" data-cmd="close_message"></a>';
		$result .= '<ul>';

		foreach($wbMessageBuffer as $args){
			if( !isset($args[0]) ){
				continue;
			}

			if( isset($args[1]) ){
				$result .= '<li>'.call_user_func_array('sprintf',$args).'</li>';
			}elseif( is_array($args[0]) ){
				$result .= '<li>'.pre($args[0]).'</li>';
			}else{
				$result .= '<li>'.$args[0].'</li>';
			}
		}

		$result .= '</ul></div></div>';
	}

	return $result .= common::ErrorBuffer().$wrap_end;
}


/**
 * Include a file relative to the include directory of the current installation
 *
 */
function includeFile( $file ){
	global $dataDir;
	require_once( $dataDir.'/include/'.$file );
}

/**
 * Include a script, unless it has caused a fatal error.
 * Using this function allows gpEasy to handle fatal errors that are thrown by the included php scripts
 *
 * @param string $file The full path of the php file to include
 * @param string $include_variation Which variation or adaptation of php's include() function to use (include,include_once,include_if, include_once_if, require ...)
 * @param array List of global variables to set
 */
function IncludeScript($file, $include_variation = 'include_once', $globals = array() ){
	global $GP_EXEC_STACK;

	$exists = file_exists($file);
	$hash = 'include'.md5($file).sha1($file);
	if( gpOutput::FatalNotice($hash) ){
		return false;
	}


	//check to see if it exists
	$include_variation = str_replace('_if','',$include_variation,$has_if);
	if( $has_if && !$exists ){
		return;
	}

	//set global variables
	foreach($globals as $global){
		global $$global;
	}

	$GP_EXEC_STACK[] = $hash;

	switch($include_variation){
		case 'include':
			$return = include($file);
		break;
		case 'include_once':
			$return = include_once($file);
		break;
		case 'require':
			$return = require_once($file);
		break;
		case 'require_once':
			$return = require_once($file);
		break;
	}

	array_pop($GP_EXEC_STACK);

	return $return;
}



/**
 * Similar to print_r and var_dump, but it is output buffer handling function safe
 * message( pre(array(array(true))) );
 * message( pre(new tempo()) );
 */
function pre($mixed){
	static $level = 0;
	$output = '';

	$type = gettype($mixed);
	switch($type){
		case 'object':
			$type = get_class($mixed).' object';
		case 'array':
			$output = $type.'('."\n";
			foreach($mixed as $key => $value){
				$level++;
				$output .= str_repeat('   ',$level) . '[' . $key . '] => ' . pre($value) . "\n";
				$level--;
			}
			$output .= str_repeat('   ',$level).')';
		break;
		case 'boolean':
			if( $mixed ){
				$mixed = 'true';
			}else{
				$mixed = 'false';
			}
		default:
			$output = '('.$type.')'.htmlspecialchars($mixed,ENT_COMPAT,'UTF-8',false).'';
		break;
	}

	if( $level == 0 ){
		return '<pre>'.htmlspecialchars($output,ENT_COMPAT,'UTF-8',false).'</pre>';
	}
	return $output;
}
/**
 * @deprecated 2.6
 */
function showArray($mixed){ return pre($mixed);}


/**
 * Modified from Wordpress function win_is_writable()
 * Working for users without requiring trailing slashes as noted in Wordpress
 *
 * Workaround for Windows bug in is_writable() function
 * will work in despite of Windows ACLs bug
 * NOTE: use a trailing slash for folders!!!
 * see http://bugs.php.net/bug.php?id=27609
 * see http://bugs.php.net/bug.php?id=30931
 *
 * @param string $path
 * @return bool
 */
function gp_is_writable( $path ){

	if( is_writable($path) ){
		return true;
	}

	// check tmp file for read/write capabilities
	if( is_dir($path) ){
		$path = rtrim($path,'/').'/' . uniqid( mt_rand() ) . '.tmp';
	}

	$should_delete_tmp_file = !file_exists( $path );
	$f = @fopen( $path, 'a' );
	if ( $f === false ) return false;
	fclose( $f );
	if ( $should_delete_tmp_file ) unlink( $path );
	return true;
}



/**
 *	Objects of this class handle the display of standard gpEasy pages
 *  The classes for admin pages and special pages extend the display class
 *
 */
class display{
	var $pagetype = 'display';
	var $gp_index;
	var $title;
	var $label;
	var $file = false;
	var $contentBuffer;
	var $TitleInfo;
	var $fileType = '';
	var $ajaxReplace = array('#gpx_content');
	var $admin_links = array();

	var $fileModTime = 0; /* @deprecated 3.0 */
	var $file_stats = array();

	//layout & theme
	var $theme_name = false;
	var $theme_color = false;
	var $get_theme_css = true;
	var $theme_dir;
	var $theme_path;
	var $theme_rel;
	var $theme_addon_id = false;
	var $theme_is_addon = false;/* @deprecated 3.5 */
	var $layout_css = false;
	var $menu_css_ordered = true;
	var $menu_css_indexed = true;
	var $gpLayout;


	//<head> content
	var $head = '';
	var $head_js = array();
	var $head_script = '';
	var $jQueryCode = false;
	var $admin_js = false;
	var $head_force_inline = false;
	var $meta_description = '';
	var $meta_keywords = array();

	//css arrays
	var $css_user = array();
	var $css_admin = array();


	var $editable_content = true;
	var $editable_details = true;

	function display($title){
		$this->title = $title;
	}


	/**
	 * Get page content or do redirect for non-existant titles
	 * see special_missing.php and admin_missing.php
	 */
	function Error_404($requested){
		includeFile('special/special_missing.php');
		ob_start();
		new special_missing($requested);
		$this->contentBuffer = ob_get_clean();
	}

	function SetVars(){
		global $gp_index, $gp_titles;

		if( !isset($gp_index[$this->title]) ){
			$this->Error_404($this->title);
			return false;
		}

		$this->gp_index = $gp_index[$this->title];
		$this->TitleInfo =& $gp_titles[$this->gp_index]; //so changes made by rename are seen
		$this->label = common::GetLabel($this->title);
		$this->file = gpFiles::PageFile($this->title);
		gpPlugin::Action('PageSetVars');

		return true;
	}


	function RunScript(){

		if( !$this->SetVars() ){
			return;
		}

		//allow addons to effect page actions and how a page is displayed
		$cmd = common::GetCommand();
		$cmd_after = gpPlugin::Filter('PageRunScript',array($cmd));
		if( $cmd !== $cmd_after ){
			$cmd = $cmd_after;
			if( $cmd === 'return' ){
				return;
			}
		}

		$this->GetFile();

		includeFile('tool/SectionContent.php');
		$this->contentBuffer = section_content::Render($this->file_sections,$this->title,$this->file_stats);
	}

	/**
	 * Retreive the data file for the current title and update the data if necessary
	 *
	 */
	function GetFile(){

		$fileModTime = $fileVersion = false;
		$file_sections = $meta_data = $file_stats = array();

		ob_start();
		if( file_exists($this->file) ){
			require($this->file);
		}
		$content = ob_get_clean();

		//update page to 2.0 if it wasn't done in upgrade.php
		if( !empty($content) && count($file_sections) == 0 ){
			if( !empty($meta_data['file_type']) ){
				$file_type =& $meta_data['file_type'];
			}elseif( !isset($file_type) ){
				$file_type = 'text';
			}

			switch($file_type){
				case 'gallery':
					$meta_data['file_type'] = 'text,gallery';
					$file_sections[0] = array(
						'type' => 'text',
						'content' => '<h2>'.strip_tags(common::GetLabel($this->title)).'</h2>',
						);
					$file_sections[1] = array(
						'type' => 'gallery',
						'content' => $content,
						);
				break;

				default:
					$file_sections[0] = array(
						'type' => 'text',
						'content' => $content,
						);
				break;
			}

		}

		//fix gallery pages that weren't updated correctly
		if( isset($fileVersion) && version_compare($fileVersion,'2.0','<=') ){
			foreach($file_sections as $section_index => $section_info){
				if( $section_info['type'] == 'text' && strpos($section_info['content'],'gp_gallery') !== false ){
					//check further
					$lower_content = strtolower($section_info['content']);
					if( strpos($lower_content,'<ul class="gp_gallery">') !== false
						|| strpos($lower_content,'<ul class=gp_gallery>') !== false ){
							$file_sections[$section_index]['type'] = 'gallery';
					}
				}
			}
		}


		if( count($file_sections) == 0 ){
			$file_sections[0] = array(
				'type' => 'text',
				'content' => '<p>Oops, this page no longer has any content.</p>',
				);
		}

		$this->file_sections = $file_sections;
		$this->meta_data = $meta_data;
		$this->fileModTime = $fileModTime;
		$this->file_stats = $file_stats;
		/* for data files older than gpEasy 3.0 */
		if( !isset($this->file_stats['modified']) ){
			$this->file_stats['modified'] = $fileModTime;
		}
		if( !isset($this->file_stats['gpversion']) ){
			$this->file_stats['gpversion'] = $fileVersion;
		}
	}


	/**
	 * Set the page's theme name and path information according to the specified $layout
	 * If $layout is not found, use the installation's default theme
	 *
	 */
	function SetTheme($layout=false){
		global $dataDir;

		if( $layout === false ){
			$layout = display::OrConfig($this->gp_index,'gpLayout');
		}

		$layout_info = common::LayoutInfo($layout);


		//check for fatal error in template.php file
		if( $layout_info ){
			$file = $layout_info['dir'].'/template.php';
			$hash = 'file'.md5($file).sha1($file);
			if( gpOutput::FatalNotice($hash) ){
				$layout_info = false;
			}
		}

		if( !$layout_info ){
			$this->gpLayout = false;
			$this->theme_name = 'Three_point_5';
			$this->theme_color = 'Shore';
			$this->theme_rel = '/themes/'.$this->theme_name.'/'.$this->theme_color;
			$this->theme_dir = $dataDir.'/themes/'.$this->theme_name;

		}else{
			$this->gpLayout = $layout;
			$this->theme_name = $layout_info['theme_name'];
			$this->theme_color = $layout_info['theme_color'];
			$this->theme_rel = $layout_info['path'];
			$this->theme_dir = $layout_info['dir'];


			if( isset($layout_info['css']) && $layout_info['css'] ){
				$this->layout_css = true;
			}
			if( isset($layout_info['addon_id']) ){
				$this->theme_addon_id = $layout_info['addon_id'];
			}
			$this->theme_is_addon = $layout_info['is_addon'];//if installed in /themes or /data/_themes

			//css preferences
			if( isset($layout_info['menu_css_ordered']) && !$layout_info['menu_css_ordered'] ){
				$this->menu_css_ordered = false;
			}
			if( isset($layout_info['menu_css_indexed']) && !$layout_info['menu_css_indexed'] ){
				$this->menu_css_indexed = false;
			}
		}

		$this->theme_path = common::GetDir($this->theme_rel);

	}


	/**
	 * Return the most relevant configuration value for a configuration option ($var)
	 * Check configuration for a page ($id) first, then parent pages (determined by main menu), then the site $config
	 *
	 * @return mixed
	 *
	 */
	static function OrConfig($id,$var){
		global $config, $gp_titles;

		if( $id ){
			if( !empty($gp_titles[$id][$var]) ){
				return $gp_titles[$id][$var];
			}

			if( display::ParentConfig($id,$var,$value) ){
				return $value;
			}
		}

		if( isset($config[$var]) ){
			return $config[$var];
		}

		return false;
	}

	/**
	 * Traverse the main menu upwards looking for a configuration setting for $var
	 * Start at the title represented by $checkId
	 * Set $value to the configuration setting if a parent page has the configuration setting
	 *
	 * @return bool
	 */
	static function ParentConfig($checkId,$var,&$value){
		global $gp_titles,$gp_menu;

		$parents = common::Parents($checkId,$gp_menu);
		foreach($parents as $parent_index){
			if( !empty($gp_titles[$parent_index][$var]) ){
				$value = $gp_titles[$parent_index][$var];
				return true;
			}
		}
		return false;
	}



	/*
	 * Get functions
	 *
	 * Missing:
	 *		$#sitemap#$
	 * 		different menu output
	 *
	 */

	function GetSiteLabel(){
		global $config;
		echo $config['title'];
	}
	function GetSiteLabelLink(){
		global $config;
		echo common::Link('',$config['title']);
	}
	function GetPageLabel(){
		echo $this->label;
	}

	function GetContent(){

		$this->GetGpxContent();

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent(){
		$class = '';
		if( isset($this->meta_data['file_number']) ){
			$class = 'filenum-'.$this->meta_data['file_number'];
		}

		echo '<div id="gpx_content" class="'.$class.' cf">';

		echo $this->contentBuffer;


		echo '</div>';
	}

	/* Deprecated functions
	 */
	function GetHead(){
		trigger_error('deprecated functions');
		gpOutput::GetHead();
	}
	function GetExtra($area,$info=array()){
		trigger_error('deprecated functions');
		gpOutput::GetExtra($area,$info);
	}
	function GetMenu(){
		trigger_error('deprecated functions');
		gpOutput::GetMenu();
	}
	function GetFullMenu(){
		trigger_error('deprecated functions');
		gpOutput::GetFullMenu();
	}
	function GetAllGadgets(){
		trigger_error('deprecated functions');
		gpOutput::GetAllGadgets();
	}
	function GetAdminLink(){
		trigger_error('deprecated functions');
		gpOutput::GetAdminLink();
	}


}





class common{


	/**
	 * Return the type of response was requested by the client
	 * @since 3.5b2
	 * @return string
	 */
	static function RequestType(){
		if( isset($_REQUEST['gpreq']) ){
			switch($_REQUEST['gpreq']){
				case 'body':
				case 'flush':
				case 'json':
				case 'content':
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
	static function Send304($etag){
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
		common::status_header(304,'Not Modified');
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
	static function status_header( $header, $text ) {
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
			$protocol = 'HTTP/1.0';
		$status_header = "$protocol $header $text";
		return @header( $status_header, true, $header );
	}

	static function GenEtag($modified,$content_length){
		return base_convert( $modified, 10, 36).'.'.base_convert( $content_length, 10, 36);
	}

	static function CheckTheme(){
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
	static function LayoutInfo( $layout, $check_existence = true ){
		global $gpLayouts,$dataDir;

		if( !isset($gpLayouts[$layout]) ){
			return false;
		}

		$layout_info = $gpLayouts[$layout];
		$layout_info += array('is_addon'=>false);
		$layout_info['theme_name'] = common::DirName($layout_info['theme']);
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

	static function EntryPoint($level=0,$expecting='index.php',$sessions=true){

		clearstatcache();

		$ob_gzhandler = false;
		if( !common::IniGet('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') ){
			@ob_start( 'ob_gzhandler' ); //ini_get() does not always work for this test
			$ob_gzhandler = true;
		}

		common::SetGlobalPaths($level,$expecting);
		includeFile('tool/gpOutput.php');
		includeFile('tool/functions.php');
		includeFile('tool/Plugins.php');
		if( $sessions ){
			ob_start(array('gpOutput','BufferOut'));
		}elseif( !$ob_gzhandler ){
			ob_start();
		}

		common::RequestLevel();
		common::gpInstalled();
		common::GetConfig();
		common::SetLinkPrefix();
		common::SetCookieArgs();
		if( $sessions ){
			common::sessions();
		}
	}


	/**
	 * Determine if gpEasy has been installed
	 *
	 */
	static function gpInstalled(){
		global $dataDir;

		if( @file_exists($dataDir.'/data/_site/config.php') ){
			return;
		}

		if( file_exists($dataDir.'/include/install/install.php') ){
			common::SetLinkPrefix();
			includeFile('install/install.php');
			die();
		}

		die('<p>Sorry, this site is temporarily unavailable.</p>');
	}

	static function SetGlobalPaths($DirectoriesAway,$expecting){
		global $dataDir, $dirPrefix, $rootDir;

		$rootDir = common::DirName( __FILE__, 2 );

		// dataDir, make sure it contains $expecting. Some servers using cgi do not set this properly
		// required for the Multi-Site plugin
		$dataDir = common::GetEnv('SCRIPT_FILENAME',$expecting);
		if( $dataDir !== false ){
			$dataDir = common::ReduceGlobalPath($dataDir,$DirectoriesAway);
		}else{
			$dataDir = $rootDir;
		}
		if( $dataDir == '/' ){
			$dataDir = '';
		}

		//$dirPrefix
		$dirPrefix = common::GetEnv('SCRIPT_NAME',$expecting);
		if( $dirPrefix === false ){
			$dirPrefix = common::GetEnv('PHP_SELF',$expecting);
		}

		//remove everything after $expecting, $dirPrefix can at times include the PATH_INFO
		$pos = strpos($dirPrefix,$expecting);
		$dirPrefix = substr($dirPrefix,0,$pos+strlen($expecting));

		$dirPrefix = common::ReduceGlobalPath($dirPrefix,$DirectoriesAway);
		if( $dirPrefix == '/' ){
			$dirPrefix = '';
		}
	}

	/**
	 * Convert backslashes to forward slashes
	 *
	 */
	static function WinPath($path){
		return str_replace('\\','/',$path);
	}

	/**
	 * Returns parent directory's path with forward slashes
	 * php's dirname() method may change slashes from / to \
	 *
	 */
	static function DirName( $path, $dirs = 1 ){
		for($i=0;$i<$dirs;$i++){
			$path = dirname($path);
		}
		return common::WinPath( $path );
	}

	/**
	 * Determine if this installation is supressing index.php in urls or not
	 *
	 */
	static function SetLinkPrefix(){
		global $linkPrefix, $dirPrefix, $config;

		$linkPrefix = $dirPrefix;

		if( !isset($_SERVER['gp_rewrite']) ){
			if( defined('gp_indexphp') && (gp_indexphp === false) ){
				$_SERVER['gp_rewrite'] = true;
			}else{
				$_SERVER['gp_rewrite'] = false;
			}
		}else{
			if( $_SERVER['gp_rewrite'] === true || $_SERVER['gp_rewrite'] == 'On' ){
				$_SERVER['gp_rewrite'] = true;
			}elseif( $_SERVER['gp_rewrite'] == substr($config['gpuniq'],0,7) ){
				$_SERVER['gp_rewrite'] = true;
			}else{
				$_SERVER['gp_rewrite'] = false;
			}
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
	static function GetEnv($var,$expecting=false){
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
	static function IniGet($key){
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


	static function ReduceGlobalPath($path,$DirectoriesAway){
		return common::DirName($path,$DirectoriesAway+1);
	}



	//use dirPrefix to find requested level
	static function RequestLevel(){
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
	static function Ampersands($str){
		return preg_replace('/&(?![#a-zA-Z0-9]{2,9};)/S','&amp;',$str);
	}


	/**
	 * Similar to htmlspecialchars, but designed for labels
	 * Does not convert existing ampersands "&"
	 *
	 */
	static function LabelSpecialChars($string){
		return str_replace( array('<','>','"',"'"), array('&lt;','&gt;','&quot;','&#39;') , $string);

		/*return str_replace(
				array('<','>','"',"'",'&','&amp;lt;','&amp;gt;','&amp;quot;','&amp;#39;','&amp;amp;')
				, array('&lt;','&gt;','&quot;','&#39;','&amp;','&lt;','&gt;','&quot;','&#39;','&amp;')
				, $str);
		*/

	}


	/**
	 * Return an html hyperlink
	 *
	 * @param string $href The href value relative to the installation root (without index.php)
	 * @param string $label Text or html to be displayed within the hyperlink
	 * @param string $query Optional query to be used with the href
	 * @param string $attr Optional string of attributes like title=".." and class=".."
	 * @param mixed $nonce_action If false, no nonce will be added to the query. Given a string, it will be used as the first argument in common::new_nonce()
	 *
	 * @return string The formatted html hyperlink
	 */
	static function Link($href='',$label='',$query='',$attr='',$nonce_action=false){
		return '<a href="'.common::GetUrl($href,$query,true,$nonce_action).'" '.common::LinkAttr($attr,$label).'>'.common::Ampersands($label).'</a>';
	}

	static function LinkAttr($attr='',$label=''){
		$string = '';
		$has_title = false;
		if( is_array($attr) ){
			$attr = array_change_key_case($attr);
			$has_title = isset($attr['title']);
			if( isset($attr['name']) && !isset($attr['data-cmd']) ){
				$attr['data-cmd'] = $attr['name'];
				unset($attr['name']);

				/*
				if( isset($attr['rel']) && !isset($attr['data-arg']) ){
					$attr['data-arg'] = $attr['rel'];
					unset($attr['rel']);
				}
				*/
			}

			if( isset($attr['data-cmd']) && $attr['data-cmd'] == 'postlink' ){
				$attr['data-nonce'] = common::new_nonce('post',true);
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
				$string .= ' data-nonce="'.common::new_nonce('post',true).'"';
			}
		}

		if( !$has_title && !empty($label) ){
			$string .= ' title="'.common::Ampersands(strip_tags($label)).'" ';
		}

		return trim($string);
	}

	/**
	 * Return an html hyperlink for a page
	 *
	 * @param string $title The title of the page
	 * @return string The formatted html hyperlink
	 */
	static function Link_Page($title=''){
		global $config, $gp_index;

		if( empty($title) && !empty($config['homepath']) ){
			$title = $config['homepath'];
		}

		$label = common::GetLabel($title);

		return common::Link($title,$label);
	}


	static function GetUrl($href='',$query='',$ampersands=true,$nonce_action=false){
		global $linkPrefix, $config;

		$filtered = gpPlugin::Filter('GetUrl',array(array($href,$query)));
		if( is_array($filtered) ){
			list($href,$query) = $filtered;
		}

		$href = common::SpecialHref($href);


		//home page link
		if( isset($config['homepath']) && $href == $config['homepath'] ){
			$href = $linkPrefix;
			if( !$_SERVER['gp_rewrite'] ){
				$href = common::DirName($href);
			}
			$href = rtrim($href,'/').'/';
		}else{
			$href = $linkPrefix.'/'.ltrim($href,'/');
		}

		$query = common::QueryEncode($query,$ampersands);

		if( $nonce_action ){
			$nonce = common::new_nonce($nonce_action);
			if( !empty($query) ){
				$query .= '&amp;'; //in the cases where $ampersands is false, nonces are not used
			}
			$query .= '_gpnonce='.$nonce;
		}
		if( !empty($query) ){
			$query = '?'.ltrim($query,'?');
		}

		return common::HrefEncode($href,$ampersands).$query;
	}

	//translate special pages from key to title
	static function SpecialHref($href){
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
				&& $index_title = common::IndexToTitle($lower)
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
	static function HrefEncode($href,$ampersands=true){
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
	static function QueryEncode($query,$ampersands = true){

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

	static function AbsoluteLink($href,$label,$query='',$attr=''){

		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
		}

		return '<a href="'.common::AbsoluteUrl($href,$query).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}

	static function AbsoluteUrl($href='',$query='',$with_schema=true,$ampersands=true){

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$schema = '';
		if( $with_schema ){
			$schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
		}

		return $schema.$server.common::GetUrl($href,$query,$ampersands);
	}

	/**
	 * Get the full path of a physical file on the server
	 * The query string component of a path should not be included but will be protected from being encoded
	 *
	 */
	static function GetDir($dir='',$ampersands = false){
		global $dirPrefix;

		$query = '';
		$pos = mb_strpos($dir,'?');
		if( $pos !== false ){
			$dir = mb_substr($dir,0,$pos);
			$query = mb_substr($dir,$pos);
		}
		$dir = $dirPrefix.'/'.ltrim($dir,'/');
		return common::HrefEncode($dir,$ampersands).$query;
	}


	/**
	 * Get the label for a page from it's index
	 * @param string $index
	 * @param bool $amp Whether or not to escape ampersand characters
	 */
	static function GetLabelIndex($index=false,$amp=false){
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
			$return = common::IndexToTitle($index);
			$return = gpFiles::CleanLabel($return);
		}
		if( $amp ){
			return str_replace('&','&amp;',$return);
		}
		return $return;
	}

	/**
	 * Get the label for a page from it's title
	 * @param string $title
	 * @param bool $amp Whether or not to escape ampersand characters
	 */
	static function GetLabel($title=false){
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
			$return = gpFiles::CleanLabel($title);
		}

		return $return;
	}

	/**
	 * Get the browser title for a page
	 * @param string $title
	 *
	 */
	static function GetBrowserTitle($title){
		global $gp_titles, $gp_index;

		if( !isset($gp_index[$title]) ){
			return false;
		}

		$index = $gp_index[$title];
		$title_info = $gp_titles[$index];

		if( isset($title_info['browser_title']) ){
			return $title_info['browser_title'];
		}

		$label = common::GetLabel($title);

		return strip_tags($label);
	}


	/**
	 * Add js and css components to the current web page
	 *
	 * @static
	 * @since 2.0b1
	 * @param string $names A comma separated list of ui components to include. Avail since gpEasy 3.5.
	 */
	static function LoadComponents( $names = ''){
		gpOutput::$components .= ','.$names.',';
		gpOutput::$components = str_replace(',,',',',gpOutput::$components);
	}


	/**
	 * Add gallery js and css to the <head> section of a page
	 *
	 */
	static function ShowingGallery(){
		global $page;
		static $showing = false;
		if( $showing ) return;
		$showing = true;

		common::AddColorBox();
		$css = gpPlugin::OneFilter('Gallery_Style');
		if( $css === false  ){
			$page->css_user[] = '/include/css/default_gallery.css';
			return;
		}
		$page->head .= "\n".'<link type="text/css" media="screen" rel="stylesheet" href="'.$css.'" />';
	}

	/**
	 * Add js and css elements to the <head> section of a page
	 *
	 */
	static function AddColorBox(){
		global $page, $config, $langmessage;
		static $init = false;

		if( $init ){
			return;
		}
		$init = true;

		$list = array('previous'=>$langmessage['Previous'],'next'=>$langmessage['Next'],'close'=>$langmessage['Close'],'caption'=>$langmessage['caption'],'current'=>sprintf($langmessage['Image_of'],'{current}','{total}')); //'Start Slideshow'=>'slideshowStart','Stop Slideshow'=>'slideshowStop'
		$page->head_script .= "\nvar colorbox_lang = ".common::JsonEncode($list).';';

		common::LoadComponents( 'colorbox' );
	}

	/**
	 * Set the $config array from /data/_site/config.php
	 *
	 */
	static function GetConfig(){
		global $config, $dataDir;

		require($dataDir.'/data/_site/config.php');
		if( !is_array($config) || !array_key_exists('gpversion',$config) ){
			common::stop();
		}
		$GLOBALS['fileModTimes']['config.php'] = $fileModTime;

		//remove old values
		if( isset($config['linkto']) ) unset($config['linkto']);
		if( isset($config['menu_levels']) ) unset($config['menu_levels']); //2.3.2
		if( isset($config['hidegplink']) ){ //2.4RC2
			if( $config['hidegplink'] === 'hide' ){
				$config['showgplink'] = false;
			}
			unset($config['hidegplink']);
		}

		//make sure defaults are set
		$config += array(
				'maximgarea' => '691200',
				'maxthumbsize' => '100',
				'check_uploads' => false,
				'colorbox_style' => 'example1',
				'combinecss' => true,
				'combinejs' => true,
				'etag_headers' => true,
				'customlang' => array(),
				'showgplink' => true,
				'showsitemap' => true,
				'showlogin' => true,
				'auto_redir' => 90,	//2.5
				'resize_images' => true,	//3.5
				'jquery' => 'local',
				'addons' => array(),
				'themes' => array(),
				'gadgets' => array(),
				'passhash' => 'sha1',
				);

		//shahash deprecated 4.0
		if( isset($config['shahash']) && !$config['shahash'] ){
			$config['passhash'] = 'md5';
		}


		// default gadgets
		$config['gadgets'] += array(
								'Contact' 		=> array('script'=>'/include/special/special_contact.php','class'=>'special_contact_gadget'),
								'Search'		=> array('script'=>'/include/special/special_search.php','method'=>array('special_gpsearch','gadget')), //3.5
								);

		common::GetLangFile();
		common::GetPagesPHP();


		//upgrade?
		if( version_compare($config['gpversion'],'2.3.4','<') ){
			includeFile('tool/upgrade.php');
			new gpupgrade();
		}
	}

	static function stop(){
		die('<p>Notice: The site configuration did not load properly.</p>'
			.'<p>If you are the site administrator, you can troubleshoot the problem turning debugging "on" or bypass it by enabling gpEasy safe mode.</p>'
			.'<p>More information is available in the <a href="http://docs.gpeasy.com/Main/Troubleshooting">gpEasy documentation</a>.</p>'
			.common::ErrorBuffer(true,false)
			);
	}


	/**
	 * Set global variables ( $gp_index, $gp_titles, $gp_menu and $gpLayouts ) from _site/pages.php
	 *
	 */
	static function GetPagesPHP(){
		global $gp_index, $gp_titles, $gp_menu, $dataDir, $gpLayouts, $config;
		$gp_index = array();

		$pages = array();
		require($dataDir.'/data/_site/pages.php');
		$GLOBALS['fileModTimes']['pages.php'] = $fileModTime;
		$gpLayouts = $pages['gpLayouts'];


		//update for < 2.0a3
		if( array_key_exists('gpmenu',$pages)
			&& array_key_exists('gptitles',$pages)
			&& !array_key_exists('gp_titles',$pages)
			&& !array_key_exists('gp_menu',$pages) ){

			foreach($pages['gptitles'] as $title => $info){
				$index = common::NewFileIndex();
				$gp_index[$title] = $index;
				$gp_titles[$index] = $info;
			}

			foreach($pages['gpmenu'] as $title => $level){
				$index = $gp_index[$title];
				$gp_menu[$index] = array('level' => $level);
			}
			return;
		}

		$gp_index = $pages['gp_index'];
		$gp_titles = $pages['gp_titles'];
		$gp_menu = $pages['gp_menu'];

		if( !is_array($gp_menu) ){
			common::stop();
		}

		//update for 3.5,
		if( !isset($gp_titles['special_gpsearch']) ){
			$gp_titles['special_gpsearch'] = array();
			$gp_titles['special_gpsearch']['label'] = 'Search';
			$gp_titles['special_gpsearch']['type'] = 'special';
			$gp_index['Search'] = 'special_gpsearch'; //may overwrite special_search settings
		}

		//fix the gpmenu
		if( version_compare($fileVersion,'3.0b1','<') ){
			$gp_menu = gpOutput::FixMenu($gp_menu);

			// fix gp_titles for gpEasy 3.0+
			// just make sure any ampersands in the label are escaped
			foreach($gp_titles as $key => $value){
				if( isset($gp_titles[$key]['label']) ){
					$gp_titles[$key]['label'] = common::GetLabelIndex($key,true);
				}
			}
		}

		//title related configuration settings
		$config['homepath_key'] = key($gp_menu);
		$config['homepath'] = common::IndexToTitle($config['homepath_key']);

	}


	/**
	 * Generate a new file index
	 * skip indexes that are just numeric
	 */
	static function NewFileIndex(){
		global $gp_index, $gp_titles;

		$num_index = 0;

		/*prevent reusing old indexes */
		if( count($gp_index) > 0 ){
			$max = count($gp_index);
			$title = end($gp_index);
			for($i = $max; $i > 0; $i--){
				$last_index = current($gp_index);
				$type = common::SpecialOrAdmin($title);
				if( $type == 'special' ){
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
		}while( is_numeric($index) || isset($gp_titles[$index]) );

		return $index;
	}


	/**
	 * Return the title of file using the index
	 * Will return false for titles that are external links
	 * @param string $index The index of the file
	 */
	static function IndexToTitle($index){
		global $gp_index;
		return array_search($index,$gp_index);
	}



	/**
	 * Traverse the $menu upwards looking for the parents of the a title given by it's index
	 * @param string $index The data index of the child title
	 * @return array
	 *
	 */
	static function Parents($index,$menu){
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
	 * @return array
	 */
	static function Descendants($index,$menu){

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

			$titles[] = $id;
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
	static function ConfigValue($key,$default=false){
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
	static function RandomString($len = 40, $cases = true ){

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
	static function GetLangFile($file='main.inc',$language=false){
		global $dataDir, $config, $langmessage;

		if( $language === false ){
			$language = $config['language'];
		}


		$fullPath = $dataDir.'/include/languages/'.$language.'/main.inc';
		if( file_exists($fullPath) ){
			include($fullPath);
			return;
		}

		//try to get the english file
		$fullPath = $dataDir.'/include/languages/en/main.inc';
		if( file_exists($fullPath) ){
			include($fullPath);
		}

	}

	/**
	 * Determine if the $title is a special or admin page
	 * @param string $title
	 * @return mixed 'admin','special' or false
	 */
	static function SpecialOrAdmin($title){
		global $gp_index,$gp_titles;

		$lower_title = strtolower($title);

		if( $lower_title === 'admin' ){
			return 'admin';
		}elseif( strpos($lower_title,'admin_') === 0 ){
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
	static function WhichPage(){
		global $config, $gp_menu;

		$path = common::CleanRequest($_SERVER['REQUEST_URI']);
		$path = preg_replace('#[[:cntrl:]]#u','', $path);// remove control characters

		$pos = mb_strpos($path,'?');
		if( $pos !== false ){
			$path = mb_substr($path,0,$pos);
		}

		$path = gpPlugin::Filter('WhichPage',array($path));

		//redirect if an "external link" is the first entry of the main menu
		if( empty($path) && isset($gp_menu[$config['homepath_key']]) ){
			$homepath_info = $gp_menu[$config['homepath_key']];
			if( isset($homepath_info['url']) ){
				common::Redirect($homepath_info['url'],302);
			}
		}

		if( empty($path) ){
			return $config['homepath'];
		}

		if( isset($config['homepath']) && $path == $config['homepath'] ){
			common::Redirect(common::GetUrl('',http_build_query($_GET),false));
		}

		return $path;
	}


	/**
	 * Redirect the request to $path with http $code
	 * @static
	 * @param string $path url to redirect to
	 * @param string $code http redirect code: 301 or 302
	 *
	 */
	static function Redirect($path,$code = 301){

		//prevent a cache from creating an infinite redirect
		Header( 'Last-Modified: ' . gmdate( 'D, j M Y H:i:s' ) . ' GMT' );
		Header( 'Expires: ' . gmdate( 'D, j M Y H:i:s', time() ) . ' GMT' );
		Header( 'Cache-Control: no-store, no-cache, must-revalidate' ); // HTTP/1.1
		Header( 'Cache-Control: post-check=0, pre-check=0', false );
		Header( 'Pragma: no-cache' ); // HTTP/1.0

		switch((int)$code){
			case 301:
				common::status_header(301,'Moved Permanently');
			break;
			case 302:
				common::status_header(302,'Found');
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
	static function CleanRequest($path){
		global $dirPrefix;

		//use dirPrefix to find requested title
		$path = rawurldecode($path); //%20 ...

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
	static function sessions(){

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

		includeFile('tool/sessions.php');
		gpsession::Init();
	}


	/**
	 * Return true if an administrator is logged in
	 * @return bool
	 */
	static function LoggedIn(){
		global $gpAdmin;

		$loggedin = false;
		if( isset($gpAdmin) && is_array($gpAdmin) ){
			$loggedin = true;
		}

		return gpPlugin::Filter('LoggedIn',array($loggedin));
	}

	static function new_nonce($action = 'none', $anon = false, $factor = 43200 ){
		global $gpAdmin;

		$nonce = $action;
		if( !$anon && !empty($gpAdmin['username']) ){
			$nonce .= $gpAdmin['username'];
		}

		return common::nonce_hash($nonce, 0, $factor );
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
	static function verify_nonce($action = 'none', $check_nonce = false, $anon = false, $factor = 43200 ){
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
		if( common::nonce_hash( $nonce, 0, $factor ) == $check_nonce ){
			return 1;
		}

		// Nonce generated 12-24 hours ago
		if( common::nonce_hash( $nonce, 1, $factor ) == $check_nonce ){
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
	static function nonce_hash( $nonce, $tick_offset=0, $factor = 43200 ){
		global $config;
		$nonce_tick = ceil( time() / $factor ) - $tick_offset;
		return substr( md5($nonce.$config['gpuniq'].$nonce_tick), -12, 10);
	}

	/**
	 * Return the command sent by the user
	 * Don't use $_REQUEST here because SetCookieArgs() uses $_GET
	 *
	 */
	static function GetCommand($type='cmd'){
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
	static function SetCookieArgs(){
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

		//parse_str will overwrite values in $_GET/$_REQUEST
		parse_str($test,$_GET);
		parse_str($test,$_REQUEST);

		//for requests with verification, we'll set $_POST
		if( !empty($_GET['verified']) ){
			parse_str($test,$_POST);
		}

		$done = true;
	}


	/**
	 * Fix the $_COOKIE array if RAW_HTTP_COOKIE is set
	 * Some servers encrypt cookie values before sending them to the client
	 * Since cookies set by the client (with JavaScript) are not encrypted, the values won't be set in $_COOOKIE
	 *
	 */
	static function RawCookies(){
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
	static function JsStart(){

		//default gpEasy Variables
		echo 'var gplinks={},gpinputs={},gpresponse={}'
				.',gpRem=true'
				.',isadmin=false'
				.',gpBase="'.rtrim(common::GetDir(''),'/').'"'
				.',post_nonce=""'
				.',req_type="'.strtolower(htmlspecialchars($_SERVER['REQUEST_METHOD'])).'";'
				."\n";
	}


	/**
	 * Return the hash of $arg using the appropriate hashing function for the installation
	 *
	 * @param string $arg The string to be hashed
	 * @param string $algo The hashing algorithm to be used
	 * @param int $loops The number of times to loop the $arg through the algorithm
	 *
	 */
	static function hash( $arg, $algo='sha512', $loops = 1000){
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

		//looped with dynamic salt
		for( $i=0; $i<$loops; $i++ ){
			$ints = preg_replace('#[a-f]#','',$arg);
			$salt_start = (int)substr($ints,0,1);
			$salt_len = (int)substr($ints,2,1);
			$salt = substr($arg,$salt_start,$salt_len);
			$arg = hash($algo,$arg.$salt);
		}

		return $arg;
	}

	static function AjaxWarning(){
		global $page,$langmessage;
		$page->ajaxReplace[] = array(0=>'admin_box_data',1=>'',2=>$langmessage['OOPS_Start_over']);
	}


	static function IdUrl($request_cmd='cv'){
		global $config, $dataDir;

		$path = addon_browse_path.'/Resources?';

		//command
		$args['cmd'] = $request_cmd;

		$_SERVER += array('SERVER_SOFTWARE'=>'');


		//checkin
		//$args['uniq'] = $config['gpuniq'];
		$args['mdu'] = substr(md5($config['gpuniq']),0,20);
		$args['site'] = common::AbsoluteUrl(''); //keep full path for backwards compat
		$args['gpv'] = gpversion;
		$args['php'] = phpversion();
		$args['se'] =& $_SERVER['SERVER_SOFTWARE'];
		$args['data'] = $dataDir;

		if( defined('service_provider_id') && is_numeric(service_provider_id) ){
			$args['provider'] = service_provider_id;
		}

		//plugins
		$addon_ids = array();
		if( isset($config['addons']) && is_array($config['addons']) ){
			foreach($config['addons'] as $addon => $addon_info){
				if( isset($addon_info['id']) ){
					$addon_id = $addon_info['id'];
					if( isset($addon_info['order']) ){
						$addon_id .= '.'.$addon_info['order'];
					}
					$addon_ids[] = $addon_id;
				}
			}
		}

		//themes
		if( isset($config['themes']) && is_array($config['themes']) ){
			foreach($config['themes'] as $addon => $addon_info){
				if( isset($addon_info['id']) ){
					$addon_id = $addon_info['id'];
					if( isset($addon_info['order']) ){
						$addon_id .= '.'.$addon_info['order'];
					}
					$addon_ids[] = $addon_id;
				}
			}
		}

		$args['as'] = implode('-',$addon_ids);

		return $path . http_build_query($args,'','&');
	}

	/**
	 * Used to send error reports without affecting the display of a page
	 *
	 */
	static function IdReq($img_path,$jquery = true){

		//using jquery asynchronously doesn't affect page loading
		//error function defined to prevent the default error function in main.js from firing
		if( $jquery ){
			echo '<script type="text/javascript" style="display:none !important">';
			echo '$.ajax("'.addslashes($img_path).'",{error:function(){}});';
			echo '</script>';
			return;
		}

		return '<img src="'.common::Ampersands($img_path).'" height="1" width="1" alt="" style="border:0 none !important;height:1px !important;width:1px !important;padding:0 !important;margin:0 !important;"/>';
	}

	//only include error buffer when admin is logged in
	static function ErrorBuffer($check_user = true, $jquery = true){
		global $wbErrorBuffer, $config, $dataDir, $rootDir;

		if( count($wbErrorBuffer) == 0 ) return;

		if( isset($config['Report_Errors']) && !$config['Report_Errors'] ) return;

		if( $check_user && !common::LoggedIn() ) return;

		$dataDir_len = strlen($dataDir);
		$rootDir_len = strlen($rootDir);
		$img_path = common::IdUrl('er');
		$i = 0;

		foreach($wbErrorBuffer as $error){

			//remove $dataDir or $rootDir from the filename
			$file_name = common::WinPath($error['ef'.$i]);
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

		return common::IdReq($img_path, $jquery);
	}


	/**
	 * Test if function exists.  Also handles case where function is disabled via Suhosin.
	 * Modified from: http://dev.piwik.org/trac/browser/trunk/plugins/Installation/Controller.php
	 *
	 * @param string $function Function name
	 * @return bool True if function exists (not disabled); False otherwise.
	 */
	static function function_exists($function){
		$function = strtolower($function);

		// eval() is a language construct
		if( $function == 'eval' ){
			// does not check suhosin.executor.eval.whitelist (or blacklist)
			if( extension_loaded('suhosin') && common::IniGet('suhosin.executor.disable_eval') ){
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
		array_walk( $blacklist,'trim');
		array_walk( $blacklist, 'strtolower');
		if( in_array($function, $blacklist) ){
			return false;
		}

		return true;
	}

	/**
	 * A more functional JSON Encode function for gpEasy than php's json_encode
	 * @param mixed $data
	 *
	 */
	static function JsonEncode($data){
		static $search = array('\\','"',"\n","\r",'<script','</script>');
		static $repl = array('\\\\','\"','\n','\r','<"+"script','<"+"/script>');

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
			return '"'.str_replace($search,$repl,$data).'"';

			case 'object':
				$data = get_object_vars($data);
			case 'array':
				$output_index_count = 0;
				$output_indexed = array();
				$output_associative = array();
				foreach( $data as $key => $value ){
					$output_indexed[] = common::JsonEncode($value);
					$output_associative[] = common::JsonEncode($key) . ':' . common::JsonEncode($value);
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
	static function Date($format='',$time=false){
		if( empty($format) ){
			return '';
		}

		if( !$time ){
			$time = time();
		}

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
	static function ThumbnailPath($img){

		//already thumbnail path
		if( strpos($img,'/data/_uploaded/image/thumbnails') !== false ){
			return $img;
		}

		$dir_part = '/data/_uploaded/';
		$pos = strpos($img,$dir_part);
		if( $pos === false ){
			return $img;
		}

		return substr_replace($img,'/data/_uploaded/image/thumbnails/',$pos, strlen($dir_part) ).'.jpg';
	}


	/**
	 * Generate a checksum for the $array
	 *
	 */
	static function ArrayHash($array){
		return md5(serialize($array) );
	}

	/**
	 * @deprecated 3.0
	 * use gp_edit::UseCK();
	 */
	static function UseFCK($contents,$name='gpcontent'){
		trigger_error('Deprecated Function');
		includeFile('tool/editing.php');
		gp_edit::UseCK($contents,$name);
	}

	/**
	 * @deprecated 3.0
	 * Use gp_edit::UseCK();
	 */
	static function UseCK($contents,$name='gpcontent',$options=array()){
		trigger_error('Deprecated Function');
		includeFile('tool/editing.php');
		gp_edit::UseCK($contents,$name,$options);
	}

}


/**
 * Contains functions for working with data files and directories
 *
 */
class gpFiles{


	/**
	 * Read directory and return an array with files corresponding to $filetype
	 *
	 * @param string $dir The path of the directory to be read
	 * @param mixed $filetype If false, all files in $dir will be included. false=all,1=directories,'php'='.php' files
	 * @return array() List of files in $dir
	 */
	static function ReadDir($dir,$filetype='php'){
		$files = array();
		if( !file_exists($dir) ){
			return $files;
		}
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}

		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			//get all
			if( $filetype === false ){
				$files[$file] = $file;
				continue;
			}

			//get directories
			if( $filetype === 1 ){
				$fullpath = $dir.'/'.$file;
				if( is_dir($fullpath) ){
					$files[$file] = $file;
				}
				continue;
			}


			$dot = strrpos($file,'.');
			if( $dot === false ){
				continue;
			}

			$type = substr($file,$dot+1);

			//if $filetype is an array
			if( is_array($filetype) ){
				if( in_array($type,$filetype) ){
					$files[$file] = $file;
				}
				continue;
			}

			//if $filetype is a string
			if( $type == $filetype ){
				$file = substr($file,0,$dot);
				$files[$file] = $file;
			}

		}
		closedir($dh);

		return $files;
	}


	/**
	 * Read all of the folders and files within $dir and return them in an organized array
	 *
	 * @param string $dir The directory to be read
	 * @return array() The folders and files within $dir
	 *
	 */
	static function ReadFolderAndFiles($dir){
		$dh = @opendir($dir);
		if( !$dh ){
			return array();
		}

		$folders = array();
		$files = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}

			$fullPath = $dir.'/'.$file;
			if( is_dir($fullPath) ){
				$folders[] = $file;
			}else{
				$files[] = $file;
			}
		}
		natcasesort($folders);
		natcasesort($files);
		return array($folders,$files);
	}


	/**
	 * Clean a string for use as a page label (displayed title)
	 * Similar to CleanTitle() but less restrictive
	 *
	 * @param string $title The title to be cleansed
	 * @return string The cleansed title
	 */
	static function CleanLabel($title=''){

		$title = str_replace(array('"'),array(''),$title);
		$title = str_replace(array('<','>'),array('_'),$title);
		$title = trim($title);

		// Remove control characters
		return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
	}


	/**
	 * Clean a string of html that may be used as file content
	 *
	 * @param string $text The string to be cleansed. Passed by reference
	 */
	static function cleanText(&$text){
		includeFile('tool/editing.php');
		gp_edit::tidyFix($text);
		gpFiles::rmPHP($text);
		gpFiles::FixTags($text);
	}

	/**
	 * Use gpEasy's html parser to check the validity of $text
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	static function FixTags(&$text){
		includeFile('tool/HTML_Output.php');
		$gp_html_output = new gp_html_output($text);
		$text = $gp_html_output->result;
	}

	/**
	 * Remove php tags from $text
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	static function rmPHP(&$text){
		$search = array('<?','<?php','?>');
		$replace = array('&lt;?','&lt;?php','?&gt;');
		$text = str_replace($search,$replace,$text);
	}

	/**
	 * Removes any NULL characters in $string.
	 * @since 3.0.2
	 * @param string $string
	 * @return string
	 */
	static function NoNull($string){
		$string = preg_replace('/\0+/', '', $string);
		return preg_replace('/(\\\\0)+/', '', $string);
	}


	/**
	 * Save the content for a new page in /data/_pages/<title>
	 * @since 1.8a1
	 *
	 */
	static function NewTitle($title,$section_content = false,$type='text'){

		if( empty($title) ){
			return false;
		}
		$file = gpFiles::PageFile($title);
		if( !$file ){
			return false;
		}

		$file_sections = array();
		if( is_array($section_content) ){
			$file_sections[0] = $section_content;
		}else{
			$file_sections[0] = array(
				'type' => $type,
				'content' => $section_content
				);
		}


		$meta_data = array(
			'file_number' => gpFiles::NewFileNumber(),
			'file_type' => $type,
			);

		return gpFiles::SaveArray($file,'meta_data',$meta_data,'file_sections',$file_sections);
	}

	/**
	 * Return the data file location for a title
	 * As of v 2.3.4, it defaults to an index based file name but falls back on title based file name for installation and backwards compatibility
	 *
	 * @param string $title
	 * @return string The path of the data file
	 */
	static function PageFile($title){
		global $dataDir, $config, $gp_index;

		$index_path = false;
		if( gp_index_filenames && isset($gp_index[$title]) && isset($config['gpuniq']) ){
			//original data path with data index at the end
			$index_path = $old_path = $dataDir.'/data/_pages/'.substr($config['gpuniq'],0,7).'_'.$gp_index[$title].'.php';
			if( file_exists($old_path) ){
				return $old_path;
			}
		}

		$normal_path = $dataDir.'/data/_pages/'.str_replace('/','_',$title).'.php';
		if( !$index_path || file_exists($normal_path) ){
			return $normal_path;
		}

		return $index_path;
	}

	static function NewFileNumber(){
		global $config;

		includeFile('admin/admin_tools.php');

		if( !isset($config['file_count']) ){
			$config['file_count'] = 0;
		}
		$config['file_count']++;

		admin_tools::SaveConfig();

		return $config['file_count'];

	}

	/**
	 * Get the meta data for the specified file
	 *
	 * @param string $file
	 * @return array
	 */
	static function GetTitleMeta($file){

		$meta_data = array();
		if( file_exists($file) ){
			ob_start();
			include($file);
			ob_end_clean();
		}
		return $meta_data;
	}

	/**
	 * Return an array of info about the data file
	 *
	 */
	static function GetFileStats($file){
		$file_stats = array();
		if( file_exists($file) ){
			ob_start();
			include($file);
			ob_end_clean();

			if( !isset($file_stats['modified']) && isset($fileModTime) ){
				$file_stats['modified'] = $fileModTime;
			}
			if( !isset($file_stats['gpversion']) && isset($fileVersion) ){
				$file_stats['gpversion'] = $fileVersion;
			}
		}else{
			$file_stats['created'] = time();
		}

		return $file_stats;
	}


	/**
	 * Save a file with content and data to the server
	 * This function will be deprecated in future releases. Using it is not recommended
	 *
	 * @param string $file The path of the file to be saved
	 * @param string $contents The contents of the file to be saved
	 * @param string $code The data to be saved
	 * @param string $time The unix timestamp to be used for the $fileVersion
	 * @return bool True on success
	 */
	static function SaveFile($file,$contents,$code=false,$time=false){

		$result = gpFiles::FileStart($file,$time);
		if( $result !== false ){
			$result .= "\n".$code;
		}
		$result .= "\n\n?".">\n";
		$result .= $contents;

		return gpFiles::Save($file,$result);
	}

	/**
	 * Save raw content to a file to the server
	 *
	 * @param string $file The path of the file to be saved
	 * @param string $contents The contents of the file to be saved
	 * @param bool $checkDir Whether or not to check to see if the parent directory exists before attempting to save the file
	 * @return bool True on success
	 */
	static function Save($file,$contents,$checkDir=true){
		global $gp_not_writable;

		if( !self::WriteLock() ){
			return false;
		}

		//make sure directory exists
		if( $checkDir && !file_exists($file) ){
			$dir = common::DirName($file);
			if( !file_exists($dir) ){
				gpFiles::CheckDir($dir);
			}
		}

		$exists = file_exists($file);

		$fp = @fopen($file,'wb');
		if( $fp === false ){
			$gp_not_writable[] = $file;
			return false;
		}

		if( !$exists ){
			@chmod($file,gp_chmod_file);
		}

		$return = fwrite($fp,$contents);
		fclose($fp);
		return ($return !== false);
	}

	/**
	 * Get a write lock to prevent simultaneous writing
	 * @since 3.5.3
	 */
	static function WriteLock(){
		global $dataDir;

		if( defined('gp_has_lock') ){
			return gp_has_lock;
		}

		$expires = gp_write_lock_time;
		if( self::Lock('write',gp_random,$expires) ){
			define('gp_has_lock',true);
			return true;
		}

		trigger_error('gpEasy write lock could not be obtained.');
		message('Oops, a write lock could not be obtained. The existing lock will expire in '.($expires).' seconds.');
		define('gp_has_lock',false);
		return false;
	}

	/**
	 * Get a lock
 	 * Loop and delay to wait for the removal of existing locks (maximum of about .2 of a second)
 	 *
 	 */
	static function Lock($file,$value,&$expires){
		global $dataDir;
		$checked_time = false;
		$tries = 0;
		$lock_file = $dataDir.'/data/_lock_'.$file;
		while($tries < 1000){

			if( !file_exists($lock_file) ){
				file_put_contents($lock_file,$value);
				usleep(100);
			}

			$contents = @file_get_contents($lock_file);
			if( $value === $contents ){
				touch($lock_file);
				return true;
			}

			if( !$checked_time ){
				$checked_time = true;
				$diff = time() - @filemtime($lock_file);
				if( $diff > $expires ){
					@unlink( $lock_file);
				}else{
					$expires -= $diff;
				}
			}
			clearstatcache();
			usleep(100);
			$tries++;
		}
		return false;
	}

	/**
	 * Remove a lock file if the value matches
	 *
	 */
	static function Unlock($file,$value){
		global $dataDir;

		$lock_file = $dataDir.'/data/_lock_'.$file;
		if( !file_exists($lock_file) ){
			return true;
		}

		$contents = @file_get_contents($lock_file);
		if( $contents === false ){
			return true;
		}
		if( $value === $contents ){
			unlink($lock_file);
			return true;
		}
		return false;
	}


	/**
	 * Save array(s) to a $file location
	 * Takes 2n+3 arguments
	 *
	 * @param string $file The location of the file to be saved
	 * @param string $varname The name of the variable being saved
	 * @param array $array The value of $varname to be saved
	 *
	 */
	static function SaveArray(){

		$args = func_get_args();
		$count = count($args);
		if( ($count %2 !== 1) || ($count < 3) ){
			trigger_error('Wrong argument count '.$count.' for gpFiles::SaveArray() ');
			return false;
		}
		$file = array_shift($args);

		$file_stats = array();
		$data = '';
		while( count($args) ){
			$varname = array_shift($args);
			$array = array_shift($args);
			if( $varname == 'file_stats' ){
				$file_stats = $array;
			}else{
				$data .= gpFiles::ArrayToPHP($varname,$array);
				$data .= "\n\n";
			}
		}

		$data = gpFiles::FileStart($file,time(),$file_stats).$data;

		return gpFiles::Save($file,$data);
	}

	/**
	 * Return the beginning content of a data file
	 *
	 */
	static function FileStart($file, $time=false, $file_stats = array() ){
		global $gpAdmin;

		if( $time === false ) $time = time();

		//file stats
		$file_stats = (array)$file_stats + gpFiles::GetFileStats($file);
		$file_stats['gpversion'] = gpversion;
		$file_stats['modified'] = $time;

		if( common::loggedIn() ){
			$file_stats['username'] = $gpAdmin['username'];
		}else{
			$file_stats['username'] = false;
		}

		return '<'.'?'.'php'
				. "\ndefined('is_running') or die('Not an entry point...');"
				. "\n".'$fileVersion = \''.gpversion.'\';' /* @deprecated 3.0 */
				. "\n".'$fileModTime = \''.$time.'\';' /* @deprecated 3.0 */
				. "\n".gpFiles::ArrayToPHP('file_stats',$file_stats)
				. "\n\n";
	}

	static function ArrayToPHP($varname,&$array){
		return '$'.$varname.' = '.var_export($array,true).';';
	}


	/**
	 * Insert a key-value pair into an associative array
	 *
	 * @param mixed $search_key Value to search for in existing array to insert before
	 * @param mixed $new_key Key portion of key-value pair to insert
	 * @param mixed $new_value Value portion of key-value pair to insert
	 * @param array $array Array key-value pair will be added to
	 * @param int $offset Offset distance from where $search_key was found. A value of 1 would insert after $search_key, a value of 0 would insert before $search_key
	 * @param int $length If length is omitted, nothing is removed from $array. If positive, then that many elements will be removed starting with $search_key + $offset
	 * @return bool True on success
	 */
	static function ArrayInsert($search_key,$new_key,$new_value,&$array,$offset=0,$length=0){

		$array_keys = array_keys($array);
		$array_values = array_values($array);

		$insert_key = array_search($search_key,$array_keys);
		if( ($insert_key === null) || ($insert_key === false) ){
			return false;
		}

		array_splice($array_keys,$insert_key+$offset,$length,$new_key);
		array_splice($array_values,$insert_key+$offset,$length,'fill'); //use fill in case $new_value is an array
		$array = array_combine($array_keys, $array_values);
		$array[$new_key] = $new_value;

		return true;
	}


	/**
	 * Replace a key-value pair in an associative array
	 * ArrayReplace() is a shortcut for using gpFiles::ArrayInsert() with $offset = 0 and $length = 1
	 */
	static function ArrayReplace($search_key,$new_key,$new_value,&$array){
		return gpFiles::ArrayInsert($search_key,$new_key,$new_value,$array,0,1);
	}



	/**
	 * Check recursively to see if a directory exists, if it doesn't attempt to create it
	 *
	 * @param string $dir The directory path
	 * @param bool $index Whether or not to add an index.hmtl file in the directory
	 * @return bool True on success
	 */
	static function CheckDir($dir,$index=true){
		global $config,$checkFileIndex;

		if( !file_exists($dir) ){
			$parent = common::DirName($dir);
			gpFiles::CheckDir($parent,$index);


			//ftp mkdir
			if( isset($config['useftp']) ){
				if( !gpFiles::FTP_CheckDir($dir) ){
					return false;
				}
			}else{
				if( !@mkdir($dir,gp_chmod_dir) ){
					return false;
				}
				@chmod($dir,gp_chmod_dir); //some systems need more than just the 0755 in the mkdir() function
			}

		}

		//make sure there's an index.html file
		if( $index && $checkFileIndex ){
			$indexFile = $dir.'/index.html';
			if( !file_exists($indexFile) ){
				gpFiles::Save($indexFile,'<html></html>',false);
			}
		}

		return true;
	}

	/**
	 * Remove a directory
	 * Will only work if directory is empty
	 *
	 */
	static function RmDir($dir){
		global $config;

		//ftp
		if( isset($config['useftp']) ){
			return gpFiles::FTP_RmDir($dir);
		}
		return @rmdir($dir);
	}

	/**
	 * Remove a file or directory and it's contents
	 *
	 */
	static function RmAll($path){

		if( empty($path) ) return false;
		if( is_link($path) ) return @unlink($path);
		if( !is_dir($path) ) return @unlink($path);

		$success = true;
		$subDirs = array();
		$files = gpFiles::ReadDir($path,false);
		foreach($files as $file){
			$full_path = $path.'/'.$file;
			if( is_dir($full_path) ){
				$subDirs[] = $full_path;
			}elseif( !@unlink($full_path) ){
				$success = false;
			}
		}

		foreach($subDirs as $subDir){
			if( !gpFiles::RmAll($subDir) ){
				$success = false;
			}
		}

		if( $success ){
			return gpFiles::RmDir($path);
		}
		return false;
	}


	/* FTP Function */

	static function FTP_RmDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);

		return ftp_rmdir($conn_id,$dir);
	}

	static function FTP_CheckDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);

		if( !ftp_mkdir($conn_id,$dir) ){
			return false;
		}
		return ftp_site($conn_id, 'CHMOD 0777 '. $dir );
	}

	static function FTPConnect(){
		global $config;

		static $conn_id = false;

		if( $conn_id ){
			return $conn_id;
		}

		if( empty($config['ftp_server']) ){
			return false;
		}

		$conn_id = @ftp_connect($config['ftp_server'],21,6);
		if( !$conn_id ){
			trigger_error('ftp_connect() failed for server : '.$config['ftp_server']);
			return false;
		}

		$login_result = @ftp_login($conn_id,$config['ftp_user'],$config['ftp_pass'] );
		if( !$login_result ){
			trigger_error('ftp_login() failed for server : '.$config['ftp_server'].' and user: '.$config['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}

	static function ftpClose($connection=false){
		if( $connection !== false ){
			@ftp_quit($connection);
		}
	}

	static function ftpLocation(&$location){
		global $config,$dataDir;

		$len = strlen($dataDir);
		$temp = substr($location,$len);
		return $config['ftp_root'].$temp;
	}


	/**
	 * @deprecated 3.0
	 * Use gp_edit::CleanTitle() instead
	 * Used by Simple_Blog1
	 */
	static function CleanTitle($title,$spaces = '_'){
		trigger_error('Deprecated Function');
		includeFile('tool/editing.php');
		return gp_edit::CleanTitle($title,$spaces);
	}

}