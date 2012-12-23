<?php
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
 * 		(HTML_Output.php does fix some of these)
 *
 * To Do
 * 	Parse Error Handling
 *  	What to do with content after last tag?
 *
 */




includeFile('tool/HTML_Parse.php');

class gp_html_output extends gp_html_parse{

	var $result = '';

	var $empty_attributes = array('checked'=>1,
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

	var $self_closing_tags = array('img'=>1,
									'input'=>1,
									'area'=>1,
									'br'=>1,
									'hr'=>1,
									'link'=>1
									);
	var $required_attr = array(
								'area'=>		array('alt'=>''),
								'bdo'=>			array('dir'=>''),
								'form'=>		array('action'=>''),
								'img'=>			array('src'=>'','alt'=>''),
								'map'=>			array('name'=>''),
								'meta'=>		array('content'=>''),
								'optgroup'=>	array('label'=>''),
								'param'=>		array('name'=>''),
								'script'=>		array('type'=>'text/javascript'),
								'style'=>		array('type'=>''),
								'textarea'=>	array('cols'=>'','rows'=>'')
								);

	function gp_html_output($text){
		$this->gp_html_parse($text);

		$this->dom_array = gpPlugin::Filter('Html_Output',array($this->dom_array));

		$this->Clean();

		$this->Rebuild();
	}


	/*
	 * Make sure certain gpEasy elements aren't copied into the html of pages
	 *
	 * 	a.find('.editable_area').removeAttr('class').removeAttr('id');
		a.find('.ExtraEditLink').removeAttr('class').removeAttr('id');
		a.find('.ExtraEditLnks').removeAttr('class').removeAttr('id');
		a.find('.gp_nosave').remove();
	 *
	 */
	function Clean(){

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
							//message('end tag not in no_save_levels');
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

			if( isset($dom_element['attributes'])
				&& is_array($dom_element['attributes'])
				&& isset($dom_element['attributes']['class'])
				){
					$class = ' '.$dom_element['attributes']['class'].' ';
					$gpeasy_element = false;
					if( strpos($class,' editable_area ') !== false ){
						$gpeasy_element = true;
					}elseif( strpos($class,' ExtraEditLink ') !== false ){
						$gpeasy_element = true;
					}elseif( strpos($class,' ExtraEditLnks ') !== false ){
						$gpeasy_element = true;
					}
					if( $gpeasy_element ){
						unset($this->dom_array[$key]['attributes']['class']);
						unset($this->dom_array[$key]['attributes']['id']);
					}

					if( strpos($class,' gp_nosave ') !== false ){
						$no_save_level = 1;
						$no_save_levels[1] = $dom_element['tag'];
						$this->dom_array[$key] = false;
					}
			}
		}
	}

	function Rebuild(){

		$this->result = '';

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
				$tag = strtolower($dom_element['tag']);
				$this->result .= '<'.$tag;

				if( isset($dom_element['attributes']) && is_array($dom_element['attributes']) ){

					if( isset($this->required_attr[$tag]) && is_array($this->required_attr[$tag]) ){
						$dom_element['attributes'] += $this->required_attr[$tag];
					}

					foreach($dom_element['attributes'] as $attr_name => $attr_value){

						if( empty($attr_value) && isset($this->empty_attributes[$attr_name]) ){
							$attr_value = $attr_name;
						}

						$this->result .= ' '.strtolower($attr_name).'="'.$this->htmlspecialchars($attr_value).'"';
					}
				}

				if( isset($this->self_closing_tags[$tag]) ){
					$this->result .= ' />';
				}elseif( isset($dom_element['self_closing']) && $dom_element['self_closing'] ){
					$this->result .= ' />';
				}else{
					$this->result .= '>';
				}
			}
		}
	}

	function htmlspecialchars($text){
		return htmlspecialchars($text,ENT_COMPAT,'UTF-8',false);
	}
}




/* Test Case
 *
 */

$test = '<H2 class=heading class="not_header" id="head">
	Always Easy</h2>
<div class="gp_nosave">
	<script> </script>
	<a href="hmm" class="ExtraEditLnks">link</a>
	div</div>
<p class="hmm" style="display:block">
	This is some text</p>
<script>var a = "</script>";
var b = "<script>";


//this is a quote

</SCript>

< and some text

<!-- this is a-comment ----- another -->

<p>
	another</p>
<input checked type="checkbox" name="check">
<style type="text/css">
css style</style>
<div style="display: block; background: url(\'this.jpg\') repeat scroll 0% 0% transparent;">
	\\\\ slashes</div>
<p>
	And this</p>
<hr style="width: 100px;" />
content after.. poorly fomatted xml
';

//$gp_html_output = new gp_html_output($test);
//$text = $gp_html_output->result;
//message('<textarea cols="100" rows="10">'.htmlspecialchars($gp_html_output->result).'</textarea>');

