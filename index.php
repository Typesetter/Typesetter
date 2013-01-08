<?php

/**
 * Make sure php is running
 *
 */
if( false ){
	?>
	<!DOCTYPE html>
	<html>
	<head>
	<meta charset="UTF-8" />
	<title>Error: PHP is not running</title>
	</head>
	<body>
	<h1><a href="http://gpeasy.com/">gpEasy</a></h1>
	<h2>Error: PHP is not running</h2>
	<p>gpEasy requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
	</body>
	</html>
	<?php
}


/**
 * See gpconfig.php for configuration options
 *
 */

if( file_exists('gpconfig.php') ) require_once('gpconfig.php');

require_once('./include/main.php');
