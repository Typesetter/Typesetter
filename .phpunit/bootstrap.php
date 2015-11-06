<?php

echo "\n************************************************************************************";
echo "\nBegin gpEasy Tests\n\n";


defined('is_running') or define('is_running',true);
include('include/common.php');
common::SetGlobalPaths(0,'bootstap.php');




class gptest_bootstrap extends PHPUnit_Framework_TestCase{

	function setUP(){}

}