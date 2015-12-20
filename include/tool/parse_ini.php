<?php
defined("is_running") or die("Not an entry point...");

/*
 * Parse ini files and strings
 * We use the custom parser
 * 		- since parse_ini_string isn't available till php 5.3
 * 		- so that we can pass variables to the ini files
 * 		- parse_ini_file/string can fatally crash php if the ini file is corrupt
 * 		- php's functions don't handle escaped quotes as expected
 */

class gp_ini{

	static function ParseFile($file,$variables=array()){

		$contents = file_get_contents($file);
		if( $contents === false ){
			return array();
		}

		return gp_ini::ParseString( $contents, $variables );
	}

	static function ParseString( $string, $variables=array() ){

		if( count($variables) > 0 ){
			$keys = array_keys($variables);
			$values = array_values($variables);
			$string = str_replace($keys,$values,$string);
		}

		$aResult  = array();
		$a = &$aResult;

		$lines = explode("\n",$string);
		foreach($lines as $line){
			$line = trim($line);
			if( strlen($line) < 1 ){
				continue;
			}
			if( $line{0} == ';' ){
				continue;
			}

			//sections
			if( $line{0} == '[' ){
				$line = gp_ini::GetQuotedText($line,']');
				if( $line == false ){
					return false;
				}
				$a = &$aResult[$line];
				continue;
			}

			gp_ini::GetAssignment($line,$key,$value);
			if( $key !== false && $value !== false ){
				$a[$key] = $value;
			}
		}
		return $aResult;
	}

	static function GetAssignment($line,&$key,&$value){
		$key = $value = false;

		//get the key
		$len = strspn($line,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_\\- *:');
		if( $len < 1 ){
			return true;
		}
		$key = substr($line,0,$len);
		$key = trim($key);
		$line = substr($line,$len);


		//check for the =
		$line = trim($line);
		if( strlen($line) < 1 ){
			return true;
		}
		if( $line{0} != '=' ){
			return true;
		}
		$line = substr($line,1);
		$line = trim($line);
		if( strlen($line) < 1 ){
			return true;
		}

		//get the value
		if( $line{0} == '"' ){
			$value = gp_ini::GetQuotedText($line,'"');
		}elseif( $line{0} == "'" ){
			$value = gp_ini::GetQuotedText($line,"'");
		}else{
			$pos = strpos($line,';');
			if( $pos > 0 ){
				$value = substr($line,0,$pos);
				$value = trim($value);
			}else{
				$value = $line;
			}
			if( !empty($value) ){
				$value = gp_ini::Value($value);
			}
		}

		return true;
	}

	static function GetQuotedText($line,$closechar=']'){

		do{
			$unique = uniqid('__');
		}while( strpos($line,$unique) );

		$line = str_replace('\\\\',$unique.'_BACKSLASHES',$line);
		$line = str_replace('\\'.$closechar,$unique.'_ESCAPED',$line);

		$line = substr($line,1);
		$pos = strpos($line,$closechar);
		if( $pos == false ){
			return false;
		}

		$line = substr($line,0,$pos);
		$line = str_replace($unique.'_ESCAPED',$closechar,$line);
		$line = str_replace($unique.'_BACKSLASHES','\\',$line);

		return $line;
	}

	static function Value($val){
		if (preg_match('/^-?[0-9]$/', $val)) { return intval($val); }
		else if (strtolower($val) === 'true') { return true; }
		else if (strtolower($val) === 'false') { return false; }
		return $val;
	}
}
