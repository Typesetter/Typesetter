<?php

$GP_MENU_ELEMENTS = 'Bootstrap4_menu';

function Bootstrap4_menu($node, $attributes, $level, $menu_id, $item_position){
  GLOBAL $GP_MENU_LINKS;

  if( $node == 'a' ){
    $format = $GP_MENU_LINKS;
    // Bootstrap4 navbar requires a nav-link class for 1st-level anchors 
    // and a dropdown-item class for anchors in dropdown menus
    $strpos_class = strpos($attributes['attr'], 'class="');
    $add_class = ( $level > 0 ) ? "dropdown-item" : "nav-link";
    if( $strpos_class === false ){
      $attributes['attr'] .= ' class="' . $add_class . '"';
      $strpos_class = strpos($attributes['attr'], 'class="');
    }else{
      $attributes['attr'] = substr($attributes['attr'], 0, $strpos_class + 7) 
        . $add_class . ' ' 
        . substr($attributes['attr'], $strpos_class + 7);
    }
    $search = array('{$href_text}', '{$attr}', '{$label}', '{$title}');
    if( in_array('dropdown-toggle', $attributes['class']) ){
      $format = '<a {$attr} data-toggle="dropdown" href="{$href_text}">{$label} <span class="caret"></span></a>';
    }else{
      $format = '<a {$attr} href="{$href_text}">{$label}</a>';
    }
    return str_replace( $search, $attributes, $format );
  }

}
