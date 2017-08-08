<?php
// Cajón Scss 3.3.6
// Theme Class

defined('is_running') or die('Not an entry point...');

class ThemeCajon{
 
  function GetHead(){
    global $theme_cajon_config, $page, $addonRelativeCode;
    $theme_cajon_config = self::GetThemeConfig();

    // enable FontAwesome everywhere
    \gp\tool::LoadComponents('fontawesome');

    // Cajon Parallax
    $page->css_user[] = $addonRelativeCode . '/addons/CajonParallax/CajonParallax.css';
    $page->head_js[]  = $addonRelativeCode . '/addons/CajonParallax/CajonParallax.js';
    $page->head_js[]  = $addonRelativeCode . '/addons/CajonParallax/jquery.scrollspeed/jQuery.scrollSpeed.js';

  }


  function GetThemeConfig(){
    global $addonPathCode, $addonPathData, $addonRelativeCode;
    $config_file = $addonPathData . '/config.php';
    if( file_exists($config_file) ){
      include $config_file;
    }else{
      include $addonPathCode . '/assets/theme_settings/default_config.php';
    }
    return $config;
  }

}
