<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'sidebars' layout functions
 */


 /**
 * If you are using Multi-Language Manager 1.2.3+
 * and want to use localized $langmessage values in the template,
 * uncomment the following line
 *
 */
// common::GetLangFile('main.inc', $page->lang);


/**
 * Load required components
 *
 */
common::LoadComponents('bootstrap4-js,fontawesome');
if( isset($layout_config['mobile_menu_style']['value']) &&
	( $layout_config['mobile_menu_style']['value'] == 'offcanvas' ||
		$layout_config['mobile_menu_style']['value'] == 'slideover' )
){
	common::LoadComponents('jquery-touch');
}

/**
 * Load layout javascript, if it exists
 *
 */
$layout_js = $page->theme_dir . '/' . $page->theme_color . '/script.js';
if( file_exists($layout_js) ){
	$page->head_js[] = rawurldecode($page->theme_path) . '/script.js';
}

/*
 * Set variables required in template.php
 */

// default values
$html_classes					= '';
$complementary_header_classes	= 'd-none d-md-block';
$navbar_expand_breakpoint		= 'lg';
$navbar_classes					= 'navbar-expand-lg';
$mobile_menu_style				= 'pulldown'; // 'pulldown' (default) | 'popup' | 'slideover' | 'offcanvas'
$menu_alignment_class			= 'ml-auto'; // 'ml-auto' = right | 'mx-auto' = center | 'mr-auto' = left
$menu_dropdown_alignment_class	= 'dropdown-menu dropdown-menu-right';
$brand_logo_img					= '';
$brand_logo_alt					= 'Logo';
$sidebar_left_class				= ' sidebar-sticky';
$sidebar_right_class			= ' sidebar-sticky';

// override default values using layout config
if( !empty($layout_config['complementary_header_fixed']['value']) &&
	( isset($layout_config['complementary_header_show']['value']) &&
		$layout_config['complementary_header_show']['value'] !== false )
){
	$html_classes .= ' complementary-header-fixed';
}

if( isset($layout_config['complementary_header_show']['value']) ){
	if( $layout_config['complementary_header_show']['value'] === true ){
		$complementary_header_classes = 'd-block';
	}else{
		$complementary_header_classes = 'd-none d-' .
		$layout_config['complementary_header_show']['value'] .
		'-block';
	}
}

if( !empty($layout_config['header_sticky']['value']) ){
	$html_classes .= ' header-sticky';
}

if( isset($layout_config['header_brand_logo_alt_text']['value']) ){
	$brand_logo_alt = htmlspecialchars($layout_config['header_brand_logo_alt_text']['value']);
}

if( !empty($layout_config['header_brand_logo']['value']) ){
	$brand_logo_img = '<img alt="' . htmlspecialchars($brand_logo_alt) . '" class="brand-logo" ' .
		'src="' . filter_var($layout_config['header_brand_logo']['value'], FILTER_SANITIZE_SPECIAL_CHARS) .
		'" />';
}

if( !empty($layout_config['navbar_expand_breakpoint']['value']) ){
	$navbar_expand_breakpoint = $layout_config['navbar_expand_breakpoint']['value'];
	$navbar_classes = ' navbar-expand-' . $navbar_expand_breakpoint;
}

if( !empty($layout_config['main_menu_align']['value']) ){
	switch( $layout_config['main_menu_align']['value'] ){
		case 'left':
			$menu_alignment_class = 'mr-auto';
			$menu_dropdown_alignment_class = 'dropdown-menu';
			break;
		case 'center':
			$menu_alignment_class = 'mx-auto';
			$menu_dropdown_alignment_class = 'dropdown-menu';
			break;
		case 'right':
			$menu_alignment_class = 'ml-auto';
			$menu_dropdown_alignment_class = 'dropdown-menu dropdown-menu-right';
			break;
	}
}

if( !empty($layout_config['mobile_menu_style']['value']) ){
	$mobile_menu_style = $layout_config['mobile_menu_style']['value'];
}
$html_classes .= ' mobile-menu-' . $mobile_menu_style;

if( isset($layout_config['sidebar_left_sticky']['value']) &&
	!empty($layout_config['sidebar_left_sticky']['value']) 
){
	$sidebar_left_class = ' sidebar-sticky';
}else{
	$sidebar_left_class = '';
}

if( isset($layout_config['sidebar_right_sticky']['value']) &&
	!empty($layout_config['sidebar_right_sticky']['value']) 
){
	$sidebar_right_class = ' sidebar-sticky';
}else{
	$sidebar_right_class = '';
}



/**
 * Function to reformat the main menu elements
 * to become a Bootstrap 4 dropdown-navigation
 *
 */
function MainNavElements($node, $attributes, $level, $menu_id, $item_position){
	GLOBAL $GP_MENU_LINKS;

	if( $node == 'a' ){
		$format = $GP_MENU_LINKS;
		// Bootstrap4 navbars require a nav-link class for 1st-level anchors 
		// and a dropdown-item class for anchors in subsequent dropdown menus
		$strpos_class = strpos($attributes['attr'], 'class="');
		$add_class = ($level > 0) ? "dropdown-item" : "nav-link";

		if( $strpos_class === false ){
			$attributes['attr'] .= ' class="' . $add_class . '"';
			$strpos_class = strpos($attributes['attr'], 'class="');
		}else{
			$attributes['attr'] = substr($attributes['attr'], 0, $strpos_class + 7) .
				$add_class . ' ' .
				substr($attributes['attr'], $strpos_class + 7);
		}

		$search = array('{$href_text}', '{$attr}', '{$label}', '{$title}');

		if( in_array('dropdown-toggle', $attributes['class']) ){
			$format =	'<a {$attr} data-toggle="dropdown" href="{$href_text}">{$label} ';
			// $format .=	 '<span class="caret"></span>';
			$format .= '</a>';
		}else{
			$format = '<a {$attr} href="{$href_text}">{$label}</a>';
		}

		return str_replace($search, $attributes, $format);
	}

}
