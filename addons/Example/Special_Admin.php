<?php

namespace Addon\Example;

defined('is_running') or die('Not an entry point...');


class Special_Admin extends \Addon\Example\Special{

	function __construct(){
		echo '<h2>This is the Admin version of a Special Script</h2>';

		echo '<p>This is an example of a Typesetter Addon in the form of a Special page.</p>';

		echo '<p>Special pages can be used to add more than just content to a Typesetter CMS installation. </p>';

		echo '<p>';
		echo \gp\tool::Link('Admin_Example','An Example Link');
		echo '</p>';

		echo '<p>You can download <a href="http://www.typesettercms.com/Special_Addon_Plugins?id=160">a plugin with addtional examples</a> from typesettercms.com </p>';

		$this->AutomaticallyLoaded();
	}

}


