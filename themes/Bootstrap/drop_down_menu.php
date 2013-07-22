<?php


$GP_MENU_ELEMENTS = 'BootstrapMenu';

function BootstrapMenu($node, $attributes){
	GLOBAL $GP_MENU_LINKS;

	if( $node == 'a' ){
		$format = $GP_MENU_LINKS;
		$search = array('{$href_text}','{$attr}','{$label}','{$title}');

		if( in_array('dropdown-toggle',$attributes['class']) ){
			$format = '<a {$attr} data-toggle="dropdown" href="{$href_text}" title="{$title}">{$label}<b class="caret"></b></a>';
		}

		return str_replace( $search, $attributes, $format );
	}
}

