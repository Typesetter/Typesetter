<?php
defined('is_running') or die('Not an entry point...');


class gp_edit{



	/**
	 * Replace css resized images with resized copies of the original
	 * ckeditor uses style height/width
	 *
	 */
	static function ResizeImages(&$html_content,&$img_list){

		includeFile('tool/HTML_Output.php');
		includeFile('image.php');
		gp_resized::SetIndex();


		$orig_list = array();
		if( is_array($img_list) ){
			$orig_list = $img_list;
		}
		$img_list = array();

		//resize images
		$gp_html_output = new gp_html_output($html_content);
		foreach($gp_html_output->dom_array as $key => $node){
			if( !is_array($node) ){
				continue;
			}

			$tag = $node['tag'];
			if( $tag != 'img' || !isset($node['attributes']['src']) ){
				continue;
			}

			$original_src = $node['attributes']['src'];
			$resized_data = gp_edit::ResizedImage($node['attributes']);
			if( $resized_data !== false ){
				$img = $resized_data['img'];
				$index = $resized_data['index'];
				$resized_src = common::GetDir('/include/image.php',true).'?i='.$index.'&amp;w='.$resized_data['w'].'&amp;h='.$resized_data['h'].'&amp;img='.rawurlencode($img);
				$gp_html_output->dom_array[$key]['attributes']['src'] = $resized_src;
				$img_list[$index][] = $resized_data['w'].'x'.$resized_data['h'];
				$img_list[$index] = array_unique($img_list[$index]);
				gp_resized::$index[$index] = $img;
			}
		}
		$gp_html_output->Rebuild();
		$html_content = $gp_html_output->result;

		gp_edit::ResizedImageUse($orig_list,$img_list);
		gp_resized::SaveIndex();
	}


	/**
	 * Attempt to create a resized image resized image
	 *
	 */
	static function ResizedImage($attributes){
		global $dataDir,$dirPrefix;


		//height and width from style
		$css_w = $css_h = false;
		if( !empty($attributes['style']) ){
			$css_args = explode(';',$attributes['style']);
			foreach($css_args as $css_arg){
				$css_arg = explode(':',$css_arg);
				if( count($css_arg) != 2 ){
					continue;
				}
				$css_key = strtolower(trim($css_arg[0]));
				$css_value = strtolower(trim($css_arg[1]));
				$px_pos = strpos($css_value,'px');
				if( !$px_pos ){
					continue;
				}
				if( $css_key == 'width' ){
					$css_w = substr($css_value,0,$px_pos);
				}elseif( $css_key == 'height' ){
					$css_h = substr($css_value,0,$px_pos);
				}
			}
		}

		//width attribute
		if( !$css_w && isset($attributes['width']) && is_numeric($attributes['width']) ){
			$css_w = $attributes['width'];
		}

		//height attribute
		if( !$css_h && isset($attributes['height']) && is_numeric($attributes['height']) ){
			$css_h = $attributes['height'];
		}

		if( !$css_w && !$css_h ){
			return false;
		}


		//check src
		if( empty($attributes['src']) ){
			return false;
		}
		$src = urldecode($attributes['src']);
		$img_dir = $dirPrefix.'/data/_uploaded';
		if( $src[0] != '/' && strpos($src,$img_dir) !== 0 ){
			return false;
		}
		$src_relative = substr($src,strlen($img_dir));

		return gp_edit::CreateImage($src_relative,$css_w,$css_h);
	}


	/**
	 * Create a resized image of the file at $src_relative
	 *
	 */
	static function CreateImage($src_relative,$width,$height){
		global $dataDir;

		$src_path = $dataDir.'/data/_uploaded'.$src_relative;
		if( !file_exists($src_path) ){
			return false;
		}

		//compare to actual size
		includeFile('tool/Images.php');
		$src_img = thumbnail::getSrcImg($src_path);
		if( !$src_img ){
			return false;
		}

		//Original Size
		$actual_w = imagesx($src_img);
		$actual_h = imagesy($src_img);

		if( $actual_w <= $width && $actual_h <= $height ){
			return false;
		}

		$info = gp_resized::ImageInfo($src_relative, $width, $height);
		if( !$info ){
			return false;
		}

		$dest_index = $info['index'];
		if( !$dest_index ){
			$dest_index = gp_resized::NewIndex();
		}
		$dest_path = $dataDir.'/data/_resized/'.$dest_index.'/'.$info['name'];
		$exists_before = file_exists($dest_path);

		//make sure the folder exists
		if( !gpFiles::CheckDir( common::DirName($dest_path) ) ){
			return false;
		}

		//create new resized image
		if( !thumbnail::createImg($src_img, $dest_path, 0, 0, 0, 0, $width, $height, $actual_w, $actual_h) ){
			return false;
		}

		//not needed if the resized image is larger than the original
		if( filesize($dest_path) > filesize($src_path) ){
			if( !$exists_before ){
				unlink($dest_path);
			}
			return false;
		}

		$data['index'] = $dest_index;
		$data['w'] = $width;
		$data['h'] = $height;
		$data['img'] = $src_relative;
		return $data;
	}

