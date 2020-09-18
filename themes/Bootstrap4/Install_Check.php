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
	//Copyright Notice
	if( !\gp\admin\Content\Extra::AreaExists('Copyright_Notice') ){
		$file		= $dataDir . '/data/_extra/Copyright_Notice/page.php';
		$content	= '<p>&copy; $currentYear My ' . CMS_NAME . '</p>';

		if( \gp\install\Tools::NewExtra($file, $content) ){
			msg('<i class="fa fa-check"></i> The new Extra Content <em>Copyright Notice</em> was created.');
		}else{
			msg('<i class="fa fa-exclamation-triangle"></i> Warning: ' .
				'The new Extra Content <em>Copyright Notice</em> could not be created.');
		}
	}

	//Header Contact
	if( !\gp\admin\Content\Extra::AreaExists('Header_Contact') ){
		$file		= $dataDir . '/data/_extra/Header_Contact/page.php';
		$content	= '<span><i class="fa fa-envelope">&zwnj;</i>&nbsp;<a href="mailto:$email">$email</a></span>
		<span><i class="fa fa-phone">&zwnj;</i>&nbsp;+1 2345 6789 0</span>';

		if( \gp\install\Tools::NewExtra($file, $content) ){
			msg('<i class="fa fa-check"></i> The new Extra Content <em>Header Contact</em> was created.');
		}else{
			msg('<i class="fa fa-exclamation-triangle"></i> Warning: ' .
				'The new Extra Content <em>Header Contact</em> could not be created.');
		}
	}

	//Header Social Media
	if( !\gp\admin\Content\Extra::AreaExists('Header_SocialMedia') ){
		$file		= $dataDir . '/data/_extra/Header_SocialMedia/page.php';
		$content	= '<a title="Twitter" class="fa fa-twitter" target="blank" href="https://twitter.com">&zwnj;</a>
		<a title="facebook" class="fa fa-facebook" target="blank" href="https://www.facebook.com">&zwnj;</a>
		<a title="Instagram" class="fa fa-instagram" target="blank" href="https://www.instagram.com">&zwnj;</a>
		<a title="LinkedIn" class="fa fa-linkedin-square" target="blank" href="https://www.linkedin.com">&zwnj;</a>
		<a title="YouTube" class="fa fa-youtube" target="blank" href="https://www.youtube.com">&zwnj;</a>
		<a title="Skype" class="fa fa-skype" target="blank" href="https://www.skype.com">&zwnj;</a>';
		if( \gp\install\Tools::NewExtra($file, $content) ){
			msg('<i class="fa fa-check"></i> The new Extra Content <em>Header SocialMedia</em> was created.');
		}else{
			msg('<i class="fa fa-exclamation-triangle"></i> Warning: ' .
				'The new Extra Content <em>Header SocialMedia</em> could not be created.');
		}
	}

	return true;
}
