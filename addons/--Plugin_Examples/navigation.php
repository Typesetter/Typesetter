<?php
defined('is_running') or die('Not an entry point...');


function PluginExampleNavigation(){
	global $page;

	$examples = array();
	$examples['special_example_map'] = 'Google Map Example with Directions';
	$examples['special_example_ajax'] = 'Asynchronous Form Submission and Page Loading';


	echo '<h3>All Examples</h3>';
	echo '<ol>';
	foreach($examples as $slug => $label){
		if( $page->gp_index == $slug ){
			echo '<li><b>'.$label.'</b></li>';
		}else{
			echo '<li>'.common::Link($slug,$label).'</li>';
		}
	}
	echo '</ol>';
}
