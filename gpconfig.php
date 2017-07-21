<?php

/**
 * $upload_extensions_allow and $upload_extensions_deny
 * Allow or deny the upload of files based on their file extensions
 * The default list of allowed extenstions is 
 * array( '7z',
 *        'aiff', 'asf', 'avi', 
 *        'bmp', 'bz', 
 *        'css', 'csv', 
 *        'doc', 'docx', 
 *        'fla', 'flac', 'flv', 
 *        'gif', 'gz', 'gzip', 
 *        'htm', 'html', 
 *        'ico', 
 *        'jpeg', 'jpg', 'js', 'json', 
 *        'less', 
 *        'm4v', 'md, 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 
 *        'ods', 'odt', 'ogg', 'oga', 'ogv', 'opus', 
 *        'pages', 'pdf', 'png', 'ppt', 'pptx', 
 *        'qt', 
 *        'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 
 *        'scss', 'svg', 'svgz', 'swf', 'sxc', 'sxw',
 *        'tar', 'tgz', 'tif', 'tiff', 'txt', 
 *        'vsd', 
 *        'wav', 'wma', 'webm', 'wmv', 
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
define('gp_default_theme','Three_point_5/Shore');


/**
 * load_css_in_body
 * If defined true, stylesheet <link>s will be placed at then end of the <body> element (but before scripts) instead of in the <head> element.
 * Defined false forces styleheets to the <head> even if a theme or addon defines it to true via gp_defined('load_css_in_body', true);
 * Undefined loads stylesheets in the head but allows later changes by themes/addons.
 * Defaults to undefined
 *
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
 *
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
define('gpdebug',false);


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
 * Include clearfloats in Typesetter generated code
 * define('clear_floats',false); experimental
 */


