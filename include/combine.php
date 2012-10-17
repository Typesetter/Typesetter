<?php
defined('is_running') or die('Not an entry point...');


class gp_combine{

	/**
	 * Generate a file with all of the combined content
	 *
	 */
	function GenerateFile($files,$type){
		global $dataDir;

		//get etag
		$full_paths = array();
		foreach($files as $file){
			$full_path = gp_combine::CheckFile($file);
			if( $full_path === false ){
				continue;
			}
			gp_combine::FileStat_Static($full_path,$modified,$content_length);
			$full_paths[$file] = $full_path;
		}


		//check css imports
		if( $type == 'css' ){
			$had_imported = false;
			$import_data = array();
			$imported_file = $dataDir.'/data/_cache/import_info.php';
			if( file_exists($imported_file) ){
				include_once($imported_file);
			}
			foreach($full_paths as $file => $full_path){
				if( !isset($import_data[$full_path]) ){
					continue;
				}
				$had_imported = true;
				foreach($import_data[$full_path] as $imported_full){
					gp_combine::FileStat_Static($imported_full,$modified,$content_length);
				}
				unset($import_data[$full_path]);
			}
		}

		//check to see if file exists
		$dir_hash = crc32( md5( sha1( $dataDir) ) );
		$dir_hash = base_convert( sprintf("%u\n", $dir_hash ), 10, 36);
		$etag = count($files).'.'.$dir_hash.'.'.common::GenEtag( $modified, $content_length );
		$cache_relative = '/data/_cache/combined_'.$etag.'.'.$type;
		$cache_file = $dataDir.$cache_relative;
		if( file_exists($cache_file) ){
			return $cache_relative;
		}

		//create file
		if( $type == 'js' ){
			ob_start();
			common::jsStart();

			foreach($full_paths as $full_path){
				readfile($full_path);
				echo ";\n";
			}
			$combined_content = ob_get_clean();

		}else{
			includeFile('thirdparty/cssmin.php');


			//add any @import first
			$combined_content = $css_content = '';
			$current_file = false;
			$new_imported = array();

			foreach($full_paths as $file => $full_path){
				$tokens = self::GetTokens($file,$full_path);

				$open_tokens = 0;
				foreach($tokens as $token){
					$token_class = get_class($token);
					switch($token_class){
						//case 'CssAtPageStartToken':
						//case 'CssAtPageDeclarationToken':
						//case 'CssAtPageEndToken':
						case 'CssAtFontFaceStartToken':
						case 'CssAtFontFaceDeclarationToken':
						case 'CssAtFontFaceEndToken':
							$combined_content .= (string)$token;
						continue 2;
						case 'CssAtImportToken':
							if( $token->Imported ){
								$new_imported[$full_path][] = $token->Imported;
							}else{
								$combined_content .= (string)$token;
							}
						continue 2;

						case 'CssRulesetStartToken':
							$open_tokens++;
						break;

						case 'CssRulesetEndToken':
							$open_tokens--;
						break;
					}
					if( $file !== $current_file ){
						$css_content .= "\n/* ".$file." */\n";
						$current_file = $file;
					}
					$css_content .= (string)$token;
				}

				//close open tokens
				for($i=0; $i<$open_tokens; $i++){
					$css_content .= '}';
				}

			}
			$combined_content .= $css_content;

			//save imported data
			if( count($new_imported) || $had_imported ){
				if( count($new_imported) ){
					$import_data = $new_imported + $import_data;
				}
				gpFiles::SaveArray($imported_file,'import_data',$import_data);
			}
		}

		if( !gpFiles::Save($cache_file,$combined_content) ){
			return false;
		}


		self::CleanCacheNew();
		return $cache_relative;
	}


	function GetTokens($file,$full_path){
		$content = file_get_contents($full_path);
		$filters = array(
			'UrlPrefix' => array( 'BaseUrl' => common::GetDir($file), 'BasePath' => dirname($full_path) )
			);
		$minifier = new CssMinifier(null, $filters);
		return $minifier->minifyTokens($content);
	}


	function CleanCacheNew(){
		global $dataDir;
		$dir = $dataDir.'/data/_cache';
		$files = scandir($dir);
		$times = array();
		$count = 0;
		foreach($files as $file){
			if( $file == '.' || $file == '..' || strpos($file,'.php') !== false ){
				continue;
			}
			$full_path = $dir.'/'.$file;
			$time = filemtime($full_path);
			$diff = time() - $time;
			//if relatively new, don't delete it
			if( $diff < 604800 ){
				$count++;
				continue;
			}
			//if old, delete it
			if( $diff > 2592000 ){
				unlink($full_path);
				continue;
			}
			$times[$file] = $time;
		}

		//reduce further if needed till we have less than 100 files
		asort($times);
		foreach($times as $file => $time){
			if( $count < 100 ){
				return;
			}
			$full_path = $dir.'/'.$file;
			unlink($full_path);
			$count--;
		}
	}

