<?php
defined('is_running') or die('Not an entry point...');

/* 
 * Install_Check() can be used to check the destination server for required features
 * 		This can be helpful for addons that require PEAR support or extra PHP Extensions
 * 		Install_Check() is called from step1 of the install/upgrade process
 */
function Install_Check(){
  return true;
}