	/**
	 * Record where reduced images are being used so that we can delete them later if they are no longer referenced
	 * ... no guarantee the reduced image won't be copy & pasted into other pages.. page copies would need to track the data as well
	 *
	 */
	static function ResizedImageUse($list_before,$list_after){
		global $dataDir;

		//subtract uses no longer
		$subtract_use = $list_before;
		foreach($list_before as $index => $sizes){
			if( isset($list_after[$index]) ){
				$subtract_use[$index] = array_diff($list_before[$index],$list_after[$index]);
			}
		}

		//add uses
		$add_use = $list_after;
		foreach($add_use as $index => $sizes){
			if( isset($list_before[$index]) ){
				$add_use[$index] = array_diff($list_after[$index],$list_before[$index]);
			}
		}

		//save info for each image
		$all_imgs = array_keys($subtract_use + $add_use);
		foreach($all_imgs as $index){
			$edited = false;
			$usage = gp_resized::GetUsage($index);


			//add uses
			if( isset($add_use[$index]) && count($add_use[$index]) ){
				$edited = true;
				foreach($add_use[$index] as $size){
					if( isset($usage[$size]) ){
						$usage[$size]['uses']++;
					}else{
						$usage[$size]['uses'] = 1;
						$usage[$size]['created'] = time();
					}
					$usage[$size]['touched'] = time();
				}
			}

			//remove uses
			$set_to_zero = array();
			if( isset($subtract_use[$index]) && is_array($subtract_use[$index]) ){
				$edited = true;
				foreach($subtract_use[$index] as $size){
					if( isset($usage[$size]) ){
						$usage[$size]['uses']--;
					}else{
						$usage[$size]['uses'] = 0;
						$usage[$size]['created'] = time();//shouldn't happen
					}
					$usage[$size]['uses'] = max($usage[$size]['uses'],0);
					if( $usage[$size]['uses'] == 0 ){
						$set_to_zero[] = $size;
					}
					$usage[$size]['touched'] = time();
				}
			}



			//if uses < 1, delete the file, but not the record
			foreach($set_to_zero as $size){
				list($width,$height) = explode('x',$size);

				//make sure the image still exists
				if( !isset(gp_resized::$index[$index]) ){
					continue;
				}
				$img = gp_resized::$index[$index];
				$info = gp_resized::ImageInfo($img,$width,$height);
				if( !$info ){
					continue;
				}
				$full_path = $dataDir.'/data/_resized/'.$index.'/'.$info['name'];
				if( file_exists($full_path) ){
					@unlink($full_path);
				}
			}

			//order usage by sizes: small to large
			uksort($usage,array('gp_edit', 'SizeCompare'));

			if( $edited ){
				gp_resized::SaveUsage($index,$usage);
			}
		}
	}

	/**
	 * Replace resized images with their originals
	 *
	 */
	static function RestoreImages($html_content,$img_list){
		global $dirPrefix;

		includeFile('tool/HTML_Output.php');
		includeFile('image.php');
		gp_resized::SetIndex();

		//
		$images = array();
		foreach($img_list as $index => $sizes){
			if( !isset(gp_resized::$index[$index]) ){
				continue;
			}
			$img = gp_resized::$index[$index];
			$original_path = $dirPrefix.'/data/_uploaded'.$img;
			foreach($sizes as $size){
				list($width,$height) = explode('x',$size);
				$resized_path = common::GetDir('/include/image.php',true).'?i='.$index.'&amp;w='.$width.'&amp;h='.$height; //not searching for the whole path in case the image was renamed
				$images[$resized_path] = $original_path;
			}
		}

		//resize images
		$gp_html_output = new gp_html_output($html_content);
		foreach($gp_html_output->dom_array as $key => $node){
			if( !is_array($node) ){
				continue;
			}

			$tag = $node['tag'];
			if( $tag != 'img' || !isset($node['attributes']['src']) ){
				continue;
			}

			$src = $node['attributes']['src'];
			foreach($images as $resized => $original){
				if( strpos($src,$resized) === 0 ){
					$gp_html_output->dom_array[$key]['attributes']['src'] = $original;
				}
			}
		}

		$gp_html_output->Rebuild();
		return $gp_html_output->result;
	}

