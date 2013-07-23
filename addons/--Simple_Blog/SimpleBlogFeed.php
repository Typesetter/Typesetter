<?php
defined('is_running') or die('Not an entry point...');

global $addonPathData;
$feed_file = $addonPathData.'/feed.atom';
if( !file_exists($feed_file) ){
	die('Feed does not exist');
}

header('Content-Type: application/atom+xml; charset=UTF-8');
echo file_get_contents($feed_file);
die();
