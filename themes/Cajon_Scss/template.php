<?php 
/* Theme 'Cajón Scss' 3.3.6 */

global $page, $theme_cajon_config; 
include_once($page->theme_dir . '/assets/php/functions.php');


// THEME INSTALLATION HELPER
// custom $theme_cajon_config will be replaced via GetHead() plugin hook once the theme is installed.
$default_logo_url = dirname($page->theme_path) . '/assets/img/default_logo.png';
$theme_cajon_config = array(
  'logo_img_url'        =>  $default_logo_url,
  'logo_img_url_enc'    =>  implode('/', array_map('rawurlencode', explode('/', $default_logo_url))),
  'logo_img_shape'      =>  'circle',
  'logo_img_size'       =>  'medium',
  'logo_img_border'     =>  'none',
  'logo_img_collapsed'  =>  'show',
  'navbar_position'     =>  'left',
  'navbar_variant'      =>  'inverse',
);


?><!DOCTYPE html>
<html lang="<?php echo getPageLanguage(); ?>">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    \gp\tool::LoadComponents( 'bootstrap3-js' );
    \gp\tool\Output::GetHead();
    // msg('$theme_cajon_config = ' . pre($theme_cajon_config));

    ?>

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!-- <link href="../../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet"> -->
    <!--[if lt IE 9]><?php
    // HTML5 shim, for IE6-8 support of HTML5 elements
    \gp\tool\Output::GetComponents( 'html5shiv' );
    \gp\tool\Output::GetComponents( 'respondjs' );
    ?><![endif]-->


  </head>
  <body>

    <nav class="navbar navbar-theme-cajon navbar-<?php echo $theme_cajon_config['navbar_variant']; ?> navbar-fixed-side navbar-fixed-side-<?php echo $theme_cajon_config['navbar_position']; ?> gp-fixed-adjust">

      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse" aria-expanded="false" aria-controls="navbar">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <?php
          global $config;
          $show_logo_image = !empty($theme_cajon_config['logo_img_url']);
          if( $show_logo_image ){
            $href = \gp\tool::GetUrl('');
            $title = $config['title'];
            $logo_url = $theme_cajon_config['logo_img_url_enc'];
            $logo_classes = 'theme-cajon-logo'
              . ' logo-shape-' . $theme_cajon_config['logo_img_shape'] 
              . ' logo-size-' . $theme_cajon_config['logo_img_size'] 
              . ' logo-border-' . $theme_cajon_config['logo_img_border'] 
              . ' logo-collapsed-' . $theme_cajon_config['logo_img_collapsed'];
            echo '<a href="' . $href . '" class="navbar-brand">';
            echo '<img class="' . $logo_classes . '" alt="logo" src="' . $logo_url . '"/>';
            echo '<span class="site-title">' . $title . '</span>';
            echo '</a>';
          }else{
            echo \gp\tool::Link('',$config['title'],'','class="navbar-brand"');
          }
        ?>
      </div><!--/.navbar-header -->

      <div class="collapse navbar-collapse">
        <?php
          // SIDE MENU
          $GP_ARRANGE = false;
          $GP_MENU_CLASSES = array(
            'menu_top'          => 'nav sidebar-nav',
            'selected'          => 'active-link',
            'selected_li'       => 'active-li',
            'childselected'     => 'active-parent',
            'childselected_li'  => 'active-parent-li',
            'li_'               => '',
            'li_title_'         => '',
            'haschildren'       => 'sublinks-toggle',
            'haschildren_li'    => 'sublinks',
            'child_ul'          => 'nav sublinks-menu',
          );
          \gp\tool\Output::Get('FullMenu'); //all levels

          // FOR POSSIBLE FOLLOWING STANDARD BOOSTRAP NAVBARS
          $GP_MENU_CLASSES = array(
            'menu_top'          => 'nav navbar-nav',
            'selected'          => '',
            'selected_li'       => 'active',
            'childselected'     => '',
            'childselected_li'  => '',
            'li_'               => '',
            'li_title_'         => '',
            'haschildren'       => 'dropdown-toggle',
            'haschildren_li'    => 'dropdown',
            'child_ul'          => 'dropdown-menu',
          );
        ?>
        <div class="admin-links"><?php \gp\tool\Output::GetAdminLink(); ?></div>

      </div><!--/.navbar-collapse -->

    </nav>

    <div class="page-content">
      <?php $page->GetContent(); ?>
    </div><!--/.page-content -->

  </body>
</html>