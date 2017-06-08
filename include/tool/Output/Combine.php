<?php

namespace gp\tool\Output;

defined('is_running') or die('Not an entry point...');


class Combine{


	public static $scripts = array(

			//cms
			'gp-main'	=> array(
									'file' 			=> '/include/js/main.js'
									),

			'gp-admin'	=> array(
									'file'			=> '/include/js/admin.js',
									'requires'		=> 'gp-main',
									),

			'gp-admin-css'=> array(
									'file'			=> '/include/css/admin.scss',
									'type'			=> 'css',
									'requires'		=> 'ui-theme',
									),

			'gp-additional'=> array(
									'file'			=> '/include/css/additional.css',
									'type'			=> 'css',
									),


			'gp-theme-css'=> array(
									'file'			=> '/include/css/theme_content.scss',
									'type'			=> 'css',
									'requires'		=> 'ui-theme',
									),


			//colorbox
			'colorbox'	=> array(
									'file'			=> '/include/thirdparty/colorbox139/colorbox/jquery.colorbox.js',
									'requires'		=> 'gp-main,colorbox-css',
									),


			'colorbox-css' => array(
									'file'			=> '/include/thirdparty/colorbox139/$config[colorbox_style]/colorbox.css',
									'type'			=> 'css',
									),


			//jquery
			'jquery'	=> array(
									'file'			=> '/include/thirdparty/js/jquery.js',
									'package'		=> 'jquery',
									'label'			=> 'jQuery',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js',
														'Google'		=> '//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js',
														),
									),


