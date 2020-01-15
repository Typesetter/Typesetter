<?php

namespace gp\tool;


class HTML{

	/**
	 * Generate html <select>
	 * @param array $possible
	 * @param string $value
	 * @param array|string $attrs
	 * @return string
	 */
	public static function Select( $possible, $value=null, $attrs = []){

		$attr_string	= self::Attributes($attrs);
		$html			= "\n<select ".$attr_string.'>';

		if( !isset($possible[$value]) ){
			$html .= '<option value="" selected="selected"></option>';
		}

		$html .= self::Options($possible,$value);
		$html .= '</select>';

		return $html;
	}

	/**
	 * Generate html <option>
	 * @param array $array
	 * @param string $current_value
	 * @return string
	 */
	public static function Options($array,$current_value){

		$html = '';
		foreach($array as $key => $value){
			if( is_array($value) ){
				$html .= '<optgroup label="'.$key.'">';
				self::Options($value,$current_value);
				$html .= '</optgroup>';
				continue;
			}

			$selected = '';
			if( $key == $current_value ){
				$selected = ' selected ';
			}

			$html .= '<option value="' . self::Chars($key) . '" ' . $selected . '>' . $value . '</option>';

		}

		return $html;
	}

	/**
	 * Generate html attributes
	 * @param array|string $attributes
	 * @return string;
	 */
	public static function Attributes($attributes){

		if( is_string($attributes) ){
			return $attributes;
		}

		$attr_string = '';
		foreach($attributes as $attr => $value){
			$attr_string .= ' ' . self::Chars($attr) . '="' . self::Chars($value) . '"';
		}

		return $attr_string;
	}

	public static function Chars($str){
		return htmlspecialchars($str, ENT_COMPAT, 'UTF-8', false);
	}


}
