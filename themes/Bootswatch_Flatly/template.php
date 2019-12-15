<?php

global $page, $config;
$path = $page->theme_dir . '/drop_down_menu.php';
include_once($path);
$lang = isset($page->lang) ? $page->lang : $config['language'];

include($page->theme_dir . '/' . $page->theme_color . '/template.php');
