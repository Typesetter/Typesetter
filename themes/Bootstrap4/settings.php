<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * settings for all layouts
 */

defined('is_running') or die('Not an entry point...');

// Text Area for navbar-brand
gpOutput::Area('Site-Name', '<span class="brand-name">%s</span>');

// Search Gadget in a Text Area
ob_start();
gpOutput::GetGadget('Search');
$area_content = ob_get_clean();
gpOutput::Area('Search-Gadget', $area_content);

// Admin Link in a Text Area
ob_start();
gpOutput::GetAdminLink(false); // false = do not attach messages here
$area_content = ob_get_clean();
gpOutput::Area('Admin-Link-Area', '%s' . $area_content);

// Simple Blog Gadgets in Text Areas
ob_start();
gpOutput::GetGadget('Simple_Blog');
$area_content = ob_get_clean();
gpOutput::Area('Simple-Blog-Gadget', $area_content);

ob_start();
gpOutput::GetGadget('Simple_Blog_Categories');
$area_content = ob_get_clean();
gpOutput::Area('Simple-Blog-Categories-Gadget', $area_content);

ob_start();
gpOutput::GetGadget('Simple_Blog_Archives');
$area_content = ob_get_clean();
gpOutput::Area('Simple-Blog-Archives-Gadget', $area_content);


/**
 * Include current layout settings
 *
 */
include($page->theme_dir . '/' . $page->theme_color . '/settings.php');
