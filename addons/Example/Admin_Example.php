<?php
defined('is_running') or die('Not an entry point...');


class Admin_Example{
	function Admin_Example(){
		echo '<h2>This is an Admin Only Script</h2>';

		echo '<p>This is an example of a gpEasy Addon in the form of a Admin page.</p>';

		echo '<p>Admin pages are only accessible to users with appropriate permissions on your installation of gpEasy. </p>';

		echo '<p>';
		echo common::Link('Special_Example','An Example Link');
		echo '</p>';

		echo '<p>You can download <a href="http://gpeasy.com/Special_Addon_Plugins?id=160">a plugin with addtional examples</a> from gpEasy.com </p>';
	}
}


