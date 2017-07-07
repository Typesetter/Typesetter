<?php 
/** 
 * Customize content variables that are replaced at output. For Typesetter 5.1-b1 and newer
 */

defined('is_running') or die('Not an entry point...');

class ReplaceCVs {

  static function ReplaceContentVars($vars) {

    // input keys names without the leading $ but use them with it in the content
    $vars['myNameIs'] = '<em>John Doe</em>'; // use $myNameIs in text content

    // you may also unset or change preset variables
    // $vars['fileModTime'] = '<strong>i won&rsquo;t tell!</strong>'; 

    return $vars;
  }

}
