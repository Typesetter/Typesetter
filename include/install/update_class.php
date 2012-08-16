<?php
defined('is_running') or die('Not an entry point...');

/*
 * update_class code moved to tool/update.php, /install/update_class.php file will be deleted
 */
trigger_error('use /tool/update.php instead of /install/update_class.php');
includeFile('tool/update.php');
