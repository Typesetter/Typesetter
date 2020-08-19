<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * Installation Check
 */

defined('is_running') or die('Not an entry point...');

/**
 * Install_Check() can be used to check the destination server for required features
 * 	This can be helpful for addons that require PEAR support or extra PHP Extensions
 * 	Install_Check() is called from step1 of the install/upgrade process
 */
function Install_Check(){
	global $dataDir;

	/**
	 * create required Extra Content areas if they don't already exist
	 *
	 */
	if( !\gp\admin\Content\Extra::AreaExists('Copyright_Notice') ){
		$file		= $dataDir . '/data/_extra/Copyright_Notice/page.php';
		$content	= '<p>&copy; $currentYear My Company</p>';

		if( \gp\install\Tools::NewExtra($file, $content) ){
			msg('<i class="fa fa-check"></i> The new Extra Content <em>Copyright Notice</em> was created.');
		}else{
			msg('<i class="fa fa-exclamation-triangle"></i> Warning: ' . 
				'The new Extra Content <em>Copyright Notice</em> could not be created.');
		}
	}

	return true;
}
