<?php 
defined('is_running') or die('Not an entry point...');

/**
 * Set notify to false to prevent notifications for particular addons
 *
 */
$deprecated_addons = array(
	'Hide Admin UI' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'Expandable Editor' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'Section Clipboard' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'Highlight Sections' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'Section Visibility' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'Selectable Classes' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'File-Include Source Link' => array(
		'upto_version'		=> 'all',
		'reason'			=> 'The addon is no longer needed because it is now part of the system core and will cause issues.',
		'notify'			=> true,
	),
	'FullCalendar for gpEasy' => array(
		'upto_version'		=> '1.1',
		'reason'			=> 'The addon is not compatible with Typesetter CMS 5+ and will not work anymore',
		'notify'			=> true,
	),
	'FlatAdmin 2015' => array(
		'upto_version'		=> '1.2',
		'reason'			=> 'The addon is not compatible with Typesetter CMS 5+ and will cause issues.',
		'notify'			=> true,
	),
);
