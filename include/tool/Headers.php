<?php

namespace gp\tool;

defined('is_running') or die('Not an entry point...');


class Headers{


	/**
	 * Return a list of all http headers from the $_SERVER superglobal
	 * If $which is specified, return that single header
	 *
	 * @param string $which
	 * @return string|array
	 *
	 */
	public static function RequestHeaders($which = false){
		$headers = [];
		foreach($_SERVER as $key => $value) {
			if( substr($key, 0, 5) <> 'HTTP_' ){
				continue;
			}

			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));

			if( $which ){
				if( strnatcasecmp($which,$header) === 0){
					return $value;
				}
			}

			$headers[$header] = $value;
		}
		if( !$which ){
			return $headers;
		}
	}


	/**
	 * Find a matching mime type from the 'accept' headers
	 *
	 * @param array $accepts List of
	 * @return string
	 *
	 */
	public static function AcceptMime($accepts){

		$accept		= self::RequestHeaders('accept');

		if( $accept && preg_match_all('#([^,;\s]+)\s*;?\s*(?:q=([^,;\s]+))?#',$accept,$matches,PREG_SET_ORDER) ){

			// filter acceptable mimes, default qvalue = 1
			foreach($matches as $match){

				$_mime = trim($match[1]);

				if( !array_key_exists($_mime, $accepts) ){
					continue;
				}

				if( isset($match[2]) ){
					$accepts[$_mime] = (float)$match[2];
				}else{
					$accepts[$_mime] += 1;
				}
			}
		}

		// best mime will be first in the list after arsort()
		arsort($accepts);
		return key($accepts);
	}

}
