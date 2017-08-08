<?php
/* 
 * Theme 'Cajón Scss' 3.3.6
 * default config 
 *
 */

defined('is_running') or die('Not an entry point...');

$logo_img_url = $addonRelativeCode . '/assets/img/default_logo.png';

$config = array(
  'logo_img_url'        =>  $logo_img_url,
  'logo_img_url_enc'    =>  implode('/', array_map('rawurlencode', explode('/', $logo_img_url))),
  'logo_img_shape'      =>  'circle',
  'logo_img_size'       =>  'medium',
  'logo_img_border'     =>  'none',
  'logo_img_collapsed'  =>  'show',

  'navbar_position'     =>  'left',
  'navbar_variant'      =>  'inverse',

);