	/**
	 * Comare the sizes of two images
	 *
	 */
	static function SizeCompare($size1, $size2){
		list($w1,$h1) = explode('x',$size1);
		list($w2,$h2) = explode('x',$size2);
		return ($w1*$h1) > ($w2*$h2);
	}



	/**
	 * Clean a string that may be used as an internal file path
	 *
	 * @param string $path The string to be cleansed
	 * @return string The cleansed string
	 */
	static function CleanArg($path){

		//all forward slashes
		$path = str_replace('\\','/',$path);

		//remove directory style changes
		$path = str_replace(array('../','./','..'),array('','',''),$path);

		//change other characters to underscore
		//$pattern = '#\\.|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		$pattern = '#\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#u';
		$path = preg_replace( $pattern, '_', $path ) ;

		//reduce multiple slashes to single
		$pattern = '#\/+#';
		$path = preg_replace( $pattern, '/', $path ) ;

		return $path;
	}


	/**
	 * Clean a string for use as a page title (url)
	 * Removes potentially problematic characters
	 *
	 * @param string $title The string to be cleansed
	 * @param string $spaces The string spaces will be replaced with
	 * @return string The cleansed string
	 */
	static function CleanTitle($title,$spaces = '_'){

		if( empty($title) ){
			return $title;
		}

		// Remove control characters
		$title = preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]

		$title = str_replace(array('"',"'",'?','*',':'),array(''),$title); // # needed for entities

		$title = str_replace(array('<','>','|','\\'),array(' ',' ',' ','/'),$title);
		$title = preg_replace('#\.+([\\\\/])#','$1',$title);
		$title = trim($title,'/');

		$title = trim($title);
		if( $spaces ){
			//$title = preg_replace( '#[[:space:]]#', $spaces, $title );
			$title = str_replace(' ',$spaces,$title);
		}

