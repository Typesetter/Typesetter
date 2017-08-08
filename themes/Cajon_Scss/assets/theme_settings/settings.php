<?php
/* 
 * Theme 'Cajón Scss' 3.3.6 
 *
 */

defined('is_running') or die('Not an entry point...');

class ThemeCajon_Settings{

  static $config = array();

  /* 
   * Typesetter Filter hook 
   */
  static function PageRunScript($cmd) {
    global $page, $langmessage, $addonRelativeCode;

    if( !\gp\tool::LoggedIn() ){
      return;
    }

    $page->admin_links[] = array(
      $page->title, 
      '<i class="fa fa-paint-brush"></i> ' . $langmessage['theme'] . ' Cajón ', 
      'cmd=ThemeCajonSettingsForm', 
      'class="theme-cajon-adminbar-button" data-cmd="gpabox" title="' . $langmessage['theme'] . ' ' . $langmessage['Settings'] . '"'
    );

    self::LoadConfig();
    $page->head_js[] = $addonRelativeCode . '/assets/theme_settings/settings.js';
    $page->head_script .=  "\n" . 'var ThemeCajonConfig = ' . json_encode(self::$config, JSON_FORCE_OBJECT) . ';' . "\n\n";

    switch( $cmd ){
      case 'ThemeCajonSettingsForm':
        ob_start();
        echo  '<div class="inline_box">';
        echo    '<h3 style="margin-top:0;">' . $langmessage['theme'] . ' Cajón &raquo; ' . $langmessage['Settings'] . '</h3>'; 
        self::SettingsForm('page');
        echo    '<script>ThemeCajonSettingsHelpers.init();</script>';
        echo  '</div>';
        $page->contentBuffer = ob_get_clean();
        return 'return';
        break;
      
      case 'ThemeCajonSaveSettings':
        self::SaveConfig();
        return true;
        break;
    }

    return $cmd;

  }



  static function LoadConfig(){
    global $addonPathCode, $addonPathData, $addonRelativeCode;
    $config_file = $addonPathData . '/config.php';
    if( file_exists($config_file) ){
      include $config_file;
    }else{
      include $addonPathCode . '/assets/theme_settings/default_config.php';
    }
    self::$config = $config;
    // msg('$config = ' . pre($config));
  }



  static function SaveConfig(){
    global $addonPathData, $langmessage;
    $config = array(
      'logo_img_url'        => ( !empty($_POST['logo_img_url'])       ? htmlspecialchars($_POST['logo_img_url']) : '' ),
      'logo_img_url_enc'    => ( !empty($_POST['logo_img_url'])       ? implode('/', array_map('rawurlencode', explode('/', htmlspecialchars_decode($_POST['logo_img_url'])))) : '' ),
      'logo_img_shape'      => ( !empty($_POST['logo_img_shape'])     ? htmlspecialchars($_POST['logo_img_shape'])  : 'default' ),
      'logo_img_size'       => ( !empty($_POST['logo_img_size'])      ? htmlspecialchars($_POST['logo_img_size'])   : 'medium' ),
      'logo_img_border'     => ( !empty($_POST['logo_img_border'])    ? htmlspecialchars($_POST['logo_img_border']) : 'none' ),
      'logo_img_collapsed'  => ( isset($_POST['logo_img_collapsed'])  ? 'show' : 'hide' ),

      'navbar_variant'      => ( !empty($_POST['navbar_variant'])     ? htmlspecialchars($_POST['navbar_variant'])  : 'inverse' ),
      'navbar_position'     => ( !empty($_POST['navbar_position'])    ? htmlspecialchars($_POST['navbar_position']) : 'left' ),
    );
    $config_file = $addonPathData . '/config.php';
    if( \gp\tool\Files::SaveData($config_file, 'config', $config) ){
      msg($langmessage['SAVED']);
    }else{
      msg($langmessage['OOPS']);
    }
  }



