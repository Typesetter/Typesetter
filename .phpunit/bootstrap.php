<?php

echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('is_running') or define('is_running',true);
global $dataDir;
$dataDir = $_SERVER['PWD'];
include('include/common.php');

includeFile('tool/display.php');
includeFile('tool/Files.php');
includeFile('tool/gpOutput.php');
includeFile('tool/functions.php');
includeFile('tool/Plugins.php');


class gptest_bootstrap extends PHPUnit_Framework_TestCase{

	function setUP(){}

}