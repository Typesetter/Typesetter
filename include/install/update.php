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

common::GetLangFile();

$page = new \gp\Admin\Update();

gpOutput::HeadContent();
includeFile('install/template.php');

