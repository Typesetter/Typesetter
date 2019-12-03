<?php

defined('is_running') or die('Not an entry point...');

class AvailableClassesExample{

  /**
   *
   * New Typesetter filter hook (as of ver 5.1.1-b1)
   *
   * can be used in plugins and themes as well
   * to replace or extend the list managed via Settings -> Configuration -> Classes
   * 
   */
  static function AvailableClasses($classes){

    // ONLY FOR USE IN A THEME
    // exits if the page does not use this theme
    /*
    if( self::IsCurrentTheme == false ){
      return $classes;
    }
    */

    // REPLACE ALL
    // not recommended because it renders the Available Classes system settings useless
    /*
    $classes = array(
      array(
        'names' => 'plugin-example-toggle',
        'desc'  => 'A single CSS class entry defined via plugin filter hook',
      ),
      array(
        'names' => 'plugin-example-option-1 example-option-3 example-option-3',
        'desc'  => 'A list of selectable classes entry defined via plugin filter hook',
      )
    );
    */
    
    // APPEND SINGLE ENTRY
    // will add one definition at the end of the defined list
    /*
    $classes[] = array(
      'names' => 'plugin-example-toggle',
      'desc'  => 'A single CSS class appended entry to preset classes via plugin filter hook',
    );
    */

    // APPEND MULTIPLE ENTRIES
    // will add multiple definitions at the end of the defined list
    /*
    $append_these =  array(
      array(
        'names' => 'plugin-example-toggle',
        'desc'  => 'A single CSS class entry appended via plugin filter hook',
      ),
      array(
        'names' => 'plugin-example-option-1 example-option-3 example-option-3',
        'desc'  => 'A list of selectable classes appended via plugin filter hook',
      )
    );
    $classes = array_merge($classes, $append_these);
    */

    // PREPEND SINGLE ENTRY
    // will add one definition at the start of the defined list
    /*
    $append_this = array(
      'names' => 'plugin-example-toggle',
      'desc'  => 'A single CSS class entry appended to preset classes via plugin filter hook',
    );
    array_unshift($classes, $append_this);
    */

    // PREPEND MULTIPLE ENTRIES
    // will add one (or more) definitions at the beginning of the defined list
    // probably the most likely useful variant in plugins or themes
    //*
    $prepend_these =  array(
      array(
        'names' => 'plugin-example-toggle',
        'desc'  => 'A single CSS class entry prepended via plugin filter hook',
      ),
      array(
        'names' => 'plugin-example-option-1 plugin-example-option-3 plugin-example-option-3',
        'desc'  => 'A list of selectable classes entry prepended via plugin filter hook',
      )
    );
    $classes = array_merge($prepend_these, $classes);
    // */


    // MANDATORY
    return $classes;
  }



  /**
   *
   * Typesetter action hook
   *
   * when AvailaleClasses hook is used in a plugin, like right here, you might want to
   * load the stylesheet(s) containing rules for your classname entries
   *
   */
  static function GetHead(){
    global $page, $addonRelativeCode, $addon_current_id, $addon_current_version;
    // \gp\tool\Plugins::css('AvailableClasses.css');      // the stylesheet will be combined with other CSS (if 'combine CSS' is active in configuration)
    \gp\tool\Plugins::css('AvailableClasses.css', false);  // the stylesheet will NOT be combined with other CSS
  }



  /**
   *
   * Custom method to check if current page uses our theme
   * 
   * obviously only useful when AvailaleClasses hook is used 
   * in the context of a theme
   *
   * returns true or false
   *
   */
  static function IsCurrentTheme(){
    global $page, $gpLayouts, $config, $addonFolderName;
    $theme_name         = $config['addons'][$addonFolderName]['name'];
    $layout             = isset($page->TitleInfo['gpLayout']) ? $page->TitleInfo['gpLayout'] : 'default';
    $default_layout     = $config['gpLayout'];
    $current_theme_name = $layout == 'default' ? $gpLayouts[$default_layout]['name'] : $gpLayouts[$layout]['name'];
    $is_current_theme   = ($current_theme_name == $theme_name);
    return $is_current_theme;
  }

}
