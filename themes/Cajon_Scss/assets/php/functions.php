<?php
// Cajón Scss 3.3.6
// Theme Functions

defined('is_running') or die('Not an entry point...');

$lang = isset($page->lang) ? $page->lang : $config['language'];

// enable FontAwesome everywhere
\gp\tool::LoadComponents('fontawesome');

// check + add theme script
$theme_js = $page->theme_dir . '/assets/js/script.js';
if( file_exists($theme_js) ){
  $page->head_js[] = dirname($page->theme_path) . '/assets/js/script.js';
}

// check + add layout script
$layout_js = $page->theme_dir . '/' . $page->theme_color . '/script.js';
if( file_exists($layout_js) ){
  $page->head_js[] = dirname($page->theme_path)  . '/' . $page->theme_color . '/script.js';
}


$GP_MENU_ELEMENTS = 'BootstrapMenu';

function BootstrapMenu($node, $attributes, $level=0, $menu_id=''){
  GLOBAL $GP_MENU_LINKS;
  if( $node == 'a' ){
    $format = $GP_MENU_LINKS;
    $search = array('{$href_text}','{$attr}','{$label}','{$title}');

    if( in_array('sublinks-toggle', $attributes['class']) ){
      if( in_array('active-parent', $attributes['class']) ){
        $format = '<a {$attr} aria-expanded="true">{$label}</a>'; //  href="{$href_text}"
      }else{
        $format = '<a {$attr}>{$label}</a>'; //  href="{$href_text}"
      }
    }

    if( in_array('dropdown-toggle', $attributes['class']) ){
      $format = '<a {$attr} data-toggle="dropdown" data-target="dropdown" href="{$href_text}">{$label} <i class="fa fa-caret-down"></i></a>';
    }

    return str_replace($search, $attributes, $format);
  }
}
