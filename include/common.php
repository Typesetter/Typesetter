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

require_once('tool.php');


gp_defined('gp_restrict_uploads',false);
gp_defined('gpdebugjs',gpdebug);
gp_defined('gp_cookie_cmd',true);
gp_defined('gp_browser_auth',false);
gp_defined('gp_require_encrypt',false);
gp_defined('gp_nonce_algo','legacy');			// Since 5.0
gp_defined('gp_chmod_file',0666);
gp_defined('gp_chmod_dir',0755);
gp_defined('gp_index_filenames',true);
gp_defined('gp_safe_mode',false);
gp_defined('gp_backup_limit',30);
gp_defined('gp_write_lock_time',5);
gp_defined('gp_dir_index',true);
gp_defined('gp_remote_addons',true); //deprecated 4.0.1
gp_defined('gp_remote_plugins',gp_remote_addons);
gp_defined('gp_remote_themes',gp_remote_addons);
gp_defined('gp_remote_update',gp_remote_addons);
gp_defined('gp_unique_addons',false);
gp_defined('gp_data_type','.php');
gp_defined('gp_default_theme','Bootswatch_Scss/Flatly');
gp_defined('gp_php53', version_compare( phpversion(), '5.4', '<' ) );


//gp_defined('CMS_DOMAIN',			'http://gpeasy.loc');
gp_defined('CMS_DOMAIN',			'http://www.typesettercms.com');
gp_defined('CMS_READABLE_DOMAIN',	'TypesetterCMS.com');
gp_defined('CMS_NAME',				'Typesetter');
gp_defined('addon_browse_path',		CMS_DOMAIN.'/index.php');
gp_defined('debug_path',			CMS_DOMAIN.'/index.php/Debug');

gp_defined('gpversion','5.1');
gp_defined('gp_random',\gp\tool::RandomString());


@ini_set( 'session.use_only_cookies', '1' );
@ini_set( 'default_charset', 'utf-8' );
@ini_set( 'html_errors', false );

if( function_exists('mb_internal_encoding') ){
	mb_internal_encoding('UTF-8');
}



//see mediawiki/languages/Names.php
$languages = array(
	'af' => 'Afrikaans',
	'ar' => 'العربية',			# Arabic
	'bg' => 'Български',		# Bulgarian
	'ca' => 'Català',
	'cs' => 'Česky',			# Czech
	'da' => 'Dansk',
	'de' => 'Deutsch',
	'el' => 'Ελληνικά',			# Greek
	'en' => 'English',
	'es' => 'Español',
	'et' => 'eesti',			# Estonian
	'fi' => 'Suomi',			# Finnish
	'fo' => 'Føroyskt',			# Faroese
	'fr' => 'Français',
	'gl' => 'Galego',			# Galician
	'hr' => 'hrvatski',			# Croatian
	'hu' => 'Magyar',			# Hungarian
	'it' => 'Italiano',
	'ja' => '日本語',			# Japanese
	'lt' => 'Lietuvių',			# Lithuanian
	'nl' => 'Nederlands',		# Dutch
	'no' => 'Norsk',			# Norwegian
	'pl' => 'Polski',			# Polish
	'pt' => 'Português',
	'pt-br' => 'Português do Brasil',
	'ro' => 'Română',			# Romanian
	'ru' => 'Русский',			# Russian
	'sk' => 'Slovenčina',		# Slovak
	'sl' => 'Slovenščina',		# Slovenian
	'sv' => 'Svenska',			# Swedish
	'tr' => 'Türkçe',			# Turkish
	'uk' => 'Українська',		# Ukrainian
	'zh' => '中文',				# (Zhōng Wén) - Chinese
	);



$gpversion = gpversion; // @deprecated 3.5b2
$addonDataFolder = $addonCodeFolder = false;//deprecated
$addonPathData = $addonPathCode = false;
$wbErrorBuffer = $gp_not_writable = $wbMessageBuffer = array();



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

	// since we supported php 4.3+, there may be a lot of strict errors
	if( $errno === E_STRICT ){
		return;
	}

	//get the backtrace and function where the error was thrown
	$backtrace = debug_backtrace();

	//remove showError() from backtrace
	if( strtolower($backtrace[0]['function']) == 'showerror' ){
		$backtrace = array_slice($backtrace,1,5);
	}else{
		$backtrace = array_slice($backtrace,0,5);
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

	//disable showError after 20 errors
	if( count($reported) >= 1 ){
		restore_error_handler();
	}

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

	//attempting to entire all data can result in a blank screen
	foreach($backtrace as $i => $trace){
		foreach($trace as $tk => $tv){
			if( is_array($tv) ){
				$backtrace[$i][$tk] = 'array('.count($tv).')';
			}elseif( is_object($tv) ){
				$backtrace[$i][$tk] = 'object '.get_class($tv);
			}
		}
	}

	$mess .= '<div><a href="javascript:void(0)" onclick="var st = this.nextSibling.style; if( st.display==\'block\'){ st.display=\'none\' }else{st.display=\'block\'};return false;">Show Backtrace</a>';
	$mess .= '<div class="nodisplay">';
	$mess .= pre($backtrace);
	$mess .= '</div></div>';
	$mess .= '</p></fieldset>';

	if( gpdebug === true ){
		msg($mess);
	}elseif( class_exists('\\gp\tool\\Emailer') && $report_error ){
		$mailer =		new \gp\tool\Emailer();
		$subject =		\gp\tool::ServerName(true).' Debug';
		$mailer->SendEmail(gpdebug, $subject, $mess);
	}
	return false;
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
}

