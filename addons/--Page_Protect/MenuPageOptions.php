<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('PageProtect.php');

global $protect_object;
$protect_object = new PageProtect();


function ProtectOptions($title,$menu_key,$menu_value,$layout_info){
	global $protect_object;


	$is_protected = $protect_object->IsProtected($menu_key);

	if( !$is_protected ){
		$label = 'Protect Page';
	}elseif( $is_protected === 1 ){
		$label = 'Remove Protection';
	}elseif( $is_protected === 2 ){
		$label = 'Parent Protected';
	}

	$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" />';
	//echo common::Link($title,$img.$label,'cmd=passprotect','name="gpajax"');
	echo common::Link('Admin_Menu',$img.$label,'cmd=passprotect&index='.$menu_key,'name="postlink"'); //menupost
}


function ProtectCommand($cmd){
	global $protect_object,$gp_titles,$langmessage;

	switch($cmd){
		case 'passprotect':
		case 'rm_protection':
		case 'protect_page':
		break;
		default:
		return $cmd;
	}

	if( !isset($_POST['index']) || !isset($gp_titles[$_POST['index']]) ){
		message($langmessage['OOPS']);
		return $cmd;
	}



	$index = $_POST['index'];
	$protect_object->IsProtected($index);
	return $protect_object->Admin($cmd,$index);

}
