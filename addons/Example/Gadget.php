<?php

namespace Addon\Example;

defined('is_running') or die('Not an entry point...');

class Gadget{

	function __construct(){
		echo '<h2>Gadget Example</h2>';
		echo '<div>';
		echo 'This is content that can be included in your theme.';
		echo '</div>';
	}

}