		return $title;
	}

	/**
	 * Use HTML Tidy to validate the $text
	 * Only runs when $config['HTML_Tidy'] is off
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	static function tidyFix(&$text){
		global $config;

		if( !function_exists('tidy_parse_string') ){
			return false;
		}
		if( empty($config['HTML_Tidy']) || $config['HTML_Tidy'] == 'off' ){
			return true;
		}


		$options = array();
		$options['wrap'] = 0;						//keeps tidy from wrapping... want the least amount of space changing as possible.. could get rid of spaces between words with the str_replaces below
		$options['doctype'] = 'omit';				//omit, auto, strict, transitional, user
		$options['drop-empty-paras'] = true;		//drop empty paragraphs
		$options['output-xhtml'] = true;			//need this so that <br> will be <br/> .. etc
		$options['show-body-only'] = true;
		$options['hide-comments'] = false;
		//$options['anchor-as-name'] = true;		//default is true, but not alwasy availabel. When true, adds an id attribute to anchor; when false, removes the name attribute... poorly designed, but we need it to be true


		//
		//	php4
		//
		if( function_exists('tidy_setopt') ){
			$options['char-encoding'] = 'utf8';
			gp_edit::tidyOptions($options);
			$tidy = tidy_parse_string($text);
			tidy_clean_repair();

			if( tidy_get_status() === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output();

		//
		//	php5
		//
		}else{
			$tidy = tidy_parse_string($text,$options,'utf8');
			tidy_clean_repair($tidy);

			if( tidy_get_status($tidy) === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output($tidy);
		}
		return true;
	}

	//for php4
	static function tidyOptions($options){
		foreach($options as $key => $value){
			tidy_setopt($key,$value);
		}
	}


	/**
	 * Return javascript code to be used with autocomplete (jquery ui)
	 *
	 */
	static function AutoCompleteValues($GetUrl=true,$options = array()){
		global $gp_index;

		$options += array(	'admin_vals' => true,
							'var_name' => 'gptitles'
							);


		//internal link array
		$code = 'var '.$options['var_name'].'=[';
		foreach($gp_index as $slug => $id){

			$label = common::GetLabel($slug);
			$label = str_replace( array('&lt;','&gt;','&quot;','&#39;','&amp;'), array('<','>','"',"'",'&')  , $label);

			if( $GetUrl ){
				$slug = common::GetUrl($slug,'',false);
				$slug = rawurldecode($slug);
			}
			$code .= '["'.addslashes($label).'","'.addslashes($slug).'"],';
		}


		if( $options['admin_vals'] && class_exists('admin_tools') ){
			$scripts = admin_tools::AdminScripts();
			foreach($scripts as $url => $info){
				if( $GetUrl ){
					$url = common::GetUrl($url,'',false);
					$url = rawurldecode($url);
				}
				$code .= '["'.addslashes($info['label']).'","'.addslashes($url).'"],';
			}
		}
		$code = trim($code,',');
		$code .= '];';
		return $code;
	}


	static function PrepAutoComplete($autocomplete_js=true,$GetUrl=true){
		global $page;

		common::LoadComponents('autocomplete');
		if( $autocomplete_js ){
			$page->head_js[] = '/include/js/autocomplete.js';
		}

		$page->head_script .= gp_edit::AutoCompleteValues($GetUrl);
	}

	/**
	 * Use ckeditor for to edit content
	 *
	 *	configuration options
	 * 	- http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
	 */
	static function UseCK($contents,$name='gpcontent',$options=array()){
		global $page;

		$options += array('rows'=>'20','cols'=>'50');

		echo "\n\n";

		echo '<textarea name="'.$name.'" style="width:90%" rows="'.$options['rows'].'" cols="'.$options['cols'].'" class="CKEDITAREA">';
		echo htmlspecialchars($contents);
		echo '</textarea><br/>';


		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/thirdparty/ckeditor_34/ckeditor.js').'?'.rawurlencode(gpversion).'"></script>';
		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/js/ckeditor_config.js').'?'.rawurlencode(gpversion).'"></script>';


		gp_edit::PrepAutoComplete(false,true);

		ob_start();

		echo 'CKEDITOR.replaceAll( function(tarea,config){';

		echo 'if( tarea.className.indexOf("CKEDITAREA") == -1 ) return false;';

		echo gp_edit::CKConfig($options);

		echo 'return true;';
		echo '});';
		echo "\n\n";
		$page->jQueryCode .= ob_get_clean();

	}


	/**
	 * CKEditor configuration settings
	 * Any settings here take precedence over settings in configuration files defined by the customConfig setting
	 *
	 */
	static function CKConfig($options=array(),$config_name='config'){
		global $config;

		$defaults = array(
						//'customConfig'				=> common::GetDir('/include/js/ckeditor_config.js'),
						'browser'					=> true, //not actually a ckeditor configuration value, but we're keeping it now for reverse compat
						'smiley_path'				=> common::GetDir('/include/thirdparty/ckeditor_34/plugins/smiley/images/'),
						'height'					=> 300,
						'contentsCss'				=> common::GetDir('/include/css/ckeditor_contents.css'),
						'fontSize_sizes'			=> 'Smaller/smaller;Normal/;Larger/larger;8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px',
						'ignoreEmptyParagraph'		=> true,
						'entities_latin'			=> false,
						'entities_greek'			=> false,
						'scayt_autoStartup'			=> false,
						'disableNativeSpellChecker'	=> false,
						'FillEmptyBlocks'			=> false,
						'toolbar'					=> array(
															array('Source','Templates','ShowBlocks','Undo','Redo','RemoveFormat'), //,'Maximize' does not work well
															array('Cut','Copy','Paste','PasteText','PasteFromWord','SelectAll','Find','Replace'),
															array('HorizontalRule','Smiley','SpecialChar','PageBreak','TextColor','BGColor'),
															array('Link','Unlink','Anchor','Image','Flash','Table'), //'CreatePlaceholder'
															array('Format','Font','FontSize'),
															array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','NumberedList','BulletedList','Outdent','Indent'),
															array('Bold','Italic','Underline','Strike','Blockquote','Subscript','Superscript')
														),

					);

		if( $config['langeditor'] == 'inherit' ){
			$defaults['language'] = $config['language'];
		}else{
			$defaults['language'] = $config['langeditor'];
		}

		$options += $defaults;

		$options = gpPlugin::Filter('CKEditorConfig',array($options));

		//browser paths
		if( $options['browser'] ){
			$options['filebrowserBrowseUrl'] = common::GetUrl('Admin_Browser').'?type=all';
			$options['filebrowserImageBrowseUrl'] = common::GetUrl('Admin_Browser').'?dir=%2Fimage';
			$options['filebrowserFlashBrowseUrl'] = common::GetUrl('Admin_Browser').'?dir=%2Fflash';
			unset($options['browser']);
		}

		switch( $config_name ){
			case 'array':
			return $options;

			case 'json':
			return json_encode($options);
		}

		return '$.extend('.$config_name.', '.json_encode($options).');';
	}


}
