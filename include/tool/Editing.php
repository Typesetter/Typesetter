<?php

namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	class Editing{



		/**
		 * Replace css resized images with resized copies of the original
		 * ckeditor uses style height/width
		 *
		 */
		public static function ResizeImages(&$html_content,&$img_list){

			includeFile('image.php');
			\gp_resized::SetIndex();


			$orig_list = array();
			if( is_array($img_list) ){
				$orig_list = $img_list;
			}
			$img_list = array();

			//resize images
			$gp_html_output = new \gp\tool\Editing\HTML($html_content);
			foreach($gp_html_output->dom_array as $key => $node){

				if( !is_array($node) || !array_key_exists('tag',$node) ){
					continue;
				}

				$tag = $node['tag'];
				if( $tag != 'img' || !isset($node['attributes']['src']) ){
					continue;
				}


				$resized_data = self::ResizedImage($node['attributes']);
				if( $resized_data !== false ){
					$img = $resized_data['img'];
					$index = $resized_data['index'];
					$resized_src = \gp\tool::GetDir('/include/image.php',true).'?i='.$index.'&amp;w='.$resized_data['w'].'&amp;h='.$resized_data['h'].'&amp;img='.rawurlencode($img);
					$gp_html_output->dom_array[$key]['attributes']['src'] = $resized_src;
					$img_list[$index][] = $resized_data['w'].'x'.$resized_data['h'];
					$img_list[$index] = array_unique($img_list[$index]);
					\gp_resized::$index[$index] = $img;
				}
			}
			$gp_html_output->Rebuild();
			$html_content = $gp_html_output->result;

			self::ResizedImageUse($orig_list,$img_list);
			\gp_resized::SaveIndex();
		}


		/**
		 * Attempt to create a resized image
		 *
		 */
		public static function ResizedImage($attributes){
			global $dataDir, $dirPrefix;


			//height and width from style
			$css_w	= null;
			$css_h	= null;

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

			return self::CreateImage($src_relative,$css_w,$css_h);
		}


		/**
		 * Create a resized image of the file at $src_relative
		 *
		 */
		public static function CreateImage($src_relative,$width,$height){
			global $dataDir;

			$src_path = $dataDir.'/data/_uploaded'.$src_relative;
			if( !file_exists($src_path) ){
				return false;
			}

			//compare to actual size
			$src_img = \gp\tool\Image::getSrcImg($src_path);
			if( !$src_img ){
				return false;
			}

			//Original Size
			$actual_w = imagesx($src_img);
			$actual_h = imagesy($src_img);

			if( $actual_w <= $width && $actual_h <= $height ){
				return false;
			}

			$info = \gp_resized::ImageInfo($src_relative, $width, $height);
			if( !$info ){
				return false;
			}

			$dest_index = $info['index'];
			if( !$dest_index ){
				$dest_index = \gp_resized::NewIndex();
			}
			$dest_path = $dataDir.'/data/_resized/'.$dest_index.'/'.$info['name'];
			$exists_before = file_exists($dest_path);

			//make sure the folder exists
			if( !\gp\tool\Files::CheckDir( \gp\tool::DirName($dest_path) ) ){
				return false;
			}

			//create new resized image
			if( !\gp\tool\Image::createImg($src_img, $dest_path, 0, 0, 0, 0, $width, $height, $actual_w, $actual_h) ){
				return false;
			}

			//not needed if the resized image is larger than the original
			if( filesize($dest_path) > filesize($src_path) ){
				if( !$exists_before ){
					unlink($dest_path);
				}
				return false;
			}

			$data			= array();
			$data['index']	= $dest_index;
			$data['w']		= $width;
			$data['h']		= $height;
			$data['img']	= $src_relative;
			return $data;
		}

		/**
		 * Record where reduced images are being used so that we can delete them later if they are no longer referenced
		 * ... no guarantee the reduced image won't be copy & pasted into other pages.. page copies would need to track the data as well
		 *
		 */
		public static function ResizedImageUse($list_before,$list_after){
			global $dataDir;

			//subtract uses no longer
			$subtract_use = self::UseDiff($list_before, $list_after);

			//add uses
			$add_use = self::UseDiff($list_after, $list_before);


			//save info for each image
			$all_imgs = array_keys($subtract_use + $add_use);
			foreach($all_imgs as $index){
				$edited = false;
				$usage = \gp_resized::GetUsage($index);


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
							self::DeleteUnused($index, $size);
						}
						$usage[$size]['touched'] = time();
					}
				}


				//order usage by sizes: small to large
				uksort($usage,array('\\gp\\tool\\Editing', 'SizeCompare'));

				if( $edited ){
					\gp_resized::SaveUsage($index,$usage);
				}
			}
		}


		/**
		 * Delete unused images
		 * if uses < 1, delete the file, but not the record
		 *
		 */
		private static function DeleteUnused($index, $size){
			global $dataDir;


			list($width,$height) = explode('x',$size);

			//make sure the image still exists
			if( !isset(\gp_resized::$index[$index]) ){
				return;
			}
			$img = \gp_resized::$index[$index];
			$info = \gp_resized::ImageInfo($img,$width,$height);
			if( !$info ){
				return;
			}
			$full_path = $dataDir.'/data/_resized/'.$index.'/'.$info['name'];
			if( file_exists($full_path) ){
				@unlink($full_path);
			}

		}


		/**
		 * Get the use difference
		 *
		 */
		private static function UseDiff( $a, $b){

			$diff = $a;
			foreach($a as $index => $sizes){
				if( isset($b[$index]) ){
					$diff[$index] = array_diff($a[$index],$b[$index]);
				}
			}

			return $diff;
		}


		/**
		 * Replace resized images with their originals
		 *
		 */
		public static function RestoreImages($html_content,$img_list){
			global $dirPrefix;

			includeFile('image.php');
			\gp_resized::SetIndex();

			//
			$images = array();
			foreach($img_list as $index => $sizes){
				if( !isset(\gp_resized::$index[$index]) ){
					continue;
				}
				$img = \gp_resized::$index[$index];
				$original_path = $dirPrefix.'/data/_uploaded'.$img;
				foreach($sizes as $size){
					list($width,$height) = explode('x',$size);
					$resized_path = \gp\tool::GetDir('/include/image.php',true).'?i='.$index.'&amp;w='.$width.'&amp;h='.$height; //not searching for the whole path in case the image was renamed
					$images[$resized_path] = $original_path;
				}
			}

			//resize images
			$gp_html_output = new \gp\tool\Editing\HTML($html_content);
			foreach($gp_html_output->dom_array as $key => $node){

				if( !is_array($node) || !array_key_exists('tag',$node) ){
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
		public static function SizeCompare($size1, $size2){
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
		public static function CleanArg($path){

			$path = self::Sanitize($path);


			//all forward slashes
			$path = str_replace('\\','/',$path);

			//remove directory style changes
			$path = str_replace(array('../','./','..'),array('','',''),$path);

			//change other characters to underscore
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
		public static function CleanTitle($title,$spaces = '_'){

			$title = self::Sanitize($title);

			if( empty($title) ){
				return '';
			}


			$title = str_replace(array('"',"'",'?','*',':'),array(''),$title); // # needed for entities

			$title = str_replace(array('<','>','|','\\'),array(' ',' ',' ','/'),$title);
			$title = preg_replace('#\.+([\\\\/])#','$1',$title);
			$title = trim($title,'/');

			$title = trim($title);
			if( $spaces ){
				$title = preg_replace( '#[[:space:]]#', $spaces, $title );
			}

			return $title;
		}


		/**
		 * Remove null and control characters from the string
		 *
		 */
		public static function Sanitize($string){

			$string = \gp\tool\Files::NoNull($string);

			// Remove control characters [\x00-\x1F\x7F]
			$clean = '';
			preg_match_all( '#[^[:cntrl:]]+#u', $string, $matches);
			foreach($matches[0] as $match){
				$clean .= $match;
			}

			$clean = rawurldecode($clean);	// remove percent encoded strings like %2e%2e%2f

			//recursively sanitize
			if( strlen($clean) !== strlen($string) ){
				$clean = self::Sanitize($clean);
			}

			return $clean;
		}


		/**
		 * Use HTML Tidy to validate the $text
		 * Only runs when $config['HTML_Tidy'] is off
		 *
		 * @param string $text The html content to be checked. Passed by reference
		 */
		public static function tidyFix(&$text,$ignore_config = false){
			global $config;

			if( !$ignore_config ){
				if( empty($config['HTML_Tidy']) || $config['HTML_Tidy'] == 'off' ){
					return true;
				}
			}

			if( !function_exists('tidy_parse_string') ){
				return false;
			}

			$options = array();
			$options['wrap'] = 0;						//keeps tidy from wrapping... want the least amount of space changing as possible.. could get rid of spaces between words with the str_replaces below
			$options['doctype'] = 'omit';				//omit, auto, strict, transitional, user
			$options['drop-empty-paras'] = true;		//drop empty paragraphs
			$options['output-xhtml'] = true;			//need this so that <br> will be <br/> .. etc
			$options['show-body-only'] = true;
			$options['hide-comments'] = false;


			$tidy = tidy_parse_string($text,$options,'utf8');
			tidy_clean_repair($tidy);

			if( tidy_get_status($tidy) === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				return false;
			}
			$text = tidy_get_output($tidy);

			return true;
		}



		/**
		 * Return javascript code to be used with autocomplete (jquery ui)
		 *
		 */
		public static function AutoCompleteValues($GetUrl=true,$options = array()){
			global $gp_index;

			$options += array(	'admin_vals' => true,
								'var_name' => 'gptitles'
								);


			//internal link array
			$array = array();
			foreach($gp_index as $slug => $id){

				$label = \gp\tool::GetLabel($slug);
				$label = str_replace( array('&lt;','&gt;','&quot;','&#39;','&amp;'), array('<','>','"',"'",'&')  , $label);

				if( $GetUrl ){
					$slug = \gp\tool::GetUrl($slug,'',false);
					$slug = rawurldecode($slug);
				}
				$array[] = array($label,$slug);
			}


			if( $options['admin_vals'] && class_exists('admin_tools') ){
				$scripts = \gp\admin\Tools::AdminScripts();
				foreach($scripts as $url => $info){
					if( !isset($info['label']) ){
						continue;
					}
					if( $GetUrl ){
						$url = \gp\tool::GetUrl($url,'',false);
						$url = rawurldecode($url);
					}
					$array[] = array($info['label'],$url);
				}
			}

			$code = json_encode($array);

			if( $options['var_name'] ){
				$code = 'var '.$options['var_name'].' = '.$code.';';
			}
			return $code;
		}


		public static function PrepAutoComplete(){
			global $page;

			\gp\tool::LoadComponents('autocomplete');
			$page->head_js[] = '/include/js/autocomplete.js';
		}


		/**
		 * Use ckeditor for to edit content
		 *
		 *	configuration options
		 * 	- http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
		 */
		public static function UseCK($contents,$name='gpcontent',$options=array()){
			global $page, $dataDir;

			$options += array('rows'=>'20','cols'=>'50');

			echo "\n\n";

			echo '<textarea name="'.$name.'" style="width:90%" rows="'.$options['rows'].'" cols="'.$options['cols'].'" class="CKEDITAREA">';
			echo htmlspecialchars($contents);
			echo '</textarea><br/>';


			$page->head .= "\n".'<script type="text/javascript" src="'.\gp\tool::GetDir('/include/thirdparty/ckeditor_34/ckeditor.js').'?'.rawurlencode(gpversion).'"></script>';
			$page->head .= "\n".'<script type="text/javascript" src="'.\gp\tool::GetDir('/include/js/ckeditor_config.js').'?'.rawurlencode(gpversion).'"></script>';

			\gp\tool::LoadComponents('autocomplete');
			$page->head_script .= self::AutoCompleteValues(true);

			ob_start();
			echo "\n\n";

			// extra plugins
			$config = self::CKConfig( $options, 'json', $plugins );
			foreach($plugins as $plugin => $plugin_path){
				echo 'CKEDITOR.plugins.addExternal('.json_encode($plugin).','.json_encode($plugin_path).');';
				echo "\n";
			}

			echo '$(".CKEDITAREA").each(function(){';
			echo 'CKEDITOR.replace( this, '.$config.' );';
			echo '});';

			echo "\n\n";
			$page->jQueryCode .= ob_get_clean();

		}

		public static function CKAdminConfig(){
			static $cke_config;

			//get ckeditor configuration set by users
			if( !is_array($cke_config) ){

				$cke_config = \gp\tool\Files::Get('_ckeditor/config','cke_config');
				if( !$cke_config ){
					$cke_config = array();
				}

				$cke_config += array('plugins'=>array(),'custom_config'=>array());
			}

			return $cke_config;
		}


		/**
		 * CKEditor configuration settings
		 * Any settings here take precedence over settings in configuration files defined by the customConfig setting
		 * Configuration precedence: (1) User (2) Addon (3) $options (4) CMS
		 *
		 */
		public static function CKConfig( $options = array(), $config_name = 'config', &$plugins = array() ){
			global $config;

			$plugins = array();

			// 4) CMS defaults
			$defaults = array(
							//'customConfig'				=> \gp\tool::GetDir('/include/js/ckeditor_config.js'),
							'skin'						=> 'kama',
							'browser'					=> true, //not actually a ckeditor configuration value, but we're keeping it now for reverse compat
							'smiley_path'				=> \gp\tool::GetDir('/include/thirdparty/ckeditor_34/plugins/smiley/images/'),
							'height'					=> 300,
							'contentsCss'				=> \gp\tool::GetDir('/include/css/ckeditor_contents.css'),
							'fontSize_sizes'			=> 'Smaller/smaller;Normal/;Larger/larger;8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px',
							'ignoreEmptyParagraph'		=> true,
							'entities_latin'			=> false,
							'entities_greek'			=> false,
							'scayt_autoStartup'			=> false,
							'disableNativeSpellChecker'	=> false,
							'FillEmptyBlocks'			=> false,
							'autoParagraph'				=> false,
							'extraAllowedContent'		=> 'iframe[align,frameborder,height,longdesc,marginheight,marginwidth,name,sandbox,scrolling,seamless,src,srcdoc,width];script[async,charset,defer,src,type,xml]; *[accesskey,contenteditable,contextmenu,dir,draggable,dropzone,hidden,id,lang,spellcheck,style,tabindex,title,translate](*)',

							'toolbar'					=> array(
																array('Sourcedialog','Templates','ShowBlocks','Undo','Redo','RemoveFormat'), //,'Maximize' does not work well
																array('Cut','Copy','Paste','PasteText','PasteFromWord','SelectAll','Find','Replace'),
																array('HorizontalRule','Smiley','SpecialChar','PageBreak','TextColor','BGColor'),
																array('Link','Unlink','Anchor','Image','Flash','Table'),
																array('Format','Font','FontSize'),
																array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','NumberedList','BulletedList','Outdent','Indent'),
																array('Bold','Italic','Underline','Strike','Blockquote','Subscript','Superscript','About')
															),

						);

			if( $config['langeditor'] == 'inherit' ){
				$defaults['language'] = $config['language'];
			}else{
				$defaults['language'] = $config['langeditor'];
			}

			// 3) $options
			$options += $defaults;


			// 2) Addon settings
			$options = \gp\tool\Plugins::Filter('CKEditorConfig',array($options));	// $options['config_key'] = 'config_value';
			$plugins = \gp\tool\Plugins::Filter('CKEditorPlugins',array($plugins)); // $plugins['plugin_name'] = 'path_to_plugin_folder';


			// 1) User
			$admin_config = self::CKAdminConfig();
			foreach($admin_config['plugins'] as $plugin => $plugin_info){
				$plugins[$plugin] = \gp\tool::GetDir('/data/_ckeditor/'.$plugin.'/');
			}

			// extra plugins
			$extra_plugins = array_keys($plugins);
			if( array_key_exists('extraPlugins',$options) ){
				$extra_plugins = array_merge( $extra_plugins, explode(',',$options['extraPlugins']) );
			}

			$options = $admin_config['custom_config'] + $options;
			$options['extraPlugins'] = implode(',',$extra_plugins);

			//browser paths
			if( $options['browser'] ){
				$options['filebrowserBrowseUrl'] = \gp\tool::GetUrl('Admin/Browser').'?type=all';
				$options['filebrowserImageBrowseUrl'] = \gp\tool::GetUrl('Admin/Browser').'?dir=%2Fimage';
				$options['filebrowserFlashBrowseUrl'] = \gp\tool::GetUrl('Admin/Browser').'?dir=%2Fflash';
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



		/**
		 * Get the default content for the specified content type
		 * @static
		 * @since 3.6
		 *
		 */
		public static function DefaultContent($type='text', $heading = 'Lorem Ipsum' ){
			global $langmessage;

			$section			= array();
			$section['type']	= $type;
			$section['content'] = '';

			switch($type){
				case 'include':
				break;

				case 'gallery':
					$section['content']		= '<ul class="gp_gallery"><li class="gp_to_remove">'
											.'<a class="gallery_gallery" data-cmd="gallery" href="'.\gp\tool::GetDir('/include/imgs/default_image.jpg').'" data-arg="gallery_gallery">'
											.'<img alt="default image" src="'.\gp\tool::GetDir('/include/imgs/default_thumb.jpg').'" />'
											.'<span class="caption">Image caption</span>'
											.'</a>'
											.'</li></ul>';
				break;

				case 'wrapper_section':
					$section['content']					= '';
					$section['gp_label']				= 'Section Wrapper';
					$section['gp_color']				= '#555';
					$section['contains_sections']		= 0;
				break;

				case 'image':
					$section['nodeName']				= 'img';
					$section['attributes']['src']		= '/include/imgs/default_image.jpg';
					$section['attributes']['width']		= '400px';
					$section['attributes']['height']	= '300px';
				break;

				case 'text':
				default:
					$section['content']					= '<div><h2>'.strip_tags($heading).'</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p> Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p></div>';
				break;
			}

			$content = \gp\tool\Plugins::Filter('GetDefaultContent',array($section['content'],$type));
			if( is_array($content) ){
				$section = $content + $section;
			}else{
				$section['content'] = $content;
			}


			$section					+= array('attributes'=>array());
			$section['attributes']		+= array('class'=>'');

			return $section;
		}


		/**
		 * Include Editing
		 *
		 */
		public static function IncludeDialog( $section ){
			global $page, $langmessage, $config, $gp_index, $dataDir;

			$page->ajaxReplace = array();

			$include_type =& $section['include_type'];

			$gadget_content = '';
			$extra_content = '';
			$file_content = '';
			switch($include_type){
				case 'gadget':
					$gadget_content =& $section['content'];
				break;
				case 'extra':
					$extra_content =& $section['content'];
				break;
				default:
					$file_content =& $section['content'];
				break;
			}

			ob_start();

			echo '<form id="gp_include_form">';

			echo '<div class="gp_scrolllist"><div>';
			echo '<input type="text" value="" class="gpsearch" placeholder="'.$langmessage['Search'].'" autocomplete="off" />';

			//gadget include autocomplete
			if( isset($config['gadgets']) ){
				foreach($config['gadgets'] as $uniq => $info){
					echo '<label>';
					$checked = '';
					if( $uniq == $gadget_content ){
						$checked = 'checked';
					}
					echo '<input type="radio" name="include" value="gadget:'.htmlspecialchars($uniq).'" '.$checked.' data-cmd="IncludePreview" /> ';
					echo '<span>';
					echo '<i class="fa fa-puzzle-piece"></i> ' . $uniq;
					echo '<span class="slug">Gadget</span>';
					echo '</span>';
					echo '</label>';
				}
			}


			//extra area include autocomplete
			$extra_areas = array();
			$extra_area_files = scandir($dataDir . '/data/_extra');
			foreach($extra_area_files as $extra_area_file){
				if( $extra_area_file ==	'index.html' || $extra_area_file == '.' || $extra_area_file == '..' ){
					continue;
				}
				if( is_dir($dataDir . '/data/_extra/' . $extra_area_file) ){
					// new
					$extra_areas[] = $extra_area_file;
				}elseif( substr($extra_area_file, -4) === '.php' ){
					// legacy
					$extra_areas[] = substr($extra_area_file, 0, -4);
				}
			}
			$extra_areas = array_unique($extra_areas);
			foreach($extra_areas as $extra_area){
				echo '<label>';
				$checked = '';
				if( $extra_content == $extra_area ){
					$checked = 'checked';
				}
				echo '<input type="radio" name="include" value="extra:'.htmlspecialchars($extra_area).'" '.$checked.' data-cmd="IncludePreview" /> ';
				echo '<span>';
				echo '<i class="fa fa-cube"></i> ' . $extra_area;
				echo '<span class="slug">' . $langmessage['theme_content'] . '</span>';
				echo '</span>';
				echo '<span style="display:none;"> extra content</span>'; // for autocomplete filtering
				echo '</label>';
			}



			$array = array();
			foreach($gp_index as $slug => $id){

				if( $page->gp_index == $id ){
					continue;
				}

				$label		= \gp\tool::GetLabel($slug);
				$label		= str_replace( array('&lt;','&gt;','&quot;','&#39;','&amp;'), array('<','>','"',"'",'&')  , $label);
				$array[]	= array($label,$slug);

				$checked = '';
				if( $slug == $file_content ){
					$checked = 'checked';
				}

				echo '<label>';
				echo '<input type="radio" name="include" value="file:'.htmlspecialchars($slug).'" '.$checked.'  data-cmd="IncludePreview" /> ';
				echo '<span>';
				echo '<i class="fa fa-file-text-o"></i> ' . $label;
				echo '<span class="slug">' . $langmessage['Page'] . ' /' . $slug . '</span>';
				echo '</span>';
				echo '<span style="display:none;"> page</span>'; // for autocomplete filtering
				echo '</label>';
			}
			echo '</div></div>';

			echo '</form>';


			$content = ob_get_clean();
			$page->ajaxReplace[] = array('gp_include_dialog','',$content);

			return false;
		}


		/**
		 * Return an array
		 *
		 */
		public static function SectionFromPost( &$existing_section, $section_num, $title, $file_stats ){
			global $page, $gpAdmin;

			$section_before		= $existing_section;
			$type				= $existing_section['type'];
			$save_this			= false;


			switch($type){
				case 'text':
					$save_this = true;
					self::SectionFromPost_Text( $existing_section );
				break;
				case 'gallery':
					$save_this = true;
					self::SectionFromPost_Gallery( $existing_section );
				break;
				case 'include':
					$save_this = self::SectionFromPost_Include( $existing_section, $section_num, $title, $file_stats );
				break;

				case 'image':
					$save_this = self::SectionFromPost_Image( $existing_section );
				break;
			}

			//make sure $existing_section is still an array
			$type_check = gettype($existing_section);
			if( $type_check !== 'array' ){
				trigger_error('$existing_section is '.$type_check.'. Array expected');
				return false;
			}


			// Hack: SaveSection used $page->file_sections
			$page->file_sections[$section_num]	= $existing_section;
			$save_this							= \gp\tool\Plugins::Filter( 'SaveSection', array( $save_this, $section_num, $type) );
			$existing_section					= $page->file_sections[$section_num];

			if( !$save_this ){
				$page->file_sections[$section_num] = $existing_section = $section_before;
			}

			$page->file_sections[$section_num]['modified']		= time();
			$page->file_sections[$section_num]['modified_by']	= $gpAdmin['username'];

			return $save_this;
		}

		/**
		 * Get the posted content for an image area
		 *
		 */
		public static function SectionFromPost_Image( &$section, $dest_dir = '/data/_resized/img_type/' ){
			global $page, $dataDir, $dirPrefix, $langmessage;

			$page->ajaxReplace = array();

			//source file
			if( !empty($_REQUEST['file']) ){
				$source_file_rel = $_REQUEST['file'];
			}
			if( !empty($_REQUEST['src']) ){
				$source_file_rel = rawurldecode($_REQUEST['src']);
				if( !empty($dirPrefix) ){
					$len = strlen($dirPrefix);
					$source_file_rel = substr($source_file_rel,$len);
				}
			}
			$source_file_rel	= '/'.ltrim($source_file_rel,'/');
			$source_file_full	= $dataDir.$source_file_rel;

			if( !file_exists($source_file_full) ){
				msg($langmessage['OOPS'].' (Source file not found)');
				return false;
			}
			$src_img = \gp\tool\Image::getSrcImg($source_file_full);
			if( !$src_img ){
				msg($langmessage['OOPS'].' (Couldn\'t create image [1])');
				return false;
			}


			//size and position variables
			$orig_w		= imagesx($src_img);
			$orig_h		= imagesy($src_img);

			$posx		= self::ReqNumeric('posx',0);
			$posy		= self::ReqNumeric('posy',0);

			$width		= self::ReqNumeric('width',$orig_w);
			$height		= self::ReqNumeric('height',$orig_h);


			//check to see if the image needs to be resized
			if( $posx == 0 && $posy == 0 && $width == $orig_w && $height == $orig_h ){
				$section['attributes']['src']		= $source_file_rel;
				$section['attributes']['height']	= $height;
				$section['attributes']['width']		= $width;
				$section['orig_src']				= $_REQUEST['src'];
				$section['posx']					= 0;
				$section['posy']					= 0;
				return true;
			}


			//destination file
			$name	= basename($source_file_rel);
			$parts	= explode('.',$name);


			//remove the time portion of the filename
			if( count($parts) > 1 ){
				$time_part = array_pop($parts);
				if( !ctype_digit($time_part) ){
					$parts[] = $time_part;
				}

			}

			$name				= implode('.',$parts);
			$time				= self::ReqTime();


			$dest_img_rel		= $dest_dir.$name.'.'.$time.'.png';
			$dest_img_full		= $dataDir.$dest_img_rel;

			//make sure the folder exists
			if( !\gp\tool\Files::CheckDir( dirname($dest_img_full) ) ){
				msg($langmessage['OOPS'].' (Couldn\'t create directory)');
				return false;
			}

			if( !\gp\tool\Image::createImg($src_img, $dest_img_full, $posx, $posy, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h, $width, $height) ){
				msg($langmessage['OOPS'].' (Couldn\'t create image [2])');
				return false;
			}

			$section['attributes']['src']			= $dest_img_rel;
			$section['attributes']['height']		= $height;
			$section['attributes']['width']			= $width;
			$section['orig_src']					= $_REQUEST['src'];
			$section['posx']						= $posx;
			$section['posy']						= $posy;

			\gp\admin\Content\Uploaded::CreateThumbnail($dest_img_full);
			return true;
		}


		/**
		 * Return the value in the request if it's a numeric value
		 *
		 */
		public static function ReqNumeric($key, $value){

			if( isset($_REQUEST[$key]) && is_numeric($_REQUEST[$key]) ){
				$value = $_REQUEST[$key];
			}
			return $value;
		}

		/**
		 * Get the timestamp used by the current request
		 *
		 */
		public static function ReqTime(){

			if( isset($_REQUEST['time']) && ctype_digit($_REQUEST['time']) ){
				return $_REQUEST['time'];
			}

			if( isset($_REQUEST['req_time']) && ctype_digit($_REQUEST['req_time']) ){
				return $_REQUEST['req_time'];
			}

			return time();
		}


		/**
		 * Get the posted content for a text area
		 *
		 */
		public static function SectionFromPost_Text( &$section ){
			global $config;
			$content =& $_POST['gpcontent'];
			\gp\tool\Files::cleanText($content);
			$section['content'] = $content;

			if( $config['resize_images'] ){
				self::ResizeImages( $section['content'], $section['resized_imgs'] );
			}

			return true;
		}

		/**
		 * Save Gallery Content
		 *
		 */
		public static function SectionFromPost_Gallery( &$section ){
			if( empty($_POST['images']) ){
				$section['content'] = '<ul class="gp_gallery"><li class="gp_to_remove"></li></ul>';
				return;
			}

			ob_start();

			echo '<ul class="gp_gallery">';

			foreach($_POST['images'] as $i => $image ){

				$thumb_path = \gp\tool::ThumbnailPath($image);
				$caption = $_POST['captions'][$i];
				\gp\tool\Files::cleanText($caption);
				$img_alt = str_replace('_', ' ', basename(pathinfo($image, PATHINFO_FILENAME)));

				echo '<li>';
				echo '<a class="gallery_gallery" data-arg="gallery_gallery" href="'.$image.'" data-cmd="gallery">'; // title="'.htmlspecialchars($caption).'"
				echo '<img src="'.$thumb_path.'" alt="'.$img_alt.'" />';
				echo '<span class="caption">' . $caption . '</span>';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
			$section['content'] = ob_get_clean();
			$section['images'] = $_POST['images'];
			$section['captions'] = $_POST['captions'];
			$section['attributes']['class'] = $_POST['attributes']['class'];
		}


		/**
		 * Save an include section
		 *
		 */
		public static function SectionFromPost_Include( &$existing_section, $section_num, $title, $file_stats ){
			global $page, $langmessage, $gp_index, $config;

			unset($existing_section['index']);


			//gadget include
			if( strpos($_POST['include'],'gadget:') === 0 ){
				$gadget = substr($_POST['include'],7);
				if( !isset($config['gadgets'][$gadget]) ){
					msg($langmessage['OOPS_TITLE']);
					return false;
				}

				$existing_section['include_type']	= 'gadget';
				$existing_section['content']		= $gadget;

			//extra area include
			}elseif( strpos($_POST['include'],'extra:') === 0 ){
				$include_title = substr($_POST['include'],6);

				// msg("AreaExists: " . pre(\gp\admin\Content\Extra::AreaExists($include_title)));
				if( \gp\admin\Content\Extra::AreaExists($include_title) === false && \gp\admin\Content\Extra::AreaExists($include_title.'.php') === false ){
					msg($langmessage['OOPS'] .  ' Extra Content Area ' . $include_title . ' does not exist.');
					return false;
				}
				// $existing_section['include_type']	= 'extra';
				ob_start();
				\gp\tool\Output::GetExtra($include_title);
				$content	= ob_get_clean();

				$existing_section['include_type']	= 'extra';
				$existing_section['content']		= $include_title;


			//file include
			}elseif( strpos($_POST['include'],'file:') === 0 ){
				$include_title = substr($_POST['include'],5);

				if( !isset($gp_index[$include_title]) ){
					msg($langmessage['OOPS_TITLE']);
					return false;
				}
				$existing_section['include_type']	= \gp\tool::SpecialOrAdmin($include_title);
				$existing_section['index']			= $gp_index[$include_title];
				$existing_section['content']		= $include_title;
			}


			//send replacement content
			$content = \gp\tool\Output\Sections::RenderSection( $existing_section, $section_num, $title, $file_stats );
			$page->ajaxReplace[] = array('gp_include_content','',$content);
			return true;
		}


		/**
		 * Display a form for creating a new directory
		 *
		 */
		public static function NewDirForm(){
			global $langmessage, $page;


			echo '<div class="inline_box">';
			echo '<h2><i class="fa fa-folder-o"></i> '.$langmessage['create_dir'].'</h2>';
			echo '<form action="'.\gp\tool::GetUrl($page->title).'" method="post" >';
			echo '<p>';
			echo htmlspecialchars($_GET['dir']).'/';
			echo ' <input type="text" class="gpinput" name="newdir" size="30" />';
			echo '</p>';
			echo '<p>';
			if( !empty($_GET['dir']) ){
				echo ' <input type="hidden" name="dir" value="'.htmlspecialchars($_GET['dir']).'" />';
			}
			echo '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="gp_gallery_folder_add gpsubmit"/>';
			echo ' <input type="submit" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel"/>';
			echo '</p>';
			echo '</form>';
			echo '</div>';

		}


		/**
		 * Perform various section editing commands
		 *
		 * @return bool true if $section should be saved
		 *
		 */
		public static function SectionEdit( $cmd, &$section, $section_num, $title, $file_stats ){
			global $langmessage;

			switch($cmd){

				case 'include_dialog':
				self::IncludeDialog( $section );
				return false;


				case 'inlineedit':
				\gp\tool\Output\Ajax::InlineEdit( $section );
				die();

				case 'save_inline':
				return self::SectionFromPost( $section, $section_num, $title, $file_stats );

			}

			msg($langmessage['OOPS'].' (Unknown Command)');
			return false;
		}



		/**
		 * Output content for use with the inline image editor
		 *
		 */
		public static function ImageEditor( $obj ){
			global $langmessage, $page;


			//image options
			ob_start();

			//edit current image
			echo '<div id="gp_current_image" class="inline_edit_area" title="'.$langmessage['edit'].'">';
			echo '<span id="gp_image_wrap"><img/></span>';
			echo '<table>';
			echo '<tr><td>'.$langmessage['Width'].'</td><td><input type="text" name="width" class="ck_input"/></td>';
			echo '<td>'.$langmessage['Height'].'</td><td><input type="text" name="height" class="ck_input"/></td>';
			echo '</tr>';
			echo '<tr><td>'.$langmessage['Left'].'</td><td><input type="text" name="left" class="ck_input" value="0"/></td>';
			echo '<td>'.$langmessage['Top'].'</td><td><input type="text" name="top" class="ck_input" value="0"/></td>';
			echo '</tr>';
			echo '<tr><td colspan="2">Alternative Text</td><td colspan="2"><input type="text" name="alt_text" style="width:70px; text-align:left;" class="ck_input" value=""/></td></tr>';
			echo '<tr><td><a data-cmd="deafult_sizes" class="ckeditor_control ck_reset_size" title="'.$langmessage['Theme_default_sizes'].'">&#10226;</a></td></tr>';
			echo '</table>';
			echo '</div>';


			//select image
			echo '<div id="gp_source_options" class="inline_edit_area" style="display:none" title="'.$langmessage['Select Image'].'">';

			if( property_exists($obj,'curr_layout') ){
				echo \gp\tool::Link('Admin_Theme_Content/Image/'.rawurlencode($obj->curr_layout),$langmessage['Theme Images'].'..','cmd=ShowThemeImages',' data-cmd="gpajax" class="ckeditor_control half_width" ');
				echo '<a class="ckeditor_control half_width" data-cmd="show_uploaded_images">'.$langmessage['uploaded_files'].'</a>';
			}

			echo '<div id="gp_image_area"></div><div id="gp_upload_queue"></div>';

			echo '<div id="gp_folder_options"></div>';
			echo '</div>';
			$content = ob_get_clean();


			$page->ajaxReplace		= array();
			$page->ajaxReplace[]	= array('inner','#ckeditor_top',$content);
			$page->ajaxReplace[]	= array('image_options_loaded','',''); //tell the script the images have been loaded
		}

	}

}

namespace{
	class gp_edit extends \gp\tool\Editing{}
}
