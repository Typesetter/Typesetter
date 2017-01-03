<?php
defined('is_running') or die('Not an entry point...');

if( !function_exists('ctype_alnum') ){
	function ctype_alnum($string){
		return (bool)preg_match('#^[a-z0-9]*$#i',$string);
	}
}

if( !function_exists('ctype_digit') ){
	function ctype_digit($string){
		return (bool)preg_match('#^[0-9]*$#',$string);
	}
}

/**
 * Also need:
 * trim
 * strspn
 *
 */

if( !function_exists('mb_strpos') ){

	function mb_strpos(){
		$args = func_get_args();
		return call_user_func_array('strpos',$args);
	}

	function mb_strlen($str){
		return strlen($str);
	}

	function mb_strtoupper($str){
		return strtoupper($str);
	}

	function mb_strtolower($str){
		return strtolower($str);
	}

	function mb_substr(){
		$args = func_get_args();
		return call_user_func_array('substr',$args);
	}

	function mb_substr_count($haystack,$needle){
		return substr_count($haystack,$needle);
	}
}


if( !function_exists('mb_substr_replace') ){
	function mb_substr_replace($str,$repl,$start,$length=0){
		$beg = mb_substr($str,0,$start);
		$end = mb_substr($str,$start+$length);
		return $beg.$repl.$end;
	}
}

if( !function_exists('mb_str_replace') ){
	function mb_str_replace($search, $replace, $subject, &$count = 0){

		// Call mb_str_replace for each subject in array, recursively
		if( is_array($subject) ){
			foreach ($subject as $key => $value) {
				$subject[$key] = mb_str_replace($search, $replace, $value, $count);
			}
			return $subject;
		}

		// Normalize $search and $replace so they are both arrays of the same length
		$searches = is_array($search) ? array_values($search) : array($search);
		$replacements = is_array($replace) ? array_values($replace) : array($replace);
		$replacements = array_pad($replacements, count($searches), '');
		foreach( $searches as $key => $search ){
			$parts = mb_split(preg_quote($search), $subject);
			$count += count($parts) - 1;
			$subject = implode($replacements[$key], $parts);
		}
		return $subject;
	}
}

if( !function_exists('mb_explode') ){
	function mb_explode(){
		$args = func_get_args();
		$args[0] = preg_quote($args[0]);
		return call_user_func_array('mb_split',$args);
	}
}

if( !function_exists('gzopen') && function_exists('gzopen64') ){
	function gzopen( $filename, $mode, $use_include_path = 0 ){
		return gzopen64( $filename, $mode, $use_include_path );
	}
}

if( !function_exists('gpSettingsOverride') ){
	function gpSettingsOverride(){}
}

function drupal_clean_css_identifier
7.x common.inc 	drupal_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => ''))

Prepares a string for use as a CSS identifier (element, class, or ID name).

http://www.w3.org/TR/CSS21/syndata.html#characters shows the syntax for valid CSS identifiers (including element names, classes, and IDs in selectors.)
Parameters

$identifier: The identifier to clean.

$filter: An array of string replacements to use on the identifier.
Return value

The cleaned identifier.
2 calls to drupal_clean_css_identifier()
File

includes/common.inc, line 3902
    Common functions that many Drupal modules will need to reference.

Code

function clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '')) {
	// Source: https://api.drupal.org/api/drupal/includes%21common.inc/function/drupal_clean_css_identifier/7.x

  $allow_css_double_underscores = false;

  // Preserve BEM-style double-underscores depending on custom setting.
  if ($allow_css_double_underscores) {
    $filter['__'] = '__';
  }

  // By default, we filter using Drupal's coding standards.
  $identifier = strtr($identifier, $filter);

  // Valid characters in a CSS identifier are:
  // - the hyphen (U+002D)
  // - a-z (U+0030 - U+0039)
  // - A-Z (U+0041 - U+005A)
  // - the underscore (U+005F)
  // - 0-9 (U+0061 - U+007A)
  // - ISO 10646 characters U+00A1 and higher
  // We strip out any character not in the above list.
  $identifier = strtolower(preg_replace('/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u', '', $identifier));

  return $identifier;
}
