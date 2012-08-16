<?php
defined('is_running') or die('Not an entry point...');


class Special_Example{
	function Special_Example(){
		echo '<h2>This is a Special Script</h2>';

		echo '<p>This is an example of a gpEasy Addon in the form of a Special page.</p>';

		echo '<p>Special pages can be used to add more than just content to a gpEasy installation. </p>';

		echo '<p>';
		echo common::Link('Admin_Example','An Example Link');
		echo '</p>';

		echo '<p>You can download <a href="http://gpeasy.com/Special_Addon_Plugins?id=160">a plugin with addtional examples</a> from gpEasy.com </p>';
	}
}