	/**
	 * Make sure the file is a css or js file and that it exists
	 * @static
	 */
	function CheckFile(&$file){
		global $dataDir;
		$comment_start = '<!--';
		$comment_end = '-->';

		$file = gp_combine::TrimQuery($file);

		if( empty($file) ){
			return false;
		}

		//remove null charachters
		$file = gpFiles::NoNull($file);

		//require .js or .css
		$test = strtolower($file);
		if( substr($test, -3) != '.js' && substr($test, -4) != '.css' ){
			echo  "\n{$comment_start} File Not CSS or JS {$file} {$comment_end}\n";
			return false;
		}

		//paths that have been urlencoded
		if( strpos($file,'%') !== false ){
			$decoded_file = rawurldecode($file);
			if( $full_path = gp_combine::CheckFileSub($decoded_file) ){
				$file = $decoded_file;
				return $full_path;
			}
		}

		//paths that have not been encoded
		if( $full_path = gp_combine::CheckFileSub($file) ){
			return $full_path;
		}


		echo  "\n{$comment_start} File Not Found {$dataDir}{$file} {$comment_end}\n";
		return false;
	}

	function CheckFileSub(&$file){
		global $dataDir, $dirPrefix;

		//realpath returns false if file does not exist
		$full_path = realpath($dataDir.$file);
		if( $full_path ){
			return $full_path;
		}


		//check for paths that have already included $dirPrefix
		if( empty($dirPrefix) ){
			return false;
		}

		if( strpos($file,$dirPrefix) === 0 ){
			$fixed = substr($file,strlen($dirPrefix));
			$full_path = realpath($dataDir.$fixed);
			if( $full_path ){
				$file = $fixed;
				return $full_path;
			}
		}

		return false;
	}


	function FileStat_Static( $file_path, &$modified, &$content_length ){
		$content_length += @filesize($file_path);
		$modified = max( $modified, @filemtime($file_path) );
		return $modified;
	}

	/*
	 *
	 * @static
	 */
	function TrimQuery($file){
		$pos = mb_strpos($file,'?');
		if( $pos > 0 ){
			$file = mb_substr($file,0,$pos);
		}
		return trim($file);
	}

