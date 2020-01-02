<?php

namespace Addon\ExtendGPUI;

defined('is_running') or die('Not an entry point...');


class Plugin{

  /**
   * New Typesetter filter hook (experimental)
   * 
   */
  public function Extend_GPUI($values){

    $values['pocx'] = 'integer';
    $values['pocy'] = 'integer';
    $values['pocw'] = 'integer';
    $values['poch'] = 'integer';

    return $values;
  }



  /**
   * Typesetter action hook
   *
   */
  public function GetHead(){

    // only when logged-in
    if( !\gp\tool::LoggedIn() ){
      return;
    }

    \gp\tool::LoadComponents('draggable,resizable');

    \gp\tool\Plugins::js('Plugin.js', false);   // false => don't add to combine
    \gp\tool\Plugins::css('Plugin.css', false); // false => don't add to combine

  }

}
