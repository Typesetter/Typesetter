<?php
defined('is_running') or die('Not an entry point...');


class Example_Map{
	function Example_Map(){
		global $page, $addonRelativeCode;

		//add css and js to <head>
		$page->head .= '<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />';
		$page->head .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&language=en"></script>';
		$page->head .= '<script type="text/javascript" src="'.$addonRelativeCode.'/static/01_script.js"></script>';


		//html contents of the page
		echo '<h2>Display a Google Map With Directions</h2>';

		echo '<div id="input">';
		echo '<input id="map_address" type="textbox" value="starting point" />';
		echo '<input type="button" value="calculate route" id="calc_route_button" />';
		echo '</div>';
		echo '<div id="directionsPanel" style="float:right;width:300px;"></div>';
		echo '<div id="map_canvas" style="width:500px;height:500px;"></div>';

		//plugin example navigation
		gpPlugin::incl('navigation.php');
		PluginExampleNavigation();
	}
}


