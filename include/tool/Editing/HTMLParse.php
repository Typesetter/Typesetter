<?php

namespace gp\tool\Editing;

defined('is_running') or die('Not an entry point...');


class HTMLParse{

	public $doc = '';
	public $dom_array = array();
	public $errors = array();

	public $random;
	public $mark_double_slash;
	public $mark_escaped_single;
	public $mark_escaped_double;

	public function __construct($text){
		$this->doc = $text;
		$this->Init_Parse();
		$this->Parse();
	}


	public function Parse(){

		$offset = 0;

		do{
			$continue = true;
			$pos = strpos($this->doc,'<',$offset);


			//no more tags
			if( $pos === false ){
				$continue = false;
				break;
			}

			//comment
			if( substr($this->doc,$pos,4) === '<!--' ){
				$this->dom_array[] = substr($this->doc,$offset,$pos-$offset); //get content before comment
				$offset = $pos + 4;
				$this->CommentContent($offset);
				continue;
			}

			//get tag name
			$tag_name = $this->TagName($pos+1,$name_len);
			if( !$tag_name ){
				$this->doc = substr_replace($this->doc,'&lt;',$pos,1);
				continue;
			}

			//content
			if( $pos > $offset ){
				$this->dom_array[] = substr($this->doc,$offset,$pos-$offset);
			}


			//advance offset to just after <tag_name
			$offset = $pos+1+$name_len;
			$new_element = array();
			$new_element['tag'] = $tag_name;

			//attributes
			if( $tag_name{0} != '/' ){
				$new_element['attributes'] = $this->GetAttributes($offset);
			}

			//tag closing and self-closing?
			$pos = strpos($this->doc,'>',$offset);
			if( $pos !== false ){
				if( $pos > 0 && $this->doc{$pos-1} == '/' ){
					$new_element['self_closing'] = true;
				}
				$offset = $pos+1;
			}

			$this->dom_array[] = $new_element;


			//content
			switch(strtolower($tag_name)){
				case 'script':
				case 'style';
					$this->NonHtmlContent($offset,$tag_name);
				break;
			}

		}while($continue);

		//content after
		if( $offset < strlen($this->doc) ){
			$this->dom_array[] = substr($this->doc,$offset);
		}

	}


