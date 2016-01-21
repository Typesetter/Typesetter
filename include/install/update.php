<?php

define('is_running',true);

//define('gpdebug',true);
require_once('../common.php');
\gp\tool::EntryPoint(2,'update.php');


/* check permissions */
if( !\gp\tool::LoggedIn() ){
	die('You must be logged in to access this area.');
}

if( !isset($gpAdmin['granted']) || ($gpAdmin['granted'] !== 'all') ){
	die('Sorry, you do not have sufficient privileges to access this area.');
}

\gp\tool::GetLangFile();

$page = new \gp\admin\Update();

\gp\tool\Output::HeadContent();
includeFile('install/template.php');

