<?php
defined('is_running') or die('Not an entry point...');


$Install['Addon_Name'] = 'Multi Site';	// (required)
$Install['Addon_Version'] = '1.1';

$Install['min_gpeasy_version'] = '1.5B1';


//	Admin_links (Optional)
//	Define scripts that are only accessible to administrators with appropriate permissions
	$Admin_Links['Site_Setup']['label'] = 'Setup Site'; 		// (required)
	$Admin_Links['Site_Setup']['script'] = 'SetupSite.php';	// (required) relative to the addon directory
	$Admin_Links['Site_Setup']['class'] = 'SetupSite';		// (optional)






