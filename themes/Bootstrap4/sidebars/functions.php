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


/**
 * Load layout javascript, if it exists
 *
 */
$layout_js = $page->theme_dir . '/' . $page->theme_color . '/script.js';
if( file_exists($layout_js) ){
  $page->head_js[] = rawurldecode($page->theme_path) . '/script.js';
}

/**
 * Set variables required in template.php
 * based on layout config/customizer
 *
 */
$html_classes = '';
if( !empty($layout_config['header_fixed']['value']) ){
  $html_classes .= ' header-fixed';
}

$header_classes = '';
if( !empty($layout_config['header_fixed']['value']) ){
  $header_classes .= ' fixed-top gp-fixed-adjust';
}

$navbar_classes = ' navbar-expand-' . $layout_config['navbar_expand_breakpoint']['value'];

$header_container_class = 'no-container';
if( !empty($layout_config['header_use_container']['value']) ){
  $header_container_class = 'container';
}

$brand_logo = '';
if( !empty($layout_config['header_brand_logo']['value']) ){
  $brand_logo = '<img class="brand-logo" src="' . $layout_config['header_brand_logo']['value'] . '" />';
}

$breadcrumb_nav = '';
if( !empty($layout_config['show_breadcrumb_nav']['value']) ){
  ob_start();
  gpOutput::Get('BreadcrumbNav');
  $breadcrumb_nav = ob_get_clean();
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
      $format =  '<a {$attr} data-toggle="dropdown" href="{$href_text}">{$label} ';
      $format .=   '<span class="caret"></span>';
      $format .= '</a>';
    }else{
      $format = '<a {$attr} href="{$href_text}">{$label}</a>';
    }

    return str_replace($search, $attributes, $format);
  }

}
