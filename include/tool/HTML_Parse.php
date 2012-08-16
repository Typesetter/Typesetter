<?php
defined('is_running') or die('Not an entry point...');




class gp_html_parse{

	var $doc = '';
	var $dom_array = array();
	var $errors = array();

	var $random;
	var $mark_double_slash;
	var $mark_escaped_single;
	var $mark_escaped_double;

	function gp_html_parse($text){
		$this->doc = $text;
		$this->Init_Parse();
		$this->Parse();
	}


	function Parse(){

		$offset = 0;

		do{
			$continue = true;
			$pos = strpos($this->doc,'<',$offset);


			//no more tags
			if( $pos === false ){
				$continue = false;
				break;
			}

			//get tag name
			$tag_name = $this->TagName($pos+1,$name_len);
			if( !$tag_name ){
				$this->doc = substr_replace($this->doc,'&lt;',$pos,1);
				continue;
			}

			//content
			if( $pos > $offset ){
				$content = substr($this->doc,$offset,$pos-$offset);
				$this->dom_array[] = $content;
			}


			//advance offset to just after <tag_name
			$offset = $pos+1+$name_len;

			//comments
			if( ($tag_name == '!--') || $tag_name == '!' ){
				$this->CommentContent($offset);
				continue;
			}

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

		//message(showArray($this->dom_array));
	}

	function TagName($pos,&$name_len){
		$tag_name = false;
		$name_len = strspn($this->doc,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890/!:--',$pos);
		if( $name_len > 0 ){
			$tag_name = substr($this->doc,$pos,$name_len);
		}
		return $tag_name;
	}


	function GetAttributes(&$offset){

		//match name="value", name=value or name
		// accounts for name="value ' value"
		$pattern_name = '([^\'"<>=\s/]+)';
		$pattern_value = '(?:\s*=\s*((?:([\'"])(?U)[^\4]*\4)|(?:[^\'"<>/\s]+)))?';
		$pattern = '#^\s+('.$pattern_name.$pattern_value.')#';

		$attributes = array();
		do{

			//get the substr because offset is not avail until php 4.3.3
			$this->doc = substr($this->doc,$offset);

			//get the substr because offset is not avail until php 4.3.3
			$continue = false;
			if( preg_match($pattern, $this->doc, $matches, PREG_OFFSET_CAPTURE) ){
				$attr_string = $matches[1][0];
				$offset = $matches[1][1] + strlen($attr_string);
				$continue = true;


				//
				$attr_name = $matches[2][0];
				$attr_value = '';
				if( isset($matches[3]) ){
					$attr_value = $matches[3][0];
					if( !empty($matches[4][0]) ){
						$attr_value = trim($attr_value,'"');
					}
				}
				if( !isset($attributes[$attr_name]) ){
					$attributes[$attr_name] = $attr_value;
				}
			}

		}while($continue);

		$offset = 0;

		return $attributes;
	}


	//support simple comments <!-- comments go here -->
	// does not support full sgml comments <!------> second comment -->
	function CommentContent(&$offset){

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

	function NonHtmlContent(&$offset,$untill){

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


	function strpos_min($needle,$offset,$length){
		$pos = strpos($this->doc,$needle,$offset);
		if( $pos === false ){
			return $length;
		}
		return $pos;
	}


	function EscapeQuotes($string){

		$search = array('\\\\','\\\'','\\"');
		$replace = array( $this->mark_double_slash, $this->mark_escaped_single, $this->mark_escaped_double);

		return str_replace($search, $replace, $string);
	}

	function UnescapeQuotes($string){
		$search = array( $this->mark_double_slash, $this->mark_escaped_single, $this->mark_escaped_double);
		$replace = array('\\\\','\\\'','\\"');
		return str_replace($search, $replace, $string);
	}

	/*
	 * Init
	 *
	 */
	function Init_Parse(){
		$this->GetRandom();
		$this->mark_double_slash = $this->GetMarker();
		$this->mark_escaped_single = $this->GetMarker();
		$this->mark_escaped_double = $this->GetMarker();
	}


	function GetRandom(){
		do{
			$this->random = dechex(mt_rand(0, 0x7fffff));
		}while(strpos($this->doc,$this->random) !== false);
	}

	function GetMarker(){
		static $n = 0;
		return $this->random . sprintf('%08X', $n++);
	}
}
