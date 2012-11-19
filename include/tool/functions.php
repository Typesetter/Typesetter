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