	/**
	 * Parse an html tag name
	 *
	 */
	public function TagName($pos,&$name_len){
		$tag_name = false;
		$name_len = strspn($this->doc,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890/!:--',$pos);
		if( $name_len > 0 ){
			$tag_name = substr($this->doc,$pos,$name_len);
		}
		return $tag_name;
	}


	/**
	 * Parse html attributes
	 * match name="value", name=value or name
	 * accounts for name="value ' value"
	 *
	 */
	public function GetAttributes(&$offset){

		$this->doc		= substr($this->doc,$offset);
		$pattern_name	= '#^\s+([^\'"<>=\s/]+)\s*(=?)\s*#';
		$attributes		= array();


		while( preg_match($pattern_name, $this->doc, $matches) ){

			$attr_name	= $matches[1];
			$attr_value = null;
			$offset		= strlen($matches[0]);

			//get attribute value
			if( !empty($matches[2]) ){

				$attr_match			= $this->MatchAttribute($offset);
				if( $attr_match ){
					$offset += strlen($attr_match[0]);
					$attr_value = $attr_match[1];
				}
			}


			if( !isset($attributes[$attr_name]) ){
				$attributes[$attr_name] = $attr_value;
			}

			$this->doc = substr($this->doc,$offset);
			$offset = 0;
		}


		$offset = 0;

		return $attributes;
	}

	/**
	 * Get an html attribute value
	 *
	 */
	protected function MatchAttribute(&$offset){

		$char = $this->doc[$offset];

		//double quote
		if( $char === '"' ){
			if( preg_match('#\\G"([^"]*)"#', $this->doc, $matches, 0, $offset) ){
				return $matches;
			}
			return;
		}

		//single quote
		if( $char == "'" ){
			if( preg_match('#\\G\'([^\']*)\'#', $this->doc, $matches, 0, $offset) ){
				return $matches;
			}
			return;
		}



		//not quoted
		if( preg_match('#\\G\s*([^\'"<>=\s/]+)#', $this->doc, $matches, 0, $offset) ){
			return $matches;
		}

	}


	/**
	 * Parse HTML comments <!-- comments go here -->
	 * Does not support full sgml comments <!------> second comment -->
	 *
	 */
	public function CommentContent(&$offset){

		$this->doc = substr($this->doc,$offset);
		$offset = 0;

		$pos = strpos($this->doc,'-->');
		if( $pos === false ){
			$pos = strlen($this->doc);
		}

		$comment_content = substr($this->doc,0,$pos);
		$this->doc = substr($this->doc,$pos+3);

		$new_element = array();
		$new_element['comment'] = $comment_content;
		$this->dom_array[] = $new_element;
	}

	public function NonHtmlContent(&$offset,$untill){

		$this->doc = substr($this->doc,$offset);
		$offset = 0;
		$this->doc = $this->EscapeQuotes($this->doc);
		$full_length = strlen($this->doc);
		$untill_length = strlen($untill);

		do{

			$continue = false;
			$end_string = false;

			$pos_quote1 = $this->strpos_min("'",$offset,$full_length);
			$pos_quote2 = $this->strpos_min('"',$offset,$full_length);
			$pos_scomment = $this->strpos_min('//',$offset,$full_length);
			$pos_mcomment = $this->strpos_min('/*',$offset,$full_length);

			$min_pos = min($pos_quote1, $pos_quote2, $pos_scomment, $pos_mcomment);

			$pos_close = strpos($this->doc,'</',$offset);

			// found </script>
			if( ($pos_close !== false)
				&& ($pos_close <= $min_pos)
				&& (strtolower(substr($this->doc,$pos_close+2,$untill_length)) == $untill)
				){
					$offset = $pos_close;
					break;
			}

			// nothing else found
			if( $min_pos === $full_length ){
				$offset = $full_length;
				break;
			}


			if( $min_pos === $pos_quote1 ){
				$end_string = "'";
			}elseif( $min_pos === $pos_quote2 ){
				$end_string = '"';
			}elseif( $min_pos === $pos_scomment ){
				$end_string = "\n";
			}elseif( $min_pos === $pos_mcomment ){
				$end_string = '*/';
			}

			$end_pos = strpos($this->doc,$end_string,$min_pos+1);
			if( $end_pos === false ){
				$offset = $full_length;
			}else{
				$offset = $full_length;
				$offset = $end_pos + strlen($end_string);
				$continue = true;
			}


		}while($continue);

		$code = substr($this->doc,0,$offset);
		$this->doc = substr($this->doc,$offset);
		$this->doc = $this->UnescapeQuotes($this->doc);
		$this->dom_array[] = $this->UnescapeQuotes($code);
		$offset = 0;
	}


	public function strpos_min($needle,$offset,$length){
		$pos = strpos($this->doc,$needle,$offset);
		if( $pos === false ){
			return $length;
		}
		return $pos;
	}


	public function EscapeQuotes($string){

		$search = array('\\\\','\\\'','\\"');
		$replace = array( $this->mark_double_slash, $this->mark_escaped_single, $this->mark_escaped_double);

		return str_replace($search, $replace, $string);
	}

	public function UnescapeQuotes($string){
		$search = array( $this->mark_double_slash, $this->mark_escaped_single, $this->mark_escaped_double);
		$replace = array('\\\\','\\\'','\\"');
		return str_replace($search, $replace, $string);
	}

	/*
	 * Init
	 *
	 */
	public function Init_Parse(){
		$this->GetRandom();
		$this->mark_double_slash = $this->GetMarker();
		$this->mark_escaped_single = $this->GetMarker();
		$this->mark_escaped_double = $this->GetMarker();
	}


	public function GetRandom(){
		do{
			$this->random = dechex(mt_rand(0, 0x7fffff));
		}while(strpos($this->doc,$this->random) !== false);
	}

	public function GetMarker(){
		static $n = 0;
		return $this->random . sprintf('%08X', $n++);
	}
}
