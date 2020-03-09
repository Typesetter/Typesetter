<?php

define("local", !filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));

/**
 * $upload_extensions_allow and $upload_extensions_deny
 * Allow or deny the upload of files based on their file extensions
 * The default list of allowed extenstions is
 * array( '7z',
 *        'aiff', 'asf', 'avi',
 *        'bmp', 'bz',
 *        'css', 'csv',
 *        'doc', 'docx',
 *        'eot',
 *        'fla', 'flac', 'flv',
 *        'gif', 'gz', 'gzip',
 *        'htm', 'html',
 *        'ico',
 *        'jpeg', 'jpg', 'js', 'json',
 *        'less',
 *        'm4v', 'md, 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg',
 *        'ods', 'odt', 'ogg', 'oga', 'ogv', 'opus', 'otf',
 *        'pages', 'pdf', 'png', 'ppt', 'pptx',
 *        'qt',
 *        'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf',
 *        'scss', 'swf', 'sxc', 'sxw', // 'svg' and 'svgz' can be enabled via Settings -> Configuration
 *        'tar', 'tgz', 'tif', 'tiff', 'ttf', 'txt',
 *        'vsd',
 *        'wav', 'webmanifest', 'webm', 'webp', 'wma', 'wmv', 'woff', 'woff2',
 *        'xls', 'xlsx', 'xml', 'xsl'
 *        'zip',
 * )
 * Note: gp_restrict_uploads has to be set to true for upload_extension settings to have any effect
 */
define('gp_restrict_uploads',true);
$upload_extensions_allow = array();
$upload_extensions_deny = array();


/**
 * gp_default_theme
 * Theme/color to be used when Typesetter is first installed.
 * Also the theme/color that Typesetter will use should the user specified theme become unavailable
 *
 */
define('gp_default_theme','Bootswatch_Scss/Flatly');


/**
 * create Scss and LESS source maps
 * Useful during design / development to see the original location of Scss / LESS rules in the web browser dev tools.
 * Source maps take up some additional disk space and should ultimately be disabled on live sites.
 *
 * NOTE! Currently we do not create source maps for combined css files.
 *       Using this option will override config settings and 'combine css' OFF
 *
 * Defaults to undefined (commented out)
 */
// define('create_css_sourcemaps',true);


/**
 * load_css_in_body
 * If defined true, stylesheet <link>s will be placed at then end of the <body> element (but before scripts) instead of in the <head> element.
 * Defined false forces styleheets to the <head> even if a theme or addon defines it to true via gp_defined('load_css_in_body', true);
 * Undefined loads stylesheets in the head but allows later changes by themes/addons.
 *
 * Defaults to undefined (commented out)
 */
// define('load_css_in_body',true);


/**
 * gp_browser_auth
 * Set to true to enable additional security by requiring a static browser identity for user session. Disabled by default since gpEasy 2.3.2
 * Enabling this feature may require administrators to log back in. If administrators report they are being logged out, then you may need to disable this feature
 * Defaults to false
 */
define('gp_browser_auth',false);


/**
 * gp_require_encrypt
 * Set to true to require admin area encrypted login.
 * This will only disable un-encrypted login and not hide the option on the login page. When true, attempts to login without encryption will fail
 * Defaults to false
 */
define('gp_require_encrypt',false);

/**
 * gp_nonce_algo
 * Which hash algorithm to use for nonces
 *
 */
define('gp_nonce_algo','sha512');


/**
 * gp_index_filenames
 * Set to false to use filenames that reflect the titles of pages instead using the data index
 * Setting to false may result in the loss of functionality including the inability to use hierarchical file names
 * Defaults to true
 */
define('gp_index_filenames',true);


/**
 * gp_remote_plugins, gp_remote_themes, gp_remote_update
 * Disable installation of remote addons
 * Defaults to true
 */
define('gp_remote_plugins',true);
define('gp_remote_themes',true);
define('gp_remote_update',true);


/**
 * gp_unique_addons
 * Displays only the latest version of an addon (plugin or theme) if true
 * Defaults to false
 */
define('gp_unique_addons',false);


/**
 * Using setlocale() may enable more language specific ouptput for dates, times etc
 * http://php.net/manual/en/function.setlocale.php
 * Defaults to en_US
 */
//setlocale(LC_ALL, 'en_US');


/**
 * service_provider_id
 * For typesettercms.com/Providers
 * Add your service provider id for tracking and to increase service provider activity level
 * Defaults to false
 */
define('service_provider_id',false);


/**
 * Limit the number of revisions to store in the backup
 * Defaults to 30
 */
define('gp_backup_limit',30);


/**
 * gp_chmod_file
 * The mode used by chmod() for data files
 * http://php.net/manual/en/function.chmod.php
 * Defaults to 0666
 */
define('gp_chmod_file',0666);

/**
 * gp_chmod_dir
 * The mode use by chmod for data folders
 * http://php.net/manual/en/function.chmod.php
 * Defaults to 0755
 */
define('gp_chmod_dir',0755);


/**
 * gpdebug
 * Set to true to display php errors in the browser window.
 * Defaults to false
 */
defined('gpdebug') or define('gpdebug',false);


/**
 * gpdebugjs
 * Set to true to display javascript errors in the browser window. Separate from gpdebug to allow specific debugging
 * Defaults to the value set for gpdebug
 */
//define('gpdebugjs',false);


/**
 * Prevent errors from being displayed to site visitors
 * Should be set to "0" for any production site
 * Set to "1" if Typesetter is unable to display errors with gpdebug set to "true" (see above)
 *
 */
@ini_set('display_errors',0);


/**
 * gp_safe_mode
 * Set to true to turn safe mode on. This will prevent addons from being used.
 * Defaults to the value set for gpdebug
 */
//define('gp_safe_mode',false);


/**
 * Show notifications of deprecated addons
 * Some addons should be uninstalled with the current version of Typesetter
 * e.g. due to incompatibilities or because their functionality has been added to the CMS core
 * Set to false if you still want to keep them installed and prevent notifications
 * See also /include/deprecated.php
 * Defaults to true
 *
 */
define('notify_deprecated',true);


/**
 * gp_prefix_urls
 * Set to true will prefix internal content URLs (href, src, ..., starting with '/')
 * with $LinkPrefix or $dirPrefix variables when saving in order to make the
 * content portable across different directory levels and hosts
 * Defaults to false
 *
 * not yet implemented
 */
define('gp_prefix_urls',false);


/**
 * Include clearfloats in Typesetter generated code
 * define('clear_floats',false); experimental
 */