  static function SettingsForm($render_mode='admin'){
    global $langmessage, $config, $page;

    $form_action =      $page->gp_index ? \gp\tool::GetUrl($page->title) : \gp\tool::GetUrl('/Admin_Theme_Cajon_Admin');
    $data_cmd =         $page->gp_index ?  ' data-cmd="gppost" ' : '';
    $admin_box_close =  $page->gp_index ?  'admin_box_close ' : '';

    echo  '<form id="theme-cajon-settings-form" method="post" action="' . $form_action . '" id="ogp-form">';
    echo    '<table class="bordered full_width">';
    echo      '<thead>';
    echo        '<tr>';
    echo          '<th class="gp_header">' . $langmessage['options'] . '</th>';
    echo          '<th class="gp_header">' . $langmessage['Current_Value'] . '</th>';
    echo        '</tr>';
    echo      '</thead>';
    echo      '<tbody>';


    echo         '<tr>';
    echo          '<td colspan="2" style="border-top:1px solid #ddd;">';
    echo            '<h4><i class="fa fa-bars"></i> ' . $langmessage['Menu'] . '</h4>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>' . $langmessage['Menu'] . ' Position</td>';
    echo          '<td>';
    echo          '<select class="gpselect" name="navbar_position">';
    echo            '<option' . (self::$config['navbar_position'] == 'left'   ? ' selected="selected"' : '') . ' value="left">left</option>';
    echo            '<option' . (self::$config['navbar_position'] == 'right'  ? ' selected="selected"' : '') . ' value="right">right</option>';
    echo          '</select>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>' . $langmessage['color'] . '</td>';
    echo          '<td>';
    echo          '<select class="gpselect" name="navbar_variant">';
    echo            '<option' . (self::$config['navbar_variant'] == 'default' ? ' selected="selected"' : '') . ' value="default">default</option>';
    echo            '<option' . (self::$config['navbar_variant'] == 'inverse' ? ' selected="selected"' : '') . ' value="inverse">inverted</option>';
    echo          '</select>';
    echo          '</td>';
    echo        '</tr>';


    echo        '<tr>';
    echo          '<td colspan="2">';
    echo            '<h4><hr/><i class="fa fa-user-circle"></i> Logo/Avatar ' . $langmessage['Image'] . '</h4>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>' . $langmessage['Image'] . ' URL</td>';
    echo          '<td>';
    echo            '<input class="gpinput" id="theme-cajon-logo-image-url" type="text" name="logo_img_url" style="width:70%;" value="' . self::$config['logo_img_url'] . '"/> ';
    echo            '<button class="gpsubmit" id="theme-cajon-select-logo-image-btn">' . $langmessage['Select Image'] . '</button>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>Size</td>';
    echo          '<td>';
    echo          '<select class="gpselect" name="logo_img_size">';
    echo            '<option' . (self::$config['logo_img_size'] == 'small'  ? ' selected="selected"' : '') . ' value="small">small</option>';
    echo            '<option' . (self::$config['logo_img_size'] == 'medium' ? ' selected="selected"' : '') . ' value="medium">medium</option>';
    echo            '<option' . (self::$config['logo_img_size'] == 'large'  ? ' selected="selected"' : '') . ' value="large">large</option>';
    echo          '</select>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>Shape</td>';
    echo          '<td>';
    echo          '<select class="gpselect" name="logo_img_shape">';
    echo            '<option' . (self::$config['logo_img_shape'] == 'default' ? ' selected="selected"' : '') . ' value="default">default</option>';
    echo            '<option' . (self::$config['logo_img_shape'] == 'circle'  ? ' selected="selected"' : '') . ' value="circle">circle</option>';
    echo          '</select>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>Border</td>';
    echo          '<td>';
    echo          '<select class="gpselect" name="logo_img_border">';
    echo            '<option' . (self::$config['logo_img_border'] == 'none'   ? ' selected="selected"' : '') . ' value="none">none</option>';
    echo            '<option' . (self::$config['logo_img_border'] == 'single' ? ' selected="selected"' : '') . ' value="single">single</option>';
    echo            '<option' . (self::$config['logo_img_border'] == 'double' ? ' selected="selected"' : '') . ' value="double">double</option>';
    echo            '<option' . (self::$config['logo_img_border'] == 'offset' ? ' selected="selected"' : '') . ' value="offset">single offset</option>';
    echo          '</select>';
    echo          '</td>';
    echo        '</tr>';

    echo        '<tr>';
    echo          '<td>Display</td>';
    echo          '<td>';
    echo            '<input' . (self::$config['logo_img_collapsed'] == 'show' ? ' checked="checked"' : '') . ' class="gpcheckbox" type="checkbox" name="logo_img_collapsed"/> show in collapsed navbar';
    echo          '</td>';
    echo        '</tr>';


    echo      '</tbody>';
    echo    '</table>';

    echo    '<hr/>';

    echo    '<input type="hidden" name="cmd" value="ThemeCajonSaveSettings"/>';
    echo    '<input type="submit" onClick="ThemeCajonSettingsHelpers.destroy(false)" name="" value="' . $langmessage['save'] . '" class="gpsubmit"' . $data_cmd . '/>';
    echo    '<input type="button" onClick="ThemeCajonSettingsHelpers.destroy(true)" class="' . $admin_box_close . 'gpcancel" name="" value="' . $langmessage['cancel'] . '" />';

    echo  '</form>';
  }


} /* class ThemeCajon_Settings --end */



