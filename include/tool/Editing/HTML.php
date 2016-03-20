<?php

namespace gp\tool\Editing;

defined('is_running') or die('Not an entry point...');

/*
 *
 * Parse xHTML into array
 * 		Handles the following special cases
 * 			<script>var a = "</script>";</script>
 * 			<div name="poorly > formatted ">
 * 			<input disabled>
 * 			<input name=no_quotes>
 * 		Change "< " to "&lt; "
 *
 * Does Not:
 * 		Change poorly formatted html attributes
 * 		Fix self closing tags
 * 		Handle use of inline regular expressions in javascript
 *
 * To Do
 * 	Parse Error Handling
 *  	What to do with content after last tag?
 *
 */


class HTML extends \gp\tool\Editing\HTMLParse{

	public $result = '';

	public $empty_attributes = array('checked'=>1,
								'compact'=>1,
								'declare'=>1,
								'defer'=>1,
								'disabled'=>1,
								'ismap'=>1,
								'multiple'=>1,
								'nohref'=>1,
								'noresize'=>1,
								'noshade'=>1,
								'nowrap'=>1,
								'readonly'=>1,
								'selected'=>1);

	public $self_closing_tags = array('img'=>1,
									'input'=>1,
									'area'=>1,
									'br'=>1,
									'hr'=>1,
									'link'=>1
									);
	public $required_attr = array(
								'area'=>		array('alt'=>''),
								'bdo'=>			array('dir'=>''),
								'form'=>		array('action'=>''),
								'img'=>			array('src'=>'','alt'=>''),
								'map'=>			array('name'=>''),
								'meta'=>		array('content'=>''),
								'optgroup'=>	array('label'=>''),
								'param'=>		array('name'=>''),
								'script'=>		array(),
								'style'=>		array('type'=>''),
								'textarea'=>	array('cols'=>'','rows'=>'')
								);

	public function __construct($text){
		parent::__construct($text);

		$this->dom_array = \gp\tool\Plugins::Filter('Html_Output',array($this->dom_array));

		$this->Clean();

		$this->Rebuild();
	}


	/*
	 * Make sure certain cms elements aren't copied into the html of pages
	 *
	 * 	a.find('.editable_area').removeAttr('class').removeAttr('id');
		a.find('.ExtraEditLink').removeAttr('class').removeAttr('id');
		a.find('.ExtraEditLnks').removeAttr('class').removeAttr('id');
		a.find('.gp_nosave').remove();
	 *
	 */
	public function Clean(){

		$no_save_level = 0;
		$no_save_levels = array();

		foreach($this->dom_array as $key => $dom_element){
			if( $no_save_level > 0 ){

				if( is_array($dom_element) && isset($dom_element['tag']) ){
					$tag = $dom_element['tag'];
					if( $tag{0} == '/' ){
						$tag_check = substr($tag,1);
						if( $no_save_levels[$no_save_level] == $tag_check ){
							array_pop($no_save_levels);
							$no_save_level--;
						}else{
							//error?
							//msg('end tag not in no_save_levels');
						}
					}elseif( !isset($this->self_closing_tags[$tag]) ){
						$no_save_level++;
						$no_save_levels[$no_save_level] = $tag;
					}
				}
				$this->dom_array[$key] = false;
				continue;
			}


			if( !is_array($dom_element) ){
				continue;
			}

			if( isset($dom_element['attributes']) && is_array($dom_element['attributes']) ){

				//remove classes used by cms
				if( isset($dom_element['attributes']['class']) ){
					$class = ' '.$dom_element['attributes']['class'].' ';
					$cms_element = false;
					if( strpos($class,' editable_area ') !== false ){
						$cms_element = true;
					}elseif( strpos($class,' ExtraEditLink ') !== false ){
						$cms_element = true;
					}elseif( strpos($class,' ExtraEditLnks ') !== false ){
						$cms_element = true;
					}
					if( $cms_element ){
						unset($this->dom_array[$key]['attributes']['class']);
						unset($this->dom_array[$key]['attributes']['id']);
					}

					if( strpos($class,' gp_nosave ') !== false ){
						$no_save_level = 1;
						$no_save_levels[1] = $dom_element['tag'];
						$this->dom_array[$key] = false;
					}
				}

				//remove javascript from links
				if( isset($dom_element['attributes']['href']) ){

					$href = $dom_element['attributes']['href'];
					if( stripos(ltrim($href),'javascript') === 0 ){
						$this->dom_array[$key]['attributes']['href'] = '';
					}

					if( stripos(ltrim($href),'vbscript') === 0 ){
						$this->dom_array[$key]['attributes']['href'] = '';
					}

				}
			}
		}
	}

	/**
	 * Rebuild the html content from the $dom_array
	 *
	 */
	public function Rebuild(){

		$this->result	= '';
		$open_tags		= array();

		foreach($this->dom_array as $dom_element){

			if( $dom_element === false ){
				continue;
			}

			if( !is_array($dom_element) ){
				$this->result .= $dom_element;
				continue;
			}

			if( isset($dom_element['comment']) ){
				$this->result .= '<!--'.$dom_element['comment'].'-->';
				continue;
			}

			if( isset($dom_element['tag']) ){
				$this->result .= $this->BuildTag($dom_element);
			}
		}
	}


	/**
	 * Rebuild an html tag
	 *
	 */
	public function BuildTag($dom_element){

		$tag	= strtolower($dom_element['tag']);
		$result	= '<'.$tag;

		if( isset($dom_element['attributes']) && is_array($dom_element['attributes']) ){
			$result .= $this->BuildAttributes($tag, $dom_element);
		}

		if( isset($this->self_closing_tags[$tag]) ){
			$result .= ' />';
		}elseif( isset($dom_element['self_closing']) && $dom_element['self_closing'] ){
			$result .= ' />';
		}else{
			$result .= '>';
		}

		return $result;
	}

	/**
	 * Rebuild a list of attributes
	 *
	 */
	public function BuildAttributes($tag, $dom_element){

		$result = '';

		if( isset($this->required_attr[$tag]) && is_array($this->required_attr[$tag]) ){
			$dom_element['attributes'] += $this->required_attr[$tag];
		}

		foreach($dom_element['attributes'] as $attr_name => $attr_value){

			if( is_null($attr_value) || isset($this->empty_attributes[$attr_name]) ){
				$result .= ' '.$attr_name;
				continue;
			}

			$result .= ' '.strtolower($attr_name).'="'.$this->htmlspecialchars($attr_value).'"';
		}

		return $result;
	}

	public function htmlspecialchars($text){
		return htmlspecialchars($text,ENT_COMPAT,'UTF-8',false);
	}
}