//If Register Globals
if( \gp\tool::IniGet('register_globals') ){
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
	$args = func_get_args(); //for php previous to 5.3
	call_user_func_array('msg',$args);
}

/**
 * @since 4.0
 *
 */
function msg(){
	global $wbMessageBuffer;

	$args = func_get_args();

	if( empty($args[0]) ){
		return;
	}

	if( isset($args[1]) ){
		$wbMessageBuffer[] = '<li>'.call_user_func_array('sprintf',$args).'</li>';
	}elseif( is_array($args[0]) || is_object($args[0]) ){
		$wbMessageBuffer[] = '<li>'.pre($args[0]).'</li>';
	}else{
		$wbMessageBuffer[] = '<li>'.$args[0].'</li>';
	}
}

/**
 * Output the message buffer
 *
 */
function GetMessages( $wrap = true ){
	global $wbMessageBuffer,$gp_not_writable,$langmessage;

	if( \gp\tool::loggedIn() && count($gp_not_writable) > 0 ){
		$files = '<ul><li>'.implode('</li><li>',$gp_not_writable).'</li></ul>';
		$message = sprintf($langmessage['not_writable'],\gp\tool::GetUrl('Admin/Status')).$files;
		msg($message);
		$gp_not_writable = array();
	}

	$result = $wrap_end = '';

	if( $wrap ){
		$result = "\n<!-- message_start ".gp_random." -->";
		$wrap_end = "<!-- message_end -->\n";
	}
	if( !empty($wbMessageBuffer) ){

		if( gpdebug === false ){
			$wbMessageBuffer = array_unique($wbMessageBuffer);
		}

		$result .= '<div class="messages gp-fixed-adjust"><div>';
		$result .= '<a style="" href="#" class="req_script close_message" data-cmd="close_message"></a>';
		$result .= '<ul>';
		$result .= implode('',$wbMessageBuffer);
		$result .= '</ul></div></div>';
	}

	return $result .= \gp\tool::ErrorBuffer().$wrap_end;
}


/**
 * Include a file relative to the include directory of the current installation
 *
 */
function includeFile( $file ){
	global $dataDir;

	switch($file){
		case 'tool/ajax.php':
		$file = 'tool/Output/Ajax.php';
		break;

		case 'tool/editing.php':
		$file = 'tool/Editing.php';
		break;

		case 'tool/email_mailer.php':
		$file = 'tool/Emailer.php';
		break;

		case 'tool/gpOutput.php':
		$file = 'tool/Output.php';
		break;

		case 'tool/Images.php':
		$file = 'tool/Image.php';
		break;

		case 'tool/sessions.php';
		$file = 'tool/Session.php';
		break;

		case 'tool/SectionContent.php':
		$file = 'tool/Output/Sections.php';
		break;

		case 'tool/recaptcha.php':
		$file = 'tool/Recaptcha.php';
		break;

		case 'tool/Page_Rename.php':
		$file = 'Page/Rename.php';
		break;

		case 'special/special_contact.php':
		$file = 'special/Contact.php';
		break;

		case 'admin/admin_browser.php':
		$file = 'admin/Content/Browser.php';
		break;

		case 'admin/admin_preferences.php':
		$file = 'admin/Settings/Preferences.php';
		break;

		case 'admin/admin_uploaded.php':
		$file = 'admin/Content/Uploaded.php';
		break;

		case 'admin/admin_tools.php':
		$file = 'admin/Tools.php';
		break;

		case 'admin/tool_thumbnails.php';
		$file = 'tool/Image.php';
		break;
	}

	require_once( $dataDir.'/include/'.$file );
}

/**
 * Include a script, unless it has caused a fatal error.
 * Using this function allows handling fatal errors that are thrown by the included php scripts
 *
 * @param string $file The full path of the php file to include
 * @param string $include_variation Which variation or adaptation of php's include() function to use (include,include_once,include_if, include_once_if, require ...)
 * @param array List of global variables to set
 */
function IncludeScript($file, $include_variation = 'include_once', $globals = array() ){

	$exists = file_exists($file);

	//check to see if it exists
	$include_variation = str_replace('_if','',$include_variation,$has_if);
	if( $has_if && !$exists ){
		return;
	}

	//check for fatal errors
	if( \gp\tool\Output::FatalNotice( 'include', $file ) ){
		return false;
	}


	//set global variables
	foreach($globals as $global){
		global $$global;
	}


	switch($include_variation){
		case 'include':
			$return = include($file);
		break;
		case 'include_once':
			$return = include_once($file);
		break;
		case 'require':
			$return = require($file);
		break;
		case 'require_once':
			$return = require_once($file);
		break;
	}

	\gp\tool\Output::PopCatchable();

	return $return;
}



/**
 * Similar to print_r and var_dump, but it is output buffer handling function safe
 * msg( pre(array(array(true))) );
 * msg( pre(new tempo()) );
 */
function pre($mixed){
	static $level = 0;
	$output = '';

	$type = gettype($mixed);
	switch($type){
		case 'object':
			$type = get_class($mixed).' object';
			$output = $type.'(...)'."\n"; //recursive object references creates an infinite loop
		break;
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
function showArray($mixed){ trigger_error('Deprecated function showArray(). Use pre() instead'); }


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

