<?php

/**
 * Bootswatch 4 Scss 4.4.1
 * Typesetter Theme Template
 * based on https://bootswatch.com
 *
 */

global $page, $config;
$path = $page->theme_dir . '/drop_down_menu.php';
include_once($path);

$lang = isset($page->lang) ? $page->lang : $config['language'];

/**
 * If you are using Multi-Language Manager 1.2.3+
 * and want to use localized $langmessage values in the template,
 * uncomment the following line
 */
 // common::GetLangFile('main.inc', $lang);

?><!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="bootstrap-4">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <?php
      common::LoadComponents( 'bootstrap4-js' );
      common::LoadComponents( 'fontawesome' );
      gpOutput::GetHead();
    ?>

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!-- <link href="../../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet"> -->

  </head>
  <body>
    <!--[if lte IE 9]>
      <div class="alert alert-warning">
        <h3>Bootstrap 4</h3>
        <p>We&rsquo;re sorry but Internet Explorer 9 support was dropped as of Bootstrap version 4.</p>
      </div>
    <![endif]-->
    <header class="main-header">
      <div class="navbar navbar-expand-lg fixed-top navbar-dark bg-primary gp-fixed-adjust">
        <div class="container">

          <?php
            global $config;
            echo common::Link('',$config['title'],'','class="navbar-brand"');
          ?>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>


          <div class="collapse navbar-collapse navbar-right" id="navbarResponsive">
            <?php
              $GP_ARRANGE = false;
              $GP_MENU_CLASSES = array(
                'menu_top'          => 'nav navbar-nav',
                'selected'          => 'active',
                'selected_li'       => '',
                'childselected'     => 'active',
                'childselected_li'  => 'active', // use '' if you don't want 1st-level nav items to indicate that a dropdown item is active
                'li_'               => 'nav-item nav-item-',
                'li_title'          => '',
                'haschildren'       => 'dropdown-toggle',
                'haschildren_li'    => 'dropdown',
                'child_ul'          => 'dropdown-menu',
              );

              gpOutput::Get('TopTwoMenu'); //top two levels
            ?>
          </div><!--/.navbar-collapse -->
        </div>
      </div>
    </header><!-- /.main-header -->

    <div class="main-content">
      <div class="container">
        <?php $page->GetContent(); ?>
      </div><!-- /.container-->
    </div><!-- /.main-content -->

    <footer class="main-footer pt-5 pb-5 mt-3">
      <div class="container">
        <div class="row">
          <div class="col-sm-6 col-lg-3 footer-column footer-column-1">
              <?php gpOutput::Get('Extra', 'Footer_Column_1'); ?>
          </div>
          <div class="col-sm-6 col-lg-3 footer-column footer-column-2">
              <?php gpOutput::Get('Extra', 'Footer_Column_2'); ?>
          </div>
          <div class="col-sm-6 col-lg-3 footer-column footer-column-3">
              <?php gpOutput::Get('Extra', 'Footer_Column_3'); ?>
          </div>
          <div class="col-sm-6 col-lg-3 footer-column footer-column-4">
              <?php gpOutput::Get('Extra', 'Footer_Column_4'); ?>
          </div>
        </div><!-- /.row -->


        <div class="row">
          <div class="col footer-admin-links">
              <?php gpOutput::GetAdminLink(); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.container -->
    </footer><!-- /.main-footer -->
  </body>
</html>
