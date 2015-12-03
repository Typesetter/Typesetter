<?php
defined('is_running') or die('Not an entry point...');


$texts = array();
$texts[] = 'Blog';
$texts[] = 'Blog Home';
$texts[] = 'Newer Entry';
$texts[] = 'Newer Entries';
$texts[] = 'Older Entry';
$texts[] = 'Older Entries';
$texts[] = 'Read More';
$texts[] = 'More Blog Entries';
$texts[] = 'Name';
$texts[] = 'Website';
$texts[] = 'Categories';
$texts[] = 'Archives';
$texts[] = 'Comments';
$texts[] = 'Comment';
$texts[] = 'Leave Comment';
$texts[] = 'Add Comment';
$texts[] = 'Comments have been closed.';
$texts[] = 'Open Comments';
$texts[] = 'Close Comments';






/* this function can be used to update the addon once changes to the text values have been made */
function OnTextChange(){
	gpPlugin::incl('SimpleBlogCommon.php');
	new SimpleBlogCommon();//regenerate the gadget and feed
}
