<?php
defined('is_running') or die('Not an entry point...');

/*
 * install_tool code moved to tool/install.php, /install/install_tools.php will be deleted
 */
trigger_error('use /tool/install.php instead of /install/install_tools.php');
includeFile('tool/install.php');
