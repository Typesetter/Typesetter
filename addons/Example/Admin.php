<?php

namespace Addon\Example;

defined('is_running') or die('Not an entry point...');


class Admin{

	function __construct(){
		echo '<h2>This is an Admin Only Script</h2>';

		echo '<p>This is an example of a Typesetter Addon in the form of a Admin page.</p>';

		echo '<p>Admin pages are only accessible to users with appropriate permissions on your installation of Typesetter CMS. </p>';

		echo '<p>';
		echo \common::Link('Special_Example','An Example Link');
		echo '</p>';

		echo '<p>You can download <a href="https://www.typesettercms.com/Special_Addon_Plugins?id=160">a plugin with addtional examples</a> from typesettercms.com </p>';
	}
}


