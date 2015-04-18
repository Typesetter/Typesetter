<?php
defined('is_running') or die('Not an entry point...');

class Example_Ajax{
	function Example_Ajax(){
		global $page, $addonRelativeCode;

		//prepare the page
		$page->head_js[] = $addonRelativeCode.'static/02_script.js';
		$page->admin_js = true;

		//get request parameters and execute any commands
		$string = '';
		if( isset($_REQUEST['string']) ){
			$string = $_REQUEST['string'];
		}
		$cmd = common::GetCommand();
		switch($cmd){
			case 'randomstring':
				$string = common::RandomString(10);
			break;
		}

		//display the form
		echo '<h2>Example Ajax Requests</h2>';
		echo '<form method="post" action="'.$page->title.'">';
		echo 'Text: <input type="text" name="string" value="'.htmlspecialchars($string).'" size="30" />';
		echo ' <input type="submit" class="gpajax" value="Post Form Asynchronosly" /> ';
		echo common::Link($page->title,'Get Random String','cmd=randomstring','name="gpajax"');
		echo '</form>';


		//output the $_REQUEST variable
		echo '<h3>Request</h3>';
		echo pre($_REQUEST);


		//plugin example navigation
		gpPlugin::incl('navigation.php');
		PluginExampleNavigation();
	}
}