			//jquery ui core
			'ui-theme'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/jquery-ui.min.css',
									'type'			=> 'css',
									'package'		=> 'jquery_ui',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css',
														'Google'		=> '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.min.css',
														),
									),

			'ui-core'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/core.js',
									'package'		=> 'jquery_ui',
									'label'			=> 'jQuery UI',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js',
														'Google'		=> '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js',
														),
									),

			'mouse'		=> array(
									'file'			=> '/include/thirdparty/jquery_ui/mouse.js',
									'requires'		=> 'ui-core,widget',
									'package' 		=> 'jquery_ui',
									),

			'position'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/position.js',
									'package'		=> 'jquery_ui',
									),

			'widget'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/widget.js',
									'package'		=> 'jquery_ui',
									),


			//jquery ui interactions
			'draggable'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/draggable.js',
									'requires'		=> 'ui-core,widget,mouse',
									'package'		=> 'jquery_ui',
									),

			'droppable'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/droppable.js',
									'requires'		=> 'ui-core,widget,mouse,draggable',
									'package'		=> 'jquery_ui',
									),

			'resizable'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/resizable.js',
									'requires'		=> 'ui-core,widget,mouse,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'selectable'=> array(
									'file'			=> '/include/thirdparty/jquery_ui/selectable.js',
									'requires'		=> 'ui-core,widget,mouse,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'selectmenu'=> array(
									'file'			=> '/include/thirdparty/jquery_ui/selectmenu.js',
									'requires'		=> 'ui-core,widget,position,menu,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'sortable'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/sortable.js',
									'requires'		=> 'ui-core,widget,mouse',
									'package'		=> 'jquery_ui',
									),

			//jquery ui widgets
			'accordion'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/accordion.js',
									'requires'		=> 'ui-core,widget,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'autocomplete'=> array(
									'file'			=> '/include/thirdparty/jquery_ui/autocomplete.js',
									'requires'		=> 'ui-core,widget,position,menu,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'button'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/button.js',
									'requires'		=> 'ui-core,widget,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'datepicker'=> array(
									'file'			=> '/include/thirdparty/jquery_ui/datepicker.js',
									'requires'		=> 'ui-core,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'dialog'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/dialog.js',
									'requires'		=> 'ui-core,widget,button,position,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'menu'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/menu.js',
									'requires'		=> 'ui-core,widget,position,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'progressbar'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/progressbar.js',
									'requires'		=> 'ui-core,widget,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'slider'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/slider.js',
									'requires'		=> 'ui-core,widget,mouse,ui-theme',
									'package'		=> 'jquery_ui',
									),

			'tabs'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/tabs.js',
									'requires'		=> 'ui-core,widget,ui-theme',
									'package'		=> 'jquery_ui',
									),


			//jquery ui effects
			'effects-core'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect.js',
									'package'		=> 'jquery_ui',
									),

			'blind'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-blind.js',
									'requires'		=> 'effects-core',
									'package'		=> 'jquery_ui',
									),

			'bounce'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-bounce.js',
									'requires'		=> 'effects-core',
									'package'		=> 'jquery_ui',
									),

			'clip'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-clip.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'drop'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-drop.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'explode'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-explode.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'fade'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-fade.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'fold'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-fold.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'highlight'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-highlight.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'puff'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-puff.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'pulsate'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-pulsate.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'scale'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-scale.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'shake'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-shake.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'size'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-size.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'slide'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-slide.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),

			'transfer'	=> array(
									'file'			=> '/include/thirdparty/jquery_ui/effect-transfer.js',
									'requires'		=> array('effects-core'),
									'package'		=> 'jquery_ui',
									),


			//html5shiv
			'html5shiv' => array(
									'file'			=> '/include/thirdparty/js/shiv/html5shiv.js',
									'label'			=> 'Html5Shiv',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js',
														),
									),

			'printshiv' => array(
									'file'			=> '/include/thirdparty/js/shiv/html5shiv-printshiv.js',
									'label'			=> 'PrintShiv',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv-printshiv.min.js',
														),
									),

			//respond.js
			'respondjs' => array(
									'file'			=> '/include/thirdparty/js/respond.min.js',
									'label'			=> 'Respond.js',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js',
														),
									),


			//bootstrap 2.3.2 css (deprecated)
			'bootstrap-css'	=> array(
									'file'			=> '/include/thirdparty/Bootstrap/css/bootstrap.min.css',
									),

			'bootstrap-responsive-css' =>array(
									'file'			=> '/include/thirdparty/Bootstrap/css/bootstrap-responsive.min.css',
									'requires'		=> 'bootstrap-css',
									),


			//bootstrap 2.3.2 js (deprecated)
			'bootstrap-alert' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-alert.js',
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-transition',
									),

			'bootstrap-button' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-button.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-carousel' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-carousel.js',
									'package'		=> 'bootstrap',
									'requires'		=> array('bootstrap-transition'),
									),

			'bootstrap-collapse' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-collapse.js',
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-transition',
									),

			'bootstrap-dropdown' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-dropdown.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-modal' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-modal.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-popover' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-popover.js',
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-tooltip',
									),

			'bootstrap-scrollspy' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-scrollspy.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-tab' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-tab.js',
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-transition',
									),

			'bootstrap-tooltip' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-tooltip.js',
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-transition',
									),

			'bootstrap-transition' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-transition.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-typeahead' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-typeahead.js',
									'package'		=> 'bootstrap',
									),

			'bootstrap-js' => array(
									'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap.min.js',
									'package'		=> 'bootstrap',
									'exclude'		=> 'bootstrap-alert,bootstrap-button,bootstrap-carousel,bootstrap-collapse,bootstrap-dropdown,bootstrap-modal,bootstrap-popover,bootstrap-scrollspy,bootstrap-tab,bootstrap-tooltip,bootstrap-transition,bootstrap-typeahead',
									),


			'bootstrap-all' => array(
									'package'		=> 'bootstrap',
									'requires'		=> 'bootstrap-responsive-css,bootstrap-js',
									),


			// Bootstrap3
			'bootstrap3-js' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/bootstrap.min.js',
									'package'		=> 'bootstrap3',
									'exclude'		=> 'bootstrap3-affix,bootstrap3-alert,bootstrap3-button,bootstrap3-carousel,bootstrap3-collapse,bootstrap3-dropdown,bootstrap3-modal,bootstrap3-popover,bootstrap3-scrollspy,bootstrap3-tab,bootstrap3-tooltip,bootstrap3-transition',
									),


			'bootstrap3-affix' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/affix.js',
									'package'		=> 'bootstrap3',
									),

			'bootstrap3-alert' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/alert.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-transition',
									),

			'bootstrap3-button' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/button.js',
									'package'		=> 'bootstrap3',
									),

			'bootstrap3-carousel' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/carousel.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-transition',
									),

			'bootstrap3-collapse' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/collapse.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-transition',
									),

			'bootstrap3-dropdown' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/dropdown.js',
									'package'		=> 'bootstrap3',
									),

			'bootstrap3-modal' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/modal.js',
									'package'		=> 'bootstrap3',
									),

			'bootstrap3-popover' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/popover.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-tooltip',
									),

			'bootstrap3-scrollspy' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/scrollspy.js',
									'package'		=> 'bootstrap3',
									),

			'bootstrap3-tab' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/tab.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-transition',
									),

			'bootstrap3-tooltip' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/tooltip.js',
									'package'		=> 'bootstrap3',
									'requires'		=> 'bootstrap3-transition',
									),

			'bootstrap3-transition' => array(
									'file'			=> '/include/thirdparty/Bootstrap3/js/transition.js',
									'package'		=> 'bootstrap3',
									),


			//fontawesome
			'fontawesome'			=> array(
									'file'			=> '/include/thirdparty/fontawesome/css/font-awesome.min.css',
									'label'			=> 'Font Awesome',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
														),
									),


			//colorbox
			'colorbox'	=> array(
									'file'			=> '/include/thirdparty/colorbox139/colorbox/jquery.colorbox.js',
									'requires'		=> 'gp-main,colorbox-css',
									'label'			=> 'Colorbox JS',
									'cdn'			=> array(
														'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.3/jquery.colorbox-min.js',
														),
									),

			'colorbox-css' => array(
									'file'			=> '/include/thirdparty/colorbox139/$config[colorbox_style]/colorbox.css',
									'type'			=> 'css',
									),


			//jQuery.dotdotdot (multi-line text truncation)
			'dotdotdot' => array(
									//'file'			=> '/include/thirdparty/dotdotdot/jquery.dotdotdot.min.js',
									'file'			=> '/include/thirdparty/dotdotdot/jquery.dotdotdot.js',
									),

	);



	/**
	 * Generate a file with all of the combined content
	 *
	 */
	public static function GenerateFile($files,$type){
		global $dataDir;

		//get etag
		$modified = $content_length = 0;
		$full_paths = array();
		foreach($files as $file){
			$full_path = self::CheckFile($file);
			if( $full_path === false ){
				continue;
			}
			self::FileStat_Static($full_path,$modified,$content_length);
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
					self::FileStat_Static($imported_full,$modified,$content_length);
				}
				unset($import_data[$full_path]);
			}
		}

		//check to see if file exists
		$etag = \gp\tool::GenEtag( json_encode($files), $modified, $content_length );
		$cache_relative = '/data/_cache/combined_'.$etag.'.'.$type;
		$cache_file = $dataDir.$cache_relative;
		if( file_exists($cache_file) ){

			// change modified time to extend cache
			if( (time() - filemtime($cache_file)) > 604800 ){
				touch($cache_file);
			}

			return $cache_relative;
		}

		//create file
		if( $type == 'js' ){
			ob_start();
			\gp\tool::jsStart();

			foreach($full_paths as $full_path){
				readfile($full_path);
				echo ";\n";
			}
			$combined_content = ob_get_clean();

		}else{

			$imports = $combined_content = '';
			$new_imported = array();
			foreach($full_paths as $file => $full_path){
				$temp = new \gp\tool\Output\CombineCss($file);

				$combined_content .= "\n/* ".$file." */\n";
				$combined_content .= $temp->content;
				$imports .= $temp->imports;
				if( count($temp->imported) ){
					$new_imported[$full_path] = $temp->imported;
				}
			}
			$combined_content = $imports . $combined_content;

			//save imported data
			if( count($new_imported) || $had_imported ){
				if( count($new_imported) ){
					$import_data = $new_imported + $import_data;
				}
				\gp\tool\Files::SaveData($imported_file,'import_data',$import_data);
			}
		}

		if( !\gp\tool\Files::Save($cache_file,$combined_content) ){
			return false;
		}


		\gp\admin\Tools::CleanCache();

		return $cache_relative;
	}


	/**
	 * Make sure the file is a css or js file and that it exists
	 * @static
	 */
	public static function CheckFile(&$file){
		global $dataDir;
		$comment_start = '<!--';
		$comment_end = '-->';

		$file = self::TrimQuery($file);

		if( empty($file) ){
			return false;
		}

		//translate addon paths
		$pos = strpos($file,'/data/_addoncode/');
		if( $pos !== false ){
			$file_parts = substr($file,$pos+17);
			$file_parts = explode('/',$file_parts);
			$addon_key = array_shift($file_parts);
			$addon_config = \gp\tool\Plugins::GetAddonConfig($addon_key);
			if( $addon_config ){
				$file = $addon_config['code_folder_rel'].'/'.implode('/',$file_parts);
			}
		}


		//remove null charachters
		$file = \gp\tool\Files::NoNull($file);

		//require .js or .css
		$ext	= \gp\tool::Ext($file);
		if( $ext !== 'js' && $ext !== 'css' && $ext !== 'less' && $ext !== 'scss' ){
			echo  "\n{$comment_start} File Not CSS, LESS or JS {$file} {$comment_end}\n";
			return false;
		}

		//paths that have been urlencoded
		if( strpos($file,'%') !== false ){
			$decoded_file = rawurldecode($file);
			if( $full_path = self::CheckFileSub($decoded_file) ){
				$file = $decoded_file;
				return $full_path;
			}
		}

		//paths that have not been encoded
		if( $full_path = self::CheckFileSub($file) ){
			return $full_path;
		}


		echo  "\n{$comment_start} File Not Found {$dataDir}{$file} {$comment_end}\n";
		return false;
	}

	public static function CheckFileSub(&$file){
		global $dataDir, $dirPrefix;

		//realpath returns false if file does not exist
		$full_path = $dataDir.$file;
		if( file_exists($full_path) ){
			return realpath($full_path);
		}

		//check for paths that have already included $dirPrefix
		if( empty($dirPrefix) ){
			return false;
		}

		if( strpos($file,$dirPrefix) === 0 ){
			$fixed = substr($file,strlen($dirPrefix));
			$full_path = $dataDir.$fixed;
			if( file_exists($full_path) ){
				$file = $fixed;
				return realpath($full_path);
			}
		}

		return false;
	}


	public static function FileStat_Static( $file_path, &$modified, &$content_length ){
		$content_length += @filesize($file_path);
		if( strpos($file_path,'/data/_cache/') === false ){
			$modified = max( $modified, @filemtime($file_path) );
		}
		return $modified;
	}

	/**
	 * Remove the query off a file path
	 *
	 */
	public static function TrimQuery($file){
		$pos = mb_strpos($file,'?');
		if( $pos > 0 ){
			$file = mb_substr($file,0,$pos);
		}
		return trim($file);
	}

	public static function ScriptInfo( $components, $dependencies = true){
		global $config;
		static $root_call = true;
		if( is_string($components) ){
			$components = explode(',',strtolower($components));
			$components = array_unique($components);
		}

		self::$scripts['colorbox-css']['file'] = '/include/thirdparty/colorbox139/'.$config['colorbox_style'].'/colorbox.css';

		$all_scripts = array();

		//get all scripts
		foreach($components as $component){
			if( !array_key_exists($component,self::$scripts) ){
				$all_scripts[$component] = false;
				continue;
			}
			$script_info = self::$scripts[$component];
			if( $dependencies && isset($script_info['requires']) ){
				$is_root_call = $root_call;
				$root_call = false;
				$all_scripts += self::ScriptInfo($script_info['requires']);
				$root_call = $is_root_call;
			}
			$all_scripts[$component] = self::$scripts[$component];
		}

		if( !$root_call ){
			return $all_scripts;

		}

		$all_scripts = array_filter($all_scripts);
		$first_scripts = array();

		//make sure jquery is the first
		if( array_key_exists('jquery',$all_scripts) ){
			$first_scripts['jquery'] = $all_scripts['jquery'];
		}

		// move any bootstrap components to front to prevent conflicts
		// hack for conflict between jquery ui button and bootstrap button
		foreach($all_scripts as $key => $script){
			if( !array_key_exists('package',$script) ){
				continue;
			}

			if( $script['package'] == 'bootstrap' || $script['package'] == 'bootstrap3'  ){
				$first_scripts[$key] = $script;
			}

		}

		$all_scripts = $first_scripts + $all_scripts;

		$all_scripts = self::RemoveExcludes($all_scripts);

		return self::OrganizeByType($all_scripts);
	}


	/**
	 * Remove Excludes
	 *
	 */
	public static function RemoveExcludes($scripts){

		$excludes = array();
		foreach($scripts as $key => $script){
			if( empty($script['exclude']) ){
				continue;
			}
			if( !is_array($script['exclude']) ){
				$script['exclude'] = explode(',',$script['exclude']);
			}
			$excludes = array_merge($excludes,$script['exclude']);
		}

		return array_diff_key($scripts,array_flip($excludes));
	}



	/**
	 * Organize $scripts by type
	 *
	 */
	public static function OrganizeByType($scripts){

		$return = array('js'=>array(),'css'=>array() );

		foreach($scripts as $key => $script){
			if( empty($script['file']) ){
				continue;
			}

			if( gpdebug && !empty($script['dev']) ){
				$script['file'] = $script['dev'];
			}

			if( empty($script['type']) ){
				$script['type'] = \gp\tool::Ext($script['file']);
			}
			if( $script['type'] == 'less' || $script['type'] == 'scss' ){
				$script['type'] = 'css';
			}
			$return[$script['type']][$key] = $script;
		}

		return $return;
	}

}
