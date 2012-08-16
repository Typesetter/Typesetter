<?php

define('is_running',true);

//define('gpdebug',true);
require_once('../common.php');
common::EntryPoint(2,'update.php');


/* check permissions */
if( !common::LoggedIn() ){
	die('You must be logged in to access this area.');
}

if( !isset($gpAdmin['granted']) || ($gpAdmin['granted'] !== 'all') ){
	die('Sorry, you do not have sufficient privileges to access this area.');
}

includeFile('admin/admin_tools.php');
includeFile('tool/update.php');
common::GetLangFile();

$page = new update_class();

gpOutput::HeadContent();
includeFile('install/template.php');