	function ScriptInfo( $components, $dependencies = true){
		global $config;
		if( is_string($components) ){
			$components = explode(',',strtolower($components));
			$components = array_unique($components);
		}

		//gpEasy
		$scripts['gp-main'] = array(
								'file' => 'js/main.js',
								'requires' => array('jquery')); //'ui-core'

		$scripts['gp-admin'] = array(
								'file' => 'js/admin.js',
								'requires' => array('jquery','gp-main'));

		$scripts['gp-admin-css'] = array(
									'file' => 'css/admin.css',
									'type' => 'css',
									'requires' => 'ui-theme');


		$scripts['gp-additional'] = array(
										'file' => 'css/additional.css',
										'type' => 'css');

		//colorbox
		$scripts['colorbox'] = array(	'file' => 'thirdparty/colorbox139/colorbox/jquery.colorbox.js',
										'requires' => array('gp-main','colorbox-css'));


		$scripts['colorbox-css'] = array(	'file' => 'thirdparty/colorbox139/'.$config['colorbox_style'].'/colorbox.css',
											'type' => 'css');


		//jquery
		$scripts['jquery'] = array(
								'file' => 'thirdparty/js/jquery.js',
								'package' => 'jquery');

		//jquery ui core
		$scripts['ui-theme'] = array(
								'file' => 'thirdparty/jquery_ui/jquery-ui.custom.css',
								'type' => 'css',
								'package' => 'jquery_ui');

		$scripts['ui-core'] = array(
								'file' => 'thirdparty/jquery_ui/ui.core.js',
								'requires' => array('jquery'),
								'package' => 'jquery_ui');

		$scripts['mouse'] = array(
								'file' => 'thirdparty/jquery_ui/ui.mouse.js',
								'requires' => array('ui-core','widget'),
								'package' => 'jquery_ui');

		$scripts['position'] = array(	'file' => 'thirdparty/jquery_ui/ui.position.js',
										'package' => 'jquery_ui');

		$scripts['widget'] = array(	'file' => 'thirdparty/jquery_ui/ui.widget.js',
									'package' => 'jquery_ui');



		//jquery ui interactions
		$scripts['draggable'] = array(	'file' => 'thirdparty/jquery_ui/ui.draggable.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'mouse')
										,'package' => 'jquery_ui');

		$scripts['droppable'] = array(	'file' => 'thirdparty/jquery_ui/ui.droppable.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'mouse', 'draggable')
										,'package' => 'jquery_ui');

		$scripts['resizable'] = array(	'file' => 'thirdparty/jquery_ui/ui.resizable.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'mouse', 'ui-theme')
										,'package' => 'jquery_ui');

		$scripts['selectable'] = array(	'file' => 'thirdparty/jquery_ui/ui.selectable.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'mouse', 'ui-theme')
										,'package' => 'jquery_ui');

		$scripts['sortable'] = array(	'file' => 'thirdparty/jquery_ui/ui.sortable.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'mouse')
										,'package' => 'jquery_ui');


		//jquery ui widgets

		$scripts['accordion'] = array(	'file' => 'thirdparty/jquery_ui/ui.accordion.js'
										,'requires' => array('jquery', 'ui-core', 'widget', 'ui-theme')
										,'package' => 'jquery_ui');

		$scripts['autocomplete'] = array(	'file' => 'thirdparty/jquery_ui/ui.autocomplete.js'
											,'requires' => array('jquery', 'ui-core', 'widget', 'position', 'ui-theme')
											,'package' => 'jquery_ui');

		$scripts['button'] = array(	'file' => 'thirdparty/jquery_ui/ui.button.js'
									,'requires' => array('jquery', 'ui-core', 'widget', 'ui-theme')
									,'package' => 'jquery_ui');

		$scripts['datepicker'] = array(	'file' => 'thirdparty/jquery_ui/ui.datepicker.js'
										,'requires' => array('jquery', 'ui-core', 'ui-theme')
										,'package' => 'jquery_ui');

		$scripts['dialog'] = array(	'file' => 'thirdparty/jquery_ui/ui.dialog.js'
									,'requires' => array('jquery', 'ui-core', 'widget', 'position', 'ui-theme')
									,'package' => 'jquery_ui');

		$scripts['progressbar'] = array(	'file' => 'thirdparty/jquery_ui/ui.progressbar.js'
											,'requires' => array('jquery', 'ui-core', 'widget', 'ui-theme')
											,'package' => 'jquery_ui');

		$scripts['slider'] = array(	'file' => 'thirdparty/jquery_ui/ui.slider.js'
									,'requires' => array('jquery', 'ui-core', 'widget', 'mouse', 'ui-theme')
									,'package' => 'jquery_ui');

		$scripts['tabs'] = array(	'file' => 'thirdparty/jquery_ui/ui.tabs.js'
									,'requires' => array('jquery', 'ui-core', 'widget', 'ui-theme')
									,'package' => 'jquery_ui');



		//jquery ui effects
		$scripts['effects-core'] = array(	'file'=>'thirdparty/jquery_ui/effects.core.js'
											,'requires' => array('jquery')
											,'package' => 'jquery_ui');

		$scripts['blind'] = array(	'file'=> 'thirdparty/jquery_ui/effects.blind.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui'
									);

		$scripts['bounce'] = array(	'file'=> 'thirdparty/jquery_ui/effects.bounce.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['clip'] = array(	'file'=> 'thirdparty/jquery_ui/effects.clip.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['drop'] = array(	'file'=> 'thirdparty/jquery_ui/effects.drop.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['explode'] = array(	'file'=> 'thirdparty/jquery_ui/effects.explode.js'
										,'requires' => array('jquery', 'effects-core')
										,'package' => 'jquery_ui');

		$scripts['fade'] = array(	'file'=> 'thirdparty/jquery_ui/effects.fade.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['fold'] = array(	'file'=> 'thirdparty/jquery_ui/effects.fold.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['highlight'] = array(	'file'=> 'thirdparty/jquery_ui/effects.highlight.js'
										,'requires' => array('jquery', 'effects-core')
										,'package' => 'jquery_ui');

		$scripts['pulsate'] = array(	'file'=> 'thirdparty/jquery_ui/effects.pulsate.js'
										,'requires' => array('jquery', 'effects-core')
										,'package' => 'jquery_ui');

		$scripts['scale'] = array(	'file'=> 'thirdparty/jquery_ui/effects.scale.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['shake'] = array(	'file'=> 'thirdparty/jquery_ui/effects.shake.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['slide'] = array(	'file'=> 'thirdparty/jquery_ui/effects.slide.js'
									,'requires' => array('jquery', 'effects-core')
									,'package' => 'jquery_ui');

		$scripts['transfer'] = array(	'file'=> 'thirdparty/jquery_ui/effects.transfer.js'
										,'requires' => array('jquery', 'effects-core')
										,'package' => 'jquery_ui');

		$all_scripts = array();
		foreach($components as $component){
			if( !isset($scripts[$component]) ){
				$all_scripts[$component] = false;
				continue;
			}
			$script_info = $scripts[$component];
			if( $dependencies && isset($script_info['requires']) ){
				$all_scripts += gp_combine::ScriptInfo($script_info['requires']);
			}
			$all_scripts[$component] = $scripts[$component];
		}
		return array_filter($all_scripts);
	}

}
