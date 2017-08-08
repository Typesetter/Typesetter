<?php
// Cajón Scss 3.3.6
// Theme Functions

defined('is_running') or die('Not an entry point...');

// enable FontAwesome everywhere
\gp\tool::LoadComponents('fontawesome');

// check + add theme script
$theme_js = $page->theme_dir . '/assets/js/script.js';
if( file_exists($theme_js) ){
  //msg('Loading Theme JS [' . dirname($page->theme_path) . '/assets/js/script.js]');
  $page->head_js[] = dirname($page->theme_path) . '/assets/js/script.js';
}

// check + add layout script
$layout_js = $page->theme_dir . '/' . $page->theme_color . '/script.js';
if( file_exists($layout_js) ){
  //msg('Loading Layout JS [' . dirname($page->theme_path)  . '/' . $page->theme_color . '/script.js]');
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


function getPageLanguage() {
  global $page, $languages, $config, $dataDir, $ml_object;
  $lang = $config["language"]; // Typesetter interface language
  if( !empty($ml_object) ){ // only if Multi-Language Manager ist installed
    $ml_list = $ml_object->GetList($page->gp_index);
    $ml_lang = is_array($ml_list) && ($ml_lang = array_search($page->gp_index, $ml_list)) !== false ? $ml_lang : false;
  }else{
    $ml_lang = false;
  }
  // msg('$ml_lang = ' . $ml_lang);
  $page_lang = $ml_lang ? $ml_lang : $lang;
  $page->head  .= "\n<script type=\"text/javascript\">\n";
  $page->head  .= 'var gp_pagelang = "' . $page_lang . '"; ' . "\n";
  if( $ml_lang && $ml_lang != $lang && array_key_exists($ml_lang, $languages) ){
    $lang_inc_file = $dataDir . "/include/languages/" . $ml_lang . "/main.inc";
    if( file_exists($lang_inc_file) ){
      // msg("Multi-Language Manager='" . $ml_lang . "', Typesetter Interface='" . $lang . "', Page='" . $page_lang . "'");
      include $lang_inc_file; // loads current page language $langmessage array
      $page->head  .= 'var colorbox_lang = {' 
        . '"previous":"' . $langmessage['Previous'] . '",' 
        . '"next":"' . $langmessage['Next'] . '",' 
        . '"close":"' . $langmessage['Close'] . '",' 
        . '"caption":"' . $langmessage['caption'] . '",' 
        . '"current":"' . sprintf( $langmessage['Image_of'], '{current}', '{total}' ) . '"}; ' . "\n";
      }
  }
  $page->head  .= "</script>\n";
  return $page_lang;
}

