<?php /* 
######################################################################
Main PHP script for Typesetter CMS - Cajón Parallax
Author: J. Krausz
Date: 2017-05-16
Version: 2.0
######################################################################
*/

defined('is_running') or die('Not an entry point...');

class CajonParallax {

  /*
   * moved to /assets/php/theme.php -> GetHead()
  static function GetHead() {
    global $page, $addonRelativeCode;
    $page->css_user[] = $addonRelativeCode . '/addons/CajonParallax/CajonParallax.css';
    $page->head_js[]  = $addonRelativeCode . '/addons/CajonParallax/CajonParallax.js';
    $page->head_js[]  = $addonRelativeCode . '/addons/CajonParallax/jquery.scrollspeed/jQuery.scrollSpeed.js';
  }
  */


  static function SectionTypes($section_types){
    $section_types['cajon_parallax'] = array();
    $section_types['cajon_parallax']['label'] = 'Cajon Parallax Image';
    return $section_types;
  }



  static function NewSections($links){
    global $addonRelativeCode;

    /* add section icon */
    foreach ($links as $key => $section_type_arr) {
      if ( $section_type_arr[0] == 'cajon_parallax' ) {
        $links[$key] = array('cajon_parallax', $addonRelativeCode . '/addons/CajonParallax/icons/section-pi.png');
      }
    }

    /* Section Combo: wrapper.parallax-wrapper -> .cajon_parallax + .text */
    if( version_compare(gpversion, '5.0.3') > 0 ){
      // Typesetter > 5.0.3: wrapper properties can be defined as array
      $wrapper_data = array(
        'gp_label' => 'Cajon Parallax Combo',
        'gp_color' => '#08a7ee',
        'attributes' => array(
          'class' => 'cajon-parallax-wrapper text-center',
         ),
      );
    }else{
      // Typesetter <= 5.0.3: only (string) wrapper class name(s) allowed
      $wrapper_data = 'cajon-parallax-wrapper text-center';
    }

    $links[] = array(
      array( 
        'cajon_parallax',                                               // section(pseudo)Type.className
        'text-cajon-parallax-caption',                                        // section(pseudo)Type.className
      ), 
      $addonRelativeCode . '/addons/CajonParallax/icons/combo-pi-fullwidth-txt.png',         // icon for the Section Combo, 88x50px
      $wrapper_data,                                                    // SectionWrapper data
    );

    return $links;
  }




  static function DefaultContent($default_content, $type){
    global $addonRelativeCode;

    switch( $type ){
      case 'cajon_parallax':
        $new_section_data = array(
          'type' =>         'cajon_parallax',
          'image_src' =>    $addonRelativeCode . '/addons/CajonParallax/img/pixabay-cowins-prairie-679016-tonemapped-1600px.jpg', // default_image.png
          'alt_text' =>     'Default Image',
          'scrolling' =>    'parallax', // 'parallax', 'fixed', 'static'
          'halign' =>       '50', //  any number from 0 to 100 for background-position-x %
          'scroll_speed' => '50', //  any number from 5 to 95
          'scaling' =>      'cover', // 'cover', 'tile'
          'attributes' =>    array(),
          'gp_label' =>     'Cajon Parallax Image',
          'gp_color' =>     '#08a7ee',
        );
        return self::SectionToContent($new_section_data);
        break;

      case 'text-cajon-parallax-caption':
        return array(
          'type' => 'text',
          'content' => '<h2 class="h1"><span style="color:#fff;">Parallax Caption</span></h2>'
                     . '<p><span style="color:#fff;">This is a generic editable text section</span></p>',
          'attributes' => array(
            'class' => 'cajon-parallax-caption',
          ),
          'gp_label' => 'Cajon Parallax Caption',
        );

      default:
         return $default_content;
    }
  }




  static function SaveSection($return, $section, $type){
    global $page;
    if( $type != 'cajon_parallax' ){
      return $return;
    }
    $page->file_sections[$section]['content'] =& $_POST['gpcontent'];
    return true;
  }




  /* !
   * This function is currently only used internally (not as plugin action hook)
   * Parallax Image 2+ doesn't store scrolling, scaling scoll_speed, ... in the section array anymore
   */
  static function SectionToContent($section_data){
    if( $section_data['type'] != 'cajon_parallax' ) {
      return $section_data;
    }
    $section_data['content']  = '<div class="cajon-parallax-image ';
    $section_data['content'] .=   'scroll-' . $section_data['scrolling'] . ' ';
    $section_data['content'] .=   'scaling-' . $section_data['scaling'] . '" ';
    $section_data['content'] .=   'data-scroll-speed="' . $section_data['scroll_speed'] . '" ';
    $section_data['content'] .=   'data-halign="' . $section_data['halign'] . '" ';
    $section_data['content'] .=   'style="background-image:url(\'' . $section_data['image_src'] . '\');">';
    $section_data['content'] .=   '<img alt="' . $section_data['alt_text'] . '" src="' . $section_data['image_src'] . '"/>';
    $section_data['content'] .= '</div>';

    return $section_data;
  }




  static function InlineEdit_Scripts($scripts, $type){
    if( $type !== 'cajon_parallax' ){
      return $scripts;
    }
    global $addonRelativeCode, $addonFolderName;
    $addonBasePath = (strpos($addonRelativeCode,'themes/') > 0) ? '/themes/' . $addonFolderName : '/data/_themes/' . $addonFolderName;
    echo "\n" . 'CajonParallax.base = "' . $addonBasePath . '/addons/CajonParallax";' . "\n";
    $scripts[] = '/include/js/inline_edit/inline_editing.js';
    $scripts[] = $addonRelativeCode . '/addons/CajonParallax/CajonParallax_edit.js'; 
    return $scripts;
  }

}
