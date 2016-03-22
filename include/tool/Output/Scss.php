<?php

namespace gp\tool\Output;

class Scss extends \Leafo\ScssPhp\Compiler{

	public $url_root = '';


	/**
	 * Extend compileValue() so we can fix background:url(path)
	 *
	 */
    public function compileValue($value){


        if( !is_array($value) || $value[0] != \Leafo\ScssPhp\Type::T_FUNCTION || strtolower($value[1]) != 'url' ){
			return parent::compileValue($value);
		}


		$arg	= !empty($value[2]) ? $this->compileValue($value[2]) : '';
		$arg	= trim($arg);

		if( empty($arg) ){
			return "$value[1]($arg)";
		}

		$arg	= $this->FixRelative($arg);

		return "$value[1]($arg)";
	}

	/**
	 * Fix a relative path
	 *
	 */
	public function FixRelative($path){

		$quote	= '';
		if( $path[0] == '"' ){
			$quote	= '"';
			$path	= trim($path,'"');
		}elseif( $path[0] == "'" ){
			$quote	= "'";
			$path	= trim($path,"'");
		}

		if( self::isPathRelative($path) ){
			$path = $this->url_root.'/'.$path;
			$path = self::normalizePath($path);
		}

		return $quote.$path.$quote;
	}

	public static function isPathRelative($path){
		return !preg_match('/^(?:[a-z-]+:|\/)/',$path);
	}


	/**
	 * Canonicalize a path by resolving references to '/./', '/../'
	 * Does not remove leading "../"
	 * @param string path or url
	 * @return string Canonicalized path
	 *
	 */
	public static function normalizePath($path){

		$segments = explode('/',$path);
		$segments = array_reverse($segments);

		$path = array();
		$path_len = 0;

		while( $segments ){
			$segment = array_pop($segments);
			switch( $segment ) {

				case '.':
				break;

				case '..':
					if( !$path_len || ( $path[$path_len-1] === '..') ){
						$path[] = $segment;
						$path_len++;
					}else{
						array_pop($path);
						$path_len--;
					}
				break;

				default:
					$path[] = $segment;
					$path_len++;
				break;
			}
		}

		return implode('/',$path);
	}

}
