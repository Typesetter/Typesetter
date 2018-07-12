<?php 
defined('is_running') or die('Not an entry point...');

class HighlightSearchResults {

  static function GetHead() {
    global $page, $addonRelativeCode;
    $page_type = $page->pagetype;
    if( $page_type != 'admin_display' ){
      $page->head_js[]  = $addonRelativeCode  . '/jquery-highlight/jquery.highlight.min.js';
      $page->css_user[] = $addonRelativeCode  . '/Highlight.css';
      $page->head_js[]  = $addonRelativeCode  . '/Highlight.js';
    }
  }

}
